<?php

namespace App\Models;

use DB;
use Illuminate\Database\Eloquent\Model;
use App\Models\CommonModel as CommonModel;

class UserFeedback extends CommonModel
{
    public $table = 'user_feedbacks';
    public $name = '会員へのフィードバックデータ';
    public $primary = 'id';
    public $columns = array(
        'id' => 'ID',
        'user_id' => '会員ID',
        'user_task_id' => 'user_tasksのPK',
        'feedback_account_id' => 'フィードバックアカウントID',
        'feedback_comment' => 'フィードバック内容',
        'feedback_status' => 'フィードバックステータス',
        'feedback_date' => 'フィードバック投稿日時',
        'approved_account_id' => '投稿承認アカウントID',
        'approved_date' => '投稿承認日時',
        'user_comment' => '会員コメント',
        'created_at' => '作成日',
        'updated_at' => '最終更新日',
        'deleted_at' => '削除日',
    );

    protected $guarded = ['id'];
    protected $fillable = [
        'user_id',
        'user_task_id',
        'feedback_account_id',
        'feedback_comment',
        'feedback_status',
        'feedback_date',
        'approved_account_id',
        'approved_date',
        'user_comment',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

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
                'feedback_account_id' => $param['feedback_account_id'],
                'feedback_comment' => $param['feedback_comment'],
                'feedback_status' => $param['feedback_status'],
                'feedback_date' => $param['feedback_date'],
                'approved_account_id' => $param['approved_account_id'],
                'approved_date' => $param['approved_date'],
                'user_comment' => $param['user_comment'],
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
    public function userTasks()
    {
        return $this->hasMany('App\Models\UserTask');
    }

    public function feedbackAccount()
    {
        return $this->belongsTo('App\Models\Account', 'feedback_account_id', 'id');
    }
    public function approvedAccount()
    {
        return $this->belongsTo('App\Models\Account', 'approved_account_id', 'id');
    }

}
