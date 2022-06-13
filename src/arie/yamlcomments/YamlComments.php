<?php
declare(strict_types=1);

namespace arie\yamlcomments;

use pocketmine\utils\Config;
use Webmozart\PathUtil\Path;

class YamlComments{
	/** @var string */
	private string $file;
	/** @var array */
	private array $comments = [];
	/** @var array */
	private array $inline_comments = [];
	/** @var bool */
	private bool $supported;

	public function __construct(string $content){
		$this->file = $config->getPath();
		$this->supported = strtolower(pathinfo($this->file, PATHINFO_EXTENSION)) === "yml";
		$this->parseComments();
	}

	public function isSupportedFile() : bool{
		return $this->supported;
	}

	/**
	 * This function will scan the YAML file for its comments then save it in two different array, one for comments
	 * above it, one for comments in that line.
	 *
	 * Not recommended for production because lack of RAM eater (Note: this only takes a lot of CPU usage when you
	 * calling it, I recommend you call this function when the server start)
	 * @return void
	 */
	public function parseComments() : void{
		if (!$this->supported) {
			return;
		}
		$lines = file($this->file, FILE_IGNORE_NEW_LINES);
		$key = "";
		$spaces = [];
		$comments = [];
		$omitted = false;

		foreach ($lines as $line) {
			$l = ltrim($line);
			$colon_pos = strpos($l, ':');
			if (!isset($l[0])) {
				$comments[] = "";
				continue;
			}
			if ($l[0] === '#') {
				$comments[] = $line;
				continue;
			}
			if (str_starts_with($l, '---')) {
				if ($omitted) {
					break;
				}
				if (!empty($comments)) {
					$this->comments['---'] = $comments;
				}
				$sharp_pos = strpos($l, '#');
				if ($sharp_pos !== false) {
					$this->inline_comments['---'] = mb_substr($l, $sharp_pos);
				}
				$omitted = true;
				continue;
			}
			if (str_starts_with($l, '...')) {
				if (!empty($comments)) {
					$this->comments['...'] = $comments;
				}
				$sharp_pos = strpos($l, '#');
				if ($sharp_pos !== false) {
					$this->inline_comments['...'] = mb_substr($l, $sharp_pos);
				}
				break;
			}
			if ($colon_pos === false) {
				$val = str_replace([' ', '-'], '', $l);
				$sharp_pos = strpos($val, '#');
				if ($sharp_pos !== false) {
					$val = mb_substr($val, 0, $sharp_pos);
					$this->inline_comments[$key . "." . $val] = mb_substr($l, $sharp_pos);
				}

				if (!empty($comments)) {
					$this->comments[$key . "." . $val] = $comments;
					$comments = [];
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

			if (!empty($comments)) {
				$this->comments[$key] = $comments;
				$comments = [];
			}
			$sharp_pos = strpos($l, '#');
			if ($sharp_pos !== false) {
				$this->inline_comments[$key] = mb_substr($l, $sharp_pos);
			}
		}
	}

	public function getHeaderComments() : ?array{
		return $this->getComments('---');
	}

	public function getHeaderParagraph() : string{
		return $this->getCommentParagraph('---');
	}

	public function setHeaderComments(array $comments = []) : void{
		$this->setComments('---', $comments);
	}

	public function addHeaderComments(array $comments = []) : void{
		$this->addComments('---', $comments);
	}

	public function getFooterComments() : ?array{
		return $this->getComments('...');
	}

	public function getFooterParagraph() : string{
		return $this->getCommentParagraph('...');
	}

	public function setFooterComments(array $comments = []) : void{
		$this->setComments('...', $comments);
	}

	public function addFooterComments(array $comments = []) : void{
		$this->addComments('...', $comments);
	}

	public function getComments(string $key) : ?array{
		if (!isset($this->comments[$key])) {
			return null;
		}
		return array_map(static fn(string $comments)  : string => mb_substr($comments, 1), $this->comments[$key]);
	}

	public function getCommentParagraph(string $key) : string{
		if (!isset($this->comments[$key])) {
			return "";
		}
		return implode(PHP_EOL, array_map(static fn(string $comments)  : string => mb_substr($comments, 1), $this->comments[$key]));
	}

	public function setComments(string $key, array $comments = []) : void{
		if (empty($comments)) {
			$this->comments[$key] = null;
			return;
		}
		$this->comments[$key] = array_map(static fn(string $comments)  : string => ltrim($comments)[0] !== '#' ? '#' . $comments : $comments, $comments);
	}

	public function addComments(string $key, array $comments = []) : void{
		if (empty($this->comments)) {
			return;
		}
		$this->comments[$key] = array_merge($this->comments[$key], array_map(static fn(string $comments)  : string => ltrim($comments)[0] !== '#' ? '#' . $comments : $comments, $comments));
	}

	public function getInlineComments(string $key) : string{
		if (!isset($this->inline_comments[$key])) {
			return "";
		}
		$comments = $this->inline_comments[$key];
		if (ltrim($comments)[0] === '#') {
			return mb_substr($comments, 1);
		}
		return $comments;
	}

	public function setInlineComments(string $key, string $comments) : void{
		if (ltrim($comments)[0] !== '#') {
			$this->inline_comments[$key] = '#' . $comments;
			return;
		}
		$this->inline_comments[$key] = $comments;
	}

	/**
	 * This function will scan the YAML file again for keys and value, then check if the saved key before exist
	 * If yes, the data will be parsed with the key and value
	 *
	 * Not recommended for production because lack of RAM eater (Note: this takes a lot of CPU usage when you call
	 * it. I recommend you calling this when the server turn off)
	 * @param bool $save
	 * @return void
	 * @throws \JsonException
	 */
	public function emitComments(bool $save = false) : void{
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
		$omitted = false;
		foreach ($lines as $line) {
			$l = ltrim($line);
			$colon_pos = strpos($l, ':');
			if (!isset($l[0])) {
				continue;
			}

			if (str_starts_with($l, '---')) {
				if ($omitted) {
					break;
				}
				if (isset($this->comments['---'])) {
					$contents .= implode(PHP_EOL, $this->comments['---']) . PHP_EOL;
				}
				$contents .= $line;
				if (isset($this->inline_comments['---'])) {
					$contents .= ' ' . $this->inline_comments['---'];
				}
				$contents .= PHP_EOL;
				$omitted = true;
				continue;
			}
			if (str_starts_with($l, '...')) {
				if (isset($this->comments['...'])) {
					$contents .= ' ' . implode(PHP_EOL, $this->comments['...']) . PHP_EOL;
				}
				$contents .= $line;
				if (isset($this->inline_comments['...'])) {
					$contents .= ' ' . $this->inline_comments['...'];
				}
				$contents .= PHP_EOL;
				break;
			}


			if ($colon_pos === false) {
				$val = str_replace([' ', '-'], '', $l);
				$sub_key = $key . "." . $val;
				if (isset($this->comments[$sub_key])) {
					$contents .= implode(PHP_EOL, $this->comments[$sub_key]) . PHP_EOL;
				}
				$contents .= $line;
				if (isset($this->inline_comments[$sub_key])) {
					$contents .= ' ' . $this->inline_comments[$sub_key];
				}
				$contents .= PHP_EOL;
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

			if (isset($this->comments[$key])) {
				$contents .= implode(PHP_EOL, $this->comments[$key]) . PHP_EOL;
			}
			$contents .= $line;
			if (isset($this->inline_comments[$key])) {
				$contents .= ' ' . $this->inline_comments[$key];
			}
			$contents .= PHP_EOL;
		}
		file_put_contents($this->file, $contents);
	}

	/** @throws \JsonException */
	public function save() : void{
		$this->emitComments(true);
	}

	/**
	 * @return Config
	 */
	public function getConfig() : Config{
		return $this->config;
	}
}
