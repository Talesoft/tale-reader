<?php
declare(strict_types=1);

namespace Tale\Reader\Text\Expression;

final class NumberExpression
{
    private $integerPart;
    private $decimalPart;

    /**
     * NumberValue constructor.
     * @param string $integerPart
     * @param string $decimalPart
     */
    public function __construct(string $integerPart, string $decimalPart)
    {
        $this->integerPart = $integerPart;
        $this->decimalPart = $decimalPart;
    }

    /**
     * @return int
     */
    public function getIntegerPart(): string
    {
        return $this->integerPart;
    }

    /**
     * @return string
     */
    public function getDecimalPart(): string
    {
        return $this->decimalPart;
    }

    public function toInt(): int
    {
        return (int)$this->integerPart;
    }

    public function toFloat(): float
    {
        return (float)((string)$this);
    }

    public function toString(string $decimalDelimiter = '.'): string
    {
        if ($this->decimalPart === '0') {
            return $this->integerPart;
        }
        //We don't use number_format to avoid floating point representation at all points
        return "{$this->integerPart}{$decimalDelimiter}{$this->decimalPart}";
    }

    public function __toString()
    {
        return $this->toString();
    }
}
