<?php

namespace App\Models;

use DB;
use Illuminate\Database\Eloquent\Model;
use App\Models\CommonModel as CommonModel;

class UserScore extends CommonModel
{
    public $table = 'user_scores';
    public $name = '会員評価データ';
    public $primary = 'id';
    public $columns = array(
        'id' => 'ID',
        'user_id' => '会員ID',
        'alugo_user_id' => 'alugo会員ID',
        'assessment_alugo_stuff_id' => 'alugoアセスメントスタッフID',
        'feedback_alugo_stuff_id' => 'alugoフィードバックスタッフID',
        'count' => 'アセスメント回数',
        'level' => 'レベル',
        'report' => '特記事項',
        'result' => 'アセスメントの結果JSON',
        'assessment_started_at' => 'アセスメント実施日時',
        'assessment_ended_at' => 'アセスメント終了日時',
        'feedback_started_at' => 'フィードバック実施日時',
        'feedback_ended_at' => 'フィードバック終了日時',
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
                'alugo_user_id' => $param['alugo_user_id'],
                'assessment_alugo_stuff_id' => $param['assessment_alugo_stuff_id'],
                'feedback_alugo_stuff_id' => $param['feedback_alugo_stuff_id'],
                'count' => $param['count'],
                'level' => $param['level'],
                'report' => $param['report'],
                'result' => $param['result'],
                'assessment_started_at' => $param['assessment_started_at'],
                'assessment_ended_at' => $param['assessment_ended_at'],
                'feedback_started_at' => $param['feedback_started_at'],
                'feedback_ended_at' => $param['feedback_ended_at'],
                'created_at' => date('Y-m-d H:i:s')
            );
        }

        $result = DB::table($this->table)->insert($insert_data);

        //最後のauto increment IDを取得
        $last_id = DB::getPdo()->lastInsertId();

        return $last_id;
    }
}
