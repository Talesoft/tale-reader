<?php
declare(strict_types=1);

namespace Tale\Reader;

use Tale\Reader\BasicSyntax\NumberValue;
use Tale\Reader\Text\Exception;

class BasicSyntaxReader extends TextReader
{
    public const DEFAULT_ESCAPE_TEXT = '\\';

    public function readContainedText(string $openText, string $closeText, string $escapeText = ''): ?string
    {
        if (!$this->peekText($openText)) {
            return null;
        }
        $location = $this->getCurrentLocation();
        $this->consume();

        $content = '';
        $closed = false;
        while (!$this->eof()) {
            if ($escapeText !== '' && $this->peekText($escapeText.$closeText)) {
                $this->consume();
                $content .= $closeText;
                continue;
            }

            if ($this->peekText($closeText)) {
                $this->consume();
                $closed = true;
                break;
            }
            $content .= $this->read();
        }

        if (!$closed) {
            throw new Exception($location, "Failed to read unclosed text, {$closeText} not found");
        }
        return $content;
    }

    public function readSingleQuotedString(string $escapeText = ''): ?string
    {
        return $this->readContainedText('\'', '\'', $escapeText ?: self::DEFAULT_ESCAPE_TEXT);
    }

    public function readDoubleQuotedString(string $escapeText = ''): ?string
    {
        return $this->readContainedText('"', '"', $escapeText ?: self::DEFAULT_ESCAPE_TEXT);
    }

    public function readBacktickedString(string $escapeText = ''): ?string
    {
        return $this->readContainedText('`', '`', $escapeText ?: self::DEFAULT_ESCAPE_TEXT);
    }

    public function readBracketContent(): ?string
    {
        return $this->readContainedText('(', ')');
    }

    public function readCurlyBracketContent(): ?string
    {
        return $this->readContainedText('{', '}');
    }

    public function readSquareBracketContent(): ?string
    {
        return $this->readContainedText('[', ']');
    }

    public function readIdentifier(): ?string
    {
        if (!$this->peekAlpha()) {
            return null;
        }
        $identifier = $this->readAlpha();
        if ($this->peekAlphaNumeric()) {
            $identifier .= $this->readAlphaNumeric();
        }
        return $identifier;
    }

    public function readNumber(?string $decimalDelimiter = '.', ?string $thousandsDelimiter = '_'): ?NumberValue
    {
        if (!$this->peekDigit() && ($decimalDelimiter === null || !$this->peekText('.'))) {
            return null;
        }

        $isDigit = function (string $byte) use ($thousandsDelimiter) {
            return ctype_digit($byte) || ($thousandsDelimiter !== null && $byte === $thousandsDelimiter);
        };

        $integer = $this->readWhile($isDigit) ?: '0';
        $decimal = '0';

        if ($decimalDelimiter !== null && $this->peekText($decimalDelimiter)) {
            $this->consume();
            $decimal = $this->readWhile($isDigit) ?: '0';
        }

        if ($thousandsDelimiter !== null) {
            $integer = str_replace($thousandsDelimiter, '', $integer);
            $decimal = str_replace($thousandsDelimiter, '', $decimal);
        }

        return new NumberValue($integer, $decimal);
    }
}
