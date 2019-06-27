<?php

namespace App\Models;

use DB;
use Illuminate\Database\Eloquent\Model;
use App\Models\CommonModel as CommonModel;

class UserSession extends CommonModel
{
    public $table = 'user_sessions';
    public $name = '会員セッションデータ';
    public $primary = 'id';
    public $columns = array(
        'id' => 'ID',
        'session_id' => 'セッションID',
        'lesson_id' => 'レッスンID',
        'module_id' => 'モジュールID',
        'stage_id' => 'ステージID',
        'unit_id' => 'ユニットID',
        'question_id' => '質問ID',
        'result' => '結果',
        'unit_order' => 'ユニット順',
        'subject_id' => '題目ID',
        'feedback_input' => 'フィードバック入力',
        'unit_type' => 'ユニット種別',
        'hearing_id' => 'ヒアリングID',
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
                'session_id' => $param['session_id'],
                'lesson_id' => $param['lesson_id'],
                'module_id' => $param['module_id'],
                'stage_id' => $param['stage_id'],
                'unit_id' => $param['unit_id'],
                'question_id' => $param['question_id'],
                'result' => $param['result'],
                'unit_order' => $param['unit_order'],
                'subject_id' => $param['subject_id'],
                'feedback_input' => $param['feedback_input'],
                'unit_type' => $param['unit_type'],
                'hearing_id' => $param['hearing_id'],
                'created_at' => date('Y-m-d H:i:s')
            );
        }

        $result = DB::table($this->table)->insert($insert_data);

        //最後のauto increment IDを取得
        $last_id = DB::getPdo()->lastInsertId();

        return $last_id;
    }
}
