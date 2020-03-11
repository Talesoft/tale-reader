<?php declare(strict_types=1);

namespace Tale\Reader\Text;

use Tale\Reader\Text\Expression\NumberExpression;
use Tale\Reader\TextReader;

final class ExpressionReader
{
    public const DEFAULT_ESCAPE_TEXT = '\\';

    private TextReader $textReader;

    public function __construct(TextReader $textReader)
    {
        $this->textReader = $textReader;
    }

    public function readContainedText(string $openText, string $closeText, string $escapeText = ''): ?string
    {
        if (!$this->textReader->peekText($openText)) {
            return null;
        }
        $location = $this->textReader->getCurrentLocation();
        $this->textReader->consume();

        $content = '';
        $closed = false;
        while (!$this->textReader->eof()) {
            if ($escapeText !== '' && $this->textReader->peekText($escapeText.$closeText)) {
                $this->textReader->consume();
                $content .= $closeText;
                continue;
            }

            if ($this->textReader->peekText($closeText)) {
                $this->textReader->consume();
                $closed = true;
                break;
            }
            $content .= $this->textReader->read();
        }

        if (!$closed) {
            throw new ReadException($location, "Failed to read unclosed text, {$closeText} not found");
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
        if (!$this->textReader->peekAlpha()) {
            return null;
        }
        $identifier = $this->textReader->readAlpha();
        if ($this->textReader->peekAlphaNumeric()) {
            $identifier .= $this->textReader->readAlphaNumeric();
        }
        return $identifier;
    }

    public function readNumber(?string $decimalDelimiter = '.', ?string $thousandsDelimiter = '_'): ?NumberExpression
    {
        if (!$this->textReader->peekDigit() && ($decimalDelimiter === null || !$this->textReader->peekText('.'))) {
            return null;
        }

        $isDigit = fn (string $byte) =>
            ctype_digit($byte) || ($thousandsDelimiter !== null && $byte === $thousandsDelimiter);

        $integer = $this->textReader->readWhile($isDigit) ?: '0';
        $decimal = '0';

        if ($decimalDelimiter !== null && $this->textReader->peekText($decimalDelimiter)) {
            $this->textReader->consume();
            $decimal = $this->textReader->readWhile($isDigit) ?: '0';
        }

        if ($thousandsDelimiter !== null) {
            $integer = str_replace($thousandsDelimiter, '', $integer);
            $decimal = str_replace($thousandsDelimiter, '', $decimal);
        }

        return new NumberExpression($integer, $decimal);
    }
}
