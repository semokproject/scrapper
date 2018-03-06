<?php

namespace Semok\Scrapper\Filter;

class BaseFilter implements Filter
{
    /**
     * Get the registered name of the component.
     *
     * @return mixed
     */
    public function runFilter($result)
    {
        return $result;
    }
}
