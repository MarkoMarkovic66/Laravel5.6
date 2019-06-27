<?php

namespace App\Models;

use DB;
use Illuminate\Database\Eloquent\Model;
use App\Models\CommonModel as CommonModel;

class OtherTask extends CommonModel
{
    public $table = 'other_tasks';
    public $name = 'その他宿題マスタ';
    public $primary = 'id';
    public $columns = array(
        'id' => 'ID',
        'task_type_id' => '宿題タイプID',
        'other_task_name' => 'その他宿題名',
        'task_context' => '宿題内容',
        'url' => '参考URL',
        'priority' => '優先度',
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
     * データ一覧検索
     */
    public function search($search_params, $search_word, $search_date_since, $search_date_until, $offset = null, $limit = null, $orderParam = null, $orderAsc = 'desc')
    {

        $result = DB::table($this->table);
        $result = $result->where('is_deleted', 0);

        //各検索項目による検索
        if(isset($search_params['task_type_id'])) {
            $result = $result->where('task_type_id', '=', $search_params['task_type_id']);
        }
        if(isset($search_params['other_task_name'])) {
            $result = $result->where('other_task_name', 'like', '%'.$search_params['other_task_name'].'%');
        }
        if(isset($search_params['task_context'])) {
            $result = $result->where('task_context', 'like', '%'.$search_params['task_context'].'%');
        }
        if(isset($search_params['url'])) {
            $result = $result->where('url', 'like', '%'.$search_params['url'].'%');
        }
        if(isset($search_params['priority'])) {
            $result = $result->where('priority', '=', $search_params['priority']);
        }

        //フリーワード検索
        if($search_word != '' && $search_word != null) {

            $result = $result->where(function ($query) use($search_word) {
                $query
                    ->orWhere('other_task_name', 'like', '%'.$search_word.'%')
                    ->orWhere('task_context', 'like', '%'.$search_word.'%')
                    ->orWhere('url', 'like', '%'.$search_word.'%');
            });
        }

        if ($search_date_since !='' && $search_date_since != null){
            $result = $result->wheredate('created_at', '>=', $search_date_since);
        }

        if ($search_date_until !='' && $search_date_until != null){
            $result = $result->wheredate('created_at', '<=', $search_date_until);
        }
        
        if($offset != null) {
            $result = $result->offset($offset);
        }
        if($limit != null) {
            $result = $result->limit($limit);
        }
        if($orderParam != null) {
            $result = $result->orderBy($orderParam, $orderAsc);
        } else {
            $result = $result->orderBy('id', 'asc');
        }
        $result = $result->get();
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
                'task_type_id' => $param['task_type_id'],
                'other_task_name' => $param['other_task_name'],
                'task_context' => $param['task_context'],
                'url' => $param['url'],
                'priority' => $param['priority'],
                'created_at' => date('Y-m-d H:i:s')
            );
        }

        $result = DB::table($this->table)->insert($insert_data);

        //最後のauto increment IDを取得
        $last_id = DB::getPdo()->lastInsertId();

        return $last_id;
    }
}
