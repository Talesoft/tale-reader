
[![Packagist](https://img.shields.io/packagist/v/talesoft/tale-reader.svg?style=for-the-badge)](https://packagist.org/packages/talesoft/tale-reader)
[![License](https://img.shields.io/github/license/Talesoft/tale-reader.svg?style=for-the-badge)](https://github.com/Talesoft/tale-reader/blob/master/LICENSE.md)
[![CI](https://img.shields.io/travis/Talesoft/tale-reader.svg?style=for-the-badge)](https://travis-ci.org/Talesoft/tale-reader)
[![Coverage](https://img.shields.io/codeclimate/coverage/Talesoft/tale-reader.svg?style=for-the-badge)](https://codeclimate.com/github/Talesoft/tale-reader)

Tale Reader
===========

What is Tale Reader?
--------------------

Tale Reader is a reading utility for PSR-7 based streams, e.g. the streams of [tale-stream](https://github.com/Talesoft/tale-stream)

It can scan text for specific occurences and parse it sequentially in a memory-efficient way.

Installation
------------

```bash
composer require talesoft/tale-reader
```

Usage
-----

### Reader
```php
use Tale\Iterator\Reader;
use Tale\Stream\MemoryStream;

$text = '"some text"';
$stream = new MemoryStream($text);
$reader = new Reader($stream);

if ($reader->peek() !== '"') {
    throw new \RuntimeException('This is not a string value!');
}
$reader->consume();
$string = $reader->readUntil(function (string $char) {
    return $char === '"';
});

if ($reader->peek() !== '"') {
    throw new \RuntimeException('String is not closed!');
}

echo $string; //some text
```

### TextReader
```php
use Tale\Iterator\TextReader;
use Tale\Stream\MemoryStream;

$text = <<<TEXT
Line 1
Line 2
Line 3
Line 4
TEXT;
$stream = new MemoryStream($text);
$reader = new Reader($stream);

while (!$reader->eof()) {
    $line = $reader->readLine();
    var_dump($line); //Line 1, Line 2, Line 3, etc.
}
```

TODO: More docs.