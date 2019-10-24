<?php declare(strict_types=1);

namespace Tale\Test\Reader;

use PHPUnit\Framework\TestCase;
use Tale\Reader\StreamReader;
use Tale\Reader\Text\Location;
use Tale\Reader\TextReader;
use function Tale\stream_memory;

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
        $stream = stream_memory();
        $reader = new TextReader(new StreamReader($stream));
        self::assertSame(0, $reader->getCurrentLine());
        self::assertSame(0, $reader->getCurrentOffset());
        self::assertSame(TextReader::LINE_DELIMITER_LF, $reader->getLineDelimiter());
    }

    /**
     * @covers ::__construct
     * @covers ::getCurrentLocation
     */
    public function testGetCurrentLocation(): void
    {
        $stream = stream_memory("some\nmultiline\nstring");
        $reader = new TextReader(new StreamReader($stream));
        $reader->read(4);
        self::assertEquals(new Location(0, 4), $reader->getCurrentLocation());
        $reader->read(1);
        self::assertEquals(new Location(1, 0), $reader->getCurrentLocation());
        $reader->read(1);
        self::assertEquals(new Location(1, 1), $reader->getCurrentLocation());

        $stream = stream_memory("some\nmultiline\nstring");
        $reader = new TextReader(new StreamReader($stream));
        $reader->read(3);
        self::assertEquals(new Location(0, 3), $reader->getCurrentLocation());
        $reader->read(14);
        self::assertEquals(new Location(2, 2), $reader->getCurrentLocation());
    }

    /**
     * @covers ::__construct
     * @covers ::peek
     * @covers ::consume
     * @covers ::read
     */
    public function testRead(): void
    {
        $reader = new TextReader(new StreamReader(stream_memory('test')));
        self::assertSame('t', $reader->read());
        self::assertSame('es', $reader->read(2));
        self::assertSame('t', $reader->read());
    }

    /**
     * @covers ::__construct
     * @covers ::peek
     * @covers ::consume
     * @covers ::readWhile
     */
    public function testReadWhile(): void
    {
        $reader = new TextReader(new StreamReader(stream_memory('testtesttesttesttesttesttestteststopabc')));
        self::assertSame('testtesttesttesttesttesttesttest', $reader->readWhile(function (string $bytes) {
            return $bytes !== 'stop';
        }, 4));
        self::assertSame('stop', $reader->read(4));
        $reader = new TextReader(new StreamReader(stream_memory('testtesttesttesttesttesttestteststopabc')));
        self::assertSame('testtesttesttesttesttesttesttest', $reader->readWhile(function (string $bytes) {
            return $bytes !== 'stop';
        }, 4, true));
        self::assertSame('abc', $reader->read(3));
    }

    /**
     * @covers ::__construct
     * @covers ::peek
     * @covers ::consume
     * @covers ::readUntil
     */
    public function testReadUntil(): void
    {
        $reader = new TextReader(new StreamReader(stream_memory('testtesttesttesttesttesttestteststopabc')));
        self::assertSame('testtesttesttesttesttesttesttest', $reader->readUntil(function (string $bytes) {
            return $bytes === 'stop';
        }, 4));
        self::assertSame('stop', $reader->read(4));

        $reader = new TextReader(new StreamReader(stream_memory('testtesttesttesttesttesttestteststopabc')));
        self::assertSame('testtesttesttesttesttesttesttest', $reader->readUntil(function (string $bytes) {
            return $bytes === 'stop';
        }, 4, true));
        self::assertSame('abc', $reader->read(3));
    }

    /**
     * @covers ::__construct
     * @covers ::peekText
     */
    public function testPeekText(): void
    {
        $stream = stream_memory('test');
        $reader = new TextReader(new StreamReader($stream));
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
        $stream = stream_memory("\n");
        $reader = new TextReader(new StreamReader($stream));
        self::assertTrue($reader->peekNewLine());
        $stream = stream_memory("t\n");
        $reader = new TextReader(new StreamReader($stream));
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
        $stream = stream_memory($streamContent);
        $reader = new TextReader(new StreamReader($stream));
        $lines = [];
        while (!$reader->eof()) {
            $lines[] = $reader->readLine();
        }
        self::assertSame($expectedLines, $lines);
    }

    public function provideNewLineStreams(): \Generator
    {
        $delimiters = [TextReader::LINE_DELIMITER_LF, TextReader::LINE_DELIMITER_CRLF];
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
        $stream = stream_memory('t');
        $reader = new TextReader(new StreamReader($stream));
        self::assertFalse($reader->peekSpace());

        $stream = stream_memory(' ');
        $reader = new TextReader(new StreamReader($stream));
        self::assertTrue($reader->peekSpace());

        $stream = stream_memory("\t");
        $reader = new TextReader(new StreamReader($stream));
        self::assertTrue($reader->peekSpace());

        $stream = stream_memory("\n");
        $reader = new TextReader(new StreamReader($stream));
        self::assertTrue($reader->peekSpace());

        $stream = stream_memory("\r");
        $reader = new TextReader(new StreamReader($stream));
        self::assertTrue($reader->peekSpace());

        $stream = stream_memory("\v");
        $reader = new TextReader(new StreamReader($stream));
        self::assertTrue($reader->peekSpace());
    }

    /**
     * @covers ::__construct
     * @covers ::peekAlpha
     */
    public function testPeekAlpha(): void
    {
        $stream = stream_memory('1');
        $reader = new TextReader(new StreamReader($stream));
        self::assertFalse($reader->peekAlpha());

        $stream = stream_memory('a');
        $reader = new TextReader(new StreamReader($stream));
        self::assertTrue($reader->peekAlpha());

        $stream = stream_memory('%');
        $reader = new TextReader(new StreamReader($stream));
        self::assertFalse($reader->peekAlpha());
    }

    /**
     * @covers ::__construct
     * @covers ::peekDigit
     */
    public function testPeekDigit(): void
    {
        $stream = stream_memory('a');
        $reader = new TextReader(new StreamReader($stream));
        self::assertFalse($reader->peekDigit());

        $stream = stream_memory('1');
        $reader = new TextReader(new StreamReader($stream));
        self::assertTrue($reader->peekDigit());

        $stream = stream_memory('%');
        $reader = new TextReader(new StreamReader($stream));
        self::assertFalse($reader->peekDigit());
    }

    /**
     * @covers ::__construct
     * @covers ::peekAlphaNumeric
     */
    public function testPeekAlphaNumeric(): void
    {
        $stream = stream_memory('a');
        $reader = new TextReader(new StreamReader($stream));
        self::assertTrue($reader->peekAlphaNumeric());

        $stream = stream_memory('1');
        $reader = new TextReader(new StreamReader($stream));
        self::assertTrue($reader->peekAlphaNumeric());

        $stream = stream_memory('%');
        $reader = new TextReader(new StreamReader($stream));
        self::assertFalse($reader->peekAlphaNumeric());
    }

    /**
     * @covers ::__construct
     * @covers ::readSpaces
     * @covers ::readNonSpaces
     */
    public function testReadSpacesAndNonSpaces(): void
    {
        $stream = stream_memory("\t  \t\vtest34563  \t\n");
        $reader = new TextReader(new StreamReader($stream));
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
        $stream = stream_memory("\t  \t\vtest34563  \t\n");
        $reader = new TextReader(new StreamReader($stream));
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
        $stream = stream_memory("\t  \t\vtest34563  \t\n");
        $reader = new TextReader(new StreamReader($stream));
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
        $stream = stream_memory("\t  \t\vtest34563  \t\n");
        $reader = new TextReader(new StreamReader($stream));
        self::assertSame("\t  \t\v", $reader->readNonAlphaNumeric());
        self::assertSame('test34563', $reader->readAlphaNumeric());
        self::assertSame("  \t\n", $reader->readNonAlphaNumeric());
    }

    /**
     * @covers ::__construct
     * @covers ::eof
     */
    public function testEof(): void
    {
        $stream = stream_memory("\t  \t\vtest34563  \t\n");
        $reader = new TextReader(new StreamReader($stream));
        self::assertFalse($reader->eof());
    }

    /**
     * @covers ::__construct
     * @covers ::consume
     */
    public function testConsume(): void
    {
        $rawData = "test34563  \t\n";
        $stream = stream_memory($rawData);
        $reader = new TextReader(new StreamReader($stream));
        self::assertSame($rawData, $reader->consume(20));
    }
}
