<?php

namespace App\Traits;

use App\Utils\DateUtils;
use \GuzzleHttp\json_encode;

/**
 * CsvDataValidateTrait
 */
trait CsvDataValidateTrait {

    public function initCsvDataValidateTrait()
    {
        $this->setUserPlanDataHeader();
        $this->setUserPackageDataHeader();
        $this->setUserDataHeader();
        $this->setPersonalDataHeader();
        $this->setUserItemProperties();
        $this->setPersonalItemProperties();
        $this->setUserScoreDataHeader();
        $this->setUserGoalDataHeader();
        $this->setUserLessonDataHeader();
        $this->setQuestionnaireDataHeader();
        $this->setCcSessionDetailDataHeader();
        $this->setSrAnswersDataHeader();
        $this->setSrTopicsDataHeader();
        $this->setSrUsersDataHeader();
    }

    private function setUserPlanDataHeader(){
        $this->userPlanDataHeader = collect(['id','student_id','goods_id','package_type','package_start_at','package_end_at','lesson_start_at','lesson_end_at','lesson_ticket','assessment_ticket','counseling_ticket','option_goal','option_review','counseling_wday','counseling_hour','counseler_id','schedule','purchase_id','created_at','updated_at','deleted_at','deleted']);
    }
    public function getUserPlanDataHeader(){
        return $this->userPlanDataHeader;
    }

    private function setUserPackageDataHeader(){
        $this->userPackageDataHeader = collect(['id','package_type','package_name','price','status','package_duration','lesson_ticket','assessment_ticket','assessment_only_ticket','counseling_ticket','option_goal','option_review','in_store_at','out_store_at','attention','campaign','auto_assessment','created_at','updated_at','deleted_at','deleted']);
    }
    public function getUserPackageDataHeader(){
        return $this->userPackageDataHeader;
    }

    private function setUserDataHeader(){
        $this->userDataHeader = collect(['id','curriculum_id','study_sec','status','lastlesson_at','lastlogin_at','created_at','updated_at','deleted_at','deleted','assigned_sec','reserve_type','notice','matrix','first_assessment','user_type','open_at','close_at','assessment_type','goal_setting','review_setting','resigned_at','resigned_send_at','resigned_mail']);
    }
    public function getUserDataHeader(){
        return $this->userDataHeader;
    }

    private function setPersonalDataHeader(){
        $this->personalDataHeader = collect(['id','first_name','last_name','first_name_en','last_name_en','mailaddress','phone_number']);
    }
    public function getPersonalDataHeader(){
        return $this->personalDataHeader;
    }

    public function setUserScoreDataHeader() {
        $this->userScoreDataHeader = collect(['id','student_id','assessment_teacher_id','feedback_teacher_id','count','level','report','contents','result','assessment_start_at','assessment_end_at','feedback_start_at','feedback_end_at','created_at','updated_at','deleted_at','deleted']);
    }
    public function getUserScoreDataHeader() {
        return $this->userScoreDataHeader;
    }

    public function setUserGoalDataHeader() {
        $this->userGoalDataHeader = collect(['id','student_id','item_id','target','level','grade','created_at','updated_at','deleted_at','deleted','goal_limit','scene']);
    }
    public function getUserGoalDataHeader() {
        return $this->userGoalDataHeader;
    }

    public function setUserLessonDataHeader() {
        $this->userLessonDataHeader = collect(['id','teacher_id','student_id','start_at','end_at','report','lesson_id','created_at','updated_at','today_feedback','message_user','share_coach','approved_at','lesson_type','deleted_at','deleted','canceled','canceled_sec','request_sec']);
    }
    public function getUserLessonDataHeader() {
        return $this->userLessonDataHeader;
    }

    public function setQuestionnaireDataHeader() {
        $this->questionnaireDataHeader = collect(['id','lesson_id','teacher_id','student_id','evaluation','comment','created_at']);
    }
    public function getQuestionnaireDataHeader() {
        return $this->questionnaireDataHeader;
    }

    public function setCcSessionDetailDataHeader() {
        $this->ccSessionDetailDataHeader = collect(['id','session_id','lesson_id','module_id','stage_id','unit_id','question_id','result','unit_order','created_at','updated_at','deleted_at','deleted','subject_id','feedback_input','unit_type','hearing_id']);
    }
    public function getCcSessionDetailDataHeader() {
        return $this->ccSessionDetailDataHeader;
    }

    public function setSrAnswersDataHeader() {
        $this->srAnswersDataHeader = collect(['id','topic_id','user_id','date','topic_category','free_topic','user_plan_type','submit_type','original_answer','revised_answer','coach_answer','coach_message','staff_id','original_answer_date','revised_answer_date','coach_answer_date','approved_date','status','speech_path','speech_duration','total_word_count','mistake_count','total_vocabulary_count','like_count','coach_like_count','revised_answer_public','coach_answer_public','key_auto','key_coach','is_auto_revised','is_first_speech','disabled','deleted','created','modified']);
    }
    public function getSrAnswersDataHeader() {
        return $this->srAnswersDataHeader;
    }

    public function setSrTopicsDataHeader() {
        $this->srTopicsDataHeader = collect(['id','date','text','category','created_staff_id','open_question','opened','deleted','created','modified']);
    }
    public function getSrTopicsDataHeader() {
        return $this->srTopicsDataHeader;
    }

    public function setSrUsersDataHeader() {
        $this->srUsersDataHeader = collect(['id','firstname','lastname','mail','birth_date','gender','password','account_type','plan_type','paypal_billing_agreemenet_id','stripe_customer_id','google_user_id','google_tokens','facebook_user_id','facebook_access_token','twitter_user_id','twitter_tokens','country_code','country_code_residence','nickname','image_path','revised_answer_public','coach_answer_public','mail_notification','one_time_token','one_time_token_expired_date','aggregation_json','occupation_code','is_use_tutorial','is_accept_dm','first_login','activated','quitted','deleted','created','modified','middlename']);
    }
    public function getSrUsersDataHeader() {
        return $this->srUsersDataHeader;
    }


    private function setUserItemProperties(){
        $this->userItemProperties = collect();
        $this->userItemProperties['arugo_user_id']  = ['type' => 'int'   , 'null' => 'YES'];
        $this->userItemProperties['sr_user_id']     = ['type' => 'int'   , 'null' => 'YES'];
        $this->userItemProperties['cw_room_id']     = ['type' => 'int'   , 'null' => 'YES'];
        $this->userItemProperties['cw_room_name']   = ['type' => 'text'  , 'null' => 'YES'];
        $this->userItemProperties['first_name']     = ['type' => 'text'  , 'null' => 'YES'];
        $this->userItemProperties['last_name']      = ['type' => 'text'  , 'null' => 'YES'];
        $this->userItemProperties['first_name_en']  = ['type' => 'text'  , 'null' => 'YES'];
        $this->userItemProperties['last_name_en']   = ['type' => 'text'  , 'null' => 'YES'];
        $this->userItemProperties['mail']           = ['type' => 'text'  , 'null' => 'YES'];
        $this->userItemProperties['phone_number']   = ['type' => 'text'  , 'null' => 'YES'];
        $this->userItemProperties['curriculum_id']  = ['type' => 'int'   , 'null' => 'YES'];
        $this->userItemProperties['study_sec']      = ['type' => 'int'   , 'null' => 'YES'];
        $this->userItemProperties['status']         = ['type' => 'int'   , 'null' => 'YES'];
        $this->userItemProperties['matrix']         = ['type' => 'text'  , 'null' => 'YES'];
        $this->userItemProperties['opened_at']      = ['type' => 'date'  , 'null' => 'YES'];
        $this->userItemProperties['closed_at']      = ['type' => 'date'  , 'null' => 'YES'];
        $this->userItemProperties['resigned_at']    = ['type' => 'date'  , 'null' => 'YES'];
        $this->userItemProperties['user_type']      = ['type' => 'int'   , 'null' => 'NO'];
        $this->userItemProperties['activated']      = ['type' => 'int'   , 'null' => 'NO'];
        $this->userItemProperties['quitted']        = ['type' => 'int'   , 'null' => 'NO'];
        $this->userItemProperties['is_deleted']     = ['type' => 'int'   , 'null' => 'YES'];
        $this->userItemProperties['created_at']     = ['type' => 'date'  , 'null' => 'YES'];
        $this->userItemProperties['updated_at']     = ['type' => 'date'  , 'null' => 'YES'];
        $this->userItemProperties['deleted_at']     = ['type' => 'date'  , 'null' => 'YES'];
    }
    private function setPersonalItemProperties(){
        $this->personalItemProperties = collect();
        $this->personalItemProperties['first_name']     = ['type' => 'text'  , 'null' => 'YES'];
        $this->personalItemProperties['last_name']      = ['type' => 'text'  , 'null' => 'YES'];
        $this->personalItemProperties['first_name_en']  = ['type' => 'text'  , 'null' => 'YES'];
        $this->personalItemProperties['last_name_en']   = ['type' => 'text'  , 'null' => 'YES'];
        $this->personalItemProperties['mailaddress']    = ['type' => 'text'  , 'null' => 'YES'];
        $this->personalItemProperties['phone_number']   = ['type' => 'text'  , 'null' => 'YES'];
    }

    public function validateUserData($dataRows, $rules) {

        $validDataRows = $dataRows->map(function($dataRow, $rowidx) use($rules){

            $isValidItem = true;
            $validResult = collect();
            $dataRow->each(function($value, $key) use($dataRow, $rowidx, $rules, &$isValidItem, &$validResult){

                if($key && !empty($key) && $rules->has($key)){

                    $itemProperty = $rules[$key];

                    if(strlen($value) < 1){  //当該項目が空欄
                        if($itemProperty['null'] == 'YES'){
                            //ok
                        }else{
                            $isValidItem = false;//ng
                            $res = [
                                'line' => $rowidx + 1,
                                'name' => $key,
                                'value' => $value,
                                'reason' => 'NOT NULL',
                                'data' => $dataRow
                            ];
                            $validResult->push($res);
                        }

                    }elseif($value == '\N'){  //当該項目がNULL指定
                        if($itemProperty['null'] == 'YES'){
                            //ok
                        }else{
                            $isValidItem = false;//ng
                            $res = [
                                'line' => $rowidx + 1,
                                'name' => $key,
                                'value' => $value,
                                'reason' => 'NOT NULL',
                                'data' => $dataRow
                            ];
                            $validResult->push($res);
                        }

                    }else{
                        //$valueにデータあり

                        if($itemProperty['type'] == 'int'){
                            //$valueが数値ならOK
                            if(is_numeric($value)){
                                //ok
                            }else{
                                $isValidItem = false;//ng
                                $res = [
                                    'line' => $rowidx + 1,
                                    'name' => $key,
                                    'value' => $value,
                                    'reason' => 'MUST BE NUMERIC',
                                    'data' => $dataRow
                                ];
                                $validResult->push($res);
                            }

                        }elseif($itemProperty['type'] == 'text'){
                            //

                        }elseif($itemProperty['type'] == 'date'){
                            //$valueが日付けならOK
                            if(DateUtils::validateDateString($value)){
                                //ok
                            }else{
                                $isValidItem = false;//ng
                                $res = [
                                    'line' => $rowidx + 1,
                                    'name' => $key,
                                    'value' => $value,
                                    'reason' => 'ILLIGAL DATE',
                                    'data' => $dataRow
                                ];
                                $validResult->push($res);
                            }
                        }
                    }
                }
            });

            if(!$isValidItem){//エラーあり
                $this->putValidResultLog($validResult);
                $dataRow->put('validStatus', false);

            }else{
                $dataRow->put('validStatus', true);
            }

            return $dataRow;
        });

        return $validDataRows;
    }

    //'\N'をnullに整形する
    public function wellFormedNull($dataRow) {
        $dataRow->transform(function($value){
            if($value == '\N'){
               $value = null;
            }
            return $value;
        });
        return $dataRow;
    }


    public function trimDblQuote($data) {
        $data->transform(function ($item) {
            return trim($item, '"');
        });
    }


    public function putValidResultLog($validResult) {
        $validResult->each(function($item){
            $this->logwrite('error', 'data error : '. json_encode($item, JSON_UNESCAPED_UNICODE));
        });
    }


}
