<?php

namespace Semok\Scrapper\BingResult;

use Illuminate\Support\Facades\Facade;

class TheFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'semok.scrapper.bingresult';
    }
}
