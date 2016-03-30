<?php

namespace Tale\Reader;

use Tale\Reader\LineOffsetTrait;
use Tale\Reader\PregUtil;

class Reader
{
    use LineOffsetTrait;

    protected static $defaultEncoding = 'UTF-8';
    protected static $badCharacters = "\0\r\v";
    protected static $indentCharacters = "\t ";
    protected static $quoteCharacters = "\"'`";
    protected static $expressionBrackets = [
        '(' => ')',
        '[' => ']',
        '{' => '}'
    ];

    private $input;
    private $encoding;

    private $position;

    private $lastPeekResult;
    private $lastMatchResult;
    private $nextConsumeLength;

    public function __construct($input, $encoding = null)
    {

        $this->input = $input;
        $this->encoding = $encoding ?: static::$defaultEncoding;

        $this->position = 0;
        $this->line = 0;
        $this->offset = 0;

        $this->lastPeekResult = null;
        $this->lastMatchResult = null;
        $this->nextConsumeLength = null;
    }

    /**
     * @return string
     */
    public function getInput()
    {

        return $this->input;
    }

    /**
     * @return string
     */
    public function getEncoding()
    {

        return $this->encoding;
    }

    /**
     * @return string
     */
    public function getLastPeekResult()
    {

        return $this->lastPeekResult;
    }

    /**
     * @return array
     */
    public function getLastMatchResult()
    {

        return $this->lastMatchResult;
    }

    /**
     * @return int
     */
    public function getNextConsumeLength()
    {

        return $this->nextConsumeLength;
    }

    /**
     * @return int
     */
    public function getPosition()
    {
        return $this->position;
    }

    public function normalize()
    {

        $this->input = str_replace(str_split(static::$badCharacters), '', $this->input);

        return $this;
    }

    public function getLength()
    {

        return mb_strlen($this->input, $this->encoding);
    }

    public function hasLength()
    {

        return $this->getLength() > 0;
    }

    public function peek($length = null, $start = null)
    {

        if (!$this->hasLength())
            return null;

        $length = $length !== null ? $length : 1;
        $start = $start !== null ? $start : 0;

        if (!is_int($length) || $length < 1)
            throw new \InvalidArgumentException(
                "Failed to peek: Length should be a number above 1"
            );

        //Cap read length to the size of this document
        if ($length > ($maxLength = $this->getLength()))
            $length = $maxLength;

        $this->lastPeekResult = mb_substr($this->input, $start, $length, $this->encoding);
        $this->nextConsumeLength = $start + mb_strlen($this->lastPeekResult, $this->encoding);

        return $this->lastPeekResult;
    }

    public function match($pattern, $modifiers = null, $ignoredSuffixes = null)
    {

        $modifiers = $modifiers ?: '';
        $ignoredSuffixes = $ignoredSuffixes ?: "\n";

        $result = preg_match(
            "/^$pattern/$modifiers",
            $this->input,
            $this->lastMatchResult
        );

        if ($result === false)
            $this->throwException(
                "Failed to match pattern: ".PregUtil::getLastPregErrorText()
            );

        if ($result === 0)
            return false;

        $this->nextConsumeLength = mb_strlen(rtrim($this->lastMatchResult[0], $ignoredSuffixes));
        return true;
    }

    public function getMatch($key)
    {

        if (!$this->lastMatchResult)
            $this->throwException(
                "Failed to get match $key: No match result found. Use match first"
            );

        return isset($this->lastMatchResult[$key])
             ? $this->lastMatchResult[$key]
             : null;
    }

    public function getMatchData()
    {

        if (!$this->lastMatchResult)
            $this->throwException(
                "Failed to get match data: No match result found. Use match first"
            );

        $data = [];
        foreach ($this->lastMatchResult as $key => $value)
            if (is_string($key))
                $data[$key] = $value;

        return $data;
    }

    public function consume($length = null)
    {

        $length = $length ?: $this->nextConsumeLength;

        if ($length === null)
            $this->throwException(
                "Failed to consume: No length given. Peek or match first."
            );

        $consumedPart = mb_substr($this->input, 0, $length, $this->encoding);;
        $this->input = mb_substr($this->input, $length, mb_strlen($this->input) - $length, $this->encoding);
        $this->position += $length;
        $this->offset += $length;

        //Check for new-lines in consumed part to increase line and offset correctly
        $newLines = mb_substr_count($consumedPart, "\n");
        $this->line += $newLines;

        if ($newLines) {

            //if we only have one new-line character, the new offset is 0
            //Else the offset is the length of the last line read - 1
            if (mb_strlen($consumedPart, $this->encoding) === 1)
                $this->offset = 0;
            else {

                $parts = explode("\n", $consumedPart);
                $this->offset = mb_strlen($parts[count($parts) - 1], $this->encoding) - 1;
            }
        }

        $this->nextConsumeLength = null;
        $this->lastPeekResult = null;
        $this->lastMatchResult = null;

        return $consumedPart;
    }

    public function readWhile($callback, $peekLength = null)
    {

        if (!is_callable($callback))
            throw new \InvalidArgumentException(
                "Argument 1 passed to Reader->readWhile needs to be callback"
            );

        if (!$this->hasLength())
            return null;

        if ($peekLength === null)
            $peekLength = 1;

        $result = '';
        while ($this->hasLength() && call_user_func($callback, $this->peek($peekLength)))
            $result .= $this->consume();

        return $result;
    }

    public function readUntil($callback, $peekLength = null)
    {

        return $this->readWhile(function($char) use ($callback) {

            return !call_user_func($callback, $char);
        }, $peekLength);
    }

    public function peekChar($char)
    {

        return $this->peek() === $char;
    }

    public function peekChars($chars)
    {

        return in_array($this->peek(), is_array($chars) ? $chars : str_split($chars), true);
    }

    public function peekString($string)
    {

        return $this->peek(mb_strlen($string)) === $string;
    }

    public function peekNewLine()
    {

        return $this->peekChars("\n");
    }

    public function peekIndentation()
    {

        return $this->peekChars(static::$indentCharacters);
    }

    public function peekQuote()
    {

        return $this->peekChars(static::$quoteCharacters);
    }

    public function peekSpace()
    {

        return ctype_space($this->peek());
    }

    public function peekDigit()
    {

        return ctype_digit($this->peek());
    }

    public function peekAlpha()
    {

        return ctype_alpha($this->peek());
    }

    public function peekAlphaNumeric()
    {

        return ctype_alnum($this->peek());
    }

    public function peekAlphaIdentifier(array $allowedChars = null)
    {

        $allowedChars = $allowedChars ?: ['_'];

        return $this->peekAlpha() || $this->peekChars($allowedChars);
    }

    public function peekIdentifier(array $allowedChars = null)
    {

        return $this->peekAlphaIdentifier($allowedChars) || $this->peekDigit();
    }

    public function readIndentation()
    {

        if (!$this->peekIndentation())
            return null;

        return $this->readWhile([$this, 'peekIndentation']);
    }

    public function readUntilNewLine()
    {

        return $this->readUntil([$this, 'peekNewLine']);
    }

    public function readSpaces()
    {

        if (!$this->peekSpace())
            return null;

        return $this->readWhile('ctype_space');
    }

    public function readDigits()
    {

        if (!$this->peekDigit())
            return null;

        return $this->readWhile('ctype_digit');
    }

    public function readAlpha()
    {

        if (!$this->peekAlpha())
            return null;

        return $this->readWhile('ctype_alpha');
    }

    public function readAlphaNumeric()
    {

        if (!$this->peekAlphaNumeric())
            return null;

        return $this->readWhile('ctype_alnum');
    }

    public function readIdentifier($prefix = null, $allowedChars = null)
    {

        if ($prefix) {

            if ($this->peek(mb_strlen($prefix)) !== $prefix)
                return null;

            $this->consume();
        } else if (!$this->peekAlphaIdentifier($allowedChars))
            return null;

        return $this->readWhile(function($char) use ($allowedChars) {

            return $this->peekIdentifier($allowedChars);
        });
    }

    public function readString(array $escapeSequences = null, $raw = false)
    {

        if (!$this->peekQuote())
            return null;

        $escapeSequences = $escapeSequences ?: [];
        $quoteStyle = $this->consume();
        $escapeSequences[$quoteStyle] = $quoteStyle;

        $last = null;
        $char = null;
        $string = '';
        while ($this->hasLength()) {

            $last = $char;
            $char = $this->peek();
            $this->consume();

            //Handle escaping based on passed sequences
            if ($char === '\\') {

                //Peek the next char
                $next = $this->peek();
                if (isset($escapeSequences[$next])) {

                    $this->consume();

                    if ($raw)
                        $string .= '\\';

                    $string .= $escapeSequences[$next];
                    continue;
                }

            }

            //End the string (Escaped quotes have already been handled)
            if ($char === $quoteStyle) {

                if ($raw)
                    $string = $quoteStyle.$string.$quoteStyle;

                return $string;
            }

            $string .= $char;
        }

        $this->throwException(
            "Unclosed string ($quoteStyle) encountered"
        );

        return '';
    }

    public function readExpression(array $breaks = null, array $brackets = null)
    {

        if (!$this->hasLength())
            return null;

        $breaks = $breaks ?: [];
        $brackets = $brackets ?: static::$expressionBrackets;
        $expression = '';
        $char = null;
        $bracketStack = [];
        while ($this->hasLength()) {

            //Append a string if any was found
            //Notice there can be brackets in strings, we dont want to
            //count those
            $expression .= $this->readString(null, true);

            if (!$this->hasLength())
                break;

            //Check for breaks
            if (count($bracketStack) === 0) {

                foreach ($breaks as $break)
                    if ($this->peekString($break))
                        break 2;
            }

            //Count brackets
            $char = $this->peek();
            if (in_array($char, array_keys($brackets), true)) {

                $bracketStack[] = $char;
            } else if (in_array($char, array_values($brackets), true)) {

                if (count($bracketStack) < 1)
                    $this->throwException(
                        "Unexpected bracket $char encountered, no brackets open"
                    );

                $last = count($bracketStack) - 1;
                if ($char !== $brackets[$bracketStack[$last]])
                    $this->throwException(
                        "Unclosed bracket {$bracketStack[$last]} encountered, "
                        ."got $char instead"
                    );

                array_pop($bracketStack);
            }

            $expression .= $char;
            $this->consume();
        }

        if (count($bracketStack) > 0)
            $this->throwException(
                "Unclosed brackets ".implode(', ', $bracketStack)." encountered "
                ."at end of expression"
            );

        return trim($expression);
    }

    protected function throwException($message)
    {

        throw new ReaderException(sprintf(
            "Failed to read: %s \nNear: %s \nLine: %s \nOffset: %s \nPosition: %s",
            $message,
            $this->peek(20),
            $this->line,
            $this->offset,
            $this->position
        ));
    }
}
