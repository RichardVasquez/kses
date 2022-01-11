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
 *	Kses strips evil scripts!
 *
 *	This class provides the capability for removing unwanted HTML/XHTML, attributes from
 *	tags, and protocols contained in links.  The net result is a much more powerful tool
 *	than the PHP internal strip_tags()
 *
 *	@author     Richard R. Vásquez (Original procedural code by Ulf Härnhammar)
 *	@link       https://github.com/RichardVasquez/kses Home Page for Kses
 *	@copyright  Richard R. Vásquez, Jr. 2005
 *	@version    PHP5 OOP 1.1.0 (0.3.0 for kses capability)
 *	@license    http://www.gnu.org/licenses/gpl.html GNU Public License
 *	@package    kses
 */
class Kses
{
    const DUMP_ARRAY = 1;
    const DUMP_JSON  = 2;

    /**
     *	@var array Allowed protocols such as https:, mailto:, etc.
     */
    private $allowed_protocols;

    /**
     * @var array Whitelist of HTML tags
     */
    private $allowed_html;

    /**
     *	Constructor for kses.
     *
     *	This sets a default collection of protocols allowed in links, and creates an
     *	empty set of allowed HTML tags.
     *	@since PHP5 OOP 1.0.0
     */
    public function __construct()
    {
        /**
         *	You could add protocols such as file, skype, tel, slack
         *
         *  As browsers have reduced the scope of default supported protocols,
         *  the initial list of protocols is fairly sparse.
         */
        $this->allowed_protocols = array('http', 'https', 'mailto');
        $this->allowed_html      = array();
    }

    /**
     *	Basic task of kses - parses $string and strips it as required.
     *
     *	This method strips all the disallowed (X)HTML tags, attributes
     *	and protocols from the input $string.
     *
     *	@access public
     *	@param string $string String to be stripped of 'evil scripts'
     *	@return string The stripped string
     *	@since PHP4 OOP 0.0.1
     */
    public function Parse($string = "")
    {
        $pattern_javascript = '%&\s*{[^}]*(}\s*;?|$)%';

        $pattern_tags =
            '%(<' .   // EITHER: <
            '[^>]*' . // things that aren't >
            '(>|$)' . // > or end of string
            '|>)%';   // OR: just a >

        $string = $this->_removeNulls($string);
        //	Remove JavaScript entities from early Netscape 4 versions
        $string = preg_replace($pattern_javascript, '', $string);
        $string = $this->normalizeEntities($string);
        $string = $this->_filterKsesTextHook($string);
        return preg_replace_callback(
            $pattern_tags,
            function ($m) { return $this->stripTags($m[1]); },
            $string);
    }

    /**
     *	Allows for single/batch addition of protocols
     *
     *	This method accepts one argument that can be either a string
     *	or an array of strings.  Invalid data will be ignored.
     *
     *	The argument will be processed, and each string will be added
     *	via AddProtocol().
     *
     *	@access public
     *	@param mixed , A string or array of protocols that will be added to the internal list of allowed protocols.
     *	@return bool Status of adding valid protocols.
     *	@see AddProtocol()
     *	@since PHP5 OOP 1.0.0
     */
    public function AddProtocols()
    {
        $c_args = func_num_args();
        if($c_args < 1)
        {
            trigger_error(ERRORS['ADD_PROTOCOLS_NO_ARG'], E_USER_WARNING);
            return false;
        }

        //  Major change - shouldn't break
        //  Iterates through all arguments and adds string or recursion through array elements
        //  Returns false on first bad protocol
        for($i = 0; $i < $c_args; $i++)
        {
            $data = func_get_arg($i);
            switch (gettype($data))
            {
                case 'string':
                    $this->AddProtocol($data);
                    break;
                case 'array':
                    call_user_func_array( array($this,'AddProtocols'), $data);
                    break;
                default:
                    trigger_error(ERRORS['ADD_PROTOCOLS_BAD_ARG'], E_USER_WARNING);
                    return false;
            }
        }

        return true;
    }

    /**
     *	Adds a single protocol to $this->allowed_protocols.
     *
     *	This method accepts a string argument and adds it to
     *	the list of allowed protocols to keep when performing
     *	Parse().
     *
     *	@access public
     *	@param string $protocol The name of the protocol to be added.
     *	@return bool Status of adding valid protocol.
     *	@since PHP4 OOP 0.0.1
     */
    public function AddProtocol($protocol = '')
    {
        if(!is_string($protocol))
        {
            trigger_error(ERRORS['ADD_PROTOCOL_NOT_STRING'], E_USER_WARNING);
            return false;
        }

        $protocol = $this->_cleanProtocol($protocol);
        if($protocol == '')
        {
            trigger_error(ERRORS['ADD_PROTOCOL_EMPTY'], E_USER_WARNING);
            return false;
        }

        //	prevent duplicate protocols from being added.
        if(!in_array($protocol, $this->allowed_protocols))
        {
            array_push($this->allowed_protocols, $protocol);
            sort($this->allowed_protocols);
        }
        return true;
    }

    /**
     *	Removes a single protocol from $this->allowed_protocols.
     *
     *	This method accepts a string argument and removes it from
     *	the list of allowed protocols to keep when performing
     *	Parse().
     *
     *	@access public
     *	@param string $protocol The name of the protocol to be removed.
     *	@return bool Status of removing valid protocol.
     *	@since PHP5 OOP 1.0.0
     */
    public function RemoveProtocol($protocol = '')
    {
        if(!is_string($protocol))
        {
            trigger_error(ERRORS['REMOVE_PROTOCOL_NOT_STRING'], E_USER_WARNING);
            return false;
        }

        $protocol = $this->_cleanProtocol($protocol);
        if($protocol == '')
        {
            trigger_error(ERRORS['REMOVE_PROTOCOL_EMPTY'], E_USER_WARNING);
            return false;
        }

        //	Ensures that the protocol exists before removing it.
        if(in_array($protocol, $this->allowed_protocols))
        {
            $this->allowed_protocols = array_diff($this->allowed_protocols, array($protocol));
            sort($this->allowed_protocols);
        }

        return true;
    }

    /**
     *	Allows for single/batch removal of protocols
     *
     *	This method accepts one argument that can be either a string
     *	or an array of strings.  Invalid data will be ignored.
     *
     *	The argument will be processed, and each string will be removed
     *	via RemoveProtocol().
     *
     *	@access public
     *	@param mixed , A string or array of protocols that will be removed from the internal list of allowed protocols.
     *	@return bool Status of removing valid protocols.
     *	@see RemoveProtocol()
     *	@since PHP5 OOP 1.0.0
     */
    public function RemoveProtocols()
    {
        $c_args = func_num_args();
        if($c_args < 1)
        {
            trigger_error(ERRORS['REMOVE_PROTOCOLS_BAD_ARG'], E_USER_WARNING);
            return false;
        }

        //  Major change - shouldn't break
        //  Iterates through all arguments and adds string or recursion through array elements
        //  Returns false on first bad protocol
        for($i = 0; $i < $c_args; $i++)
        {
            $data = func_get_arg($i);
            switch (gettype($data))
            {
                case 'string':
                    $this->RemoveProtocol($data);
                    break;
                case 'array':
                    call_user_func_array( array($this,'RemoveProtocols'), $data);
                    break;
                default:
                    trigger_error(ERRORS['REMOVE_PROTOCOL_NOT_STRING'], E_USER_WARNING);
                    return false;
            }
        }

        return true;
    }

    /**
     *	Allows for single/batch replacement of protocols
     *
     *	This method accepts one argument that can be either a string
     *	or an array of strings.  Invalid data will be ignored.
     *
     *	Existing protocols will be removed, then the argument will be
     *	processed, and each string will be added via AddProtocol().
     *
     *	@access public
     *	@param mixed , A string or array of protocols that will be the new internal list of allowed protocols.
     *	@return bool Status of replacing valid protocols.
     *	@since PHP5 OOP 1.0.1
     *	@see AddProtocol()
     */
    public function SetProtocols()
    {

        $c_args = func_num_args();
        if($c_args < 1)
        {
            trigger_error(ERRORS['SET_PROTOCOLS_NO_ARG'], E_USER_WARNING);
            return false;
        }

        $this->allowed_protocols = array();
        //  Major change - shouldn't break
        //  Iterates through all arguments and adds string or recursion through array elements
        //  Returns false on first bad protocol
        for($i = 0; $i < $c_args; $i++)
        {
            $data = func_get_arg($i);
            switch (gettype($data))
            {
                case 'string':
                    $this->AddProtocol($data);
                    break;
                case 'array':
                    call_user_func_array( array($this,'AddProtocols'), $data);
                    break;
                default:
                    trigger_error(ERRORS['SET_PROTOCOLS_BAD_ARG'], E_USER_WARNING);
                    return false;
            }
        }

        return true;
    }

    /**
     *	Raw dump of allowed protocols
     *
     *	This returns an indexed array of allowed protocols for a particular KSES
     *	instantiation.
     *
     *	@access public
     *	@return array The list of allowed protocols.
     *	@since PHP5 OOP 1.0.2
     */
    public function DumpProtocols()
    {
        return $this->allowed_protocols;
    }

    /**
     *	Raw dump of allowed (X)HTML elements
     *
     *	This returns by defauly, an indexed array of allowed (X)HTML
     *  elements and attributes for a particular KSES instantiation.
     *
     *  Using kses::DUMP_JSON will return a JSON encoding of the data.
     *
     *	@access public
     *	@return array|string|false The list of allowed elements.
     *	@since PHP5 OOP 1.0.2
     */
    public function DumpElements($flag = self::DUMP_ARRAY)
    {
        switch($flag)
        {
            case self::DUMP_JSON:
                return json_encode($this->allowed_html);
            default:
                return $this->allowed_html;
        }

    }

    /**
     *	Adds valid (X)HTML with corresponding attributes that will be kept when stripping 'evil scripts'.
     *
     *	This method accepts one argument that can be either a string
     *	or an array of strings.  Invalid data will be ignored.
     *
     *	@access public
     *	@param string $tag (X)HTML tag that will be allowed after stripping text.
     *	@param array $attribs Associative array of allowed attributes - key => attribute name - value => attribute parameter
     *	@return bool Status of Adding (X)HTML and attributes.
     *	@since PHP4 OOP 0.0.1
     */
    public function AddHTML($tag = '', $attribs = array())
    {
        if(!is_string($tag))
        {
            trigger_error(ERRORS['ADD_HTML_NO_STRING'], E_USER_WARNING);
            return false;
        }

        $tag = strtolower(trim($tag));
        if($tag == '')
        {
            trigger_error(ERRORS['ADD_HTML_EMPTY'], E_USER_WARNING);
            return false;
        }

        if(!is_array($attribs))
        {
            trigger_error(ERRORS['ADD_HTML_ATTRIBS_ARRAY'] . "'$tag'", E_USER_WARNING);
            return false;
        }

        $new_attribs = array();
        if(count($attribs) > 0)
        {
            foreach($attribs as $idx1 => $val1)
            {
                $new_idx1 = strtolower($idx1);
                $new_val1 = $val1;

                if(is_array($new_val1) && count($attribs) > 0)
                {
                    $tmp_val = array();
                    foreach($new_val1 as $idx2 => $val2)
                    {
                        $new_idx2 = strtolower($idx2);
                        $tmp_val[$new_idx2] = $val2;
                    }
                    $new_val1 = $tmp_val;
                }

                $new_attribs[$new_idx1] = $new_val1;
            }
        }

        $this->allowed_html[$tag] = $new_attribs;
        return true;
    }

    /**
     *	This method removes any NULL characters in $string.
     *
     *	@access private
     *	@param string $string
     *	@return string String without any NULL/chr(173)
     *	@since PHP4 OOP 0.0.1
     */
    private function _removeNulls($string)
    {
        $string = preg_replace('/\0+/', '', $string);
        return preg_replace('/(\\\\0)+/', '', $string);
    }

    /**
     *	Normalizes HTML entities
     *
     *	This function normalizes HTML entities. It will convert "AT&T" to the correct
     *	"AT&amp;T", "&#00058;" to "&#58;", "&#XYZZY;" to "&amp;#XYZZY;" and so on.
     *
     *	@access private
     *	@param string $string
     *	@return string String with normalized entities
     *	@since PHP4 OOP 0.0.1
     */
    private function normalizeEntities($string)
    {
        // Disarm all entities by converting & to &amp;
        $string = str_replace('&', '&amp;', $string);

        //	Keeps entities that start with [A-Za-z]
        $string = preg_replace(
            '/&amp;([A-Za-z][A-Za-z0-9]{0,19});/',
            '&\\1;',
            $string
        );

        //	Change numeric entities to valid 16 bit values
        $string = preg_replace_callback(
            '/&amp;#0*([0-9]{1,5});/',
            function ($m) { return $this->_normalizeEntities16bit($m[1]); },
            $string
        );

        //	Change &XHHHHHHH (Hex digits) to 16 bit hex values
        return preg_replace(
            '/&amp;#([Xx])0*(([0-9A-Fa-f]{2}){1,2});/',
            '&#\\1\\2;',
            $string
        );
    }

    /**
     *	Helper method used by normalizeEntities()
     *
     *	This method helps normalizeEntities() to only accept 16 bit values
     *	and nothing more for &#number; entities.
     *
     *	This method helps normalize_entities() during a preg_replace()
     *	where a &#(0)*XXXXX; occurs.  The '(0)*XXXXXX' value is converted to
     *	a number and the result is returned as a numeric entity if the number
     *	is less than 65536.  Otherwise, the value is returned 'as is'.
     *
     *	@access private
     *	@param string $i
     *	@return string Normalized numeric entity
     *	@see normalizeEntities()
     *	@since PHP4 OOP 0.0.1
     */
    private function _normalizeEntities16bit($i)
    {
        return (($i > 65535) ? "&amp;#$i;" : "&#$i;");
    }

    /**
     *	Allows for additional user defined modifications to text.
     *
     *	This method allows for additional modifications to be performed on
     *	a string that's being run through Parse().  Currently, it returns the
     *	input string 'as is'.
     *
     *	This method is provided for users to extend the kses class for their own
     *	requirements.
     *
     *	@access public
     *	@param string $string String to perfrom additional modifications on.
     *	@return string User modified string.
     *	@see Parse()
     *	@since PHP5 OOP 1.0.0
     */
    private function _filterKsesTextHook($string)
    {
        return $string;
    }

    /**
     *	This method goes through an array, and changes the keys to all lower case.
     *
     *	@access private
     *	@param array $in_array Associative array
     *	@return array Modified array
     *	@since PHP4 OOP 0.0.1
     */
    private function _makeArrayKeysLowerCase($in_array)
    {
        $out_array = array();

        if(is_array($in_array) && count($in_array) > 0)
        {
            foreach ($in_array as $in_key => $in_val)
            {
                $out_key = strtolower($in_key);
                $out_array[$out_key] = array();

                if(is_array($in_val) && count($in_val) > 0)
                {
                    foreach ($in_val as $in_key2 => $in_val2)
                    {
                        $out_key2 = strtolower($in_key2);
                        $out_array[$out_key][$out_key2] = $in_val2;
                    }
                }
            }
        }

        return $out_array;
    }

    /**
     *	This method strips out disallowed and/or mangled (X)HTML tags along with assigned attributes.
     *
     *	This method does a lot of work. It rejects some very malformed things
     *	like <:::>. It returns an empty string if the element isn't allowed (look
     *	ma, no strip_tags()!). Otherwise it splits the tag into an element and an
     *	allowed attribute list.
     *
     *	@access private
     *	@param string $string
     *	@return string Modified string minus disallowed/mangled (X)HTML and attributes
     *	@since PHP4 OOP 0.0.1
     */
    private function stripTags($string)
    {
        $string = preg_replace('%\\\\"%', '"', $string);

        if (substr($string, 0, 1) != '<')
        {
            // It matched a ">" character
            return '&gt;';
        }

        if (!preg_match('%^<\s*(/\s*)?([a-zA-Z0-9]+)([^>]*)>?$%', $string, $matches))
        {
            // It's seriously malformed
            return '';
        }

        list(,$slash,$elem,$attribute_list) = $matches;
        $slash=trim($slash);

        if (
            !isset($this->allowed_html[strtolower($elem)]) ||
            !is_array($this->allowed_html[strtolower($elem)]))
        {
            //	Found an HTML element not in the white list
            return '';
        }

        if ($slash != '')
        {
            return "<$slash$elem>";
        }

        // No attributes are allowed for closing elements
        return $this->stripAttributes("$slash$elem", $attribute_list);
    }

    /**
     *	This method strips out disallowed attributes for (X)HTML tags.
     *
     *	This method removes all attributes if none are allowed for this element.
     *	If some are allowed it calls combAttributes() to split them further, and then it
     *	builds up new HTML code from the data that combAttributes() returns. It also
     *	removes "<" and ">" characters, if there are any left. One more thing it
     *	does is to check if the tag has a closing XHTML slash, and if it does,
     *	it puts one in the returned code as well.
     *
     *	@access private
     *	@param string $element (X)HTML tag to check
     *	@param string $attr Text containing attributes to check for validity.
     *	@return string Resulting valid (X)HTML or ''
     *	@see combAttributes()
     *	@since PHP4 OOP 0.0.1
     */
    private function stripAttributes($element, $attr)
    {
        // Is there a closing XHTML slash at the end of the attributes?
        $xhtml_slash = '';
        if (preg_match('%\s/\s*$%', $attr))
        {
            $xhtml_slash = ' /';
        }

        // Are any attributes allowed at all for this element?
        if (
            !isset($this->allowed_html[strtolower($element)]) ||
            count($this->allowed_html[strtolower($element)]) == 0
        )
        {
            return "<$element$xhtml_slash>";
        }

        // Split it
        $attribute_array = $this->combAttributes($attr);

        // Go through $attribute_array, and save the allowed attributes for this element
        // in $attr2
        $attr2 = '';
        if(is_array($attribute_array) && count($attribute_array) > 0)
        {
            foreach ($attribute_array as $array_each)
            {
                if(!isset($this->allowed_html[strtolower($element)][strtolower($array_each['name'])]))
                {
                    continue;
                }

                $current = $this->allowed_html[strtolower($element)][strtolower($array_each['name'])];

                if (!is_array($current))
                {
                    # there are no checks
                    $attr2 .= ' '.$array_each['whole'];
                }
                else
                {
                    // there are some checks
                    $ok = true;
                    if(count($current) > 0)
                    {
                        foreach ($current as $current_key => $current_value)
                        {
                            if (!$this->_checkAttributeValue($array_each['value'], $array_each['vless'], $current_key, $current_value))
                            {
                                $ok = false;
                                break;
                            }
                        }
                    }

                    if ($ok)
                    {
                        // it passed them
                        $attr2 .= ' '.$array_each['whole'];
                    }
                }
            }
        }

        // Remove any "<" or ">" characters
        $attr2 = preg_replace('/[<>]/', '', $attr2);
        return "<$element$attr2$xhtml_slash>";
    }

    /**
     *	This method combs through an attribute list string and returns an associative array of attributes and values.
     *
     *	This method does a lot of work. It parses an attribute list into an array
     *	with attribute data, and tries to do the right thing even if it gets weird
     *	input. It will add quotes around attribute values that don't have any quotes
     *	or apostrophes around them, to make it easier to produce HTML code that will
     *	conform to W3C's HTML specification. It will also remove bad URL protocols
     *	from attribute values.
     *
     *	@access private
     *	@param string $attr Text containing tag attributes for parsing
     *	@return array Associative array containing data on attribute and value
     *	@since PHP4 OOP 0.0.1
     */
    private function combAttributes($attr)
    {
        $attribute_array  = array();
        $mode     = 0;
        $attribute_name = '';

        // Loop through the whole attribute list
        while (strlen($attr) != 0)
        {
            # Was the last operation successful?
            $working = 0;

            switch ($mode)
            {
                case 0:	# attribute name, href for instance
                    if (preg_match('/^([-a-zA-Z]+)/', $attr, $match))
                    {
                        $attribute_name = $match[1];
                        $working = $mode = 1;
                        $attr = preg_replace('/^[-a-zA-Z]+/', '', $attr);
                    }
                    break;
                case 1:	# equals sign or valueless ("selected")
                    if (preg_match('/^\s*=\s*/', $attr)) # equals sign
                    {
                        $working = 1;
                        $mode    = 2;
                        $attr    = preg_replace('/^\s*=\s*/', '', $attr);
                        break;
                    }
                    if (preg_match('/^\s+/', $attr)) # valueless
                    {
                        $working   = 1;
                        $mode      = 0;
                        $attribute_array[] = array(
                            'name'  => $attribute_name,
                            'value' => '',
                            'whole' => $attribute_name,
                            'vless' => 'y'
                        );
                        $attr      = preg_replace('/^\s+/', '', $attr);
                    }
                    break;
                case 2: # attribute value, a URL after href= for instance
                    if (preg_match('/^"([^"]*)"(\s+|$)/', $attr, $match)) # "value"
                    {
                        $thisval   = $this->removeBadProtocols($match[1]);
                        $attribute_array[] = array(
                            'name'  => $attribute_name,
                            'value' => $thisval,
                            'whole' => $attribute_name . '="' . $thisval . '"',
                            'vless' => 'n'
                        );
                        $working   = 1;
                        $mode      = 0;
                        $attr      = preg_replace('/^"[^"]*"(\s+|$)/', '', $attr);
                        break;
                    }
                    if (preg_match("/^'([^']*)'(\s+|$)/", $attr, $match)) # 'value'
                    {
                        $thisval   = $this->removeBadProtocols($match[1]);
                        $attribute_array[] = array(
                            'name'  => $attribute_name,
                            'value' => $thisval,
                            'whole' => "$attribute_name='$thisval'",
                            'vless' => 'n'
                        );
                        $working   = 1;
                        $mode      = 0;
                        $attr      = preg_replace("/^'[^']*'(\s+|$)/", '', $attr);
                        break;
                    }
                    if (preg_match("%^([^\s\"']+)(\s+|$)%", $attr, $match)) # value
                    {
                        $thisval   = $this->removeBadProtocols($match[1]);
                        $attribute_array[] = array(
                            'name'  => $attribute_name,
                            'value' => $thisval,
                            'whole' => $attribute_name . '="' . $thisval . '"',
                            'vless' => 'n'
                        );
                        # We add quotes to conform to W3C's HTML spec.
                        $working   = 1;
                        $mode      = 0;
                        $attr      = preg_replace("%^[^\s\"']+(\s+|$)%", '', $attr);
                    }
                    break;
            }

            if ($working == 0) # not well formed, remove and try again
            {
                $attr = preg_replace('/^("[^"]*("|$)|\'[^\']*(\'|$)|\S)*\s*/', '', $attr);
                $mode = 0;
            }
        }

        # special case, for when the attribute list ends with a valueless
        # attribute like "selected"
        if ($mode == 1)
        {
            $attribute_array[] = array(
                'name'  => $attribute_name,
                'value' => '',
                'whole' => $attribute_name,
                'vless' => 'y'
            );
        }

        return $attribute_array;
    }

    /**
     *	This method removes disallowed protocols.
     *
     *	This method removes all non-allowed protocols from the beginning of
     *	$string. It ignores whitespace and the case of the letters, and it does
     *	understand HTML entities. It does its work in a while loop, so it won't be
     *	fooled by a string like "javascript:javascript:alert(57)".
     *
     *	@access private
     *	@param string $string String to check for protocols
     *	@return string String with removed protocols
     *	@since PHP4 OOP 0.0.1
     */
    private function removeBadProtocols($string)
    {
        $string  = $this->_removeNulls($string);
        $string = preg_replace('/\xad+/', '', $string); # deals with Opera "feature"
        $string2 = $string . 'a';

        while ($string != $string2)
        {
            $string2 = $string;
            $string  =  preg_replace_callback(
                '/^((&[^;]*;|[\sA-Za-z0-9])*)'.
                '(:|&#58;|&#[Xx]3[Aa];)\s*/',
                function ($m) { return $this->_filterProtocols($m[1]); },
                $string
            );
        }

        return $string;
    }

    /**
     *	Helper method used by removeBadProtocols()
     *
     *	This function processes URL protocols, checks to see if they're in the white-
     *	list or not, and returns different data depending on the answer.
     *
     *	@access private
     *	@param string $string String to check for protocols
     *	@return string String with removed protocols
     *	@see removeBadProtocols()
     *	@since PHP4 OOP 0.0.1
     */
    private function _filterProtocols($string)
    {
        $string = $this->_decodeEntities($string);
        $string = preg_replace('/\s/', '', $string);
        $string = $this->_removeNulls($string);
        $string = preg_replace('/\xad+/', '', $string); # deals with Opera "feature"
        $string = strtolower($string);

        if(is_array($this->allowed_protocols) && count($this->allowed_protocols) > 0)
        {
            foreach ($this->allowed_protocols as $one_protocol)
            {
                if (strtolower($one_protocol) == $string)
                {
                    return "$string:";
                }
            }
        }

        return '';
    }

    /**
     *	Controller method for performing checks on attribute values.
     *
     *	This method calls the appropriate method as specified by $checkname with
     *	the parameters $value, $vless, and $checkvalue, and returns the result
     *	of the call.
     *
     *	This method's functionality can be expanded by creating new methods
     *	that would match checkAttributeValue[$checkname].
     *
     *	Current checks implemented are: "maxlen", "minlen", "maxval", "minval" and "valueless"
     *
     *	@access private
     *	@param string $value The value of the attribute to be checked.
     *	@param string $vless Indicates whether the the value is supposed to be valueless
     *	@param string $checkname The check to be performed
     *	@param string $checkvalue The value that is to be checked against
     *	@return bool Indicates whether the check passed or not
     *	@since PHP5 OOP 1.0.0
     */
    private function _checkAttributeValue($value, $vless, $checkname, $checkvalue)
    {
        $ok = true;
        $check_attribute_method_name  = 'checkAttributeValue' . ucfirst(strtolower($checkname));
        if(method_exists($this, $check_attribute_method_name))
        {
            $ok = $this->$check_attribute_method_name($value, $checkvalue, $vless);
        }

        return $ok;
    }

    /**
     *	Helper method invoked by checkAttributeValue().
     *
     *	The maxlen check makes sure that the attribute value has a length not
     *	greater than the given value. This can be used to avoid Buffer Overflows
     *	in WWW clients and various Internet servers.
     *
     *	@access private
     *	@param string $value The value of the attribute to be checked.
     *	@param int $checkvalue The maximum value allowed
     *	@return bool Indicates whether the check passed or not
     *	@see _checkAttributeValue()
     *	@since PHP5 OOP 1.0.0
     */
    private function checkAttributeValueMaxlen($value, $checkvalue)
    {
        if (strlen($value) > intval($checkvalue))
        {
            return false;
        }
        return true;
    }

    /**
     *	Helper method invoked by checkAttributeValue().
     *
     *	The minlen check makes sure that the attribute value has a length not
     *	smaller than the given value.
     *
     *	@access private
     *	@param string $value The value of the attribute to be checked.
     *	@param int $checkvalue The minimum value allowed
     *	@return bool Indicates whether the check passed or not
     *	@see _checkAttributeValue()
     *	@since PHP5 OOP 1.0.0
     */
    private function checkAttributeValueMinlen($value, $checkvalue)
    {
        if (strlen($value) < intval($checkvalue))
        {
            return false;
        }
        return true;
    }

    /**
     *	Helper method invoked by checkAttributeValue().
     *
     *	The maxval check does two things: it checks that the attribute value is
     *	an integer from 0 and up, without an excessive amount of zeroes or
     *	whitespace (to avoid Buffer Overflows). It also checks that the attribute
     *	value is not greater than the given value.
     *
     *	This check can be used to avoid Denial of Service attacks.
     *
     *	@access private
     *	@param int $value The value of the attribute to be checked.
     *	@param int $checkvalue The maximum numeric value allowed
     *	@return bool Indicates whether the check passed or not
     *	@see _checkAttributeValue()
     *	@since PHP5 OOP 1.0.0
     */
    private function checkAttributeValueMaxval($value, $checkvalue)
    {
        if (!preg_match('/^\s{0,6}[0-9]{1,6}\s{0,6}$/', $value))
        {
            return false;
        }
        if (intval($value) > intval($checkvalue))
        {
            return false;
        }
        return true;
    }

    /**
     *	Helper method invoked by checkAttributeValue().
     *
     *	The minval check checks that the attribute value is a positive integer,
     *	and that it is not smaller than the given value.
     *
     *	@access private
     *	@param int $value The value of the attribute to be checked.
     *	@param int $checkvalue The minimum numeric value allowed
     *	@return bool Indicates whether the check passed or not
     *	@see _checkAttributeValue()
     *	@since PHP5 OOP 1.0.0
     */
    private function checkAttributeValueMinval($value, $checkvalue)
    {
        if (!preg_match('/^\s{0,6}[0-9]{1,6}\s{0,6}$/', $value))
        {
            return false;
        }
        if (intval($value) < ($checkvalue))
        {
            return false;
        }
        return true;
    }

    /**
     *	Helper method invoked by checkAttributeValue().
     *
     *	The valueless check checks if the attribute has a value
     *	(like <a href="blah">) or not (<option selected>). If the given value
     *	is a "y" or a "Y", the attribute must not have a value.
     *
     *	If the given value is an "n" or an "N", the attribute must have one.
     *
     *	@access private
     *	@param int $value The value of the attribute to be checked.
     *	@param mixed $checkvalue This variable is ignored for this test
     *	@param string $vless Flag indicating if this attribute is not supposed to have an attribute
     *	@return bool Indicates whether the check passed or not
     *	@see _checkAttributeValue()
     *	@since PHP5 OOP 1.0.0
     */
    private function checkAttributeValueValueless($value, $checkvalue, $vless)
    {
        if (strtolower($checkvalue) != $vless)
        {
            return false;
        }
        return true;
    }

    /**
     *	Decodes numeric HTML entities
     *
     *	This method decodes numeric HTML entities (&#65; and &#x41;). It doesn't
     *	do anything with other entities like &auml;, but we don't need them in the
     *	URL protocol white listing system anyway.
     *
     *	@access private
     *	@param string $string The entitiy to be decoded.
     *	@return string Decoded entity
     *	@since PHP4 OOP 0.0.1
     */
    private function _decodeEntities($string)
    {
        $string = preg_replace_callback(
            '/&#([0-9]+);/',
            function ($m) { return chr($m[1]); },
            $string);

        return preg_replace_callback(
            '/&#[Xx]([0-9A-Fa-f]+);/',
            function ($m) { return chr(hexdec($m[1])); },
            $string);
    }

    /**
     *	Returns PHP5 OOP version # of kses.
     *
     *	Since this class has been refactored and documented and proven to work,
     *	I'm fixing the version number at 1.0.0.
     *
     *	This version is syntax compatible with the PHP4 OOP version 0.0.2.  Future
     *	versions may not be syntax compatible.
     *
     *	@access public
     *	@return string Version number
     *	@since PHP4 OOP 0.0.1
     */
    public function Version()
    {
        return VERSION;
    }

    // Remove any inadvertent ':' at the end of the protocol.
    private function _cleanProtocol($protocol)
    {
        if(substr($protocol, strlen($protocol) - 1, 1) == ':')
        {
            $protocol = substr($protocol, 0, strlen($protocol) - 1);
        }

        return strtolower(trim($protocol));
    }
}
