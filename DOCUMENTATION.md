# KSES 2022 Documentation

Quick notes for using the basic kses.php library from the 0.2.2 documentation.

## Attribute Value Checks

As you've probably already read in the README file, an $allowed_html array
normally looks like this:

```php
$allowed = array(
    'b' => array(),
    'i' => array(),
    'a' => array('href' => 1, 'title' => 1),
    'p' => array('align' => 1),
    'br' => array());
```

This sets the allowed elements and attributes.

From kses 0.2.0, you can also perform some checks on the attribute values. You
do it like this:

```php
$allowed = array(
    'b' => array(),
    'i' => array(),
    'a' => array('href' => array('maxlen' => 100), 'title' => 1),
    'p' => array('align' => 1),
    'font' => array('size' => array('maxval' => 20)),
    'br' => array());
```

This means that kses should perform the maxlen check with the value 100 on the
<a href=> value, as well as the maxval check with the value 20 on the <font
size=> value.

The currently implemented checks (with more to come) are 'maxlen', 'maxval',
'minlen', 'minval' and 'valueless'.

'maxlen' checks that the length of the attribute value is not greater than the
given value. It is helpful against Buffer Overflows in WWW clients and various
servers on the Internet. In my example above, it would mean that
"&lt;a href='ftp://ftp.v1ct1m.com/AAAA..thousands_of_A's...' >" wouldn't be
accepted.

Of course, this problem is even worse if you put that long URL in a <frame>
tag instead, so the WWW client will fetch it automatically without a user
clicking it.

'maxval' checks that the attribute value is an integer greater than or equal to
zero, that it doesn't have an unreasonable amount of zeroes or whitespace (to
avoid Buffer Overflows), and that it is not greater than the given value. In
my example above, it would mean that "&lt;font size='20'>" is accepted but
"&lt;font size='21'>" is not. This check helps against Denial of Service attacks
against WWW clients.

One example of this DoS problem is <iframe src="http://some.web.server/"
width="20000" height="2000">, which makes some client machines completely
overloaded.

'minlen' and 'minval' works the same as 'maxlen' and 'maxval', except that they
check for minimum lengths and values instead of maximum ones.

'valueless' checks if an attribute has a value (like &lt;a href="blah">) or not
(&lt;option selected>). If the given value is a "y" or a "Y", the attribute must
not have a value to be accepted. If the given value is an "n" or an "N", the
attribute must have a value. Note that &lt;a href=""> is considered to have a
value, so there's a difference between valueless attributes and attribute
values with the length zero.

You can combine more than one check, by putting one after the other in the
inner array.

## Whitelisted URL Protocols

From kses 0.2.0, it has a function that checks all attribute values for URL
protocols and only allows the protocols given in a whitelist.

If you call kses the old way with two parameters - a string and an
$allowed_html array - it will take its own default array, which whitelists the
protocols http, https, ftp, news, nntp, telnet, gopher and mailto. Pretty
reasonable, but anyone who wants to change it just calls the kses() function
with a third parameter, like this:

```php
$string = kses($string, $allowed_html, array('http', 'https'));
```

Note that you shouldn't include any colon after http or other protocol names.

## Stripping Everything

Sometimes you want to use kses for stripping all (X)HTML tags from a document.
You do it by calling kses like this:

```php
$doc = kses($doc, array());
```

## Supported Formats

It should be noted that kses doesn't deal with any smiley plus newline plus
HTML format. It uses HTML or XHTML, both as input and as output.
Conversions from the preferred format to or from HTML or XHTML is up to you.

## Hooks
Sometimes you want to perform one more action on all data that kses will
filter. There is a special function for that purpose called kses_hook(). kses
calls it from its main function kses(), so if you insert some code in
kses_hook(), it will always be called to change all data that kses sees.
