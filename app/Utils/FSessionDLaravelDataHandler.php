<?php


namespace App\Utils;

use Facebook\PersistentData\PersistentDataInterface;

class FSessionDLaravelDataHandler implements PersistentDataInterface
{
    /**
     * @var string Prefix to use for session variables.
     */
    protected $sessionPrefix = 'LFBRLH_';

    /**
     * @inheritdoc
     */
    public function get($key)
    {
        if (\Session::has($this->sessionPrefix . $key)) {
            return \Session::get($this->sessionPrefix . $key);
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function set($key, $value)
    {
        \Session::put($this->sessionPrefix . $key, $value);
    }
}