<?php

const DS = DIRECTORY_SEPARATOR;
include_once '..' . DS . 'kses.class.php';

class TestKsesClass5 extends PHPUnit_Framework_TestCase
{
    private $protoData = array(
        'kses' => array('http','https','mailto'),
        'base1' => array('proto1', 'proto2','proto3'),
        'colon' => array('proto4:'),
        'random' => array('proto9','mystery','anarchy'),
        'greek' => array('alpha', 'beta', 'gamma'),
        'alpha' => array(
            'a', 'b', 'c', 'd', 'e',
            'f', 'g', 'h', 'i', 'j',
            'k', 'l', 'm', 'n', 'o',
            'p', 'q', 'r', 's', 't'
        )
    );

    //	Allows 'a' tag with href|name attributes,
    //	href has minlen of 10 chars, and maxlen of 25 chars
    //	name has minlen of  2 chars
    private $tagList = [
        'a' => [
            "href" => ['maxlen' => 25, 'minlen' => 10],
            "name" => ['minlen' => 2]
        ],
        'td' => [
            'colspan' => ['minval' =>   2, 'maxval' =>   5],
            'rowspan' => ['minval' =>   3, 'maxval' =>   6],
            'class'   => ['minlen' =>   1, 'maxlen' =>  10],
            'width'   => ['maxval' => 100],
            'style'   => ['minlen' =>  10, 'maxlen' => 100],
            'nowrap'  => ['valueless' => 'y']
        ]
    ];

    private $tagsTest = [
        1 =>  '<a href="http://www.chaos.org/">www.chaos.org</a>',
        2 =>  '<a name="X">Short \'a name\' tag</a>',
        3 =>  '<td colspan="3" rowspan="5">Foo</td>',
        4 =>  '<td rowspan="2" class="mugwump" style="background-color: rgb(255, 204 204);">Bar</td>',
        5 =>  '<td nowrap>Very Long String running to 1000 characters...</td>',
        6 =>  '<td bgcolor="#00ff00" nowrap>Very Long String with a blue background</td>',
        7 =>  '<a href="proto1://www.foo.com">New protocol test</a>',
        8 =>  '<img src="proto2://www.foo.com" />',
        9 =>  '<a href="javascript:javascript:javascript:javascript:javascript:alert(\'Boo!\');">bleep</a>',
        10 => '<a href="proto4://abc.xyz.foo.com">Another new protocol</a>',
        11 => '<a href="proto9://foo.foo.foo.foo.foo.org/">Test of "proto9"</a>',
        12 => '<td width="75">Bar!</td>',
        13 => '<td width="200">Long Cell</td>'
      ];

    private $tagsResult = [
        1=> '<a href="http://www.chaos.org/">www.chaos.org</a>',
        2=> '<a>Short \'a name\' tag</a>',
        3=>'<td colspan="3" rowspan="5">Foo</td>',
        4=>'<td class="mugwump" style="background-color: rgb(255, 204 204);">Bar</td>',
        5=>'<td nowrap>Very Long String running to 1000 characters...</td>',
        6=>'<td nowrap>Very Long String with a blue background</td>',
        7=>'<a href="proto1://www.foo.com">New protocol test</a>',
        8=>'',
        9=>'<a href="alert(\'Boo!\');">bleep</a>',
        10=>'<a href="proto4://abc.xyz.foo.com">Another new protocol</a>',
        11=>'<a>Test of "proto9"</a>',
        12=>'<td width="75">Bar!</td>',
        13=>'<td>Long Cell</td>'
    ];

    //  Test base protocols exist
    public function testProtocols01()
    {
        $kses = new Kses\Kses();
        $default_protocols = $kses->DumpProtocols();
        $this->compareArrays($default_protocols, $this->protoData['kses']);
    }

    //  Merge a set into base protocols
    public function testProtocols02()
    {
        $kses = new Kses\Kses();
        $kses->AddProtocols($this->protoData['base1']);

        $dump = $kses->DumpProtocols();

        $final = array_merge(
            $this->protoData['kses'],
            $this->protoData['base1']);

        $this->compareArrays($dump, $final);
    }

    //  Make sure a protocol gets cleaned
    public function testProtocols03()
    {
        $kses = new Kses\Kses();
        $kses->AddProtocols($this->protoData['colon']);

        $dump = $kses->DumpProtocols();

        $this->assertTrue(in_array('proto4', $dump));
    }

    //  Remove a protocol
    public function testProtocols04()
    {
        $kses = new Kses\Kses();
        $kses->AddProtocols($this->protoData['greek']);
        $dump1 = $kses->DumpProtocols();

        $kses->RemoveProtocol('beta');
        $dump2 = $kses->DumpProtocols();

        $this->assertNotEquals(count($dump1), count($dump2));
        $this->assertTrue(in_array('beta', $dump1));
        $this->assertFalse(in_array('beta', $dump2));
    }

    //  Remove multiple protocol
    public function testProtocols05()
    {
        $kses = new Kses\Kses();
        $kses->AddProtocols($this->protoData['greek']);
        $dump1 = $kses->DumpProtocols();

        $kses->RemoveProtocols($this->protoData['greek']);
        $dump2 = $kses->DumpProtocols();

        $this->compareArrays(
            $dump1,
            array_merge($this->protoData['kses'], $this->protoData['greek']));

        $this->compareArrays(
            $dump2,
            array_merge($this->protoData['kses']));
    }

    //  Add multiple strings for protocol.
    public function testProtocols06()
    {
        $kses = new Kses\Kses();
        //  Let's try adding 20 at once.
        $kses->AddProtocols(
            'a', 'b', 'c', 'd', 'e',
            'f', 'g', 'h', 'i', 'j',
            'k', 'l', 'm', 'n', 'o',
            'p', 'q', 'r', 's', 't'
        );

        $dump1 = $kses->DumpProtocols();

        $dump2 = array_merge(
            $this->protoData['alpha'],
            $this->protoData['kses']);

        $this->compareArrays($dump1, $dump2);
    }

    //  Add multiple elements for protocol.
    public function testProtocols07()
    {
        $kses = new Kses\Kses();
        // Let's mix it up
        $kses->AddProtocols(
            'a', 'b', 'c', 'd', 'e',
            array('f', 'g', 'h', 'i', 'j'),
            'k', 'l', 'm', 'n', 'o',
            array('p', 'q', 'r', 's', 't')
        );

        $dump1 = $kses->DumpProtocols();

        $dump2 = array_merge(
            $this->protoData['alpha'],
            $this->protoData['kses']);

        $this->compareArrays($dump1, $dump2);
    }

    //  Add multiple arrays for protocol.
    public function testProtocols08()
    {
        $kses = new Kses\Kses();
        // Let's mix it up
        $kses->AddProtocols(
            array('a', 'b', 'c', 'd', 'e'),
            array(
                array('f', 'g', 'h', 'i', 'j'),
                array('k', 'l', 'm', 'n', 'o')
            ),
            array('p', 'q', 'r', 's', 't')
        );

        $dump1 = $kses->DumpProtocols();

        $dump2 = array_merge(
            $this->protoData['alpha'],
            $this->protoData['kses']);

        $this->compareArrays($dump1, $dump2);
    }

    //  Add mixed for protocol.
    public function testProtocols09()
    {
        $kses = new Kses\Kses();
        // Let's mix it up
        $kses->AddProtocols(
            'a', 'b', 'c', 'd', 'e',
            array(
                array('f', 'g', 'h', 'i', 'j'),
                array('k', 'l', 'm', 'n', 'o'),
                'p', 'q'
            ),
            array('r', 's', 't')
        );

        $dump1 = $kses->DumpProtocols();

        $dump2 = array_merge(
            $this->protoData['alpha'],
            $this->protoData['kses']);

        $this->compareArrays($dump1, $dump2);
    }

    //  Add mixed dirty for protocol.
    public function testProtocols10()
    {
        $kses = new Kses\Kses();
        // Let's mix it up
        $kses->AddProtocols(
            'a:', 'b', 'c:', 'd', 'e:',
            array(
                array('f', 'g', 'h:', 'i', 'j'),
                array('k', 'l', 'm', 'n:', 'o'),
                'p', 'q'
            ),
            array('r', 's:', 't')
        );

        $dump1 = $kses->DumpProtocols();

        $dump2 = array_merge(
            $this->protoData['alpha'],
            $this->protoData['kses']);

        $this->compareArrays($dump1, $dump2);
    }


    //  Add mixed for protocol.
    public function testProtocols11()
    {
        $kses = new Kses\Kses();
        // Let's mix it up
        $kses->AddProtocols(
            'a', 'b', 'c', 'd', 'e',
            array(
                array('f', 'g', 'h', 'i', 'j'),
                array('k', 'l', 'm', 'n', 'o'),
                'p', 'q'
            ),
            array('r', 's', 't')
        );

        $dump1 = $kses->DumpProtocols();

        $dump2 = array_merge(
            $this->protoData['alpha'],
            $this->protoData['kses']);

        for($letter = 'a'; $letter <='z'; $letter++)
        {
            $kses->RemoveProtocol($letter);
        }

        $dump3 = $kses->DumpProtocols();

        $this->compareArrays($dump1, $dump2);
        $this->compareArrays($dump3, $this->protoData['kses']);
    }

    //  Run through the original tests provided for tags.
    public function testTag01()
    {
        $kses = new Kses\Kses();
        $kses->AddProtocols($this->protoData);

        foreach ($this->tagList as $tag => $attributes)
        {
            $kses->AddHTML($tag, $attributes);
        }

        for($i = 1; $i <= 13; $i++)
        {
            $data = $this->tagsTest[$i];
            $text = $kses->Parse($data);
            $this->assertEquals($text, $this->tagsResult[$i]);
        }
    }

    //  Basic array comparison test
    private function compareArrays( $array1, $array2)
    {
        foreach($array1 as $val1)
        {
            if(!in_array($val1, $array2))
            {
                $this->fail('Inequal arrays');
            }
        }
        $this->assertTrue(true);
    }


}
