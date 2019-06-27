<?php
namespace App\Dtos;
use App\Enums\IsDeletedType;
use App\Models\LpCategory;

/**
 * UserLearningPolicyDto
 */
class UserLearningPolicyDto {
    public $id;
    public $user_id;
    public $posted_at;

    public $lpCategory1Id;
    public $lpCategory1Name;
    public $lpCategory2Id;
    public $lpCategory2Name;

    public $lpTagName; //text
    public $lpTags;    //array

    public $policy;
    public $comment;

    function __construct(){
    }

    public function setCategory1Name(){
        $this->lpCategory1Name = '';
        if(empty($this->lpCategory1Id)){
            return;
        }
        $cat = LpCategory::where('is_deleted', IsDeletedType::ACTIVE)
                ->where('id', $this->lpCategory1Id)
                ->first();
        if($cat){
            $this->lpCategory1Name = $cat->category_name;
        }
    }

    public function setCategory2Name(){
        $this->lpCategory2Name = '';
        if(empty($this->lpCategory2Id)){
            return;
        }
        $cat = LpCategory::where('is_deleted', IsDeletedType::ACTIVE)
                ->where('id', $this->lpCategory2Id)
                ->first();
        if($cat){
            $this->lpCategory2Name = $cat->category_name;
        }
    }
    public function setTags(){
        $this->lpTags = '';
        if(empty($this->lpTagName)){
            return;
        }
        $this->lpTags = explode(' ', $this->lpTagName);
    }


}
