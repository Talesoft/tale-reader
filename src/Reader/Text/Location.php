<?php
declare(strict_types=1);

namespace Tale\Reader\Text;

final class Location
{
    /**
     * @var int
     */
    private $line;
    /**
     * @var int
     */
    private $offset;

    /**
     * TextLocation constructor.
     * @param int $line
     * @param int $offset
     */
    public function __construct(int $line, int $offset)
    {
        $this->line = $line;
        $this->offset = $offset;
    }

    /**
     * @return int
     */
    public function getLine(): int
    {
        return $this->line;
    }

    /**
     * @return int
     */
    public function getOffset(): int
    {
        return $this->offset;
    }

    public function __toString()
    {
        return "{$this->line}:{$this->offset}";
    }
}
