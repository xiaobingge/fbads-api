<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ClearLogCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clear:log {--type=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'clear log';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $type = $this->option('type');
        if ('log' == $type) {
            $this->clearLog();
        } elseif ('upload' == $type) {
            $this->clearUpLoad();
        } else {
            $this->clearUpLoad();
            $this->clearLog();
        }
    }

    protected function clearUpLoad()
    {
        for($i = 1; $i <= 10; $i ++) {
            $newImgPath = date('YmdH', strtotime('-' . $i . ' hour'));
            $result = \Storage::disk('html')->deleteDirectory($newImgPath);
            var_dump($result);
        }
    }

    public function clearLog()
    {
        for($i = 3; $i <= 7; $i ++) {
            $logpath = date('Y-m-d', strtotime('-' . $i . ' day'));
            $result = \Storage::disk('logs')->delete("laravel-".$logpath.".log");
            var_dump($result);
            $result = \Storage::disk('logs')->delete("facebook/faceapi/faceapi-".$logpath.".log");
            var_dump($result);
        }
    }

}
