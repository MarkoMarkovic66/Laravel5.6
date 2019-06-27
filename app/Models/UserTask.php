<?php

namespace App\Models;

use DB;
use Illuminate\Database\Eloquent\Model;
use App\Models\CommonModel as CommonModel;

class UserTask extends CommonModel
{
    public $table = 'user_tasks';
    public $name = '会員課題結果データ';
    public $primary = 'id';
    public $columns = array(
        'id' => 'ID',
        'user_id' => '会員ID',
        'task_id' => '問題ID',
        'user_task_status' => 'ステータス',
        'question_set_date' => '出題日',
        'answer_deadline_date' => '回答期限',
        'answered_date' => '回答日',
        'user_message_log_id' => '回答レコードid',
        //'counselor_account_ids' => 'カウンセラー担当IDs',
        //'operator_account_ids' => 'オペレータ担当IDs',
        'original_answer' => '初期の回答',
        'user_feedback_id' => 'フィードバックID',
        'comment' => '備考',
        'created_at' => '作成日',
        'updated_at' => '最終更新日',
        'deleted_at' => '削除日',
    );
    
    protected $guarded = ['id', 'created_at'];

    /*
     * データ全件取得
     */
    public function getAll($offset = null, $limit = null, $orderParam = null, $orderAsc = 'desc')
    {
        $result = CommonModel::getAll($offset, $limit, $orderParam, $orderAsc);
        return $result;
    }

    /*
     * データ１件取得
     */
    public function getOne($id)
    {
        $result = CommonModel::getOne($id);
        return $result;
    }

    /*
     * データ一覧取得
     */
    public function getList($params, $offset = null, $limit = null, $orderParam = null, $orderAsc = 'desc', $operators = null)
    {
        $result = CommonModel::getList($params, $offset, $limit, $orderParam, $orderAsc, $operators);
        return $result;
    }

    /*
     * データ更新
     */
    public function updateData($params, $data)
    {
        $result = CommonModel::updateData($params, $data);
        return $result;
    }

    /*
     * データ論理削除
     */
    public function deleteData($id)
    {
        $result = CommonModel::deleteData($id);
        return $result;
    }

    /*
     * データ登録
     */
    public function setData($data)
    {
        $insert_data = array();
        foreach($data as $key => $param) {
            $insert_data[] = array(
                'user_id' => $param['user_id'],
                'task_id' => $param['task_id'],
                'user_task_status' => $param['user_task_status'],
                //'counselor_account_ids' => $param['counselor_account_ids'],
                //'operator_account_ids' => $param['operator_account_ids'],
                'original_answer' => $param['original_answer'],
                'user_feedback_id' => $param['user_feedback_id'],
                'comment' => $param['comment'],
                'created_at' => date('Y-m-d H:i:s')
            );
        }

        $result = DB::table($this->table)->insert($insert_data);

        //最後のauto increment IDを取得
        $last_id = DB::getPdo()->lastInsertId();

        return $last_id;
    }

    /*
     * リレーション設定
     */
    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

    public function task()
    {
        return $this->belongsTo('App\Models\Task');
    }

    public function userFeedback()
    {
        return $this->belongsTo('App\Models\UserFeedback');
    }


}
