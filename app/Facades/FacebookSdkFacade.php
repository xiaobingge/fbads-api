<?php


namespace App\Facades;


use Illuminate\Support\Facades\Facade as LaravelFacade;

class FacebookSdkFacade extends LaravelFacade
{
    protected static function getFacadeAccessor()
    {
        return 'FacebookSdk';
    }

}