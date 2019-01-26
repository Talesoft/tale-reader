<?php declare(strict_types=1);

namespace Tale\Test\Reader\Text;

use PHPUnit\Framework\TestCase;
use Tale\Reader\Text\Location;
use Tale\Reader\Text\ReadException;

/**
 * @coversDefaultClass \Tale\Reader\Text\ReadException
 */
class ReadExceptionTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::getLocation
     */
    public function testGetLocation(): void
    {
        $location = new Location(10, 15);
        $prev = new \RuntimeException();
        $ex = new ReadException($location, 'Test', 15, $prev);
        self::assertSame($location, $ex->getLocation());
        self::assertSame('Test (at 10:15)', $ex->getMessage());
        self::assertSame(15, $ex->getCode());
        self::assertSame($prev, $ex->getPrevious());
    }
}
