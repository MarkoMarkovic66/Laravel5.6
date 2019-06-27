<?php
namespace App\Console\Commands\AlueCommand\Traits;
use Carbon\Carbon;
use App\Models\BatchCommand;

/**
 * AlueCommandTrait
 * 二重起動の防止、および
 * 他のバッチ実行中の起動を防止する。
 */
trait AlueCommandRunTrait {

    /**
     * 起動許可の判定
     * （自身も含めて）いずれかのバッチ処理が起動中は
     * 実行は不可。
     *
     */
    public function isRunnable(){
        $reccnt = BatchCommand::count();
        if($reccnt > 0){
            return false;
        }
        return true;
    }

    /**
     * 実行開始処理
     */
    public function startRunning($commandName){
        $ret = BatchCommand::insert([
                                    'command_name'=>$commandName,
                                    'created_at'=> Carbon::now()
                                ]);
        return $ret;
    }

    /**
     * 実行完了処理
     */
    public function finishRunning($commandName){
        $reccnt = BatchCommand::where(['command_name'=>$commandName])
                            ->delete();
        if($reccnt < 1){
            return false;
        }
        return true;
    }

}
