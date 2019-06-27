<?php

namespace App\Console\Commands\AlueCommand\Traits;

use \Monolog\Logger;
use \Monolog\Formatter\LineFormatter;
use \Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;

/**
 * 共通ログ出力
 */
trait AlueCommandLogTrait {

    private function initAlueCommandLogTrait($logFilePath) {
        $this->commandLogger = new Logger($this->commandName);

        $logformat = "[%datetime%] %channel%.%level_name% %message%\n";
        $formatter = new LineFormatter($logformat);
        //コマンド用ログはDailyRotateとし、その保存数は 365（毎日のログを1年分）である。
        //$stream = new StreamHandler(storage_path().'/logs/'.$logFilePath, config('app.log_level'));
        $stream = new RotatingFileHandler(storage_path().'/logs/'.$logFilePath, config('app.log_max_files'), config('app.log_level'), true, 0666);
        $stream->setFormatter($formatter);
        $this->commandLogger->pushHandler($stream);
    }

    private function logwrite($level, $msg) {
        if($level == 'debug'){
            $this->commandLogger->addDebug($msg);

        }elseif($level == 'error'){
            $this->commandLogger->addError($msg);

        }elseif($level == 'warn'){
            $this->commandLogger->addWarning($msg);

        }elseif($level == 'info'){
            $this->commandLogger->addInfo($msg);

        }else{
            $this->commandLogger->addDebug($msg);
        }
    }

}


