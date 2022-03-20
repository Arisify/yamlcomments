# yamlcomments
A PMMP virion pasting YAML comments.

## How does it work?
It simply cuts through each line of Config and calculates things to find documents and comments. Then it saves those data back to Config if the old key is still there. You can use something like this.
```php
$config = new Config("path");
$yaml_comments = new YamlComments($config);
```

## How do I save the comments/documents?
```php
$yaml_comments->save(); //If you want to save both config and documents
$yaml_comments->parseDocuments(save: false); //If you want to parse documents, if make is true. It would save the config first 
```
Nothing happens when you don't save your config first, but I should tell you that if you save your config after save documents, the documents won't be saved.
## How will this affect my server performance?
Yesn't, this won't affect so much when the server is running stably, but it does require some cpu usage when you start or stop your server. 
<br> But none or less, I don't recommend you using this for a producing server
## What is doc and Inline doc?
It doesn't make much sense, it's just that my understanding is so low that I don't know what to call it. It's like this:
```yml
---
#This is a doc
key: val #This is an Inline doc
key1: val
...
```
## Can I add documents for non-documented line?
Yes, you can add it by this simply code:
```php
$key1 = "key1";
$doc = "This is a doc for key1";
$inline_doc = "This is an inline doc for key1";
$yaml_comments->setDoc($key1, $doc);
$yaml_comments->setInlineDoc($key1, $inline_doc);
```
After savings the config and documents, the config file will appear to have something like this
```yml
---
# This is a doc for key1
key1: val #This is an inline doc for key1
...
```
## How do I include this in other plugins
If you use [Poggit](https://poggit.pmmp.io) to build your plugin, you can add it to your `.poggit.yml` like so:

```yml
projects:
  YourPlugin:
    libs:
      - src: Arisify/yamlcomments/yamlcomments
        version: ^1.0.0
```

## Are there any issues?
- Currently, I only know there was an issue where you can't add documents on the header and footer (this was intended because I thought this was not possible on YAML).
- The array in yaml is working different from what i expected but this won't cause anything but the logic
<br> but if you found any issues, please report it [here](https://github.com/NTT1906/yamlcomments/issues)
