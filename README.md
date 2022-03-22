# yamlcomments
A PMMP virion pasting YAML comments.

## How does it work?
It simply cuts through each line of Config and calculates things to find comments. Then it saves those data back to Config if the old key is still there. You can use something like this.
```php
$config = new Config("path");
$yaml_comments = new YamlComments($config);
```

## How do I save the comments?
```php
$yaml_comments->save(); //If you want to save both config and comments
$yaml_comments->emitComments(save: false); //If you want to parse comments. Make 'make' true if you want to save the config first 
```
Nothing happens when you don't save your config first, but I should tell you that if you save your config after save comments, the comments won't be saved.
## How will this affect my server performance?
Yesn't, this won't affect so much when the server is running stably, but it does require some cpu usage when you start or stop your server. 
<br> But none or less, I don't recommend you using this for a producing server.
## What is comments and inline comments?
An YAML comment is started with **#**, this can be classified into two groups: comments (intended comments?) and inline comments
- A comment appears at the beginning of a line.
- An inline comment appears after the value of a keys.
```yml
---
#This is a comment
key: val #This is an inline comment
#However, this virion considered this is key1' comment even if the content is pointing at key, you can't know it?
key1: val
...
```
## Can I add comments for non-commented line?
Yes, you can add it and modify the old comments too.
```php
$key1 = "key1";
$comment = "This is a comment for key1";
$inline_comment = "This is an inline comments for key1";
$yaml_comments->setComments($key1, $comment);
$yaml_comments->setInlineComments($key1, $inline_comment);
```
After savings the config and comments, the config file will appear to have something like this
```yml
---
#This is a comment for key1
key1: val #This is an inline comment for key1
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
- The array in yaml is working different from what I expected but this won't cause anything but the logic
<br> but if you found any issues, please report it [here](https://github.com/Arisify/yamlcomments/issues)
