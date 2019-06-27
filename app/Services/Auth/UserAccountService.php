<?php
namespace App\Services\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

use App\Http\Controllers\ApiController;

use App\Dtos\ApiRequestDto;
use App\Dtos\ApiResponseDto;
use App\Dtos\ApiErrorDto;
use App\Dtos\UserDto;

use App\Enums\ApiMethodNameType;
use App\Enums\ApiResultStatusType;
use App\Enums\ApiErrorCodeType;

use App\Utils\CommonUtils;
use Lang;

use App\User;
use Validator;
use Hash;
use DB;

/**
 * UserAccountService class
 */
class UserAccountService
{
    /**
     * __construct
     */
    public function __construct()
    {

    }

    /**
     * GetUserAccount
     * 
     * @param array $params 
     * 
     * @return void
     */
    public static function getUserAccount($params = array())
    {
        //Get user logined
        $user = User::where('id', Auth::user()->id)->first();

        //Setting field display on API
        $aryDisplayField = [
            'arugo_user_id',
            'first_name',
            'first_name',
            'last_name',
            'first_name_en',
            'last_name_en',
            'mail',
            'phone_number',
            'image_url',
            'opened_at',
            'closed_at',
        ];

        $response = new UserDto();

        //Setting custom field not using type slack to camel
        $response->userId =  $user->id;

        //Set response from ary Setting Field
        foreach ($aryDisplayField as $field) {
            //Convert snack convention to Camel convention
            $camel = CommonUtils::snackToCamel($field);

            //set field to response
            $response->{$camel} = $user->{$field};
        }

        //get level
        $user_goal = DB::table('user_scores')
            ->where('user_id', $user->id)
            ->whereNotNull('result')    //アセスメント情報が格納されていて...
            ->where('result', '<>', '') //アセスメント情報が格納されていて...
            ->whereNotNull('assessment_ended_at')    //アセスメント日付が格納されていて...
            ->orderby('assessment_ended_at', 'desc') //最新のアセスメント情報を取得
            ->orderby('id', 'desc') //同一日付けの場合はより後のレコードを取得する...
            ->first();
        
        $response->level = $user_goal->level;

        //get info review beffore lesson
        $rv_before_lesson = DB::table('review_before_lessons')
            ->where('user_id', $user->id)
            ->orderBy('id', 'desc')
            ->first();

        if ($rv_before_lesson) {
            $response->arugoLevel = $rv_before_lesson->alugo_level;
            $response->usageCase  = $rv_before_lesson->usage_case;
            $response->comment    = $rv_before_lesson->comment;
        }

        //Make result OK, and set Response UserAccount
        $result = CommonUtils::apiResultOK(
            [
                'userAccount' => $response
            ],
            CommonUtils::getCommon()
        );

        return $result;
    }

    /**
     * PutUserAccount function
     *
     * @param array $params 
     * 
     * @return void
     */
    public static function putUserAccount($params = array())
    {
        //Get user logined
        $user = Auth::user();

        //Check validate param
        $validate = Validator::make(
            $params,
            [
                'last_name'     => 'required',
                'first_name'    => 'required',
                'last_name_en'  => 'required',
                'first_name_en' => 'required',
                'mail'          => 'required|email',
                'password'      => 'required|min:8|max:20|regex:/(^([a-zA-Z0-9!@#$%^&*()-_+ ]+)(\d+)?$)/u',
            ]
        );
        if ($validate->fails()) {

            $result = new ApiResponseDto();
            $apiError = new ApiErrorDto();
           
            $result = CommonUtils::apiResultNG(
                ApiErrorCodeType::API_ERROR_VALIDATION_CODE,
                Lang::get(ApiErrorCodeType::API_ERROR_VALIDATION_MSG),
                $validate->messages()->toArray()
            );
            
            return $result;
        }

        //set name
        /* $last_name = explode(" ", $params['name'], 2)[0];
        $first_name = explode(" ", $params['name'], 2)[1];
        $last_name_en = explode(" ", $params['name_en'], 2)[0];
        $first_name_en = explode(" ", $params['name_en'], 2)[1]; */


        //get user in database by user id
        $userAccount = User::where('id', $user->id)->first();

        //remove field method in list param update
        unset($params['method']);

        //update user by params 
        foreach ($params as $field => $val) {
            if ($field != 'password') {
                $userAccount->{$field} = $val;
            } else {
                $userAccount->{$field} = Hash::make($val);
            }
        }
        
        $userAccount->save();

        //Make result OK, and set Response UserAccount
        $result = self::getUserAccount();

        return $result;
    }
}