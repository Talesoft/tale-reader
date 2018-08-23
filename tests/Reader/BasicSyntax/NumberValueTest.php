<?php
declare(strict_types=1);

namespace Tale\Test\Reader\Text;

use PHPUnit\Framework\TestCase;
use Tale\Reader;
use Tale\Stream\MemoryStream;

/**
 * @coversDefaultClass \Tale\Reader\BasicSyntax\NumberValue
 */
class NumberValueTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::getIntegerPart
     */
    public function testGetIntegerPart(): void
    {
        $value = new Reader\BasicSyntax\NumberValue('15', '13');
        self::assertSame('15', $value->getIntegerPart());
    }

    /**
     * @covers ::__construct
     * @covers ::getDecimalPart
     */
    public function testGetDecimalPart(): void
    {
        $value = new Reader\BasicSyntax\NumberValue('15', '13');
        self::assertSame('13', $value->getDecimalPart());
    }

    /**
     * @covers ::__construct
     * @covers ::toInt
     */
    public function testToInt(): void
    {
        $value = new Reader\BasicSyntax\NumberValue('15', '13');
        self::assertSame(15, $value->toInt());
    }

    /**
     * @covers ::__construct
     * @covers ::toFloat
     */
    public function testToFloat(): void
    {
        $value = new Reader\BasicSyntax\NumberValue('15', '13');
        self::assertSame(15.13, $value->toFloat());
    }

    /**
     * @covers ::__construct
     * @covers ::toString
     * @covers ::__toString
     */
    public function testToString(): void
    {
        $value = new Reader\BasicSyntax\NumberValue('15', '13');
        self::assertSame('15.13', $value->toString());
        self::assertSame('15,13', $value->toString(','));

        $value = new Reader\BasicSyntax\NumberValue('15', '0');
        self::assertSame('15', $value->toString());
        self::assertSame('15', $value->toString(','));

        $value = new Reader\BasicSyntax\NumberValue('15', '13');
        self::assertSame('15.13', (string)$value);
    }
}
