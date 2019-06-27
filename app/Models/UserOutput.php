<?php

namespace App\Models;

use DB;
use Illuminate\Database\Eloquent\Model;
use App\Models\CommonModel as CommonModel;

class UserOutput extends CommonModel
{
    public $table = 'user_outputs';
    public $name = '会員OUTPUT履歴データ';
    public $primary = 'id';
    public $columns = array(
        'id' => 'ID',
        'data_type' => '1:sr_answer 2:user_lesson',
        'user_id' => '会員ID',
        'activated_at' => '実施日',
        'category' => 'カテゴリ',
        'topic' => 'トピック',
        'original_answer' => '初期の回答',
        'revised_answer' => 'リバイス後回答',
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
                'data_type'=> $param['data_type'],
                'user_id' => $param['user_id'],
                'activated_at' => $param['activated_at'],
                'category' => $param['category'],
                'topic' => $param['topic'],
                'original_answer' => $param['original_answer'],
                'revised_answer' => $param['revised_answer'],
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
