<?php

namespace App\Dtos;

/**
 * MemberReportDto
 * 会員レポート情報
 *
 * レポートグラフ情報と、レポートPDF情報を兼用する
 */
class MemberReportDto {

    public $userId;
    public $arugoUserId;

    public $memberName;
    public $memberNameEn;

    public $contractPeriodSince;
    public $contractPeriodUntil;
    public $lessonPeriodSince;
    public $lessonPeriodUntil;

    public $reportCreatedAt;        //レポート発行日
    public $beforeReportCreatedAt;  //前回レポート発行日

    public $reportName; // レポート名
    public $s3Url;      // レポートPDFの場合のS3URL
    public $remark;     // 備考

    public $isPosted;
    public $postedAt;

    public $chartList;  // レポートグラフ画像情報

    public $grammarRates;   // grammarRate用表データ

    public $gSampleDatas; // g-sample用表データ

    public $accumulatedLessonTicketsDatas; //累積レッスンチケット消化データ
    public $accumulatedTaskCompleteRates;  //累積タスク完了数データ

}
