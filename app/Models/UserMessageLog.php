<?php

namespace App\Models;

use DB;
use Illuminate\Database\Eloquent\Model;
use App\Models\CommonModel as CommonModel;

use App\Enums\UserMessageLogCwPostType;
use App\Enums\UserMessageLogConnectType;
use App\Enums\IsDeletedType;

class UserMessageLog extends CommonModel
{
    public $table = 'user_message_logs';
    public $name = 'メッセージ履歴データ';
    public $primary = 'id';
    public $columns = array(
        'id' => 'ID',
        'user_id' => 'userId',
        'message_type' => 'メッセージ種別',
        'cw_room_id' => 'chatworkルームID',
        'cw_room_name' => 'chatworkルーム名',
        'cw_post_type' => 'chatwork投稿タイプ',
        'connect_type' => '送受信フラグ',
        'posted_name' => '投稿者名',
        'posted_at' => '投稿日時',
        'deadline' => '期日',
        'topic_category' => 'トピックカテゴリ',
        'topic_id' => 'トピックID',
        'topic_stuff_id' => 'カウンセラー担当者',
        'cw_message_id' => 'chatworkメッセージid',
        'cw_task_id' => 'chatworkタスクid',
        'posted_context' => '送付内容',
        'user_task_linked' => '回答紐付け済みフラグ',
        'comment' => '備考欄',
        'created_at' => '作成日',
        'updated_at' => '最終更新日',
        'deleted_at' => '削除日',
    );

    protected $guarded = ['id', 'created_at'];

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
        //$result = DB::table($this->table);
        $result = UserMessageLog::from('user_message_logs as ulog')
                             ->join('users as usr', 'user_id', 'usr.id')
                             ->where('ulog.is_deleted', IsDeletedType::ACTIVE)
                             ->where('usr.is_deleted', IsDeletedType::ACTIVE)
                             ->select(DB::raw('
                                ulog.id,
                                usr.arugo_user_id,
                                ulog.user_id,
                                ulog.message_type,
                                ulog.cw_room_id,
                                ulog.cw_room_name,
                                ulog.cw_post_type,
                                ulog.connect_type,
                                ulog.posted_name,
                                DATE_FORMAT(ulog.posted_at, "%Y-%m-%d") as posted_at,
                                DATE_FORMAT(ulog.deadline, "%Y-%m-%d") as deadline,
                                ulog.topic_category,
                                ulog.topic_id,
                                ulog.topic_stuff_id,
                                ulog.cw_message_id,
                                ulog.cw_task_id,
                                ulog.posted_context,
                                ulog.user_task_linked,
                                ulog.comment')
                            );

        //各検索項目による検索
        if(isset($search_params['alugo_user_id'])) {
            //$result = $result->where('user_id', '=', $search_params['user_id']);
            $result = $result->where('usr.arugo_user_id', '=', $search_params['alugo_user_id']);
        }
        if(isset($search_params['message_type'])) {
            $result = $result->where('ulog.message_type', '=', $search_params['message_type']);
        }
        if(isset($search_params['cw_room_id'])) {
            $result = $result->where('ulog.cw_room_id', '=', $search_params['cw_room_id']);
        }
        if(isset($search_params['cw_post_type'])) {
            $result = $result->where('ulog.cw_post_type', '=', $search_params['cw_post_type']);
        }
        if(isset($search_params['connect_type'])) {
            $result = $result->where('ulog.connect_type', '=', $search_params['connect_type']);
        }
        if(isset($search_params['user_task_linked'])) {
            $result = $result->where('ulog.user_task_linked', '=', $search_params['user_task_linked']);
        }
        if(isset($search_params['posted_at'])) {
            $result = $result->whereDate('ulog.posted_at', '=', $search_params['posted_at']);
        }
        if(isset($search_params['deadline'])) {
            $result = $result->whereDate('ulog.deadline', '=', $search_params['deadline']);
        }
        if(isset($search_params['topic_id'])) {
            $result = $result->where('ulog.topic_id', '=', $search_params['topic_id']);
        }

        if(isset($search_params['cw_room_name'])) {
            $result = $result->where('ulog.cw_room_name', 'like', '%'.$search_params['cw_room_name'].'%');
        }
        if(isset($search_params['posted_name'])) {
            $result = $result->where('ulog.posted_name', 'like', '%'.$search_params['posted_name'].'%');
        }
        if(isset($search_params['topic_category'])) {
            $result = $result->where('ulog.topic_category', 'like', '%'.$search_params['topic_category'].'%');
        }
        if(isset($search_params['topic_stuff_id'])) {
            $result = $result->where('ulog.topic_stuff_id', 'like', '%'.$search_params['topic_stuff_id'].'%');
        }
        if(isset($search_params['posted_context'])) {
            $result = $result->where('ulog.posted_context', 'like', '%'.$search_params['posted_context'].'%');
        }

        //cwメッセージid
        if(isset($search_params['cw_message_id'])) {
            $result = $result->where('ulog.cw_message_id', $search_params['cw_message_id']);
        }
        //cwタスクid
        if(isset($search_params['cw_task_id'])) {
            $result = $result->where('ulog.cw_task_id', $search_params['cw_task_id']);
        }
        //音声ファイル投稿メッセージ
        if(isset($search_params['is_sound_file'])) {
            $p = $search_params['is_sound_file'];
            if($p == '0'){
                //音声ファイル投稿メッセージ以外に限定
                $result = $result->where('ulog.cw_post_type', '!=', UserMessageLogCwPostType::FILE);

            }elseif($p == '1'){
                //音声ファイル投稿メッセージに限定
                $result = $result->where('ulog.cw_post_type', UserMessageLogCwPostType::FILE)
                                 ->where('ulog.connect_type', UserMessageLogConnectType::RECEIVE)
                                 ->whereNotNull('ulog.posted_context');
            }
        }
        //タスク完了メッセージ
        if(isset($search_params['is_task_done'])) {
            $p = $search_params['is_task_done'];
            if($p == '0'){
                //タスク完了メッセージ以外に限定
                $result = $result->where(function($q){
                                    $q->orWhere('ulog.cw_post_type', '!=', UserMessageLogCwPostType::MESSAGE)
                                    ->orWhere('ulog.connect_type', '!=', UserMessageLogConnectType::RECEIVE)
                                    ->orWhereNull('ulog.cw_task_id');
                });

            }elseif($p == '1'){
                //タスク完了メッセージに限定
                $result = $result->where('ulog.cw_post_type', UserMessageLogCwPostType::MESSAGE)
                                 ->where('ulog.connect_type', UserMessageLogConnectType::RECEIVE)
                                 ->whereNotNull('ulog.cw_task_id');
            }
        }

        //フリーワード検索
        if($search_word != '' && $search_word != null) {

            $result = $result->where(function ($query) use($search_word) {
                $query
                    ->orWhere('ulog.cw_room_name', 'like', '%'.$search_word.'%')
                    ->orWhere('ulog.posted_name', 'like', '%'.$search_word.'%')
                    ->orWhere('ulog.topic_category', 'like', '%'.$search_word.'%')
                    ->orWhere('ulog.topic_stuff_id', 'like', '%'.$search_word.'%')
                    ->orWhere('ulog.posted_context', 'like', '%'.$search_word.'%');
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
            $result = $result->orderBy('ulog.posted_at', 'desc');
        }
        $result = $result->get();

        $searchResults = [];
        foreach($result as $row) {
            $searchResults[] = $row->toArray();
        }

        return $searchResults;
    }

    /*
     * データ一覧検索結果件数のみ取得
     */
    public function getSearchCount($search_params, $search_word )
    {

        //$result = DB::table($this->table);
        $result = UserMessageLog::from('user_message_logs as ulog')
                             ->join('users as usr', 'user_id', 'usr.id')
                             ->where('ulog.is_deleted', IsDeletedType::ACTIVE)
                             ->where('usr.is_deleted', IsDeletedType::ACTIVE);

        //各検索項目による検索
        if(isset($search_params['alugo_user_id'])) {
            //$result = $result->where('user_id', '=', $search_params['user_id']);
            $result = $result->where('usr.arugo_user_id', '=', $search_params['alugo_user_id']);
        }
        if(isset($search_params['message_type'])) {
            $result = $result->where('ulog.message_type', '=', $search_params['message_type']);
        }
        if(isset($search_params['cw_room_id'])) {
            $result = $result->where('ulog.cw_room_id', '=', $search_params['cw_room_id']);
        }
        if(isset($search_params['cw_post_type'])) {
            $result = $result->where('ulog.cw_post_type', '=', $search_params['cw_post_type']);
        }
        if(isset($search_params['connect_type'])) {
            $result = $result->where('ulog.connect_type', '=', $search_params['connect_type']);
        }
        if(isset($search_params['user_task_linked'])) {
            $result = $result->where('ulog.user_task_linked', '=', $search_params['user_task_linked']);
        }
        if(isset($search_params['posted_at'])) {
            $result = $result->whereDate('ulog.posted_at', '=', $search_params['posted_at']);
        }
        if(isset($search_params['deadline'])) {
            $result = $result->whereDate('ulog.deadline', '=', $search_params['deadline']);
        }
        if(isset($search_params['topic_id'])) {
            $result = $result->where('ulog.topic_id', '=', $search_params['topic_id']);
        }

        if(isset($search_params['cw_room_name'])) {
            $result = $result->where('ulog.cw_room_name', 'like', '%'.$search_params['cw_room_name'].'%');
        }
        if(isset($search_params['posted_name'])) {
            $result = $result->where('ulog.posted_name', 'like', '%'.$search_params['posted_name'].'%');
        }
        if(isset($search_params['topic_category'])) {
            $result = $result->where('ulog.topic_category', 'like', '%'.$search_params['topic_category'].'%');
        }
        if(isset($search_params['topic_stuff_id'])) {
            $result = $result->where('ulog.topic_stuff_id', 'like', '%'.$search_params['topic_stuff_id'].'%');
        }
        if(isset($search_params['posted_context'])) {
            $result = $result->where('ulog.posted_context', 'like', '%'.$search_params['posted_context'].'%');
        }

        //cwメッセージid
        if(isset($search_params['cw_message_id'])) {
            $result = $result->where('ulog.cw_message_id', $search_params['cw_message_id']);
        }
        //cwタスクid
        if(isset($search_params['cw_task_id'])) {
            $result = $result->where('ulog.cw_task_id', $search_params['cw_task_id']);
        }
        //音声ファイル投稿メッセージ
        if(isset($search_params['is_sound_file'])) {
            $p = $search_params['is_sound_file'];
            if($p == '0'){
                //音声ファイル投稿メッセージ以外に限定
                $result = $result->where('ulog.cw_post_type', '!=', UserMessageLogCwPostType::FILE);

            }elseif($p == '1'){
                //音声ファイル投稿メッセージに限定
                $result = $result->where('ulog.cw_post_type', UserMessageLogCwPostType::FILE)
                                 ->where('ulog.connect_type', UserMessageLogConnectType::RECEIVE)
                                 ->whereNotNull('ulog.posted_context');
            }
        }
        //タスク完了メッセージ
        if(isset($search_params['is_task_done'])) {
            $p = $search_params['is_task_done'];
            if($p == '0'){
                //タスク完了メッセージ以外に限定
                $result = $result->where(function($q){
                                    $q->orWhere('ulog.cw_post_type', '!=', UserMessageLogCwPostType::MESSAGE)
                                    ->orWhere('ulog.connect_type', '!=', UserMessageLogConnectType::RECEIVE)
                                    ->orWhereNull('ulog.cw_task_id');
                });

            }elseif($p == '1'){
                //タスク完了メッセージに限定
                $result = $result->where('ulog.cw_post_type', UserMessageLogCwPostType::MESSAGE)
                                 ->where('ulog.connect_type', UserMessageLogConnectType::RECEIVE)
                                 ->whereNotNull('ulog.cw_task_id');
            }
        }

        //フリーワード検索
        if($search_word != '' && $search_word != null) {
            $result = $result->where(function ($query) use($search_word) {
                $query
                    ->orWhere('ulog.cw_room_name', 'like', '%'.$search_word.'%')
                    ->orWhere('ulog.posted_name', 'like', '%'.$search_word.'%')
                    ->orWhere('ulog.topic_category', 'like', '%'.$search_word.'%')
                    ->orWhere('ulog.topic_stuff_id', 'like', '%'.$search_word.'%')
                    ->orWhere('ulog.posted_context', 'like', '%'.$search_word.'%');
            });
        }
        /*
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
        }**/
        $recCnt = $result->count();
        return $recCnt;
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
                'message_type' => $param['message_type'],
                'cw_room_id' => $param['cw_room_id'],
                'cw_room_name' => $param['cw_room_name'],
                'cw_post_type' => $param['cw_post_type'],
                'connect_type' => $param['connect_type'],
                'posted_name' => $param['posted_name'],
                'posted_at' => $param['posted_at'],
                'deadline' => $param['deadline'],
                'topic_category' => $param['topic_category'],
                'topic_id' => $param['topic_id'],
                'topic_stuff_id' => $param['topic_stuff_id'],
                'cw_message_id' => $param['cw_message_id'],
                'cw_task_id' => $param['cw_task_id'],
                'posted_context' => $param['posted_context'],
                'user_task_linked' => $param['user_task_linked'],
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
    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }


}
