<?php

//  seriously, we're testing against junk values, so hush, PhpStorm.
/** @noinspection PhpIllegalPsrClassPathInspection */
/** @noinspection HtmlUnknownTarget */

const DS = DIRECTORY_SEPARATOR;
include_once '..' . DS . '_new.php';

use function Kses\kses;

/**
 * Class TestProcedural5
 *
 * This is the unit test for PHP 5.6
 * and was run on phpunit-5.7.27
 *
 */
class TestProcedural5 extends PHPUnit_Framework_TestCase
{
    public function testBasicEquivalency()
    {
        $html_before = 'kses \'kses\' kses "kses" kses \\kses\\';
        $html_after =  $html_before;

        $this->equivalencyTest($html_before, $html_after);
    }

    public function testBasicRemoveTag()
    {
        $html_before = 'kses <br>';
        $html_after =  'kses ';

        $this->equivalencyTest($html_before, $html_after);
    }

    public function testBasicTagCorrection()
    {
        $html_before = 'kses <  BR  >';
        $html_after =  'kses <BR>';
        $allowed = array('br'=>array());

        $this->equivalencyTest($html_before, $html_after, $allowed);
    }

    public function testBasicEntityExpansion()
    {
        $html_before = 'kses > 5 <br>';
        $html_after =  'kses &gt; 5 <br>';
        $allowed = array('br'=>array());

        $this->equivalencyTest($html_before, $html_after, $allowed);
    }

    public function testBasicTagClose()
    {
        $html_before = 'kses <  br';
        $html_after =  'kses <br>';
        $allowed = array('br'=>array());

        $this->equivalencyTest($html_before, $html_after, $allowed);
    }

    public function testBasicRemoveAttribute()
    {
        $html_before = 'kses <a href=5>';
        $html_after =  'kses <a>';
        $allowed = array('a'=>array());

        $this->equivalencyTest($html_before, $html_after, $allowed);
    }

    public function testBasicQuoteAttributeValue()
    {
        $html_before = 'kses <a href=5>';
        $html_after =  'kses <a href="5">';
        $allowed = array('a'=>array('href' => 1));

        $this->equivalencyTest($html_before, $html_after, $allowed);
    }

    public function testBasicKeepUnvaluedAttribute()
    {
        $html_before = 'kses <a href>';
        $html_after =  $html_before;
        $allowed = array('a'=>array('href' => 1));

        $this->equivalencyTest($html_before, $html_after, $allowed);
    }

    public function testBasicQuoteSingleUnquoted()
    {
        $html_before = 'kses <a href href=5 href=\'5\' href="5" dummy>';
        $html_after =  'kses <a href href="5" href=\'5\' href="5">';
        $allowed = array('a'=>array('href' => 1));

        $this->equivalencyTest($html_before, $html_after, $allowed);
    }

    public function testBasicKeepBackslashes()
    {
        $html_before = 'kses <a href="kses\\\\kses">';
        $html_after =  $html_before;
        $allowed = array('a'=>array('href' => 1));

        $this->equivalencyTest($html_before, $html_after, $allowed);
    }

    public function testBasicKeepWithinMaxlength()
    {
        $html_before = 'kses <a href="xxxxxx">';
        $html_after =  $html_before;
        $allowed = array('a' => array('href' => array('maxlen' => 6)));

        $this->equivalencyTest($html_before, $html_after, $allowed);
    }

    public function testBasicKeepPastMaxlength()
    {
        $html_before = 'kses <a href="xxxxxxx">';
        $html_after =  'kses <a>';
        $allowed = array('a' => array('href' => array('maxlen' => 6)));

        $this->equivalencyTest($html_before, $html_after, $allowed);
    }

    public function testBasicValueCheck()
    {
        $html_before = 'kses <a href="687">';
        $html_after =  'kses <a>';
        $allowed = array('a' => array('href' => array('maxval' => 686)));

        $this->equivalencyTest($html_before, $html_after, $allowed);
    }

    public function testBasicUnderMaxlength()
    {
        $html_before = 'kses <a href="xx"   /  >';
        $html_after =  'kses <a href="xx" />';
        $allowed = array('a' => array('href' => array('maxlen' => 6)));

        $this->equivalencyTest($html_before, $html_after, $allowed);
    }

    public function testBasicRemoveProtocols()
    {
        $html_before = 'kses <a href="JAVA java scrIpt : SCRIPT  :  alert(57)">';
        $html_after =  'kses <a href="alert(57)">';
        $allowed = array('a' => array('href' => 1));

        $this->equivalencyTest($html_before, $html_after, $allowed);
    }

    public function testBasicRemoveChr173()
    {
        $html_before = 'kses <a href="htt&#32; &#173;&#Xad;'.chr(173).'P://ulf">';
        $html_after =  'kses <a href="http://ulf">';
        $allowed = array('a' => array('href' => 1));

        $this->equivalencyTest($html_before, $html_after, $allowed);
    }

    public function testBasicMultipleTags()
    {
        $html_before = 'kses <a href="/start.php"> kses <a href="start.php">';
        $html_after =  $html_before;

        $allowed = array('a' => array('href' => 1));

        $this->equivalencyTest($html_before, $html_after, $allowed);
    }

    /**
     * @param string $html_before HTML or other text that needs to be converted
     * @param string $html_after Expected result
     * @param array $allowed allowed tags and attributes
     */
    private function equivalencyTest($html_before, $html_after, $allowed = array())
    {
        $html_kses = kses($html_before, $allowed);
        $this->assertEquals($html_kses, $html_after);
    }
}
