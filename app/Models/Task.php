<?php

namespace App\Models;

use DB;
use Illuminate\Database\Eloquent\Model;
use App\Models\CommonModel as CommonModel;

class Task extends CommonModel
{
    public $table = 'tasks';
    public $name = '出題課題データ';
    public $primary = 'id';
    public $guarded = ['id'];

    public $columns = array(
        'id' => 'ID',
        'user_id' => '会員ID',
        'task_type' => '課題種別',
        'cand_task_id' => '候補課題ID',
        'task_order' => '課題順番',
        'is_selected' => '選択フラグ',
        'comment' => '備考',
        'created_at' => '作成日',
        'updated_at' => '最終更新日',
        'deleted_at' => '削除日',
    );

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
                'task_type' => $param['task_type'],
                'cand_task_id' => $param['cand_task_id'],
                'task_order' => $param['task_order'],
                'is_selected' => $param['is_selected'],
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
    public function userTasks()
    {
        return $this->hasMany('App\Models\UserTask');
    }

}
