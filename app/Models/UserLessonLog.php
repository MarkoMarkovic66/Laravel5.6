<?php

namespace App\Models;

use DB;
use Illuminate\Database\Eloquent\Model;
use App\Models\CommonModel as CommonModel;

class UserLessonLog extends CommonModel
{
    public $table = 'user_lesson_logs';
    public $name = '会員へのレッスン履歴データ';
    public $primary = 'id';
    public $columns = array(
        'id' => 'ID',
        'user_id' => '会員ID',
        'alugo_teacher_id' => 'ALUGO講師ID',
        'alugo_user_id' => 'ALUGO会員ID',
        'start_at' => 'レッスン開始日時',
        'end_at' => 'レッスン終了日時',
        'report' => 'レポート内容',
        'lesson_id' => 'レッスンID',
        'today_feedback' => 'フィードバック内容',
        'message_user' => 'ユーザからのメッセージ',
        'share_coach' => 'コーチへの共有事項',
        'approved_at' => '承認日時',
        'lesson_type' => 'レッスン種別',
        'canceled' => 'キャンセルフラグ',
        'canceled_sec' => 'キャンセル減算時間',
        'request_sec' => '申し込み時間',
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
                'alugo_teacher_id' => $param['alugo_teacher_id'],
                'alugo_user_id' => $param['alugo_user_id'],
                'start_at' => $param['start_at'],
                'end_at' => $param['end_at'],
                'report' => $param['report'],
                'lesson_id' => $param['lesson_id'],
                'today_feedback' => $param['today_feedback'],
                'message_user' => $param['message_user'],
                'share_coach' => $param['share_coach'],
                'approved_at' => $param['approved_at'],
                'lesson_type' => $param['lesson_type'],
                'canceled' => $param['canceled'],
                'canceled_sec' => $param['canceled_sec'],
                'request_sec' => $param['request_sec'],
                'created_at' => date('Y-m-d H:i:s')
            );
        }

        $result = DB::table($this->table)->insert($insert_data);

        //最後のauto increment IDを取得
        $last_id = DB::getPdo()->lastInsertId();

        return $last_id;
    }
}
