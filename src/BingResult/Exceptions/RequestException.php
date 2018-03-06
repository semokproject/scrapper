<?php

namespace Semok\Scrapper\BingResult\Exceptions;

use Semok\Support\Exceptions\RuntimeException;

class RequestException extends RuntimeException
{
    public $filename = 'scrapper.error.log';

    public function __construct($message, $code = 0, Exception $previous = null)
    {
        $message = 'BingResultScrapper: ' . $message;
        parent::__construct($message, $code, $previous);
    }
}
