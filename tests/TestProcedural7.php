<?php

//  seriously, we're testing against junk values, so hush, PhpStorm.
/** @noinspection PhpIllegalPsrClassPathInspection */
/** @noinspection HtmlUnknownTarget */
/** @noinspection PhpMissingParamTypeInspection */

const DS = DIRECTORY_SEPARATOR;
include_once '..' . DS . '_new.php';

use function Kses\kses;
use PHPUnit\Framework\TestCase;

/**
 * Class TestProcedural7
 *
 * This is the unit test for PHP 7 and PHP 8
 * and was run on phpunit-9.5.11
 *
 */
class TestProcedural7 extends TestCase
{
    public function test_basic_equivalency()
    {
        $html_before = 'kses \'kses\' kses "kses" kses \\kses\\';
        $html_after =  $html_before;

        $this->equivalency_test($html_before, $html_after);
    }

    public function test_basic_remove_tag()
    {
        $html_before = 'kses <br>';
        $html_after =  'kses ';

        $this->equivalency_test($html_before, $html_after);
    }

    public function test_basic_tag_correction()
    {
        $html_before = 'kses <  BR  >';
        $html_after =  'kses <BR>';
        $allowed = array('br'=>array());

        $this->equivalency_test($html_before, $html_after, $allowed);
    }

    public function test_basic_entity_expansion()
    {
        $html_before = 'kses > 5 <br>';
        $html_after =  'kses &gt; 5 <br>';
        $allowed = array('br'=>array());

        $this->equivalency_test($html_before, $html_after, $allowed);
    }

    public function test_basic_tag_close()
    {
        $html_before = 'kses <  br';
        $html_after =  'kses <br>';
        $allowed = array('br'=>array());

        $this->equivalency_test($html_before, $html_after, $allowed);
    }

    public function test_basic_remove_attribute()
    {
        $html_before = 'kses <a href=5>';
        $html_after =  'kses <a>';
        $allowed = array('a'=>array());

        $this->equivalency_test($html_before, $html_after, $allowed);
    }

    public function test_basic_quote_attribute_value()
    {
        $html_before = 'kses <a href=5>';
        $html_after =  'kses <a href="5">';
        $allowed = array('a'=>array('href' => 1));

        $this->equivalency_test($html_before, $html_after, $allowed);
    }

    public function test_basic_keep_unvalued_attribute()
    {
        $html_before = 'kses <a href>';
        $html_after =  $html_before;
        $allowed = array('a'=>array('href' => 1));

        $this->equivalency_test($html_before, $html_after, $allowed);
    }

    public function test_basic_quote_single_unquoted()
    {
        $html_before = 'kses <a href href=5 href=\'5\' href="5" dummy>';
        $html_after =  'kses <a href href="5" href=\'5\' href="5">';
        $allowed = array('a'=>array('href' => 1));

        $this->equivalency_test($html_before, $html_after, $allowed);
    }

    public function test_basic_keep_backslashes()
    {
        $html_before = 'kses <a href="kses\\\\kses">';
        $html_after =  $html_before;
        $allowed = array('a'=>array('href' => 1));

        $this->equivalency_test($html_before, $html_after, $allowed);
    }

    public function test_basic_keep_within_maxlength()
    {
        $html_before = 'kses <a href="xxxxxx">';
        $html_after =  $html_before;
        $allowed = array('a' => array('href' => array('maxlen' => 6)));

        $this->equivalency_test($html_before, $html_after, $allowed);
    }

    public function test_basic_keep_past_maxlength()
    {
        $html_before = 'kses <a href="xxxxxxx">';
        $html_after =  'kses <a>';
        $allowed = array('a' => array('href' => array('maxlen' => 6)));

        $this->equivalency_test($html_before, $html_after, $allowed);
    }

    public function test_basic_value_check()
    {
        $html_before = 'kses <a href="687">';
        $html_after =  'kses <a>';
        $allowed = array('a' => array('href' => array('maxval' => 686)));

        $this->equivalency_test($html_before, $html_after, $allowed);
    }

    public function test_basic_under_maxlength()
    {
        $html_before = 'kses <a href="xx"   /  >';
        $html_after =  'kses <a href="xx" />';
        $allowed = array('a' => array('href' => array('maxlen' => 6)));

        $this->equivalency_test($html_before, $html_after, $allowed);
    }

    public function test_basic_remove_protocols()
    {
        $html_before = 'kses <a href="JAVA java scrIpt : SCRIPT  :  alert(57)">';
        $html_after =  'kses <a href="alert(57)">';
        $allowed = array('a' => array('href' => 1));

        $this->equivalency_test($html_before, $html_after, $allowed);
    }

    public function test_basic_remove_chr173()
    {
        $html_before = 'kses <a href="htt&#32; &#173;&#Xad;'.chr(173).'P://ulf">';
        $html_after =  'kses <a href="http://ulf">';
        $allowed = array('a' => array('href' => 1));

        $this->equivalency_test($html_before, $html_after, $allowed);
    }

    public function test_basic_multiple_tags()
    {
        $html_before = 'kses <a href="/start.php"> kses <a href="start.php">';
        $html_after =  $html_before;

        $allowed = array('a' => array('href' => 1));

        $this->equivalency_test($html_before, $html_after, $allowed);
    }

    /**
     * @param string $html_before HTML or other text that needs to be converted
     * @param string $html_after Expected result
     * @param array $allowed allowed tags and attributes
     */
    private function equivalency_test($html_before, $html_after, $allowed = array())
    {
        $html_kses = kses($html_before, $allowed);
        $this->assertEquals($html_kses, $html_after);
    }
}
