<?php declare(strict_types=1);

namespace Tale;

use Psr\Http\Message\StreamInterface;
use Tale\Reader\StreamReader;
use Tale\Reader\StringReader;
use Tale\Reader\Text\ExpressionReader;
use Tale\Reader\TextReader;

function reader_stream(StreamInterface $stream, int $bufferSize = 1024): ReaderInterface
{
    return new StreamReader($stream, $bufferSize);
}

function reader_string(string $string): ReaderInterface
{
    return new StringReader($string);
}

function reader_text(ReaderInterface $reader): TextReader
{
    return new TextReader($reader);
}

function reader_text_expression(TextReader $textReader): ExpressionReader
{
    return new ExpressionReader($textReader);
}
