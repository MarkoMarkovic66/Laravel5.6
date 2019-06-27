<?php
namespace App\Http\Controllers\Admin\Master;

use App\Models\WordCard;
use App\Models\WordCardCategory;
use App\Models\ListeningTask;
use App\Models\Grammar;
use App\Models\Vocabulary;
use App\Models\SpeakingRally;
use App\Models\SrCategory;
use App\Models\Review;
use App\Models\ReviewPeriod;
use App\Models\OtherTask;
use App\Models\TaskSetting;
use App\Models\LpCategory;
use App\Models\GslVerbMatrix;
use App\Models\GslWordFreq;

use Illuminate\Http\Request;
use App\Exceptions\AppException;
use \App\Http\Controllers\ApiBaseController;
use Log;

/**
 * AjaxController
 */
class AjaxController extends ApiBaseController {

//    public function __construct() {
//        parent::__construct();
//    }

    //データ取得API
    public function getdata(Request $request)
    {

        $params = $request->all();
//        Log::debug($params);

        $data_id = $params['data_id'];
        $data_master = $params['data_master'];
        $MasterTable = $this->getMasterClass($data_master);
        
        $result = $MasterTable->getOne($data_id);        
//        Log::debug((array)$result);

        //JSON形式で返却する
        return response((array)$result);

    }

    /*
     * 該当マスタテーブルクラスを取得
     */
    private function getMasterClass($master_name) {

        switch ($master_name) {            
            case 'wordcard':
                $master = new WordCard();
                break;
            case 'wordcardcategory':
                $master = new WordCardCategory();
                break;
            case 'listeningtask':
                $master = new ListeningTask();
                break;            
            case 'grammar':
                $master = new Grammar();
                break;
            case 'vocabulary':
                $master = new Vocabulary();
                break;
            case 'speakingrally':
                $master = new SpeakingRally();
                break;
            case 'sr_category':
                $master = new SrCategory();
                break;
            case 'review':
                $master = new Review();
                break;
            case 'review_period':
                $master = new ReviewPeriod();
                break;
            case 'other_task':
                $master = new OtherTask();
                break;
            case 'task_setting':
                $master = new TaskSetting();
                break;
            case 'lp_category':
                $master = new LpCategory();
                break;
            case 'gsl_verb_matrix':
                $master = new GslVerbMatrix();
                break;
            case 'gsl_word_freq':
                $master = new GslWordFreq();
                break;
            default:
                $master = new Grammar();
                break;
        }
        return $master;
    }
}
