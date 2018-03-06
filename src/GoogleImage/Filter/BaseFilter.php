<?php

namespace Semok\Scrapper\GoogleImage\Filter;

use Semok\Scrapper\Filter\BaseFilter as TheFilter;

class BaseFilter extends TheFilter
{
    public function runFilter($result)
    {
        return $result;
    }
}
