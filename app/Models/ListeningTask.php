<?php

namespace App\Models;

use DB;
use Illuminate\Database\Eloquent\Model;
use App\Models\CommonModel as CommonModel;

class ListeningTask extends CommonModel
{
    public $table = 'listening_tasks';
    public $name = 'リスニング';
    public $primary = 'id';
    public $guarded = ['id'];

    /*
     * リレーション設定
     */
    public $columns = array(
        'id' => 'ID',
        'listening_type' => 'リスニングタイプ',
        'unit_name' => 'Unit名',
        'subject_name' => 'Subject名',
        'hearing_text' => 'パラグラフ',
        'question' => '設問文',
        'answer' => '回答文',
        'file_url1' => '音源ファイルパス1',
        'file_url2' => '音源ファイルパス2',
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
        $result = $result->select('id', 'listening_type', 'unit_name', 'subject_name', 'hearing_text', 'question', 'answer', 'file_url1', 'file_url2', 'created_at', 'updated_at');

        //各検索項目による検索
        if(isset($search_params['unit_name'])) {
            $result = $result->where('unit_name', 'like', '%'.$search_params['unit_name'].'%');
        }
        
        if(isset($search_params['listening_type'])) {
            $result = $result->where('listening_type', '=', $search_params['listening_type']);
        }

        //フリーワード検索
        if($search_word != '' && $search_word != null) {

            $result = $result->where(function ($query) use($search_word) {
                $query
                    ->orWhere('hearing_text', 'like', '%'.$search_word.'%')
                    ->orWhere('question', 'like', '%'.$search_word.'%')
                    ->orWhere('answer', 'like', '%'.$search_word.'%')
                    ->orWhere('unit_name', 'like', '%'.$search_word.'%')
                    ->orWhere('subject_name', 'like', '%'.$search_word.'%');
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
                // 'category' => $param['category'],
                // 'grades' => $param['grades'],
                // 'priority' => $param['priority'],
                // 'created_at' => date('Y-m-d H:i:s')
            );
        }

        $result = DB::table($this->table)->insert($insert_data);

        //最後のauto increment IDを取得
        $last_id = DB::getPdo()->lastInsertId();

        return $last_id;
    }
}

