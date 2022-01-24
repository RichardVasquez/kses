<?php

/* [kses strips evil scripts!]
 *
 * kses 2022 - A small rewrite of kses 0.2.2 to work with versions of PHP from
 * 5.6 and later.  You can find current versions of this script at
 *
 * https://github.com/RichardVasquez/kses
 *
 * Copyright 2022 Richard R. Vasquez
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *  http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Kses;
require_once 'kses.info.php';

/**
 * @param string $string Input to run through kses
 * @param array $allowed_html Allowed tags with attributes
 * @param string[] $allowed_protocols Allowed protocols within links
 * @return array|string|string[]|null Cleaned HTML
 *
 * This function makes sure that only the allowed HTML element names, attribute
 * names and attribute values plus only sane HTML entities will occur in
 * $string.
 */
function kses($string, $allowed_html,
              $allowed_protocols = array(
                  'http', 'https', 'ftp', 'news', 'nntp', 'telnet',
                  'gopher', 'mailto'))
{
  $string = ksesHook(
      ksesNormalizeEntities(
          ksesJavascriptEntities(
              ksesNoNull($string)
          )
      )
  );

  $allowed_html_fixed = ksesArrayLowerCase($allowed_html);
  return ksesSplit($string, $allowed_html_fixed, $allowed_protocols);
}


/**
 * @param string $string a kses processed string
 * @return mixed result of hook operations
 *
 * You add any kses hooks here to perform any desired post kses operations.
 */
function ksesHook($string)
{
  return $string;
}


/**
 * @return string version number
 */
function ksesVersion()
{
  return VERSION;
}

/**
 * @param string $string string to be processed
 * @param array $allowed_html allowed HTML tags and attributes
 * @param array $allowed_protocols allowed URL protocols
 * @return array|string|string[]|null
 *
 * This function searches for HTML tags, no matter how malformed. It also
 * matches stray ">" characters.
 */
function ksesSplit($string, $allowed_html, $allowed_protocols)
{
  $pattern =
      '%(<'   . # EITHER: <
      '[^>]*' . # things that aren't >
      '(>|$)' . # > or end of string
      '|>)%';   # OR: just a >

  return preg_replace_callback(
      $pattern,
      function($m) use($allowed_html, $allowed_protocols)
      {
        return ksesSplit2($m[1], $allowed_html, $allowed_protocols);
      },
      $string
  );
}

/**
 * @param string $string string to be processed
 * @param array $allowed_html allowed HTML tags and attributes
 * @param array $allowed_protocols allowed URL protocols
 * @return string
 *
 * This function does a lot of work. It rejects some very malformed things
 * like <:::>. It returns an empty string, if the element isn't allowed (look
 * ma, no strip_tags()!). Otherwise it splits the tag into an element and an
 * attribute list.
 */
function ksesSplit2($string, $allowed_html, $allowed_protocols)
{
  $string = ksesStripSlashes($string);

  //  It matched a ">" character
  if (substr($string, 0, 1) != '<')
  {
    return '&gt;';
  }

  //  Seriously malformed
  if (!preg_match('%^<\s*(/\s*)?([a-zA-Z0-9]+)([^>]*)>?$%', $string, $matches))
  {
    return '';
  }

  $slash = trim($matches[1]);
  $elem = $matches[2];
  $attribute_list = $matches[3];

  // They are using a not allowed HTML element
  if (!@isset($allowed_html[strtolower($elem)]))
  {
    return '';
  }

  // No attributes are allowed for closing elements
  if ($slash != '')
  {
    return "<$slash$elem>";
  }

  return ksesAttributes("$slash$elem", $attribute_list, $allowed_html,
                   $allowed_protocols);
}

/**
 * @param string $element Tag to examine
 * @param array $attr Allowed attributes
 * @param array $allowed_html Allowed tags
 * @param array $allowed_protocols Allowed URL protocols
 * @return string
 *
 * This function removes all attributes, if none are allowed for this element.
 * If some are allowed it calls kses_hair() to split them further, and then it
 * builds up new HTML code from the data that kses_hair() returns. It also
 * removes "<" and ">" characters, if there are any left. One more thing it
 * does is to check if the tag has a closing XHTML slash, and if it does,
 * it puts one in the returned code as well.
 */
function ksesAttributes($element, $attr, $allowed_html, $allowed_protocols)
{
  // Is there a closing XHTML slash at the end of the attributes?
  $xhtml_slash = '';
  if (preg_match('%\s/\s*$%', $attr))
  {
    $xhtml_slash = ' /';
  }

  // Are any attributes allowed at all for this element?
  if (@count($allowed_html[strtolower($element)]) == 0)
  {
    return "<$element$xhtml_slash>";
  }

  // Split it
  $attribute_array = ksesHair($attr, $allowed_protocols);

  // Go through $attribute_array, and save the allowed attributes for this element
  // in $attr2
  $attr2 = '';

  foreach ($attribute_array as $array_each)
  {
    if (!@isset($allowed_html[strtolower($element)][strtolower($array_each['name'])]))
    {
      // the attribute is not allowed
      continue;
    }

    $current = $allowed_html[strtolower($element)][strtolower($array_each['name'])];

    // there are no checks
    if (!is_array($current))
    {
      $attr2 .= ' '.$array_each['whole'];
    }
    // there are some checks
    else
    {
      $ok = true;
      foreach ($current as $key => $value)
      {
        if (!ksesCheckAttributeValue($array_each['value'], $array_each['vless'], $key, $value))
        {
          $ok = false;
          break;
        }
      }

      if ($ok)
      {
        $attr2 .= ' '.$array_each['whole']; # it passed them
      }
    }
  }

  // Remove any "<" or ">" characters
  $attr2 = preg_replace('/[<>]/', '', $attr2);

  return "<$element$attr2$xhtml_slash>";
}

/**
 * @param array $attr attributes to examine
 * @param array $allowed_protocols Allowed URL protocols
 * @return array
 *
 * This function does a lot of work. It parses an attribute list into an array
 * with attribute data, and tries to do the right thing even if it gets weird
 * input. It will add quotes around attribute values that don't have any quotes
 * or apostrophes around them, to make it easier to produce HTML code that will
 * conform to W3C's HTML specification. It will also remove bad URL protocols
 * from attribute values.
 */
function ksesHair($attr, $allowed_protocols)
{
  $attribute_array = array();
  $mode = 0;
  $attribute_name = '';

  // Loop through the whole attribute list
  while (strlen($attr) != 0)
  {
    // Was the last operation successful?
    $working = 0;

    switch ($mode)
    {
      // attribute name, href for instance
      case 0:
        if (preg_match('/^([-a-zA-Z]+)/', $attr, $match))
        {
          $attribute_name = $match[1];
          $working = $mode = 1;
          $attr = preg_replace('/^[-a-zA-Z]+/', '', $attr);
        }
        break;

      // equals sign or valueless ("selected")
      case 1:
        if (preg_match('/^\s*=\s*/', $attr)) # equals sign
        {
          $working = 1;
          $mode = 2;
          $attr = preg_replace('/^\s*=\s*/', '', $attr);
          break;
        }

        if (preg_match('/^\s+/', $attr)) # valueless
        {
          $working = 1;
          $mode = 0;
          $attribute_array[] = array
                        ('name'  => $attribute_name,
                         'value' => '',
                         'whole' => $attribute_name,
                         'vless' => 'y');
          $attr = preg_replace('/^\s+/', '', $attr);
        }
        break;

      // attribute value, a URL after href= for instance
      case 2:
        if (preg_match('/^"([^"]*)"(\s+|$)/', $attr, $match))
        {
          $this_value = ksesBadProtocol($match[1], $allowed_protocols);

          $attribute_array[] = array
                        ('name'  => $attribute_name,
                         'value' => $this_value,
                         'whole' => "$attribute_name=\"$this_value\"",
                         'vless' => 'n');
          $working = 1;
          $mode = 0;
          $attr = preg_replace('/^"[^"]*"(\s+|$)/', '', $attr);
          break;
        }

        // value
        if (preg_match("/^'([^']*)'(\s+|$)/", $attr, $match))
        {
          $this_value = ksesBadProtocol($match[1], $allowed_protocols);

          $attribute_array[] = array
                        ('name'  => $attribute_name,
                         'value' => $this_value,
                         'whole' => "$attribute_name='$this_value'",
                         'vless' => 'n');
          $working = 1;
          $mode = 0;
          $attr = preg_replace("/^'[^']*'(\s+|$)/", '', $attr);
          break;
        }

        // value
        if (preg_match("%^([^\s\"']+)(\s+|$)%", $attr, $match))
        {
          $this_value = ksesBadProtocol($match[1], $allowed_protocols);

          $attribute_array[] = array
                        ('name'  => $attribute_name,
                         'value' => $this_value,
                         'whole' => "$attribute_name=\"$this_value\"",
                         'vless' => 'n');
                         # We add quotes to conform to W3C's HTML spec.
          $working = 1;
          $mode = 0;
          $attr = preg_replace("%^[^\s\"']+(\s+|$)%", '', $attr);
        }

        break;
    }

    // not well formed, remove and try again
    if ($working == 0)
    {
      $attr = ksesHtmlError($attr);
      $mode = 0;
    }
  }

  // special case, for when the attribute list ends with a valueless
  // attribute like "selected"
  if ($mode == 1)
  {
    $attribute_array[] = array
    ('name' => $attribute_name,
        'value' => '',
        'whole' => $attribute_name,
        'vless' => 'y');
  }

  return $attribute_array;
}

/**
 * @param string $value
 * @param string $vless
 * @param string $checkname
 * @param int $checkvalue
 * @return bool
 *
 * This function performs different checks for attribute values. The currently
 * implemented checks are "maxlen", "minlen", "maxval", "minval" and "valueless"
 * with even more checks to come soon.
 */
function ksesCheckAttributeValue($value, $vless, $checkname, $checkvalue)
{
  $ok = true;

  switch (strtolower($checkname))
  {
    // The maxlen check makes sure that the attribute value has a length not
    // greater than the given value. This can be used to avoid Buffer Overflows
    // in WWW clients and various Internet servers.
    case 'maxlen':
      if (strlen($value) > $checkvalue)
      {
        $ok = false;
      }
      break;

    // The minlen check makes sure that the attribute value has a length not
    // smaller than the given value.
    case 'minlen':
      if (strlen($value) < $checkvalue)
      {
        $ok = false;
      }
      break;

    // The maxval check does two things: it checks that the attribute value is
    // an integer from 0 and up, without an excessive amount of zeroes or
    // whitespace (to avoid Buffer Overflows). It also checks that the attribute
    // value is not greater than the given value.
    // This check can be used to avoid Denial of Service attacks.
    case 'maxval':
      if (!preg_match('/^\s{0,6}[0-9]{1,6}\s{0,6}$/', $value))
      {
        $ok = false;
      }
      if ($value > $checkvalue)
      {
        $ok = false;
      }
      break;

    // The minval check checks that the attribute value is a positive integer,
    // and that it is not smaller than the given value.
    case 'minval':
      if (!preg_match('/^\s{0,6}[0-9]{1,6}\s{0,6}$/', $value))
      {
        $ok = false;
      }
      if ($value < $checkvalue)
      {
        $ok = false;
      }
      break;

    // The valueless check checks if the attribute has a value
    // (like <a href="blah">) or not (<option selected>). If the given value
    // is a "y" or a "Y", the attribute must not have a value.
    // If the given value is an "n" or an "N", the attribute must have one.
    case 'valueless':
      if (strtolower($checkvalue) != $vless)
      {
        $ok = false;
      }
      break;
  }

  return $ok;
}

/**
 * @param string $string protocol to check
 * @param array $allowed_protocols list of allowed protocols
 * @return array|string|string[]|null
 *
 * This function removes all non-allowed protocols from the beginning of
 * $string. It ignores whitespace and the case of the letters, and it does
 * understand HTML entities. It does its work in a while loop, so it won't be
 * fooled by a string like "javascript:javascript:alert(57)".
 */
function ksesBadProtocol($string, $allowed_protocols)
{
  $string = ksesNoNull($string);
  // deals with Opera "feature"
  $string = preg_replace('/\xad+/', '', $string);
  $string2 = $string.'a';

  while ($string != $string2)
  {
    $string2 = $string;
    $string = ksesBadProtocolOnce($string, $allowed_protocols);
  }

  return $string;
}

/**
 * @param string $string
 * @return array|string|string[]|null
 *
 * This function removes any NULL characters in $string.
 */
function ksesNoNull($string)
{
  $string = preg_replace('/\0+/', '', $string);
  $string = preg_replace('/(\\\\0)+/', '', $string);

  return $string;
} # function kses_no_null

/**
 * @param string $string
 * @return array|string|string[]|null
 *
 * This function changes the character sequence  \"  to just  "
 * It leaves all other slashes alone. It's really weird, but the quoting from
 * preg_replace(//e) seems to require this.
 */
function ksesStripSlashes($string)
{
  return preg_replace('%\\\\"%', '"', $string);
}

/**
 * @param array $in_array
 * @return array
 *
 * This function goes through an array, and changes the keys to all lower case.
 */
function ksesArrayLowerCase($in_array)
{
  $out_array = array();

  foreach ($in_array as $in_key => $inval)
  {
    $out_key = strtolower($in_key);
    $out_array[$out_key] = array();

    foreach ($inval as $in_key_2 => $in_val_2)
    {
      $out_key_2 = strtolower($in_key_2);
      $out_array[$out_key][$out_key_2] = $in_val_2;
    }
  }

  return $out_array;
}

/**
 * @param string $string
 * @return array|string|string[]|null
 *
 * This function removes the HTML JavaScript entities found in early versions of
 * Netscape 4.
 */
function ksesJavascriptEntities($string)
{
  return preg_replace('%&\s*{[^}]*(}\s*;?|$)%', '', $string);

}

/**
 * @param string $string
 * @return array|string|string[]|null
 *
 * This function deals with parsing errors in kses_hair(). The general plan is
 * to remove everything to and including some whitespace, but it deals with
 * quotes and apostrophes as well.
 */
function ksesHtmlError($string)
{
  return preg_replace('/^("[^"]*("|$)|\'[^\']*(\'|$)|\S)*\s*/', '', $string);
}

/**
 * @param string $string string to check
 * @param array $allowed_protocols allowed URL protocols
 * @return array|string|string[]|null
 *
 * This function searches for URL protocols at the beginning of $string, while
 * handling whitespace and HTML entities.
 */
function ksesBadProtocolOnce($string, $allowed_protocols)
{
  return preg_replace_callback(
      '/^((&[^;]*;|[\sA-Za-z0-9])*)'.
      '(:|&#58;|&#[Xx]3[Aa];)\s*/',
      function ($m)use($allowed_protocols) {
        return ksesBadProtocolOnce2($m[1], $allowed_protocols);
        },
      $string);
}

/**
 * @param string $string
 * @param array $allowed_protocols
 * @return string
 *
 * This function processes URL protocols, checks to see if they're in the white-
 * list or not, and returns different data depending on the answer.
 */
function ksesBadProtocolOnce2($string, $allowed_protocols)
{
  $string2 = ksesDecodeEntities($string);
  $string2 = preg_replace('/\s/', '', $string2);
  $string2 = ksesNoNull($string2);
  // deals with Opera "feature"
  $string2 = preg_replace('/\xad+/', '', $string2);
  $string2 = strtolower($string2);

  $allowed = false;
  foreach ($allowed_protocols as $one_protocol)
  {
    if (strtolower($one_protocol) == $string2)
    {
      $allowed = true;
      break;
    }
  }

  if ($allowed)
  {
    return "$string2:";
  }
  else
  {
    return '';
  }
}

/**
 * @param string $string String to normalize entities in.
 * @return array|string|string[]|null
 *
 * This function normalizes HTML entities. It will convert "AT&T" to the correct
 * "AT&amp;T", "&#00058;" to "&#58;", "&#XYZZY;" to "&amp;#XYZZY;" and so on.
 */
function ksesNormalizeEntities($string)
{
  // Disarm all entities by converting & to &amp;
  $string = str_replace('&', '&amp;', $string);

  // Change back the allowed entities in our entity whitelist
  $string = preg_replace(
      '/&amp;([A-Za-z][A-Za-z0-9]{0,19});/',
      '&\\1;',
      $string);

  $string = preg_replace_callback(
      '/&amp;#0*([0-9]{1,5});/',
      function ($m) { return ksesNormalizeEntities2($m[1]); },
      $string);

  return preg_replace(
      '/&amp;#([Xx])0*(([0-9A-Fa-f]{2}){1,2});/',
      '&#\\1\\2;',
      $string);
}

/**
 * @param string $i
 * @return string
 *
 * This function helps kses_normalize_entities() to only accept 16 bit values
 * and nothing more for &#number; entities.
 */
function ksesNormalizeEntities2($i)
{
  return (($i > 65535) ? "&amp;#$i;" : "&#$i;");
} # function kses_normalize_entities2

/**
 * @param string $string String to decode entities in
 * @return array|string|string[]|null
 * This function decodes numeric HTML entities (&#65; and &#x41;). It doesn't
 * do anything with other entities like &auml;, but we don't need them in the
 * URL protocol whitelisting system anyway.
 */
function ksesDecodeEntities($string)
{
  $string = preg_replace_callback(
      '/&#([0-9]+);/',
      function ($m) {return chr($m[1]);},
      $string);

  return preg_replace_callback(
      '/&#[Xx]([0-9A-Fa-f]+);/',
      function ($m) { return chr(hexdec($m[1])); },
      $string);
}


