Pakket
======
[![Build Status](https://travis-ci.org/webdevvie/pakket.svg?branch=master)](https://travis-ci.org/webdevvie/pakket)

Pakket is the Dutch word for package. This tool will help you package your project into a PHAR file.

It is meant to be framework agnostic.
It does not include update code (for downloading new versions of the phar with the phar itself Look elsewhere for that
It just makes PHAR files.

This is the initial version. I suggest not using it for production just yet.


How to use as a library
-----------------------

Either include the package using composer using :
```
composer require webdevvie/pakket
```

Create your phar stub (the thing that calls your other files inside your phar file) and call it "stub"

Then create a builder object

```php
<?php
include("vendor/autoload.php");
$builder = new \Webdevvie\Pakket\Builder();

$config = [
    "stubFile"=>__DIR__."/stub"
];

$builder->build(
__DIR__,
__DIR__."/yourphar.phar",
$config
)
```

And you are off to the races!

Of course you may not want to include everything and the kitchen sink into your phar. (Making a "Homer")
You can exclude these by filling the key "exclude" with an array of regular expressions (these are thrown through preg_match)


How to use as a phar
--------------------
Download a prebuilt pakket.phar from here(todo get a place to host the phar files)

Create a pakket.json with the correct configuration.

This is a sample pakket.json that is used for pakket itself
```json
{
  "exclude": [
    "/^Tests/i",
    "/^coverage/i",
    "/^yourphar.phar/i",
    "/^yourphar-(.*)\\.phar/i",
    "/^Tests\\/(.*)/i",
    "/^.git/i",
    "/^(.*)\\/.git/i",
    "/\\.gitignore/i",
    "/\\.travis.yml/i"
  ],
  "parse": [
    "/^console/i"
  ],
  "targetPath": "yourphar.phar",
  "stubFile": "stub",
  "vars": {}
}
```
In this case we create the file `yourphar.phar` parsing the /console file to include the default variables.

Create your phar stub (the thing that calls your other files inside your phar file) and call it "stub"

Now run pakket.phar
```
./pakket.phar build .
```
Here the command is  `build` the path to work with is `.` (current directory) the output file is `yourphar.phar`

You can also specify the output (say you want to specify a target file in command line with automated builds)

```
./pakket.phar build . output.phar
```

Here the command is  `build` the path to work with is `.` (current directory) the output file is `yourphar-1.0.phar`



TODO
====
The following things are still todos :
 - A setup command that configures the pakket.json file
 - A better help text
 - Get a place to host the .phar file
 - Test with php 5.6
 - Test with php 7.0
 - Test with php 7.2
