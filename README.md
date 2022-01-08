# KSES 2022

This is the experimental stage of the project where
everything probably breaks for a while, but every so
often I think about this and want to do _something_ with
it.  So here we are.

A new branch, a new version, hopefully new code, and
some unit testing.

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

    include 'kses.php';

    $allowed = array(
        'b' => array(),
        'i' => array(),
        'a' => array('href' => 1, 'title' => 1),
        'p' => array('align' => 1),
        'br' => array());

    $val = $_POST['val'];
    if (get_magic_quotes_gpc())
    {
        $val = stripslashes($val);
    }
    // You must strip slashes from magic quotes, or kses will get confused.

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

# TODO - Consider removal of this

_Considering this page https://en.wikipedia.org/wiki/Magic_quotes , it looks like
magic_quotes isn't an issue since PHP 5.4, and I'm looking at the floor for this
to be PHP 5.6.  Make a note for folks to use kses 0.2.2 if they're earlier._

All HTML input for kses must be cleaned of slashes coming from magic quotes.
If the rest of your code requires these slashes to be present, you can always
add them again after calling kses with a simple addslashes() call.

You should take a look at the documentation in the docs/ directory and the
examples in the examples/ directory, to get more information on how to use
kses. The object-oriented versions of kses are also worth checking out, and
they're included in the oop/ directory.

## License

Licensed under [Apache 2.0](https://www.apache.org/licenses/LICENSE-2.0)

You should take a look at the documentation in the docs/ directory and the
examples in the examples/ directory, to get more information on how to use
kses. The object-oriented versions of kses are also worth checking out, and
they're included in the oop/ directory.


* UPGRADING TO 0.2.2 *


kses 0.2.2 is backwards compatible with all previous releases, so upgrading
should just be a matter of using a new version of kses.php instead of an old
one.


* NEW VERSIONS, MAILING LISTS AND BUG REPORTS *


If you want to download new versions, subscribe to the kses-general mailing
list or even take part in the development of kses, we refer you to its
homepage at  http://sourceforge.net/projects/kses . New developers and beta
testers are more than welcome!

If you have any bug reports, suggestions for improvement or simply want to tell
us that you use kses for some project, feel free to post to the kses-general
mailing list. If you have found any security problems (particularly XSS,
naturally) in kses, please contact Ulf privately at  metaur at users dot
sourceforge dot net  so he can correct it before you or someone else tells the
public about it.

(No, it's not a security problem in kses if some program that uses it allows a
bad attribute, silly. If kses is told to accept the element body with the
attributes style and onLoad, it will accept them, even if that's a really bad
idea, securitywise.)


* OTHER HTML FILTERS *


Here are the other stand-alone, open source HTML filters that we currently know
of:

* Htmlfilter for PHP - the filter from Squirrelmail
  PHP
  Konstantin Riabitsev
  http://linux.duke.edu/projects/mini/htmlfilter/

* HTML::StripScripts and related CPAN modules
  Perl
  Nick Cleaton
  http://search.cpan.org/perldoc?HTML%3A%3AStripScripts

* SafeHtmlChecker [is this really open source?]
  PHP
  Simon Willison
  http://simon.incutio.com/archive/2003/02/23/safeHtmlChecker

There are also a lot of HTML filters that were written specifically for some
program. Some of them are better than others.

Please write to the kses-general mailing list if you know of any other
stand-alone, open-source filters.


* DEDICATION *


kses 0.2.2 is dedicated to Audrey Tautou and Jean-Pierre Jeunet.


* MISC *


The kses code is based on an HTML filter that Ulf wrote on his own back in 2002
for the open-source project Gnuheter ( http://savannah.nongnu.org/projects/
gnuheter ). Gnuheter is a fork from PHP-Nuke. The HTML filter has been
improved a lot since then.

To stop people from having sleepless nights, we feel the urgent need to state
that kses doesn't have anything to do with the KDE project, despite having a
name that starts with a K.

In case someone was wondering, Ulf is available for kses-related consulting.

Finally, the name kses comes from the terms XSS and access. It's also a
recursive acronym (every open-source project should have one!) for "kses
strips evil scripts".


// Ulf and the kses development group, February 2005
