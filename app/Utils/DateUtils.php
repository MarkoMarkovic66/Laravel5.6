<?php

namespace App\Utils;

use DateTime;
use DateTimeZone;
use DateInterval;
use Carbon\Carbon;

class DateUtils
{
    /*
     * 現在の日付型Dateを返します。
    */
    public static function getCurrent()
    {
        $dt = new DateTime();
        $dt->setTimeZone(new DateTimeZone('Asia/Tokyo'));
        return $dt;
    }

    /**
     * 現在の日付をstring形式で返します。
     */
    public static function getCurrentStr($formmater = 'YmdHis')
    {
        return self::getCurrent()->format($formmater);
    }

    /**
     * 現在の日付をstring形式で返します。
     */
    public static function getCurrentStrYYYYMM($formmater = 'Ym')
    {
        return self::getCurrent()->format($formmater);
    }

    /*
     * 本日の日付型Dateを返します。
    */
    public static function getToday()
    {
        $dt = new DateTime();
        $dt->setTimeZone(new DateTimeZone('Asia/Tokyo'));
        $dt->setTime(0, 0, 0);
        return $dt;
    }

    /*
     * 明日の日付型Dateを返します。
    */
    public static function getTommorow()
    {
        $dt = new DateTime();
        $dt->setTimeZone(new DateTimeZone('Asia/Tokyo'));
        $dt->add(new DateInterval('P0Y1DT0H0S'));
        $dt->setTime(0, 0, 0);
        return $dt;
    }

    /*
     * 昨日の日付型Dateを返します。
    */
    public static function getYestaday()
    {
        $dt = new DateTime();
        $dt->setTimeZone(new DateTimeZone('Asia/Tokyo'));
        $dt->modify("-1 day");
        $dt->setTime(0, 0, 0);
        return $dt;
    }

    /*
     * 今月1日の日付型Dateを返します。
    */
    public static function getCurrentMonth()
    {
        $dt = self::getToday();
        $dt->modify('first day of next months');
        $dt->modify("-1 month");
        return $dt;
    }

    /*
     * 来月1日の日付型Dateを返します。
    */
    public static function getNextMonth()
    {
        $dt = self::getToday();
        $dt->modify('first day of next months');
        return $dt;
    }

    /*
     * 今月末の日付型Dateを返します。
     */
    public static function getCurrentMonthLastDay()
    {
        $dt = self::getNextMonth();
        $dt->modify("-1 day");
        return $dt;
    }

    /**
     * yyyy-mm-dd形式で渡されたstring日付をDateTime型に変換します。
     * @param string $strDate
     */
    public static function convertDateTime($strDate)
    {
        if(empty($strDate) || strlen($strDate) < 8){
            return false;
        }
        $year = substr($strDate, 0, 4);
        $month = substr($strDate, 5, 2);
        $date = substr($strDate, 8, 2);
        return self::getStartDay($year, $month, $date);
    }

    /**
     * yyyy-mm-dd形式で渡されたstring日付を YYYY/mm/dd型 に変換します。
     * @param string $strDate
     */
    public static function convertToYYMMDD($strDate)
    {
        if(empty($strDate) || strlen($strDate) < 10){
            return '';
        }
        $year = substr($strDate, 0, 4);
        $month = substr($strDate, 5, 2);
        $date = substr($strDate, 8, 2);
        return ($year.'/'.$month.'/'.$date);
    }

    /**
     * yyyy-mm-dd hh:mm:ssまたはyyyy/mm/dd hh:mm:ss形式で渡されたstring日付を
     * YYYY/mm/dd hh:mm:ss型 に変換します。
     * @param string $strDate
     */
    public static function convertToYYMMDDHHMMSS($strDate)
    {
        if(empty($strDate) || strlen($strDate) < 19){
            return '';
        }
        $year  = substr($strDate, 0, 4);
        $month = substr($strDate, 5, 2);
        $date  = substr($strDate, 8, 2);
        $hour  = substr($strDate, 11, 2);
        $min   = substr($strDate, 14, 2);
        $sec   = substr($strDate, 17, 2);

        return ($year.'/'.$month.'/'.$date.' '.$hour.':'.$min.':'.$sec);
    }
    /**
     * yyyy-mm-dd hh:mm:ssまたはyyyy/mm/dd hh:mm:ss形式で渡されたstring日付を
     * YYYY-mm-dd hh:mm:ss型 に変換します。
     * @param string $strDate
     */
    public static function convertToYYMMDDHHMMSS_HyphenDelimited($strDate)
    {
        if(empty($strDate) || strlen($strDate) < 19){
            return '';
        }
        $year  = substr($strDate, 0, 4);
        $month = substr($strDate, 5, 2);
        $date  = substr($strDate, 8, 2);
        $hour  = substr($strDate, 11, 2);
        $min   = substr($strDate, 14, 2);
        $sec   = substr($strDate, 17, 2);

        return ($year.'/'.$month.'/'.$date.' '.$hour.':'.$min.':'.$sec);
    }


    /*
	 * YYYY/MM/DD --> YYYY-MM-DDに変換
	 * データが空の場合はnullに
	 */
    public static function convertToYYMMDD_HyphenDelimited($strDate)
    {
        if(empty($strDate) || strlen($strDate) < 10){
            return '';
        }
        $year = substr($strDate, 0, 4);
        $month = substr($strDate, 5, 2);
        $date = substr($strDate, 8, 2);
        return ($year.'-'.$month.'-'.$date);
	}

    /**
     * yyyy/mm/dd hh:mm:ss形式 または yyyy-mm-dd hh:mm:ss形式 で
     * 渡されたstring日付を YYYY-mm-dd型 に変換します。
     * @param string $strDate
     */
    public static function shortenToYYMMDD($strDate)
    {
        if(empty($strDate) || strlen($strDate) < 8){
            return '';
        }
        $year = substr($strDate, 0, 4);
        $month = substr($strDate, 5, 2);
        $date = substr($strDate, 8, 2);
        return ($year.'-'.$month.'-'.$date);
    }

    /**
     * yyyy/mm/dd hh:mm:ss形式 または yyyy-mm-dd hh:mm:ss形式 で
     * 渡されたstring日付を mm-dd型 に変換します。
     * @param string $strDate
     */
    public static function shortenToMMDD($strDate)
    {
        if(empty($strDate) || strlen($strDate) < 8){
            return '';
        }
        $month = substr($strDate, 5, 2);
        $date = substr($strDate, 8, 2);
        return ($month.'-'.$date);
    }

    /**
     * yyyy/mm/dd hh:mm:ss形式 または yyyy-mm-dd hh:mm:ss形式 で
     * 渡されたstring日付を Jan-dd型 に変換します。（Janは月の英語表記）
     * @param string $strDate
     */
    public static function shortenToJanDD($strDate)
    {
        if(empty($strDate) || strlen($strDate) < 8){
            return '';
        }
        $month = substr($strDate, 5, 2);
        $date = substr($strDate, 8, 2);

        if($month == '01'){
            $m = 'Jan';
        }elseif($month == '02'){
            $m = 'Feb';
        }elseif($month == '03'){
            $m = 'Mar';
        }elseif($month == '04'){
            $m = 'Apr';
        }elseif($month == '05'){
            $m = 'May';
        }elseif($month == '06'){
            $m = 'Jun';
        }elseif($month == '07'){
            $m = 'Jul';
        }elseif($month == '08'){
            $m = 'Aug';
        }elseif($month == '09'){
            $m = 'Sep';
        }elseif($month == '10'){
            $m = 'Oct';
        }elseif($month == '11'){
            $m = 'Nov';
        }elseif($month == '12'){
            $m = 'Dec';
        }
        return ($m.'-'.$date);
    }

    /**
     * 渡されたstring日付を長さに応じてフォーマットする。
     *
     * @param type $strDate
     * @return string
     */
    public static function wellFormToDateTime($strDate)
    {
        if(empty($strDate) || strlen($strDate) < 10){
            return null;

        }elseif(strlen($strDate) < 19){
            $year  = substr($strDate, 0, 4);
            $month = substr($strDate, 5, 2);
            $date  = substr($strDate, 8, 2);
            return ($year.'-'.$month.'-'.$date);

        }else{
            $year  = substr($strDate, 0, 4);
            $month = substr($strDate, 5, 2);
            $date  = substr($strDate, 8, 2);
            $hour  = substr($strDate, 11, 2);
            $min   = substr($strDate, 14, 2);
            $sec   = substr($strDate, 17, 2);
            return ($year.'-'.$month.'-'.$date.' '.$hour.':'.$min.':'.$sec);
        }
    }

    /**
     * yyyy/mm/dd hh:mm:ss形式 または yyyy-mm-dd hh:mm:ss形式 で
     * 渡されたstring日付を YYYY/mm/dd型 に変換します。
     * @param string $strDate
     */
    public static function shortenToYYMMDD_SlashDelimited($strDate)
    {
        if(empty($strDate) || strlen($strDate) < 8){
            return '';
        }
        $year = substr($strDate, 0, 4);
        $month = substr($strDate, 5, 2);
        $date = substr($strDate, 8, 2);
        return ($year.'/'.$month.'/'.$date);
    }

    /**
     * yyyy/mm/dd hh:mm:ss形式 または yyyy-mm-dd hh:mm:ss形式 で
     * 渡されたstring日付を YYYY/mm/dd hh:mm に変換します。
     * @param string $strDate
     */
    public static function shortenToYYMMDDHHMM_SlashDelimited($strDate)
    {
        if(empty($strDate) || strlen($strDate) < 16){
            return '';
        }
        $year  = substr($strDate, 0, 4);
        $month = substr($strDate, 5, 2);
        $date  = substr($strDate, 8, 2);
        $hour  = substr($strDate, 11, 2);
        $min   = substr($strDate, 14, 2);
        return ($year.'/'.$month.'/'.$date.' '.$hour.':'.$min);
    }

    /**
     * yyyy/mm/dd hh:mm:ss形式 または yyyy-mm-dd hh:mm:ss形式 で
     * 渡されたstring日付を YYYY年mm月dd日 に変換します。
     * @param string $strDate
     */
    public static function convertToYYMMDD_Japanese($strDate)
    {
        if(empty($strDate) || strlen($strDate) < 8){
            return '';
        }
        $year = substr($strDate, 0, 4);
        $month = substr($strDate, 5, 2);
        $date = substr($strDate, 8, 2);
        return ($year.'年'.$month.'月'.$date.'日');
    }

    /**
     * 指定された日付の00:00:00の日付型を返します。
     */
    public static function getStartDay($year, $month, $date)
    {
        $dt = new DateTime();
        $dt->setTimeZone(new DateTimeZone('Asia/Tokyo'));
        $dt->setDate($year, $month, $date);
        $dt->setTime(0, 0, 0);
        return $dt;
    }

    /**
     * 指定された月の月初日の00:00:00の日付型を返します。
     */
    public static function getStartMonth($year, $month)
    {
        $dt = new DateTime();
        $dt->setTimeZone(new DateTimeZone('Asia/Tokyo'));
        $dt->setDate($year, $month, 1);
        $dt->setTime(0, 0, 0);
        return $dt;
    }

    /**
     * 引数で渡された秒数を、HH:mm:ss 形式の文字列で返します。
     */
    public static function convertHMS($seconds)
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds / 60) % 60);
        $sec = $seconds % 60;
        $hms = sprintf("%02d:%02d:%02d", $hours, $minutes, $sec);
        return $hms;
    }

    /**
     * 日付の差分を日数で返します。
     *
     * $startDate = 2011/10/1 、$endDate = 2011/10/3 の場合、2。
     */
    public static function difDate($startDate, $endDate)
    {
        $interval = date_diff($startDate, $endDate);
        $dateStr = $interval->format('%a');
        return (int)$dateStr;
    }

    /**
     * RFC日付をdateに変換します。
     */
    public static function convertFromRFC($rfcDate)
    {
        return date('Y-m-d H:i:s', strtotime($rfcDate));
    }

    /**
     * 日時文字列をミリ秒まで取得する
     * @return type
     */
    public static function getUnixTimeMillSecond(){
        //microtimeを.で分割
        $arrTime = explode('.', microtime(true));
        //日時＋ミリ秒
        return date('YmdHis', $arrTime[0]).'_'.$arrTime[1];
    }

    /**
     * 日付け文字列をバリデーションする
     * yyyy/mm/dd または yyyy-mm-dd に対応
     *
     * @param string $strDate
     * @param bool $isEmptyOK (true:空文字を許可する false:空文字をエラーとする)
     * @return true:OK false:NG
     */
    public static function validateDateString($strDate, $isEmptyOK=false){

        if($isEmptyOK && empty($strDate)){
            return true;
        }

        if((!empty($strDate) && strlen($strDate) < 10)){
            return false;
        }

        //日付けのみにする
        $strDate = self::shortenToYYMMDD_SlashDelimited($strDate);

        if(strpos($strDate, '/') !== false){
            // '/'区切り
            list($Y, $m, $d) = explode('/', $strDate);

        }elseif(strpos($strDate, '-') !== false){
            // '-'区切り
            list($Y, $m, $d) = explode('-', $strDate);
        }

        if (checkdate($m, $d, $Y) === true) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 日付け時刻文字列をバリデーションする
     * yyyy/mm/dd hh:mm:ss または yyyy-mm-dd hh:mm:ssに対応
     *
     * @param string $datetime
     * @param bool $isEmptyOK (true:空文字を許可する false:空文字をエラーとする)
     * @return true:OK false:NG
     *
     */
    public static function validateDateTimeString($datetime, $isEmptyOK=false){
        if($isEmptyOK && empty($datetime)){
            return true;
        }

        if((!empty($datetime) && strlen($datetime) < 19)){
            return false;
        }

        if(strpos($datetime, '/') !== false){
            // '/'区切り
            return ($datetime === date("Y/m/d H:i:s", strtotime($datetime)));

        }elseif(strpos($datetime, '-') !== false){
            // '-'区切り
            return ($datetime === date("Y-m-d H:i:s", strtotime($datetime)));
        }
        return false;
    }

    /**
     * 呼び出された時点での曜日を番号で返す。
     * 1:月曜日 2:火曜日 ... 7:日曜日
     * @return int
     */
    public static function getDayNumber() {
        $dayNumber = date('w');//日:0  月:1  火:2  水:3  木:4  金:5  土:6
        return ($dayNumber < 1) ? 7: $dayNumber; //日:7 とする
    }

    /**
     * 指定したxx日後の日付けを取得する
     * 負数の場合はxx日前となる
     */
    public static function getSpecifiedDate($spec) {
        $dt = Carbon::now();
        return $dt->addDay($spec);
    }



}

