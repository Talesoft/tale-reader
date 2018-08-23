<?php
declare(strict_types=1);

namespace Tale\Reader\Text;

use Throwable;

class Exception extends \RuntimeException
{
    /**
     * @var Location
     */
    private $location;

    public function __construct(Location $location, string $message = '', int $code = 0, Throwable $previous = null)
    {
        parent::__construct("$message (at {$location})", $code, $previous);
        $this->location = $location;
    }

    /**
     * @return Location
     */
    public function getLocation(): Location
    {
        return $this->location;
    }
}
