<?php

namespace App\Models;

use DB;
use Illuminate\Database\Eloquent\Model;
use App\Models\CommonModel as CommonModel;

class UserLearningPolicy extends CommonModel
{
    public $table = 'user_learning_policies';
    public $name = '会員学習方針データ';
    public $primary = 'id';
    public $columns = array(
        'id' => 'ID',
        'user_id' => '会員ID',
        'posted_at' => '投稿日',
        'lp_category_id1' => '学習方針カテゴリ大',
        'lp_category_id2' => '学習方針カテゴリ小',
        'tag_name' => '学習方針カテゴリタグ',
        'policy' => '学習方針',
        'comment' => '備考欄',
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
                'posted_at' => $param['posted_at'],
                'lp_category_id1' => $param['lp_category_id1'],
                'lp_category_id2' => $param['lp_category_id2'],
                'tag_name' => $param['tag_name'],
                'policy' => $param['policy'],
                'comment' => $param['comment'],
                'created_at' => date('Y-m-d H:i:s')
            );
        }

        $result = DB::table($this->table)->insert($insert_data);

        //最後のauto increment IDを取得
        $last_id = DB::getPdo()->lastInsertId();

        return $last_id;
    }



}
