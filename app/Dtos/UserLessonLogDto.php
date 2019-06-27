<?php

namespace App\Dtos;

/**
 * UserLessonLogDto
 */
class UserLessonLogDto {
    public $id;
    public $user_id;
    public $alugo_teacher_id;
    public $alugo_user_id;
    public $start_at;
    public $end_at;
    public $report;
    public $lesson_id;
    public $today_feedback;
    public $message_user;
    public $share_coach;
    public $approved_at;
    public $lesson_type;
    public $canceled;
    public $canceled_sec;
    public $request_sec;
    public $is_deleted;
    public $created_at;
    public $updated_at;
    public $deleted_at;

    public $whiteboard_id;
    public $whiteboard_board;
    public $whiteboard_create_at;
    public $lesson_evaluation_mark;

    public $recNo;

    public function setLessonEvaluationMark(){
        $evalValue = 0;
        if(!empty($this->lesson_evaluation) && is_numeric($this->lesson_evaluation)){
            $evalValue = intval($this->lesson_evaluation);
        }

        if( $evalValue == 1 ){
            $this->lesson_evaluation_mark = '★☆☆☆☆';
        }elseif( $evalValue == 2 ){
            $this->lesson_evaluation_mark = '★★☆☆☆';
        }elseif( $evalValue == 3 ){
            $this->lesson_evaluation_mark = '★★★☆☆';
        }elseif( $evalValue == 4 ){
            $this->lesson_evaluation_mark = '★★★★☆';
        }elseif( $evalValue == 5 ){
            $this->lesson_evaluation_mark = '★★★★★';
        }else{
            $this->lesson_evaluation_mark = '';
        }
    }


}
