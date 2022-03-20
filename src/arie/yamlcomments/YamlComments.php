<?php
declare(strict_types=1);

namespace arie\yamlcomments;

use pocketmine\utils\Config;
use Webmozart\PathUtil\Path;

class YamlComments{
	/** @var string */
	private string $file;
	/** @var Config */
	private Config $config;
	/** @var array */
	private array $doc = [];
	/** @var array */
	private array $inline_doc = [];
	/** @var bool */
	private bool $supported;

	public function __construct(Config $config){
		$this->file = $config->getPath();
		$this->supported = strtolower(Path::getExtension($this->file)) === "yml";
		$this->config = $config;
		$this->emitDocuments();
	}

	public function isSupportedFile() : bool{
		return $this->supported;
	}

	/**
	 * This function will scan the YAML file for comments and stuff then save it in two different array, one for
	 * documentations above it, one for documentations after it.
	 *
	 * Not recommended for production because lack of RAM eater (Note that this only takes a lot of CPU usage when the
	 * server turn on and off)
	 * @return void
	 */
	public function emitDocuments() : void{
		if (!$this->supported) {
			return;
		}
		$lines = file($this->file, FILE_IGNORE_NEW_LINES);
		$key = "";
		$spaces = [];
		$doc = [];
		foreach ($lines as $line) {
			if ($line === '...' || $line === '---') {
				continue;
			}
			$l = ltrim($line);
			$colon_pos = strpos($l, ':');
			if (!isset($l[0])) { //Todo: $this->isBlank($line)?
				$doc[] = "";
				continue;
			}
			if ($l[0] === '#') {
				$doc[] = $line;
				continue;
			}

			if ($colon_pos === false) {
				$val = str_replace([' ', '-'], '', $l);
				$sharp_pos = strpos($val, '#');
				if ($sharp_pos !== false) {
					$val = mb_substr($val, 0, $sharp_pos);
					$this->inline_doc[$key . "." . $val] = mb_substr($l, $sharp_pos);
				}

				if (!empty($doc)) {
					$this->doc[$key . "." . $val] = $doc;
					$doc = [];
				}
				continue;
			}
			$space = strlen($line) - strlen($l);

			if ($space === 0) {
				if ($line[0] !== '-') {
					$key = mb_substr($l, 0, $colon_pos);
				} else {
					$key .= "." . str_replace([' ', '-'], '', mb_substr($l, 0, $colon_pos));
				}
			} else if ($spaces[$key] < $space) {
				$key .= "." . str_replace([' ', '-'], '', mb_substr($l, 0, $colon_pos));
			} else {
				while($spaces[$key] >= $space) {
					$last_dotpos = strrpos($key, '.');
					if ($spaces[$key] === $space) {
						$key = mb_substr($key, 0, $last_dotpos);
						$key .= "." . str_replace([' ', '-'], '', mb_substr($l, 0, $colon_pos));
						break; //This will stop the loop from checking for non-exist key...
					}
					$key = mb_substr($key, 0, $last_dotpos);
				}
			}

			$spaces[$key] = $space;

			if (!empty($doc)) {
				$this->doc[$key] = $doc;
				$doc = [];
			}
			$sharp_pos = strpos($l, '#');
			if ($sharp_pos !== false) {
				$this->inline_doc[$key] = mb_substr($l, $sharp_pos);
			}
		}
	}

	/**
	 * @throws \JsonException
	 */
	public function saveConfig() : void{
		$this->parseDocuments(true);
	}

	/**
	 * @param string $key
	 * @return array|null
	 */
	public function getDoc(string $key) : ?array{
		return $this->doc[$key] ?? null;
	}

	public function getDocParagraph(string $key) : ?string{
		return isset($this->doc[$key]) ? implode(PHP_EOL, $this->doc[$key]) : null;
	}

	public function setDoc(string $key, array $doc = []) : void{
		$this->doc[$key] = $doc;
	}

	public function addDoc(string $key, array $doc = []) : void{
		$this->doc[$key] = array_merge($this->doc[$key] ?? [], $doc);
	}

	/**
	 * @param string $key
	 * @return string|null
	 */
	public function getInlineDoc(string $key) : ?string{
		return $this->inline_doc[$key] ?? null;
	}

	public function setInlineDoc(string $key, string $doc) : void{
		$this->inline_doc[$key] = $doc;
	}

	public function isBlank(string $line) : bool{
		return preg_match('#^\s*$#', $line);
	}

	/**
	 * This function will scan the YAML file again for keys and value, then check if there are comments of it in the data
	 * If yes, the data will be parsed with the key and value
	 *
	 * Not recommended for production because lack of RAM eater (Note that this only takes a lot of CPU usage when the
	 * server turn on and off)
	 * @return void
	 * @throws \JsonException
	 */
	public function parseDocuments(bool $save = false) : void{
		if ($save) {
			$this->config->save();
		}
		if (!$this->supported) {
			return;
		}
		$lines = file($this->file, FILE_IGNORE_NEW_LINES);
		$key = "";
		$spaces = [];
		$contents = "";
		foreach ($lines as $line) {
			$l = ltrim($line);
			$colon_pos = strpos($l, ':');
			if (!isset($l[0])) { //Todo: $this->isBlank($line)?
				continue;
			}

			if ($colon_pos === false) {
				$val = str_replace([' ', '-'], '', $l);
				$sub_key = $key . "." . $val;
				if (isset($this->doc[$sub_key])) {
					$contents .= implode(PHP_EOL, $this->doc[$sub_key]) . PHP_EOL;
				}
				$contents .= $line . ($this->inline_doc[$sub_key] ?? "") . PHP_EOL;
				continue;
			}
			$space = strlen($line) - strlen($l);
			if ($space === 0) {
				if ($line[0] !== '-') {
					$key = mb_substr($l, 0, $colon_pos);
				} else {
					$key .= "." . str_replace([' ', '-'], '', mb_substr($l, 0, $colon_pos));
				}
			} else if ($spaces[$key] < $space) {
				$key .= "." . str_replace([' ', '-'], '', mb_substr($l, 0, $colon_pos));
			} else {
				while ($spaces[$key] >= $space) {
					$last_dotpos = strrpos($key, '.');
					if ($spaces[$key] === $space) {
						$key = mb_substr($key, 0, $last_dotpos);
						$key .= "." . str_replace([' ', '-'], '', mb_substr($l, 0, $colon_pos));
						break; //This will stop the loop from checking for non-exist key...
					}
					$key = mb_substr($key, 0, $last_dotpos);
				}
			}
			$spaces[$key] = $space;

			if (isset($this->doc[$key])) {
				$contents .= $this->getDocParagraph($key) . PHP_EOL;
			}
			$contents .= $line . ($this->getInlineDoc($key) ?? "") . PHP_EOL;
		}
		file_put_contents($this->file, $contents);
	}
}
