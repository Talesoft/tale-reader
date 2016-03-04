<?php

namespace Tale\Test\Di;

use Tale\Reader;
use Tale\ReaderException;

class ReaderTest extends \PHPUnit_Framework_TestCase
{

    public function testReadString()
    {


        $this->assertEquals('abc"def', (new Reader('"abc\"def" ghi'))->readString());
        $this->assertEquals('abc"def', (new Reader('\'abc"def\' ghi'))->readString());
        $this->assertEquals('abc`def', (new Reader('`abc\`def` ghi'))->readString());
        $this->assertEquals('"abc\"def"', (new Reader('"abc\"def" ghi'))->readString(null, true));
        $this->assertEquals('`abc\`def`', (new Reader('`abc\`def` ghi'))->readString(null, true));
        $this->assertEquals('abc a fucking bear def', (new Reader('"abc\Xdef" ghi'))->readString([
            'X' => ' a fucking bear '
        ]));


        $this->setExpectedException(ReaderException::class);
        (new Reader('"abc'))->readString();

        $this->setExpectedException(ReaderException::class);
        (new Reader('"\'abc\''))->readString();
    }

    public function testReadExpression()
    {

        $this->assertEquals('{ $abc (def) }', (new Reader('{ $abc (def) } ghi'))->readExpression([' ']));
        $this->assertEquals('$a ? ($b, $c) : $d', (new Reader('$a ? ($b, $c) : $d, $f, $g'))->readExpression([',']));
        $this->assertEquals('$a["1, 2", $f, $g]', (new Reader('$a["1, 2", $f, $g], $f, $g'))->readExpression([',']));

        $this->setExpectedException(ReaderException::class);
        (new Reader('([), '))->readExpression([',']);

        $this->setExpectedException(ReaderException::class);
        (new Reader('([)]'))->readExpression([',']);

        $this->setExpectedException(ReaderException::class);
        (new Reader('($a{$b},'))->readExpression([',']);
    }
}