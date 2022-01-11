# KSES 2022 (v 0.3.0)

This is the experimental stage of the project where
everything probably breaks for a while, but every so
often I think about this and want to do _something_ with
it.  So here we are.

A new branch, a new version, hopefully new code, and
some unit testing.

Documentation is currently in [DOCUMENTATION.md](DOCUMENTATION.md)

## Introduction

Welcome to kses - an HTML filter written in PHP.

It removes all unwanted HTML elements and attributes,
no matter how malformed the HTML input that you give it.
It also does several checks on attribute values.
kses can be used to avoid Cross-Site Scripting (XSS),
Buffer Overflows and Denial of Service attacks,
among other things.

Information for the previous version of this code:
* [Version 0.2.2](https://github.com/RichardVasquez/kses/blahblahblah)
* Licensed under [GPL V2](https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html)

## Features

* Only accepts whitelisted HTML elements and attributes.
* Can whitelist user defined protocols such as http:, https:, ftp:.
* Normalizes HTML entities.
* Element and attribute names are case-insensitive (a href vs A HREF).
* Understands and process whitespace correctly.
* Attribute values can be surrounded with quotes, apostrophes or nothing.
* It will accept valueless attributes with just names and no values (e.g., selected).
* It will accept XHTML's closing " /" marks.
* Attribute values that are surrounded with nothing will get quotes to avoid
  producing non-W3C conforming HTML
  (\<a href=https://github.com/RichardVasquez/blahblah> works but isn't valid HTML).
* It handles lots of types of malformed HTML, by interpreting the existing
  code the best it can and then rebuilding new code from it. That's a better
  approach than trying to process existing code, as you're bound to forget about
  some weird special case somewhere. It handles problems like never-ending
  quotes and tags gracefully.
* It will remove additional "<" and ">" characters that people may try to
  sneak in somewhere.
* It supports checking attribute values for minimum/maximum length and
  minimum/maximum value, to protect against Buffer Overflows and Denial of
  Service attacks against WWW clients and various servers. You can stop
  <iframe src= width= height=> from having too high values for width and height,
  for instance.
* It removes Netscape 4's JavaScript entities ("&{alert(57)};").
* It handles NULL bytes and Opera's chr(173) whitespace characters.
* There is a procedural version and two object-oriented versions (for PHP 4
  and PHP 5) of kses.

## Usage

```php
<?php
    include_once 'kses.functions.php';
    use function Kses\kses;

    $allowed = array(
        'b' => array(),
        'i' => array(),
        'a' => array('href' => 1, 'title' => 1),
        'p' => array('align' => 1),
        'br' => array());

    $val = $_POST['val'];
    $val = kses($val, $allowed); # The filtering takes place here.

    // Do something with $val.
```

The data provided in the ```$allowed``` array has key values that define the allowed
HTML tags in the text to parse, with the value having an array that lists allowed attributes.

In this case, they are 'b', 'i','a', 'p', and 'br'.
The 'a' tag can only have the attributes 'href' and 'title', while 'p' is only allowed
'align', and the other tags are forbidden to have attributes.

It's important to select the right allowed attributes, so you won't open up
an XSS hole by mistake. Some attributes that you should consider carefully include: 
  1. style
  2. all intrinsic events attributes (i.e., onMouseOver, onClick, etc.)

## PHP Version Requirements
The minimal version of PHP tested on was PHP 5.6.  Earlier versions of PHP
will not work.

This removes the 0.2.2 requirement for checking against the ```get_magic_quotes_gpc()```
configuration setting, along with ```addslashes()``` and ```stripslashes()```.

## Compatibility
Assuming your PHP version is compatible, this should be a simple replacement
providing backwards compatibility.  Check the documentation directory for more
information.

## Other Filter Tools

* Htmlfilter for PHP - the filter from Squirrelmail
  
  PHP
  
  Konstantin Riabitsev
  
  https://web.archive.org/web/20070103015820/http://linux.duke.edu/projects/mini/htmlfilter/

* HTML::StripScripts and related CPAN modules
  
  Perl
  
  Clinton Gormley
  
  https://metacpan.org/pod/HTML::StripScripts

* Submit a PR with a modification to this readme for additional libraries to add.

## Miscellaneous

The kses code based on an HTML filter that Ulf wrote on his own back in 2002
for the open-source project Gnuheter ( http://savannah.nongnu.org/projects/
gnuheter ). Gnuheter is a fork from PHP-Nuke. The HTML filter has been
improved a lot since then.

Richard was the creator of the OOP version as he needed it for his blog back
in the day.  Then he found it on SourceForge and knew it belonged in a better
place as [SourceForge had turned evil](https://www.infoworld.com/article/2929732/sourceforge-commits-reputational-suicide.html).
Regardless of any changes SourceForge may have made since then, I haven't
trusted them since, and I doubt if I ever will.  I also haven't had any
problems with GitHub, so for the duration, I'll keep it here and tinker with it
as I find time.

Finally, the name kses comes from the terms XSS and access. It's also a
recursive acronym (every open-source project should have one!) for "kses
strips evil scripts".

## Dedications

  * kses 0.2.2 is dedicated to Audrey Tautou and Jean-Pierre Jeunet.
  * kses 2022 is dedicated to Natalie, Tracie, and Dia.

## License

Licensed under [Apache 2.0](https://www.apache.org/licenses/LICENSE-2.0)
