<?php

namespace Guzzle\Psr7;

use Psr\HttpMessage\StreamInterface;

class LazyOpenStream implements StreamInterface
{
    use StreamDecoratorTrait;
    private $filename;
    private $mode;

    public function __construct($filename, $mode)
    {
        $this->filename = $filename;
        $this->mode = $mode;
    }

    protected function createStream()
    {
        return stream_for(try_fopen($this->filename, $this->mode));
    }
}
