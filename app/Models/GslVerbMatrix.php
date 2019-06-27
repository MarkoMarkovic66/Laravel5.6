<?php

namespace App\Models;

use DB;
use Illuminate\Database\Eloquent\Model;
use App\Models\CommonModel as CommonModel;

class GslVerbMatrix extends CommonModel
{
    public $table = 'gsl_verb_matrix';
    public $name = 'GSL動詞/文型';
    public $primary = 'id';
    public $columns = array(
        'id' => 'ID',
        'gsl_lemma' => 'GSL補助定理',
        'gsl_code' => 'GSLコード',
        'sv' => '第1文型(SV)',
        'svc' => '第2文型(SVC)',
        'svo' => '第3文型(SVO)',
        'svoo' => '第4文型(SVOO)',
        'svoc' => '第5文型(SVOC)',
        'flag' => '重み',
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
        if(isset($search_params['gsl_lemma'])) {
            $result = $result->where('gsl_lemma', 'like', '%'.$search_params['gsl_lemma'].'%');
        }
        if(isset($search_params['gsl_code'])) {
            $result = $result->where('gsl_code', 'like', '%'.$search_params['gsl_code'].'%');
        }
        if(isset($search_params['sv'])) {
            $result = $result->where('sv', '=', $search_params['sv']);
        }
        if(isset($search_params['svc'])) {
            $result = $result->where('svc', '=', $search_params['svc']);
        }
        if(isset($search_params['svo'])) {
            $result = $result->where('svo', '=', $search_params['svo']);
        }
        if(isset($search_params['svoo'])) {
            $result = $result->where('svoo', '=', $search_params['svoo']);
        }
        if(isset($search_params['svoc'])) {
            $result = $result->where('svoc', '=', $search_params['svoc']);
        }
        if(isset($search_params['flag'])) {
            $result = $result->where('flag', '=', $search_params['flag']);
        }

        //フリーワード検索
        if($search_word != '' && $search_word != null) {

            $result = $result->where(function ($query) use($search_word) {
                $query
                    ->orWhere('gsl_lemma', 'like', '%'.$search_word.'%')
                    ->orWhere('gsl_code', 'like', '%'.$search_word.'%')
                    ->orWhere('sv', 'like', '%'.$search_word.'%')
                    ->orWhere('svc', 'like', '%'.$search_word.'%')
                    ->orWhere('svo', 'like', '%'.$search_word.'%')
                    ->orWhere('svoo', 'like', '%'.$search_word.'%')
                    ->orWhere('svoc', 'like', '%'.$search_word.'%');
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
                'gsl_lemma' => $param['gsl_lemma'],
                'gsl_code' => $param['gsl_code'],
                'sv' => $param['sv'],
                'svc' => $param['svc'],
                'svo' => $param['svo'],
                'svoo' => $param['svoo'],
                'svoc' => $param['svoc'],
                'flag' => $param['flag'],
                'created_at' => date('Y-m-d H:i:s')
            );
        }

        $result = DB::table($this->table)->insert($insert_data);

        //最後のauto increment IDを取得
        $last_id = DB::getPdo()->lastInsertId();

        return $last_id;
    }
}
