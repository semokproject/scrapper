<?php

namespace Semok\Scrapper\BingResult\Filter;

use Semok\Scrapper\Filter\BaseFilter as TheFilter;

class BaseFilter extends TheFilter
{
    public function runFilter($result)
    {
        return $result;
    }
}
