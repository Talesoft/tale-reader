<?php
declare(strict_types=1);

namespace Tale\Test\Reader;

use PHPUnit\Framework\TestCase;
use Tale\Reader;
use Tale\Stream\MemoryStream;

/**
 * @coversDefaultClass \Tale\Reader\TextReader
 */
class TextReaderTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::getCurrentLine
     * @covers ::getCurrentOffset
     * @covers ::getLineDelimiter
     */
    public function testConstruct(): void
    {
        $stream = new MemoryStream();
        $reader = new Reader\TextReader($stream);
        self::assertSame(0, $reader->getCurrentLine());
        self::assertSame(0, $reader->getCurrentOffset());
        self::assertSame(Reader\TextReader::LINE_DELIMITER_LF, $reader->getLineDelimiter());
    }

    /**
     * @covers ::__construct
     * @covers ::getCurrentLocation
     * @covers ::onConsume
     */
    public function testGetCurrentLocation(): void
    {
        $stream = new MemoryStream("some\nmultiline\nstring");
        $reader = new Reader\TextReader($stream);
        $reader->read(4);
        self::assertEquals(new Reader\Text\Location(0, 4), $reader->getCurrentLocation());
        $reader->read(1);
        self::assertEquals(new Reader\Text\Location(1, 0), $reader->getCurrentLocation());
        $reader->read(1);
        self::assertEquals(new Reader\Text\Location(1, 1), $reader->getCurrentLocation());

        $stream = new MemoryStream("some\nmultiline\nstring");
        $reader = new Reader\TextReader($stream);
        $reader->read(3);
        self::assertEquals(new Reader\Text\Location(0, 3), $reader->getCurrentLocation());
        $reader->read(14);
        self::assertEquals(new Reader\Text\Location(2, 2), $reader->getCurrentLocation());
    }

    /**
     * @covers ::__construct
     * @covers ::peekText
     */
    public function testPeekText(): void
    {
        $stream = new MemoryStream('test');
        $reader = new Reader\TextReader($stream);
        self::assertTrue($reader->peekText('tes'));
        self::assertFalse($reader->peekText('tas'));
        self::assertTrue($reader->peekText('tes'));
    }

    /**
     * @covers ::__construct
     * @covers ::peekNewLine
     */
    public function testPeekNewLine(): void
    {
        $stream = new MemoryStream("\n");
        $reader = new Reader\TextReader($stream);
        self::assertTrue($reader->peekNewLine());
        $stream = new MemoryStream("t\n");
        $reader = new Reader\TextReader($stream);
        self::assertFalse($reader->peekNewLine());
    }

    /**
     * @covers ::__construct
     * @covers ::readLine
     * @dataProvider provideNewLineStreams
     * @param string $streamContent
     * @param array $expectedLines
     */
    public function testReadLine(string $streamContent, array $expectedLines): void
    {
        $stream = new MemoryStream($streamContent);
        $reader = new Reader\TextReader($stream);
        $lines = [];
        while (!$reader->eof()) {
            $lines[] = $reader->readLine();
        }
        self::assertSame($expectedLines, $lines);
    }

    public function provideNewLineStreams(): \Generator
    {
        $delimiters = [Reader\TextReader::LINE_DELIMITER_LF, Reader\TextReader::LINE_DELIMITER_CRLF];
        foreach ($delimiters as $delim) {
            yield from [
                ['', []],
                [$delim, ['']],
                ["{$delim}{$delim}", ['', '']],
                ["line 1{$delim}line 2{$delim}{$delim}line 3{$delim}", [
                    'line 1',
                    'line 2',
                    '',
                    'line 3'
                ]],
                ["line 1{$delim}line 2{$delim}{$delim}line 3", [
                    'line 1',
                    'line 2',
                    '',
                    'line 3'
                ]],
                ["{$delim}line 1{$delim}line 2{$delim}{$delim}line 3", [
                    '',
                    'line 1',
                    'line 2',
                    '',
                    'line 3'
                ]]
            ];
        }
    }

    /**
     * @covers ::__construct
     * @covers ::peekSpace
     */
    public function testPeekSpace(): void
    {
        $stream = new MemoryStream('t');
        $reader = new Reader\TextReader($stream);
        self::assertFalse($reader->peekSpace());

        $stream = new MemoryStream(' ');
        $reader = new Reader\TextReader($stream);
        self::assertTrue($reader->peekSpace());

        $stream = new MemoryStream("\t");
        $reader = new Reader\TextReader($stream);
        self::assertTrue($reader->peekSpace());

        $stream = new MemoryStream("\n");
        $reader = new Reader\TextReader($stream);
        self::assertTrue($reader->peekSpace());

        $stream = new MemoryStream("\r");
        $reader = new Reader\TextReader($stream);
        self::assertTrue($reader->peekSpace());

        $stream = new MemoryStream("\v");
        $reader = new Reader\TextReader($stream);
        self::assertTrue($reader->peekSpace());
    }

    /**
     * @covers ::__construct
     * @covers ::peekAlpha
     */
    public function testPeekAlpha(): void
    {
        $stream = new MemoryStream('1');
        $reader = new Reader\TextReader($stream);
        self::assertFalse($reader->peekAlpha());

        $stream = new MemoryStream('a');
        $reader = new Reader\TextReader($stream);
        self::assertTrue($reader->peekAlpha());

        $stream = new MemoryStream('%');
        $reader = new Reader\TextReader($stream);
        self::assertFalse($reader->peekAlpha());
    }

    /**
     * @covers ::__construct
     * @covers ::peekDigit
     */
    public function testPeekDigit(): void
    {
        $stream = new MemoryStream('a');
        $reader = new Reader\TextReader($stream);
        self::assertFalse($reader->peekDigit());

        $stream = new MemoryStream('1');
        $reader = new Reader\TextReader($stream);
        self::assertTrue($reader->peekDigit());

        $stream = new MemoryStream('%');
        $reader = new Reader\TextReader($stream);
        self::assertFalse($reader->peekDigit());
    }

    /**
     * @covers ::__construct
     * @covers ::peekAlphaNumeric
     */
    public function testPeekAlphaNumeric(): void
    {
        $stream = new MemoryStream('a');
        $reader = new Reader\TextReader($stream);
        self::assertTrue($reader->peekAlphaNumeric());

        $stream = new MemoryStream('1');
        $reader = new Reader\TextReader($stream);
        self::assertTrue($reader->peekAlphaNumeric());

        $stream = new MemoryStream('%');
        $reader = new Reader\TextReader($stream);
        self::assertFalse($reader->peekAlphaNumeric());
    }

    /**
     * @covers ::__construct
     * @covers ::readSpaces
     * @covers ::readNonSpaces
     */
    public function testReadSpacesAndNonSpaces(): void
    {
        $stream = new MemoryStream("\t  \t\vtest34563  \t\n");
        $reader = new Reader\TextReader($stream);
        self::assertSame("\t  \t\v", $reader->readSpaces());
        self::assertSame('test34563', $reader->readNonSpaces());
        self::assertSame("  \t\n", $reader->readSpaces());
    }

    /**
     * @covers ::__construct
     * @covers ::readAlpha
     * @covers ::readNonAlpha
     */
    public function testReadAlphaAndNonAlpha(): void
    {
        $stream = new MemoryStream("\t  \t\vtest34563  \t\n");
        $reader = new Reader\TextReader($stream);
        self::assertSame("\t  \t\v", $reader->readNonAlpha());
        self::assertSame('test', $reader->readAlpha());
        self::assertSame("34563  \t\n", $reader->readNonAlpha());
    }

    /**
     * @covers ::__construct
     * @covers ::readDigits
     * @covers ::readNonDigits
     */
    public function testReadDigitAndNonDigit(): void
    {
        $stream = new MemoryStream("\t  \t\vtest34563  \t\n");
        $reader = new Reader\TextReader($stream);
        self::assertSame("\t  \t\vtest", $reader->readNonDigits());
        self::assertSame('34563', $reader->readDigits());
        self::assertSame("  \t\n", $reader->readNonDigits());
    }

    /**
     * @covers ::__construct
     * @covers ::readAlphaNumeric
     * @covers ::readNonAlphaNumeric
     */
    public function testReadAlphaNumericAndNonAlphaNumeric(): void
    {
        $stream = new MemoryStream("\t  \t\vtest34563  \t\n");
        $reader = new Reader\TextReader($stream);
        self::assertSame("\t  \t\v", $reader->readNonAlphaNumeric());
        self::assertSame('test34563', $reader->readAlphaNumeric());
        self::assertSame("  \t\n", $reader->readNonAlphaNumeric());
    }
}
