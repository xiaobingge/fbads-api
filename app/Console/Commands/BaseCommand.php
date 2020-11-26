<?php


namespace App\Console\Commands;

use Illuminate\Console\Command;

class BaseCommand extends Command
{
    /**
     * @var string $appId
     */
    protected $appId;

    public function __construct()
    {
        parent::__construct();

        $this->appId = \FacebookSdk::getAppId();

        if (empty($this->appId)) {
            throw new \Exception('No Found Conf AppId');
        }
    }


}