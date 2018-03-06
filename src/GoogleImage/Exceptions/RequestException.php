<?php

namespace Semok\Scrapper\GoogleImage\Exceptions;

use Semok\Support\Exceptions\RuntimeException;

class RequestException extends RuntimeException
{
    protected $filename = 'scrapper.error.log';

    public function __construct($message, $code = 0, Exception $previous = null)
    {
        $message = 'GoogleImageScrapper: ' . $message;
        parent::__construct($message, $code, $previous);
    }
}
