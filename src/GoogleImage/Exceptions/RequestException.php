<?php

namespace Semok\Scrapper\GoogleImage\Exceptions;

use Semok\Support\Exceptions\RuntimeException;

class RequestException extends RuntimeException
{
    protected $filename = 'semok/scrapper/googleimage.log';
}
