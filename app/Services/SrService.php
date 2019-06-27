<?php
namespace App\Services;

use App\Models\HomeworkAnswer;

use App\Utils\CommonUtils;
use Carbon\Carbon;
use Log;

/**
 * SrService
 */
class SrService {

    public function __construct(){
    }

	public function registFeedback($homeworkAnswerId, $feedback, $feedbackDate){
        Log::info(__CLASS__.':'.__FUNCTION__.' start: homeworkAnswerId: '. $homeworkAnswerId);

        HomeworkAnswer::where('id', $homeworkAnswerId)
            ->update([
                'feedback' => $feedback,
                'feedback_date' => empty($feedbackDate) ? null : $feedbackDate,
                'updated_at' => Carbon::now()
            ]);

        Log::info(__CLASS__.':'.__FUNCTION__.' finish: homeworkAnswerId: '. $homeworkAnswerId);
    }

	public function getFeedback($srAnswerId, &$result){
        Log::info(__CLASS__.':'.__FUNCTION__.' start: srAnswerId: ' . $srAnswerId);

        $ep = env('SR_FEEDBACK_API_ENDPOINT');
        $url = $ep . $srAnswerId;
		$opt = [
		  CURLOPT_URL => $url,
		  CURLOPT_RETURNTRANSFER => TRUE,
		  CURLOPT_SSL_VERIFYPEER => FALSE,
		];

        $ch = curl_init();
		curl_setopt_array($ch, $opt);
		$response = curl_exec($ch);
        $status = curl_getinfo($ch);
        curl_close($ch);

        //jsonレスポンスを配列で返却する
        if($status['http_code'] == '200'){
            $result = CommonUtils::objArray2arr(json_decode($response));
            $resultStat = true;

        }elseif($status['http_code'] == '204'){ //no contents
            $result = [];
            $resultStat = true;

        }else{
            $resp = CommonUtils::obj2arr(json_decode($response));
            if(is_array($resp)){
                $result = $resp;
                $result['http_code'] = $status['http_code'];
            }else{
                $result = [];
                $result['http_code'] = $status['http_code'];
            }
            $resultStat = false;
        }

        Log::info(__CLASS__.':'.__FUNCTION__.' finish: resultStat: ' . $resultStat);

        return $resultStat;
    }



}
