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

//use App\Facades\TaskServiceFacade;
use TaskService;

use Illuminate\Http\Request;
use App\Http\Controllers\BaseViewController;
use App\Enums\MenuNameType;
use Log;
use DB;

/**
 * MasterController
 */
class MasterController extends BaseViewController {

    public function index(Request $request, $master_name = 'grammar', $offset = 0)
	{
        //ログインユーザ情報
        $login_user = $this->getLoginUser();

        //管理者、または運用担当のみがアクセス可能
        if ($login_user['account_kind_id'] != 1 && $login_user['account_kind_id'] != 2) {
            return redirect('/');
        }

	    //リクエストデータを取得
        $params = $request->all();

	    //指定マスタを切り替え
	    $MasterTable = $this->getMasterClass($master_name);

        //新規登録・更新・削除・出題ロジック設定
        if(isset($params['_token'])) {

            if(isset($params['btn-create'])) {

                foreach ($MasterTable->columns as $key => $val) {
                    if($key != 'id' && $key != 'created_at' && $key != 'updated_at' && $key != 'deleted_at') {
                        $insert_data[$key] = $params['create-'.$key];
                    }
                }
                $MasterTable->setData([$insert_data]);

            }

            if(isset($params['btn-update'])) {

                //Log::debug($params);
                $sel_update_id = $params['sel_update_id'];

                $where = array(
                    'id' => $sel_update_id
                );
                foreach ($MasterTable->columns as $key => $val) {
                    if($key != 'updated_at' && $key != 'deleted_at') {
                        $update_data[$key] = $params['edit-'.$key];
                    }
                }
                $MasterTable->updateData($where, $update_data);

            }

            if(isset($params['btn-delete'])) {

                //該当データを論理削除
                $sel_delete_id = $params['sel_delete_id'];
                $MasterTable->deleteData($sel_delete_id);

            }

            //出題ロジック設定限定
            if(isset($params['btn-tasksetting'])) {

                DB::beginTransaction();
                try {
                    foreach ($params as $key => $task_type) {
                        if ($key != '_token' && $key != 'btn-tasksetting') {
                            $where = array(
                                'id' => $key
                            );
                            $update_data = array(
                                'task_type' => $task_type
                            );
                            $MasterTable->updateData($where, $update_data);
                        }
                    }
                    DB::commit();
                } catch(\Exception $e) {
                    DB::rollback();
                }
            }
        }

        $search_params = (isset($params)) ? $params : null;
        $search_word = (isset($params['search_word'])) ? $params['search_word'] : null;

        $search_date_since = (isset($params['createDateSince'])) ? $params['createDateSince'] : null;
        $search_date_until = (isset($params['createDateUntil'])) ? $params['createDateUntil'] : null;

        if(isset($params['btn-search'])) {
            //検索ボタン、またはEnterを押したときはoffsetをリセット
            $offset = 0;
        }
        $limit = 50;
        $skip = $offset * $limit;        
        $dataList = $MasterTable->search($search_params, $search_word, $search_date_since, $search_date_until, $skip, $limit);
                       
	    //全件データも取得（ページング用）
        $dataAll = $MasterTable->search($search_params, $search_word, $search_date_since, $search_date_until, null, null);

        //検索パラメータ文字列を生成（ページング用）
        $search_str = "?";
        foreach($MasterTable->columns as $key => $val) {
            if($key != 'id' && $key != 'deleted_at' && $key != 'created_at' && $key != 'updated_at' && $key != 'module') {
                if(isset($search_params[$key])) {
                    $search_str .= $key . "=" . $search_params[$key] . "&";
                }
            }
        }
        if(isset($search_word)) {
            $search_str .= "search_word=" . $search_word . "&";
        }
        $search_str = substr($search_str, 0, -1);

        $this->view_params['login_user'] = $login_user;
        $this->view_params['search_str'] = $search_str; //ページング用パラメータ
        $this->view_params['search_params'] = $search_params; //検索ボタン用パラメータ
        $this->view_params['search_word'] = $search_word; //フリーワード検索
        $this->view_params['createDateSince'] = $search_date_since; //フリーワード検索
        $this->view_params['createDateUntil'] = $search_date_until; //フリーワード検索
        $this->view_params['dataAll'] = $dataAll;
	    $this->view_params['dataList'] = $dataList;
	    $this->view_params['columns'] = $MasterTable->columns;
        $this->view_params['name'] = $MasterTable->name;
	    $this->view_params['master_name'] = $master_name;
        $this->view_params['offset'] = $offset;
        $this->view_params['limit'] = $limit;
        $this->view_params['next'] = ((int)(count($dataAll)/$limit) > $offset) ? $offset + 1 : (int)(count($dataAll)/$limit);
        $this->view_params['prev'] = ($offset > 0) ? $offset - 1 : 0;
        $this->view_params->put('menuName', MenuNameType::MENU_MASTER);

        // 各マスタ特有のデータ        
        if($master_name == 'wordcard') {

            $wc_categories = (new WordCardCategory())->getAll();
            $wc_categories_arr = array();
            foreach($wc_categories as $key => $val) {
                $wc_categories_arr[$val->id] = $val;
            }
            $this->view_params['wc_categories'] = $wc_categories_arr;            
        }        
        
        if($master_name == 'grammar' || $master_name == 'vocabulary') {

            //グレード
            $grade_arr = array(
                'A' => 'A',
                'B' => 'B',
                'C' => 'C',
                'D' => 'D',
                'E' => 'E',
            );
            $this->view_params['grade_arr'] = $grade_arr;

        }

        if($master_name == 'gsl_verb_matrix') {

            //文型フラグ
            $pattern_flg_arr = array(
                '1' => '有り',
            );
            $this->view_params['pattern_flg_arr'] = $pattern_flg_arr;

        }

        if($master_name == 'speakingrally') {

            $sr_categories = (new SrCategory())->getAll();
            $sr_categories_arr = array();
            foreach($sr_categories as $key => $val) {
                $sr_categories_arr[$val->id] = $val;
            }
            $this->view_params['sr_categories'] = $sr_categories_arr;

        }

        if($master_name == 'other_task') {

            //宿題種別の配列を生成
            $is_all = false;
            $task_type_arr = TaskService::getTaskTypes($is_all);
            $this->view_params['task_type_arr'] = $task_type_arr;

            //次の宿題タイプIDを計算
            $OtherTask = new OtherTask();
            $next_task_type_id = $OtherTask->totalCount() + 5;
            $this->view_params['next_task_type_id'] = $next_task_type_id;

        }

//        Log::debug($params);
        if($master_name == 'task_setting') {

            //出題ロジック設定データを整形
            $task_setting_data = array();
            foreach($dataAll as $key => $val) {

                //アセスメント項目、グレードを配列に格納
                $task_setting_data[$val->assessment_item][$val->grade]['assessment_item'] = $val->assessment_item;
                $task_setting_data[$val->assessment_item][$val->grade]['grade'] = $val->grade;
                $task_setting_data[$val->assessment_item][$val->grade]['day_number'][$val->day_number]['id'] = $val->id;
                $task_setting_data[$val->assessment_item][$val->grade]['day_number'][$val->day_number]['task_type'] = $val->task_type;
                //$task_setting_data[$val->assessment_item][$val->grade]['day_number'][$val->day_number]['task_type_name'] = $this->task_type_arr[$val->task_type];
                //$task_setting_data[$val->assessment_item][$val->grade]['day_number'][$val->day_number]['other_task_id'] = $val->other_task_id;

            }

            //宿題種別の配列を生成
            $task_type_arr = TaskService::getTaskTypes();
            $this->view_params['task_type_arr'] = $task_type_arr;
            $this->view_params['task_setting_data'] = $task_setting_data;

            return view('Admin.Master.tasksetting', $this->view_params)->with('view_params', $this->view_params);
        } else {
            return view('Admin.Master.index', $this->view_params)->with('view_params', $this->view_params);
        }
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
