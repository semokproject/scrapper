<?php

namespace Semok\Scrapper\BingResult\Exceptions;

use Semok\Support\Exceptions\RuntimeException;

class RequestException extends RuntimeException
{
    protected $filename = 'semok/scrapper/bingresult.log';
}
