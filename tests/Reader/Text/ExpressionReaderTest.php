<?php declare(strict_types=1);

namespace Tale\Test\Reader\Text;

use PHPUnit\Framework\TestCase;
use Tale\Reader\StreamReader;
use Tale\Reader\Text\Expression\NumberExpression;
use Tale\Reader\Text\ExpressionReader;
use Tale\Reader\Text\ReadException;
use Tale\Reader\TextReader;
use function Tale\stream_memory;

/**
 * @coversDefaultClass \Tale\Reader\Text\ExpressionReader
 */
class ExpressionReaderTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::readContainedText
     */
    public function testReadContainedText(): void
    {
        $stream = new StreamReader(stream_memory(' STARTTEXTSOMEESCAPEENDTEXTACTUALENDTEXTDEMACIA'));
        $reader = new ExpressionReader($textReader = new TextReader($stream));
        self::assertNull($reader->readContainedText('STARTTEXT', 'ENDTEXT', 'ESCAPE'));
        $textReader->consume(1);
        self::assertSame('SOMEENDTEXTACTUAL', $reader->readContainedText('STARTTEXT', 'ENDTEXT', 'ESCAPE'));
        self::assertSame('DEMACIA', $textReader->readAlpha());
    }

    /**
     * @covers ::__construct
     * @covers ::readContainedText
     */
    public function testReadContainedTextThrowsExceptionOnMissingCloseText(): void
    {
        $this->expectException(ReadException::class);
        $stream = new StreamReader(stream_memory('STARTTEXTSOMEESCAPEENDTEXTACTUALENDTEXDEMACIA'));
        $reader = new ExpressionReader($textReader = new TextReader($stream));
        $reader->readContainedText('STARTTEXT', 'ENDTEXT', 'ESCAPE');
    }

    /**
     * @covers ::__construct
     * @covers ::readSingleQuotedString
     */
    public function testReadSingleQuotedString(): void
    {
        $stream = new StreamReader(stream_memory('\'test\\\'test\'abc'));
        $reader = new ExpressionReader($textReader = new TextReader($stream));
        self::assertSame('test\'test', $reader->readSingleQuotedString());
        self::assertSame('abc', $textReader->readAlpha());
    }

    /**
     * @covers ::__construct
     * @covers ::readDoubleQuotedString
     */
    public function testReadDoubleQuotedString(): void
    {
        $stream = new StreamReader(stream_memory('"test\\"test"abc'));
        $reader = new ExpressionReader($textReader = new TextReader($stream));
        self::assertSame('test"test', $reader->readDoubleQuotedString());
        self::assertSame('abc', $textReader->readAlpha());
    }

    /**
     * @covers ::__construct
     * @covers ::readBacktickedString
     */
    public function testReadBacktickedString(): void
    {
        $stream = new StreamReader(stream_memory('`test\\`test`abc'));
        $reader = new ExpressionReader($textReader = new TextReader($stream));
        self::assertSame('test`test', $reader->readBacktickedString());
        self::assertSame('abc', $textReader->readAlpha());
    }

    /**
     * @covers ::__construct
     * @covers ::readBracketContent
     */
    public function testReadBracketContent(): void
    {
        $stream = new StreamReader(stream_memory('(test test)abc'));
        $reader = new ExpressionReader($textReader = new TextReader($stream));
        self::assertSame('test test', $reader->readBracketContent());
        self::assertSame('abc', $textReader->readAlpha());
    }

    /**
     * @covers ::__construct
     * @covers ::readCurlyBracketContent
     */
    public function testReadCurlyBracketContent(): void
    {
        $stream = new StreamReader(stream_memory('{test test}abc'));
        $reader = new ExpressionReader($textReader = new TextReader($stream));
        self::assertSame('test test', $reader->readCurlyBracketContent());
        self::assertSame('abc', $textReader->readAlpha());
    }

    /**
     * @covers ::__construct
     * @covers ::readSquareBracketContent
     */
    public function testReadSquareBracketContent(): void
    {
        $stream = new StreamReader(stream_memory('[test test]abc'));
        $reader = new ExpressionReader($textReader = new TextReader($stream));
        self::assertSame('test test', $reader->readSquareBracketContent());
        self::assertSame('abc', $textReader->readAlpha());
    }

    /**
     * @covers ::__construct
     * @covers ::readIdentifier
     */
    public function testReadIdentifier(): void
    {
        $stream = new StreamReader(stream_memory('123someIdentifier243%&'));
        $reader = new ExpressionReader($textReader = new TextReader($stream));
        self::assertNull($reader->readIdentifier());
        $textReader->consume(3);
        self::assertSame('someIdentifier243', $reader->readIdentifier());
        self::assertSame('%&', $textReader->read(2));
    }

    /**
     * @covers ::__construct
     * @covers ::readNumber
     */
    public function testReadNumber(): void
    {
        $stream = new StreamReader(stream_memory('test1234.345test'));
        $reader = new ExpressionReader($textReader = new TextReader($stream));
        self::assertNull($reader->readNumber());
        $textReader->consume(4);
        self::assertEquals(new NumberExpression('1234', '345'), $reader->readNumber());
        self::assertSame('test', $textReader->readAlpha());

        $stream = new StreamReader(stream_memory('123_456'));
        $reader = new ExpressionReader(new TextReader($stream));
        self::assertEquals(new NumberExpression('123456', '0'), $reader->readNumber());

        $stream = new StreamReader(
            stream_memory('.000000000000000000000000000000000000000000000000000000000000000000000123456')
        );
        $reader = new ExpressionReader(new TextReader($stream));
        self::assertEquals(new NumberExpression(
            '0',
            '000000000000000000000000000000000000000000000000000000000000000000000123456'
        ), $reader->readNumber());

        $stream = new StreamReader(stream_memory('100_00.00_100_200'));
        $reader = new ExpressionReader(new TextReader($stream));
        self::assertEquals(new NumberExpression(
            '10000',
            '00100200'
        ), $reader->readNumber());
    }
}
