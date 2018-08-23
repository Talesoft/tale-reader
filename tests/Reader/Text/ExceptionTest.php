<?php
declare(strict_types=1);

namespace Tale\Test\Reader\Text;

use PHPUnit\Framework\TestCase;
use Tale\Reader;
use Tale\Stream\MemoryStream;

/**
 * @coversDefaultClass \Tale\Reader\Text\Exception
 */
class ExceptionTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::getLocation
     */
    public function testGetLocation(): void
    {
        $location = new Reader\Text\Location(10, 15);
        $prev = new \RuntimeException();
        $ex = new Reader\Text\Exception($location, 'Test', 15, $prev);
        self::assertSame($location, $ex->getLocation());
        self::assertSame('Test (at 10:15)', $ex->getMessage());
        self::assertSame(15, $ex->getCode());
        self::assertSame($prev, $ex->getPrevious());
    }
}
