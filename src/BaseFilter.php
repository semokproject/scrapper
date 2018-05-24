<?php

namespace Semok\Scrapper;

use Semok\Scrapper\Contracts\Filter;

class BaseFilter implements Filter
{
    protected $item;

    public function __construct($item)
    {
        $this->item = $item;
    }

    public function handle()
    {
        return $this->item;
    }
}
