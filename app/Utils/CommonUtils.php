<?php

namespace App\Utils;

use Auth;
use DateTime;
use Log;

use App\Dtos\ApiResponseDto;
use App\Dtos\ApiErrorDto;
use App\Enums\ApiResultStatusType;
use App\Services\Common\CommonService;

/**
 * 共通Utility
 * 使用の際は use App\Utils\CommonUtils; を行うこと。
 */
class CommonUtils
{
  /**
   * 指定された変数が Empty かどうかを判定する。
   * 変数には文字列やスカラー以外に、配列、booleanの指定も可能。
   *
   * 変数が下記の状態の場合は true:Empty を返却する。
   * ①未初期化またはnull
   * ②空の文字列
   * ③空の配列
   *
   * 上記以外は false:NotEmpty を返却する。
   *
   * 2016-11-01 注記.
   * 配列を渡す場合は、存在するキーが指定されているかについて呼び出し側でチェックすること。
   * 存在しないキーを指定した配列を渡そうとすると例外となるので注意してください。
   *
   * @param type $data
   * @return boolean true:Empty false:NotEmpty
   */
  public static function isEmpty($data)
  {
    //変数が 未初期化、または null の場合...
    if (!isset($data) || is_null($data)) {
      return true;//Empty
    }

    //変数が 文字列 の場合...
    if (is_string($data)) {
      if (strlen($data) < 1) {
        return true;//空の文字列(Empty)
      }
      return false;
    }

    //変数が 数字または数値形式の文字列、またはスカラ、またはboolean の場合...
    if (is_numeric($data) || is_scalar($data) || is_bool($data)) {
      return false;//常にNotEmpty
    }

    //変数が配列の場合...
    if (is_array($data)) {
      if (count($data) < 1) {
        return true;//空の配列(Empty)
      }
      return false;
    }

    //それ以外の場合(Object,resource)...
    return false;//常にNotEmpty

  }//end of function [isEmpty]


  /**
   * 指定された変数が NotEmpty かどうかを判定する。
   * isEmptyの逆判定となる。
   *
   * @param type $data
   * @return boolean true:NotEmpty false:Empty
   */
  public static function isNotEmpty($data)
  {
    return !self::isEmpty($data);
  }//end of function [isNotEmpty]


  /**
   * 連想配列の最初のkey取得
   * @param type $array
   * @return type
   */
  public static function firstKey($array)
  {
    reset($array);
    return key($array);
  }
  /**
   * 連想配列の最後のkey取得
   * @param type $array
   * @return type
   */
  public static function endKey($array)
  {
    end($array);
    return key($array);
  }

  public static function numberFormat($n)
  {
    if (preg_match('/^[0-9]+$/', $n)) {
        //整数
        $number = number_format($n);
    } else {
        //小数
        $number = number_format($n, 2);
        $number = preg_replace("/\.?0+$/", "", $number); // 末尾の0を除去
    }
    return $number;
  }

  /**
   * デバッグ用ログメッセージ出力
   * @param type $msg
   */
  public static function putDebugMessage($msg){
    $debug_date = new DateTime();
    $debug_msg = $debug_date->format('Y-m-d H:i:s') . ' ' . $msg;
    Log::debug($debug_msg);
  }

  /**
   * バイト単位をメガバイト単位に変換する
   * ※小数点以下2桁までとする
   *
   * @param type $num
   * @return type
   */
  public static function convertByteToMegabyte($num){
    return strval( round(intval($num) / (1024 * 1024), 2, PHP_ROUND_HALF_UP) );
  }

  /**
   * 行頭、末尾の半角/全角スペースを空文字に置き換える
   * @param string $str
   * @return string
   */
  public static function trimSpace($str = '') {
    if(!is_string($str)){
        return $str;
    }
    if(strlen($str) < 1){
        return '';
    }
    // 行頭の半角、全角スペースを、空文字に置き換える
    $work1 = preg_replace('/^[ 　]+/u', '', $str);
    // 末尾の半角、全角スペースを、空文字に置き換える
    $work2 = preg_replace('/[ 　]+$/u', '', $work1);

    return $work2;
  }

  /**
   * trimSpaceを再帰的に呼び出す
   * @param array $params
   */
  public static function trimSpaceRecursive(&$params = []) {

    foreach($params as $idx => $value){
        if(is_string($value)){
            $params[$idx] = CommonUtils::trimSpace($value);

        }elseif(is_array($value)){
            CommonUtils::trimSpaceRecursive($value);
            $params[$idx] = $value;
        }
    }

  }

    /**
     * 文字列内の特殊文字を除去する。
     *
     * ①\n \r \t \0 等の特殊文字
     * ②上記の文字列表現　'\n' '\r' '\t' 等の文字列
     * ③\(バックスラッシュ)
     * を除去する。
     *
     * @param type $str
     * @return type
     */
    public static function removeEscapeString($str) {
        /*
         * trim関数は str の最初および最後から空白文字を取り除き、 取り除かれた文字列を返します。
         * 2番目のパラメータを指定しない場合、 trim()は以下の文字を削除します。
         *     " " (ASCII 32 (0x20)), 通常の空白。
         *     "\t" (ASCII 9 (0x09)), タブ。
         *     "\n" (ASCII 10 (0x0A)), リターン。
         *     "\r" (ASCII 13 (0x0D)), 改行。
         *     "\0" (ASCII 0 (0x00)), NULバイト
         *     "\x0B" (ASCII 11 (0x0B)), 垂直タブ
         *
         * 上記に加えて下記も除去する。
         *     "　"  先頭末尾の全角スペース
         *     "-"   先頭末尾の半角ハイフン
         *     "\s"  空白文字（半角スペース、\t、\n、\r、\f）すべての文字。( |\t|\n|\r|\f)と同義
         *     "\v"
         *     \u0020         Unicode空白
         *     \u00a0         Unicode空白
         *     \u2000-\u200b  Unicode空白
         */
        $str1 = trim($str);//先頭末尾の制御文字除去
        $str2 = trim($str1, '-');//先頭末尾の半角ハイフン除去
        $str3 = trim($str2, '　');//先頭末尾の全角スペース除去

        //4バイト・ユニコード文字を削除する
        $str4 = preg_replace('/[\xF0-\xF7][\x80-\xBF][\x80-\xBF][\x80-\xBF]/', '', $str3);

        //ユニコード空白をtrimする(先頭、末尾)
        $str5 = preg_replace('/^[\x{0020}][\x{00a0}][\x{2000}-\x{200b}]/u', '', $str4);
        $str6 = preg_replace('/[\x{0020}][\x{00a0}][\x{2000}-\x{200b}]$/u', '', $str5);

        //ユニコード空白文字そのものを除去
        $str7 = str_replace(
                    [
                    '\\u0020', '\\u00a0',
                    '\\u2000', '\\u2001', '\\u2002', '\\u2003', '\\u2004',
                    '\\u2005', '\\u2006', '\\u2007', '\\u2008', '\\u2009',
                    '\\u200a', '\\u200b'
                    ], '', $str6);

        //特殊文字の除去
        $str8 = str_replace(['\t', '\n', '\r', '\0', '\s', '\x0B'], '', $str7);

        //特殊文字文字そのものを除去
        $str9 = str_replace(['\\t', '\\n','\\r', '\\0', '\\s', '\\x0B', '\\'], '', $str8);

        return $str9;
    }

    /**
     * integer項目の取り扱い
     * @param type $str
     * @return type
     */
    public static function treatInteger($str) {
        if(is_null($str)){
            return null;
        }
        if(strlen(trim($str)) < 1){
            return null;
        }
        if(!is_numeric($str)){
            return null;
        }
        return $str;
    }


    /**
     * stdClassObject -> array
     * @param type $obj
     * @return array
     */
    public static function obj2arr($obj){
        if ( !is_object($obj) ){
            return $obj;
        }
        $arr = (array) $obj;

        foreach ( $arr as &$a ){
            $a = CommonUtils::obj2arr($a);
        }
        return $arr;
    }
    /**
     * array<stdClassObject> -> array
     * @param array<obj> $objArray
     * @return array
     */
    public static function objArray2arr($objArray){
        $retArray = [];
        if($objArray){
            if(is_array($objArray)){
                foreach ( $objArray as $obj ){
                    $retArray[] = CommonUtils::obj2arr($obj);
                }
            }else{
                $retArray[] = CommonUtils::obj2arr($objArray);
            }
        }
        return $retArray;
    }

    /**
     * 配列の全データを連結して取得
     * @param type $arr
     * @return string
     */
    public static function arrayFlatten($arr){
        $ret = '';
        if(is_array($arr) && count($arr) > 0){
            foreach($arr as $key => $val){
                $ret .= $val . ' ';
            }
        }
        return trim($ret);
    }

    /**
     * 指定された文字列がJSONかどうかを判定する
     * @param string $string
     * @return bool
     */
    public static function is_json($string){
        return  ( !empty($string) &&
                  is_string($string) &&
                  is_array(json_decode($string, true)) &&
                  (json_last_error() == JSON_ERROR_NONE)
                )
                ? true : false;
    }

    /**
     * apiResultOK
     *
     * @param array $responses
     * @param array $commons
     * @return void
     */
    public static function apiResultOK($responses = array(), $commons = array())
    {
        $result = new ApiResponseDto();
        $result->status = ApiResultStatusType::OK;
        $apiError = new ApiErrorDto();
        $apiError->code = null;
        $apiError->message = null;
        $apiError->details = [];
        $result->commons = $commons;
        $result->errors = $apiError;
        $result->responses = $responses;

        return $result;
    }

    /**
     * apiResultNG
     *
     * @param [type] $code
     * @param [type] $message
     * @param array $details
     * @param array $commons
     * @return void
     */
    public static function apiResultNG($code = null, $message = null, $details = array(), $commons = array())
    {
        $result = new ApiResponseDto();
        $result->status = ApiResultStatusType::NG;
        $apiError = new ApiErrorDto();
        $apiError->code = $code;
        $apiError->message = $message;
        $apiError->details = $details;
        $result->commons = $commons;
        $result->errors = $apiError;
        $result->responses = [];

        return $result;
    }

    /**
     * SnackToCamel function
     *
     * @param  String $str String Snack
     * @return String $str String Camel
     */
    public static function snackToCamel($str)
    {
        $str = str_replace('_', ' ', $str);
        $str = ucwords($str);
        $str = str_replace(' ', '', $str);
        $str = lcfirst($str);
        return $str;
    }

    /**
     * GetCommon function
     *
     * @return void
     */
    public static function getCommon()
    {
        //get user loging
        $user = Auth::user();
        
        //get task progress
        $task_progress = CommonService::getTaskProgess();

        //get point
        $point = CommonService::getPoint();

        //get ticket
        $ticket = CommonService::getValidTicketNum();

        //check review leson
        $onTaget = CommonService::checkReviewLesson();

        //get num task larte
        $numLate = CommonService::countTaskLate();

        //set up result
        $result = [
            'taskProgress' => $task_progress,
            'numTaskLate' => $numLate,
            'point' => $point,
            'ticket' => $ticket,
            'onTaget' => $onTaget,
        ];

        return $result;
    }


}//end of class [CommonUtils]
