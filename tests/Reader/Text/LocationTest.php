<?php
declare(strict_types=1);

namespace Tale\Test\Reader\Text;

use PHPUnit\Framework\TestCase;
use Tale\Reader;
use Tale\Stream\MemoryStream;

/**
 * @coversDefaultClass \Tale\Reader\Text\Location
 */
class LocationTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::getLine
     */
    public function testGetLine(): void
    {
        $location = new Reader\Text\Location(10, 15);
        self::assertSame(10, $location->getLine());
    }

    /**
     * @covers ::__construct
     * @covers ::getOffset
     */
    public function testGetOffset(): void
    {
        $location = new Reader\Text\Location(10, 15);
        self::assertSame(15, $location->getOffset());
    }

    /**
     * @covers ::__construct
     * @covers ::__toString
     */
    public function testToString(): void
    {
        $location = new Reader\Text\Location(10, 15);
        self::assertSame('10:15', (string)$location);
    }
}
