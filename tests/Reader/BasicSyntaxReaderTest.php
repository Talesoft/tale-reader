<?php
declare(strict_types=1);

namespace Tale\Test\Reader;

use PHPUnit\Framework\TestCase;
use Tale\Reader;
use Tale\Reader\Text\Exception;
use Tale\Stream\MemoryStream;

/**
 * @coversDefaultClass \Tale\Reader\BasicSyntaxReader
 */
class BasicSyntaxReaderTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::readContainedText
     */
    public function testReadContainedText(): void
    {
        $stream = new MemoryStream(' STARTTEXTSOMEESCAPEENDTEXTACTUALENDTEXTDEMACIA');
        $reader = new Reader\BasicSyntaxReader($stream);
        self::assertNull($reader->readContainedText('STARTTEXT', 'ENDTEXT', 'ESCAPE'));
        $reader->consume(1);
        self::assertSame('SOMEENDTEXTACTUAL', $reader->readContainedText('STARTTEXT', 'ENDTEXT', 'ESCAPE'));
        self::assertSame('DEMACIA', $reader->readAlpha());
    }

    /**
     * @covers ::__construct
     * @covers ::readContainedText
     * @expectedException Exception
     */
    public function testReadContainedTextThrowsExceptionOnMissingCloseText(): void
    {
        $stream = new MemoryStream('STARTTEXTSOMEESCAPEENDTEXTACTUALENDTEXDEMACIA');
        $reader = new Reader\BasicSyntaxReader($stream);
        $text = $reader->readContainedText('STARTTEXT', 'ENDTEXT', 'ESCAPE');
    }

    /**
     * @covers ::__construct
     * @covers ::readSingleQuotedString
     */
    public function testReadSingleQuotedString(): void
    {
        $stream = new MemoryStream('\'test\\\'test\'abc');
        $reader = new Reader\BasicSyntaxReader($stream);
        self::assertSame('test\'test', $reader->readSingleQuotedString());
        self::assertSame('abc', $reader->readAlpha());
    }

    /**
     * @covers ::__construct
     * @covers ::readDoubleQuotedString
     */
    public function testReadDoubleQuotedString(): void
    {
        $stream = new MemoryStream('"test\\"test"abc');
        $reader = new Reader\BasicSyntaxReader($stream);
        self::assertSame('test"test', $reader->readDoubleQuotedString());
        self::assertSame('abc', $reader->readAlpha());
    }

    /**
     * @covers ::__construct
     * @covers ::readBacktickedString
     */
    public function testReadBacktickedString(): void
    {
        $stream = new MemoryStream('`test\\`test`abc');
        $reader = new Reader\BasicSyntaxReader($stream);
        self::assertSame('test`test', $reader->readBacktickedString());
        self::assertSame('abc', $reader->readAlpha());
    }

    /**
     * @covers ::__construct
     * @covers ::readBracketContent
     */
    public function testReadBracketContent(): void
    {
        $stream = new MemoryStream('(test test)abc');
        $reader = new Reader\BasicSyntaxReader($stream);
        self::assertSame('test test', $reader->readBracketContent());
        self::assertSame('abc', $reader->readAlpha());
    }

    /**
     * @covers ::__construct
     * @covers ::readCurlyBracketContent
     */
    public function testReadCurlyBracketContent(): void
    {
        $stream = new MemoryStream('{test test}abc');
        $reader = new Reader\BasicSyntaxReader($stream);
        self::assertSame('test test', $reader->readCurlyBracketContent());
        self::assertSame('abc', $reader->readAlpha());
    }

    /**
     * @covers ::__construct
     * @covers ::readSquareBracketContent
     */
    public function testReadSquareBracketContent(): void
    {
        $stream = new MemoryStream('[test test]abc');
        $reader = new Reader\BasicSyntaxReader($stream);
        self::assertSame('test test', $reader->readSquareBracketContent());
        self::assertSame('abc', $reader->readAlpha());
    }

    /**
     * @covers ::__construct
     * @covers ::readIdentifier
     */
    public function testReadIdentifier(): void
    {
        $stream = new MemoryStream('123someIdentifier243%&');
        $reader = new Reader\BasicSyntaxReader($stream);
        self::assertNull($reader->readIdentifier());
        $reader->consume(3);
        self::assertSame('someIdentifier243', $reader->readIdentifier());
        self::assertSame('%&', $reader->read(2));
    }

    /**
     * @covers ::__construct
     * @covers ::readNumber
     */
    public function testReadNumber(): void
    {
        $stream = new MemoryStream('test1234.345test');
        $reader = new Reader\BasicSyntaxReader($stream);
        self::assertNull($reader->readNumber());
        $reader->consume(4);
        self::assertEquals(new Reader\BasicSyntax\NumberValue('1234', '345'), $reader->readNumber());
        self::assertSame('test', $reader->readAlpha());

        $stream = new MemoryStream('123_456');
        $reader = new Reader\BasicSyntaxReader($stream);
        self::assertEquals(new Reader\BasicSyntax\NumberValue('123456', '0'), $reader->readNumber());

        $stream = new MemoryStream('.000000000000000000000000000000000000000000000000000000000000000000000123456');
        $reader = new Reader\BasicSyntaxReader($stream);
        self::assertEquals(new Reader\BasicSyntax\NumberValue(
            '0',
            '000000000000000000000000000000000000000000000000000000000000000000000123456'
        ), $reader->readNumber());

        $stream = new MemoryStream('100_00.00_100_200');
        $reader = new Reader\BasicSyntaxReader($stream);
        self::assertEquals(new Reader\BasicSyntax\NumberValue(
            '10000',
            '00100200'
        ), $reader->readNumber());
    }
}
