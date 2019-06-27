<?php
namespace App\Services\Data;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

use App\Http\Controllers\ApiController;

use App\Dtos\ApiRequestDto;
use App\Dtos\ApiResponseDto;
use App\Dtos\ApiErrorDto;
use App\Dtos\UserCounselingInfoDto;
use App\Dtos\MemberReportDto;

use App\Models\MemberReport;

use App\Enums\IsDeletedType;
use App\Enums\ApiMethodNameType;
use App\Enums\ApiResultStatusType;
use App\Enums\ApiErrorCodeType;
use App\Enums\ApiLessonLang;

use App\Models\Counseling;
use App\Models\MemberReportChart;
use App\Models\MemberReportGsampleData;

use App\Utils\CommonUtils;
use App\Utils\DateUtils;
use App\Services\MemberService;
use App\Mail\PutInquiry;
use Lang;
use DB;
use S3Service;
use Hash;

use Validator;

/**
 * LessonService class
 */
class DataService
{
    /**
     * __construct
     */
    public function __construct()
    {

    }

    /**
     * PutInquiry function
     *
     * @param array $params
     * @return void
     */
    public static function putInquiry($params = array())
    {
        //get user loging
        $user = Auth::user();
        
        //setup insert data
        $insert_data = [
            'user_id' => $user->id,
            'member_name' => $params['member_name'],
            'email' => $params['email'],
            'phone' => $params['phone'],
            'inquiry_type' => $params['inquiry_type'],
            'question' => $params['question'],
            'is_agreed_campaign_info' => $params['is_agreed_campaign_info'],
            'is_agreed_personal_info' => $params['is_agreed_personal_info'],
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $success = DB::table('user_inquiries')->insert($insert_data);
        try {
            \Mail::to($user->mail)
            ->cc(['alugo2c_info@alue.co.jp'])
            ->send(new PutInquiry($insert_data));
        } catch (\Throwable $th) {
            $result = CommonUtils::apiResultNG(
                ApiErrorCodeType::API_ERROR_OTHERS_CODE,
                Lang::get(ApiErrorCodeType::API_ERROR_OTHERS_MSG),
                [
                    'MSG' => Lang::get('custom.mail_error')
                ],
                CommonUtils::getCommon()
            );
            return $result;
        }
       

        $result = CommonUtils::apiResultOK(
            null,
            CommonUtils::getCommon()
        );
        return $result;
    }

    /**
     * GetCounselingHistory function
     *
     * @return void
     */
    public static function getCounselingHistory()
    {
        //get user loging
        $user = Auth::user();
        
        //get list counseling
        $list_counseling = Counseling::where('student_id', $user->arugo_user_id)->get();

        if (!$list_counseling) {
            $result = CommonUtils::apiResultNG(
                ApiErrorCodeType::API_ERROR_PARAMS_CODE,
                Lang::get(ApiErrorCodeType::API_ERROR_PARAMS_MSG),
                [
                    'MSG' => Lang::get('custom.not_exist')
                ],
                CommonUtils::getCommon()
            );
            return $result;
        }

        $response = [];
        foreach ($list_counseling as $counseling) {
            $responseItem = new UserCounselingInfoDto();

            $responseItem->counselignId = $counseling->id;
            $responseItem->goalId = $counseling->goal_id;
            $responseItem->lessonId = $counseling->lesson_id;
            $responseItem->trouble = $counseling->trouble;
            $responseItem->impression = $counseling->impression;
            $responseItem->advice = $counseling->advice;
            $responseItem->shareCoach = $counseling->share_coach;
            $responseItem->createdAt =$counseling->created_at;
            $responseItem->updatedAt =$counseling->updated_at;
            $responseItem->deletedAt =$counseling->deleted_at;
            $responseItem->deleted =$counseling->deleted;
            $responseItem->counselingTicketId =$counseling->counseling_ticket_id;
            $responseItem->studentId =$counseling->arugo_user_id;
            $responseItem->userId =$user->id;

            $response[] = $responseItem;
        }

        $result = CommonUtils::apiResultOK(
            $response,
            CommonUtils::getCommon()
        );
        return $result;
    }

    /**
     * getHelp function
     *
     * @return void
     */
    public static function getHelp()
    {
        $contents = Lang::get('help');

        $result = CommonUtils::apiResultOK(
            $contents,
            CommonUtils::getCommon()
        );
        return $result;
    }

    /**
     * GetTermsOfService function
     *
     * @return void
     */
    public static function getTermsOfService()
    {
        $contents = Lang::get('terms_of_service');

        $result = CommonUtils::apiResultOK(
            $contents,
            CommonUtils::getCommon()
        );
        return $result;
    }

    /**
     * GetPrivacyPolicy function
     *
     * @return void
     */
    public static function getPrivacyPolicy()
    {
        $contents = Lang::get('privacy_policy');

        $result = CommonUtils::apiResultOK(
            $contents,
            CommonUtils::getCommon()
        );
        return $result;
    }

    /**
     * GetAchievementData function
     *
     * @return void
     */
    public static function getAchievementData()
    {
        $user = Auth::user();

        $userId = $user->id;

        $response = [];

        $memberService = new MemberService();

        $params['userId'] = $userId;
        
        //該当発行号のレポート情報を取得
        $reportInfo = MemberReport::where('is_deleted', IsDeletedType::ACTIVE)
            ->where('user_id', $userId)
            ->orderBy('id', 'desc')
            ->first();

        //会員基本情報(最新情報)の取得
        $memberDetail = $memberService->getMemberBasic($params);
        $userDto = $memberDetail->userBasicInfo;

       
        $memberReportDto = new MemberReportDto();

        $memberReportDto->userId       = $userDto->userId;
        $memberReportDto->arugoUserId  = $userDto->arugoUserId;

        //2018-05-31 会員レポート氏名表記は「英字の苗字のみ」とする
        $memberReportDto->memberName   = $userDto->lastName . ' ' . $userDto->firstName;
        $memberReportDto->memberNameEn = $userDto->lastNameEn; //$userDto->firstNameEn . ' ' . $userDto->lastNameEn;

        if($reportInfo){
            $memberReportDto->contractPeriodSince = DateUtils::convertToYYMMDD(DateUtils::shortenToYYMMDD($reportInfo->contract_period_since));
            $memberReportDto->contractPeriodUntil = DateUtils::convertToYYMMDD(DateUtils::shortenToYYMMDD($reportInfo->contract_period_until));
            $memberReportDto->lessonPeriodSince   = DateUtils::convertToYYMMDD(DateUtils::shortenToYYMMDD($reportInfo->lesson_period_since));
            $memberReportDto->lessonPeriodUntil   = DateUtils::convertToYYMMDD(DateUtils::shortenToYYMMDD($reportInfo->lesson_period_until));
            $memberReportDto->reportCreatedAt     = DateUtils::convertToYYMMDD(DateUtils::shortenToYYMMDD($reportInfo->report_created_at));
            $memberReportDto->reportName          = $reportInfo->report_name;
            $memberReportDto->remark              = $reportInfo->report_remark;
            $memberReportDto->isPosted            = $reportInfo->is_posted;
            $memberReportDto->postedAt            = $reportInfo->posted_at;

            //


            //該当発行号のグラフ画像情報を取得
            $chartInfos = MemberReportChart::where('is_deleted', IsDeletedType::ACTIVE)
                                ->where('user_id', $userId)
                                ->whereDate('report_created_at', $reportInfo->report_created_at)
                                ->orderby('chart_order')
                                ->get();
            if ($chartInfos) {
                $chartList = [];
                foreach ($chartInfos as $chartInfo) {
                    //s3Keyから署名付きURLを取得する
                    $chartS3Key = $chartInfo->chart_s3_key;
                    $chartUrl = S3Service::getPreSignedUrlByS3Key($chartS3Key);
                    $chartList[] = [
                                    'chartType'     => $chartInfo->chart_type,
                                    'chartTitle'    => $chartInfo->chart_title,
                                    'chartFilePath' => $chartUrl,
                                    'chartRemark'   => $chartInfo->chart_remark,
                                    'chartMessage'  => $chartInfo->chart_message,
                                ];
                }
                $memberReportDto->chartList = $chartList;
            }

            //grammarRate用表データ設定
            $memberReportDto->grammarRates = $memberService->getGrammarRateData($userId, $reportInfo->report_created_at);

            // g-sample用表データ設定
            $memberReportGsampleDatas = MemberReportGsampleData::where('is_deleted', IsDeletedType::ACTIVE)
                                        ->where('user_id', $userId)
                                        ->whereDate('report_created_at', $reportInfo->report_created_at)
                                        ->orderby('id')
                                        ->get();
            $gSampleDatas = [];
            if ($memberReportGsampleDatas) {
                foreach ($memberReportGsampleDatas as $memberReportGsampleData) {
                    $gSampleDatas[] = [
                                    'id'          => $memberReportGsampleData->id,
                                    'g_index'     => $memberReportGsampleData->g_index,
                                    'g_name'      => $memberReportGsampleData->g_name,
                                    'lesson_time' => DateUtils::shortenToYYMMDDHHMM_SlashDelimited($memberReportGsampleData->lesson_time),
                                    'tf_flag'     => ($memberReportGsampleData->tf_flag == 1) ? 'True' : 'False',
                                    'sentence'    => $memberReportGsampleData->sentence,
                                ];
                }
            }
            $memberReportDto->gSampleDatas = $gSampleDatas;


            //累積レッスンチケット消化データ
            $memberReportDto->accumulatedLessonTicketsDatas = $memberService->getAccumulatedLessonTicketsData($userId);
            //累積タスク完了数データ取得
            $memberReportDto->accumulatedTaskCompleteRates = $memberService->getAccumulatedTaskCompleteRate($userId);

            $response = $memberReportDto;

        }//end of if

        
        
        $result = CommonUtils::apiResultOK(
            $response,
            CommonUtils::getCommon()
        );
        return $result;
    }

    /**
     * GetMemberDetail function
     *
     * @return void
     */
    public static function getMemberDetail()
    {
        $user = Auth::user();

        $userId = $user->id;

        $memberService = new MemberService();

        $params['userId'] = $userId;

        $memberDetail = $memberService->getMemberDetail($params);

        $result = CommonUtils::apiResultOK(
            [
                'member_detail' => $memberDetail
            ],
            CommonUtils::getCommon()
        );
        return $result;
    }

    public static function putMemberImage($params = array()) 
    {
        //get user loging
        $user = Auth::user();

         //validate param
        $validate = Validator::make(
            $params,
            [
                'image_url'             => 'required',
                'last_name'             => 'required',
                'first_name'            => 'required',
                'password'              => 'required_with:password_confirmation|min:8|max:20|regex:/(^([a-zA-Z0-9!@#$%^&*()-_+ ]+)(\d+)?$)/u',
                'password_confirmation' => 'required_with:password|same:password',
            ]
        );
        if ($validate->fails()) {
 
            $result = new ApiResponseDto();
            $apiError = new ApiErrorDto();
        
            $result = CommonUtils::apiResultNG(
                ApiErrorCodeType::API_ERROR_VALIDATION_CODE,
                Lang::get(ApiErrorCodeType::API_ERROR_VALIDATION_MSG),
                $validate->messages()->toArray(),
                CommonUtils::getCommon()
            );
            
            return $result;
        }

        /* $last_name = explode(" ", $params['name'], 2)[0];
        $first_name = explode(" ", $params['name'], 2)[1]; */

        $password = isset($params['password']) ? Hash::make($params['password']) : $user->password;

        //update img avatar
        $update_img = DB::table('users')
            ->where('id', $user->id)
            ->update(
                [
                    'image_url'  => $params['image_url'],
                    'last_name'  => $params['last_name'],
                    'first_name' => $params['first_name'],
                    'password'   => $password,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]
            );
        
            $result = CommonUtils::apiResultOK(
                [
                    'img_url' => $params['image_url']
                ],
                CommonUtils::getCommon()
            );
            return $result;
    }
}