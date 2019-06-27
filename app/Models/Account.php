<?php

namespace App\Models;

use DB;
use Illuminate\Database\Eloquent\Model;
use App\Models\CommonModel as CommonModel;
use Illuminate\Support\Facades\Hash;

class Account extends CommonModel
{
    public $table = 'accounts';
    public $name = '会員データ';
    public $primary = 'id';
    public $columns = array(
        'id' => 'ID',
        'last_name' => '氏',
        'first_name' => '名',
        'last_name_en' => '氏英語表記',
        'first_name_en' => '名英語表記',
        'mail' => 'メールアドレス',
        'password' => 'パスワード',
        'phone_number' => '電話番号',
        'account_kind_id' => 'アカウント種別',
        'alugo_stuff_id' => 'ALUGO ID',
        'sr_stuff_id' => 'SR ID',
        'curriculum_id' => 'カリキュラムID',
        'skill_japanese' => '日本語スキル',
        'status' => 'ステータス',
        'last_lesson_at' => '最終レッスン日時',
        'last_login_at' => '最終ログイン日時',
        'work_sec' => '対応時間(秒)',
        'return_sec' => '差戻し時間(秒)',
        'last_assign_at' => '最終アサイン日時',
        'assign_flag' => '今すぐアサイン',
        'level' => '熟達度',
        'counseler' => 'カウンセラー権限',
        'organization' => '組織名',
        'authority' => '仕事可能フラグ',
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
    public function search($search_params, $search_word, $search_date_since, $search_date_until,$offset = null, $limit = null, $orderParam = null, $orderAsc = 'desc')
    {

        $result = DB::table($this->table);
        $result = $result->where('is_deleted', 0);

        //各検索項目による検索
        if(isset($search_params['alugo_stuff_id'])) {
            $result = $result->where('alugo_stuff_id', '=', $search_params['alugo_stuff_id']);
        }
        if(isset($search_params['sr_stuff_id'])) {
            $result = $result->where('sr_stuff_id', '=', $search_params['sr_stuff_id']);
        }
        if(isset($search_params['account_kind_id'])) {
            $result = $result->where('account_kind_id', '=', $search_params['account_kind_id']);
        }
        if(isset($search_params['first_name'])) {
            $result = $result->where(function ($query) use($search_params) {
                $query
                    ->orWhere('first_name', 'like', '%'.$search_params['first_name'].'%')
                    ->orWhere('first_name_en', 'like', '%'.$search_params['first_name'].'%');
            });
        }
        if(isset($search_params['last_name'])) {
            $result = $result->where(function ($query) use($search_params) {
                $query
                    ->orWhere('last_name', 'like', '%'.$search_params['last_name'].'%')
                    ->orWhere('last_name_en', 'like', '%'.$search_params['last_name'].'%');
            });
        }
        if(isset($search_params['mail'])) {
            $result = $result->where('mail', 'like', '%'.$search_params['mail'].'%');
        }
        if(isset($search_params['phone_number'])) {
            $result = $result->where('phone_number', 'like', '%'.$search_params['phone_number'].'%');
        }

        if(isset($search_params['curriculum_id'])) {
            $result = $result->where('curriculum_id', '=', $search_params['curriculum_id']);
        }
        if(isset($search_params['status'])) {
            $result = $result->where('status', '=', $search_params['status']);
        }
        if(isset($search_params['assign_flag'])) {
            $result = $result->where('assign_flag', '=', $search_params['assign_flag']);
        }
        if(isset($search_params['level'])) {
            $result = $result->where('level', '=', $search_params['level']);
        }
        if(isset($search_params['organization'])) {
            $result = $result->where('organization', '=', $search_params['organization']);
        }
        if(isset($search_params['authority'])) {
            $result = $result->where('authority', '=', $search_params['authority']);
        }

        //フリーワード検索
        if($search_word != '' && $search_word != null) {

            $result = $result->where(function ($query) use($search_word) {
                $query
                    ->orWhere('first_name', 'like', '%'.$search_word.'%')
                    ->orWhere('last_name', 'like', '%'.$search_word.'%')
                    ->orWhere('first_name_en', 'like', '%'.$search_word.'%')
                    ->orWhere('last_name_en', 'like', '%'.$search_word.'%')
                    ->orWhere('mail', 'like', '%'.$search_word.'%')
                    ->orWhere('phone_number', 'like', '%'.$search_word.'%');
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
                'alugo_stuff_id' => $param['alugo_stuff_id'],
                'sr_stuff_id' => $param['sr_stuff_id'],
                'account_kind_id' => $param['account_kind_id'],
                'first_name' => $param['first_name'],
                'last_name' => $param['last_name'],
                'first_name_en' => $param['first_name_en'],
                'last_name_en' => $param['last_name_en'],
                'mail' => $param['mail'],
                'phone_number' => $param['phone_number'],
                'curriculum_id' => ($param['curriculum_id'] != null) ? $param['curriculum_id'] : 0,
                'skill_japanese' => $param['skill_japanese'],
                'status' => ($param['status'] != null) ? $param['status'] : 0,
                'last_lesson_at' => $param['last_lesson_at'],
                'last_login_at' => $param['last_login_at'],
                'work_sec' => ($param['work_sec'] != null) ? $param['work_sec'] : 0,
                'return_sec' => ($param['return_sec'] != null) ? $param['return_sec'] : 0,
                'last_assign_at' => $param['last_assign_at'],
                'assign_flag' => ($param['assign_flag'] != null) ? $param['assign_flag'] : 0,
                'level' => ($param['level'] != null) ? $param['level'] : 0,
                'counseler' => $param['counseler'],
                'organization' => $param['organization'],
                'password' => Hash::make($param['password']),
                'authority' => ($param['authority'] != null) ? $param['authority'] : 0,
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
    public function feedbackAccount()
    {
        return $this->hasMany('App\Models\UserFeedback', 'id', 'feedback_account_id');
    }
    public function approvedAccount()
    {
        return $this->hasMany('App\Models\UserFeedback', 'id', 'approved_account_id');
    }


}

