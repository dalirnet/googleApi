<?php

namespace Guzzle\Http\Exception;

use Psr\HttpMessage\StreamInterface;

class SeekException extends \RuntimeException implements GuzzleException
{
    private $stream;

    public function __construct(StreamInterface $stream, $pos = 0, $msg = '')
    {
        $this->stream = $stream;
        $msg = $msg ?: 'Could not seek the stream to position ' . $pos;
        parent::__construct($msg);
    }

    public function getStream()
    {
        return $this->stream;
    }
}
