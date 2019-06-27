@extends('layouts.member-report-master')

@section('styles')
@endsection
@php
use App\Utils\DateUtils;

    $memberReportInfo = $view_params['memberReportInfo'];

    //会員基本情報
    $userId       = $memberReportInfo->userId;
    $memberName   = $memberReportInfo->memberName;
    $memberNameEn = $memberReportInfo->memberNameEn;
    $arugoUserId  = $memberReportInfo->arugoUserId;
    //レポート名
    //$reportName = $memberReportInfo->reportName;
    $reportName = DateUtils::convertToYYMMDD($memberReportInfo->reportCreatedAt);
    //レポート発行日
    $issueDate = $memberReportInfo->reportCreatedAt;
    $issueDateHyphen = DateUtils::convertToYYMMDD_HyphenDelimited($issueDate);

    //前回レポート発行日
    $beforeIssueDate = $memberReportInfo->beforeReportCreatedAt;
    //契約期間
    $contractPeriodSince = $memberReportInfo->contractPeriodSince;
    $contractPeriodUntil = $memberReportInfo->contractPeriodUntil;
    $contractPeriod      = '';
    if(!empty($contractPeriodSince) || !empty($contractPeriodUntil)){
        $contractPeriod = $contractPeriodSince . ' - ' . $contractPeriodUntil;
    }
    //レッスン受講期間
    $lessonPeriodSince   = $memberReportInfo->lessonPeriodSince;
    $lessonPeriodUntil   = $memberReportInfo->lessonPeriodUntil;
    $lessonPeriod        = '';
    if(!empty($lessonPeriodSince) || !empty($lessonPeriodUntil)){
        $lessonPeriod = $lessonPeriodSince . ' - ' . $lessonPeriodUntil;
    }

    //グラフ画像情報
    $chartTitle01   = '';
    $chartUrl01     = '';
    $chartRemark01  = '';
    $chartMessage01 = '';

    $chartTitle02   = '';
    $chartUrl02     = '';
    $chartRemark02  = '';
    $chartMessage02 = '';

    $chartTitle03   = '';
    $chartUrl03     = '';
    $chartRemark03  = '';
    $chartMessage03 = '';

    $chartTitle04   = '';
    $chartUrl04     = '';
    $chartRemark04  = '';
    $chartMessage04 = '';

    $chartTitle05   = '';
    $chartUrl05     = '';
    $chartRemark05  = '';
    $chartMessage05 = '';

    $chartTitle10   = '';
    $chartUrl10     = '';
    $chartRemark10  = '';
    $chartMessage10 = '';

    $chartTitle20   = '';
    $chartUrl20     = '';
    $chartRemark20  = '';
    $chartMessage20 = '';

    $chartTitle21   = '';
    $chartUrl21     = '';
    $chartRemark21  = '';
    $chartMessage21 = '';

    $chartTitle22   = '';
    $chartUrl22     = '';
    $chartRemark22  = '';
    $chartMessage22 = '';

    $chartList = $memberReportInfo->chartList;
    if(count($chartList) > 0){
        foreach($chartList as $chartType => $row){
            if($chartType == 1){
                $chartTitle01   = $row['chartTitle'];
                $chartUrl01     = $row['chartFilePath'];
                $chartRemark01  = $row['chartRemark'];
                $chartMessage01 = $row['chartMessage'];
            }elseif($chartType == 2){
                $chartTitle02   = $row['chartTitle'];
                $chartUrl02     = $row['chartFilePath'];
                $chartRemark02  = $row['chartRemark'];
                $chartMessage02 = $row['chartMessage'];
            }elseif($chartType == 3){
                $chartTitle03   = $row['chartTitle'];
                $chartUrl03     = $row['chartFilePath'];
                $chartRemark03  = $row['chartRemark'];
                $chartMessage03 = $row['chartMessage'];
            }elseif($chartType == 4){
                $chartTitle04   = $row['chartTitle'];
                $chartUrl04     = $row['chartFilePath'];
                $chartRemark04  = $row['chartRemark'];
                $chartMessage04 = $row['chartMessage'];
            }elseif($chartType == 5){
                $chartTitle05   = $row['chartTitle'];
                $chartUrl05     = $row['chartFilePath'];
                $chartRemark05  = $row['chartRemark'];
                $chartMessage05 = $row['chartMessage'];

            /** 2018-07-23 v1.2対応 グラフ画像追加 */
            }elseif($chartType == 10){
                 //予測グラフ
                $chartTitle10   = $row['chartTitle'];
                $chartUrl10     = $row['chartFilePath'];
                $chartRemark10  = $row['chartRemark'];
                $chartMessage10 = $row['chartMessage'];

            }elseif($chartType == 20){
                //統計グラフ01
                $chartTitle20   = $row['chartTitle'];
                $chartUrl20     = $row['chartFilePath'];
                $chartRemark20  = $row['chartRemark'];
                $chartMessage20 = $row['chartMessage'];

            }elseif($chartType == 21){
                //統計グラフ02
                $chartTitle21   = $row['chartTitle'];
                $chartUrl21     = $row['chartFilePath'];
                $chartRemark21  = $row['chartRemark'];
                $chartMessage21 = $row['chartMessage'];

            }elseif($chartType == 22){
                //統計グラフ03
                $chartTitle22   = $row['chartTitle'];
                $chartUrl22     = $row['chartFilePath'];
                $chartRemark22  = $row['chartRemark'];
                $chartMessage22 = $row['chartMessage'];
            }
        }
    }

    // grammarRate用表データ
    $grammarRates = $memberReportInfo->grammarRates;

    // grammarRate用表の作成
    $grammarRateTableHeaderTitle = trans('member_report_chart.chart06_title_ja');
    $grammarRateTableHtml = '';
    if(count($grammarRates) < 1){
        $grammarRateTableHtml = '';
    }else{
        $grammarRateTableHtml =
                  '<div class="grammar-rate-table">'
                    . '<div class="grammar-rate-table-row-top">'
                        . '<div class="row-head-top"><br /></div>'
                        . '<div class="row-head">今回発行時</div>'
                        . '<div class="row-head">前回発行時</div>'
                        . '<div class="row-head">前回からの差分</div>'
                    . '</div>';

        foreach($grammarRates as $idx => $grammarRate){
            if($idx < 5){
                $grammarRateTableHtml .= '<div class="grammar-rate-table-row">';
            }else{
                $grammarRateTableHtml .= '<div class="grammar-rate-table-row-last">';
            }
            $grammarRateTableHtml .=
                          '<div class="cell-head">' . $grammarRate['g_name']         . '</div>'
                        . '<div class="cell-data">' . $grammarRate['latest_value']   . '</div>'
                        . '<div class="cell-data">' . $grammarRate['previous_value'] . '</div>'
                        . '<div class="cell-data" style="color:' . $grammarRate['diffValueColor'] .'">'. $grammarRate['diff_value'] . '</div>'
                    . '</div>';
        }
        $grammarRateTableHtml .= '</div>'
                            . '<div class="member-chart-rate-table-ps">※50.0が標準的な頻度</div>';
    }

    // g-sample用表データ
    $gSampleDatas = $memberReportInfo->gSampleDatas;

    //累積レッスンチケット消化データ
    $accumulatedLessonTicketsDatas = $memberReportInfo->accumulatedLessonTicketsDatas;

    //累積タスク完了数データ取得
    $accumulatedTaskCompleteRates = $memberReportInfo->accumulatedTaskCompleteRates;
    //表を見やすくするためにデータを整形する
    $accumulatedTaskCompleteRateDatas = [];
    $rowDatas = [];
    $countedAt = '';
    $rowCount = 0;
    foreach($accumulatedTaskCompleteRates as $idx => $row){
        if(empty($countedAt)){
            $rowDatas = [];
            $rowCount = 0;
            $countedAt = $row->counted_at;

        }elseif($countedAt !== $row->counted_at){
            $accumulatedTaskCompleteRateDatas[] = [
                'countedAt' => $countedAt,
                'rowCount'  => $rowCount,
                'rowDatas'  => $rowDatas
            ];

            $rowDatas = [];
            $rowCount = 0;
            $countedAt = $row->counted_at;
        }

        $rowDatas[] = $row;
        $rowCount ++;
    }
    $accumulatedTaskCompleteRateDatas[] = [
        'countedAt' => $countedAt,
        'rowCount'  => $rowCount,
        'rowDatas'  => $rowDatas
    ];


    //会員レポートの概要説明
    $overview  = trans('member_report_chart.20180804_member_report_overview');

    //会員レポートの各種キャプション
    //ブロック01
    $block01Title  = trans('member_report_chart.20180804_member_report_block01_title');
    $block01Header = trans('member_report_chart.20180804_member_report_block01_header');

    $block01Remark01 = trans('member_report_chart.20180804_member_report_block01_remark01');
    $block01Remark02 = trans('member_report_chart.20180804_member_report_block01_remark02');
    $block01Remark03 = trans('member_report_chart.20180804_member_report_block01_remark03');
    $block01Remark04 = trans('member_report_chart.20180804_member_report_block01_remark04');
    $block01Remark05 = trans('member_report_chart.20180804_member_report_block01_remark05');

    //ブロック02
    $block02Title  = trans('member_report_chart.20180804_member_report_block02_title');
    $block02Header = trans('member_report_chart.20180804_member_report_block02_header');

    //ブロック03
    $block03Title  = trans('member_report_chart.20180804_member_report_block03_title');
    $block03Header = trans('member_report_chart.20180804_member_report_block03_header');

    //ブロック04
    $block04Title  = trans('member_report_chart.20180804_member_report_block04_title');
    $block04Header = trans('member_report_chart.20180804_member_report_block04_header');

    $block04Remark01 = trans('member_report_chart.20180804_member_report_block04_remark01');
    $block04Remark02 = trans('member_report_chart.20180804_member_report_block04_remark02');

    //ブロック05
    $block05Title  = trans('member_report_chart.20180804_member_report_block05_title');
    $block05Header = trans('member_report_chart.20180804_member_report_block05_header');

    //ブロック06
    $block06Title  = trans('member_report_chart.20180804_member_report_block06_title');
    $block06Header = trans('member_report_chart.20180804_member_report_block06_header');

    //ブロック07
    $block07Title  = trans('member_report_chart.20180804_member_report_block07_title');
    $block07Header = trans('member_report_chart.20180804_member_report_block07_header');

    //ブロック08
    $block08Title  = trans('member_report_chart.20180804_member_report_block08_title');
    $block08Header = trans('member_report_chart.20180804_member_report_block08_header');

@endphp

@section('body-contents')
    <div class="overall">

        <!-- 1ページ目ヘッダ -->
        <div class="hero-section">
            <div class="secondary-item">
              <span class="report-top-title">Mr./Ms. {{$memberNameEn}} のレッスンレポート (ALUGO ID: {{$arugoUserId}})</span>
              <span class="report-top-title-issue">{{$reportName}}</span>
              <input type="hidden" name="userId" value="{{ $userId }}" />
              <input type="hidden" name="issueDate" value="{{ $issueDateHyphen }}" />
            </div>
        </div>

        <!-- 1ページ目コンテンツ -->
        <div class="detail-box">
            <div class="member-report-box-pdf_20180804">

                <div class="member-info-box">
                    <table name="member-basic-info">
                        <tr>
                            <th>発行日</th>
                            <td>{{ $issueDate }}<br /></td>
                            <th>前回発行日</th>
                            <td>{{ $beforeIssueDate }}<br /></td>
                        </tr>
                        <tr>
                            <th>レッスン受講期間</th>
                            <td>{{ $lessonPeriod }}<br /></td>
                            <th>ご契約期間</th>
                            <td>{{ $contractPeriod }}<br /></td>
                        </tr>
                    </table>
                </div>

                <!-- ブロック01 -->
                <div class="report-block">
                    <div class="report-block-title">{{ $block01Title }}</div>
                    <div class="report-block-title-line"><div class="line1"></div><div class="line2"></div></div>
                    <div class="report-block-header"><span>{{ $block01Header }}</span></div>
                    <div class="report-block-chart-box">

                        <div class="report-block-chart-wrapper">
                            <!-- 予測グラフ -->
                            <div class="report-block-chart-image-forecast">
                                <div class="chart-image">
                                @if(empty($chartUrl10))
                                    <div class="msg">該当データがありません</div>
                                @else
                                    <div class="img"><img src="{{ $chartUrl10 }}"></div>
                                @endif
                                </div>
                            </div><!-- report-block-chart-image-forecast -->
                        </div><!-- report-block-chart-wrapper -->

                        <div class="chart-image-forecast-remark">
                            <div class="remark01">{{ $block01Remark01 }}</div>
                            <div class="remark02">{{ $block01Remark02 }}</div>
                            <div class="remark03">{{ $block01Remark03 }}</div>
                            <div class="remark04">{{ $block01Remark04 }}</div>
                            <div class="remark05">{{ $block01Remark05 }}</div>
                        </div><!-- report-block-chart-remark -->

                    </div><!-- report-block-chart-box -->
                </div><!-- report-block -->

                <!-- ブロック02 -->
                <div class="report-block">
                    <div class="report-block-title">{{ $block02Title }}</div>
                    <div class="report-block-title-line"><div class="line1"></div><div class="line2"></div></div>
                    <div class="report-block-header"><span>{{ $block02Header }}</span></div>
                    <div class="report-block-chart-box">

                        <div class="report-block-chart-wrapper">
                            <div class="report-block-chart-image-double">
                                <div class="chart-image">
                                    <div class="chart-image-title">受講レッスン数</div>
                                @if(empty($chartUrl20))
                                    <div class="msg">該当データがありません</div>
                                @else
                                    <div class="img"><img src="{{ $chartUrl20 }}"></div>
                                @endif
                                </div>
                            </div><!-- report-block-chart-image-double -->

                            <div class="report-block-chart-image-space"></div>

                            <div class="report-block-chart-image-double">
                                <div class="chart-image">
                                    <div class="chart-image-title">宿題実施率</div>
                                @if(empty($chartUrl21))
                                    <div class="msg">該当データがありません</div>
                                @else
                                    <div class="img"><img src="{{ $chartUrl21 }}"></div>
                                @endif
                                </div>
                            </div><!-- report-block-chart-image-double -->
                        </div><!-- report-block-chart-wrapper -->

                    </div><!-- report-block-chart-box -->
                </div><!-- report-block -->

                <!-- ブロック03 -->
                <div class="report-block">
                    <div class="report-block-title">{{ $block03Title }}</div>
                    <div class="report-block-title-line"><div class="line1"></div><div class="line2"></div></div>
                    <div class="report-block-header"><span>{{ $block03Header }}</span></div>
                    <div class="report-block-chart-box">

                        <div class="report-block-chart-wrapper">
                            <div class="report-block-chart-image-double">
                                <div class="chart-image">
                                    <div class="chart-image-title">単語「総数」</div>
                                @if(empty($chartUrl01))
                                    <div class="msg">該当データがありません</div>
                                @else
                                    <div class="img"><img src="{{ $chartUrl01 }}"></div>
                                @endif
                                </div>
                            </div><!-- report-block-chart-image-double -->

                            <div class="report-block-chart-image-space"></div>

                            <div class="report-block-chart-image-double">
                                <div class="chart-image">
                                    <div class="chart-image-title">単語「種類数」</div>
                                @if(empty($chartUrl02))
                                    <div class="msg">該当データがありません</div>
                                @else
                                    <div class="img"><img src="{{ $chartUrl02 }}"></div>
                                @endif
                                </div>
                            </div><!-- report-block-chart-image-double -->
                        </div><!-- report-block-chart-wrapper -->

                    </div><!-- report-block-chart-box -->
                </div><!-- report-block -->

                <!-- ブロック04 -->
                <div class="report-block">
                    <div class="report-block-title">{{ $block04Title }}</div>
                    <div class="report-block-title-line"><div class="line1"></div><div class="line2"></div></div>
                    <div class="report-block-header"><span>{{ $block04Header }}</span></div>
                    <div class="report-block-chart-box">

                        <div class="report-block-chart-wrapper">
                            <div class="report-block-chart-image-double">
                                <div class="chart-image">
                                    <div class="chart-image-title">使用頻度の高い語彙</div>
                                @if(empty($chartUrl03))
                                    <div class="msg">該当データがありません</div>
                                @else
                                    <div class="img"><img src="{{ $chartUrl03 }}"></div>
                                    <div class="report-block04-chart-remark">
                                        <div class="">{{ $block04Remark01 }}</div>
                                    </div>
                                @endif
                                </div>
                            </div><!-- report-block-chart-image-double -->

                            <div class="report-block-chart-image-space"></div>

                            <div class="report-block-chart-image-double">
                                <div class="chart-image">
                                    <div class="chart-image-title">使用を推奨する語彙</div>
                                @if(empty($chartUrl04))
                                    <div class="msg">該当データがありません</div>
                                @else
                                    <div class="img"><img src="{{ $chartUrl04 }}"></div>
                                    <div class="report-block04-chart-remark">
                                        <div class="">{{ $block04Remark02 }}</div>
                                    </div>
                                @endif
                                </div>
                            </div><!-- report-block-chart-image-double -->
                        </div><!-- report-block-chart-wrapper -->

                    </div><!-- report-block-chart-box -->
                </div><!-- report-block -->

                <!-- ブロック05 -->
                <div class="report-block">
                    <div class="report-block-title">{{ $block05Title }}</div>
                    <div class="report-block-title-line"><div class="line1"></div><div class="line2"></div></div>
                    <div class="report-block-header"><span>{{ $block05Header }}</span></div>
                    <div class="report-block-chart-box">

                        <div class="report-block-chart-wrapper">
                            <!-- 文法パイチャート -->
                            <div class="report-block05-chart-image-pie">
                                <div class="chart-image">
                                    <div class="chart-image-title">文法頻度分析チャート</div>
                                @if(empty($chartUrl05))
                                    <div class="msg">該当データがありません</div>
                                @else
                                    <div class="img"><img src="{{ $chartUrl05 }}"></div>
                                @endif
                                </div>
                            </div><!-- report-block-chart-image-pie -->

                            <div class="report-block05-grammar-rate-table">
                                <div class="chart-image">
                                    <div class="chart-image-title">文法頻度分析一覧表</div>
                                @if(empty($grammarRateTableHtml))
                                    <div class="msg">該当データがありません</div>
                                @else
                                    <div class="grammar-rate-table-wrapper">{!! $grammarRateTableHtml !!}</div>
                                @endif
                                </div>
                            </div><!-- report-block05-grammar-rate-table -->
                        </div><!-- report-block-chart-wrapper -->

                    </div><!-- report-block-chart-box -->
                </div><!-- report-block -->


                <!-- ブロック06 -->
                <div class="report-block">
                    <div class="report-block-title">{{ $block06Title }}</div>
                    <div class="report-block-title-line"><div class="line1"></div><div class="line2"></div></div>
                    <div class="report-block-header"><span>{{ $block06Header }}</span></div>
                    <div class="report-block-chart-box">

                        <div class="member-g-sample-box">
                            <div class="member-g-sample-data">
                        @if(count($gSampleDatas) > 0)
                            <table>
                                <tr>
                                    <th>No</th>
                                    <th>g_index</th>
                                    <th>g_name</th>
                                    <th>lesson_time</th>
                                    <th>TF</th>
                                    <th>sentence</th>
                                </tr>
                            @foreach($gSampleDatas as $idx => $gSampleData)
                                <tr>
                                    <td>{{ intval($idx)+1 }}</td>
                                    <td>{{ $gSampleData['g_index'] }}</td>
                                    <td>{{ $gSampleData['g_name'] }}</td>
                                    <td>{{ $gSampleData['lesson_time'] }}</td>
                                    <td>{{ $gSampleData['tf_flag'] }}</td>
                                    <td>{{ $gSampleData['sentence'] }}</td>
                                </tr>
                            @endforeach
                            </table>
                        @else
                            <span>該当データがありません</span>
                        @endif
                            </div>
                        </div><!-- member-g-sample-box -->

                    </div><!-- report-block-chart-box -->
                </div><!-- report-block -->

                <!-- ブロック07 -->
                <div class="report-block">
                    <div class="report-block-title">{{ $block07Title }}</div>
                    <div class="report-block-title-line"><div class="line1"></div><div class="line2"></div></div>
                    <div class="report-block-header"><span>{{ $block07Header }}</span></div>
                    <div class="report-block-chart-box">

                        <div class="member-lesson-ticket-box">
                            <div class="member-lesson-ticket-data">
                        @if(count($accumulatedLessonTicketsDatas) > 0)
                            <table>
                                <tr>
                                    <th>No</th>
                                    <th>集計日</th>
                                    <th>受講期間契約日数</th>
                                    <th>受講開始からの経過日数</th>
                                    <th>全チケット枚数</th>
                                    <th>消化済みチケット枚数</th>
                                    <th>チケット消化率</th>
                                </tr>
                            @foreach($accumulatedLessonTicketsDatas as $idx => $row)
                                <tr>
                                    <td>{{ intval($idx)+1 }}</td>
                                    <td>{{ $row->counted_at }}</td>
                                    <td>{{ $row->contract_term_days }}</td>
                                    <td>{{ $row->elapsed_days }}</td>
                                    <td>{{ $row->tickets_total }}</td>
                                    <td>{{ $row->tickets_used }}</td>
                                    <td>{{ $row->tickets_used_rate }}</td>
                                </tr>
                            @endforeach
                            </table>
                        @else
                            <span>該当データがありません</span>
                        @endif
                            </div>
                        </div><!-- member-lesson-ticket-box -->

                    </div><!-- report-block-chart-box -->
                </div><!-- report-block -->

                <!-- ブロック08 -->
                <div class="report-block">
                    <div class="report-block-title">{{ $block08Title }}</div>
                    <div class="report-block-title-line"><div class="line1"></div><div class="line2"></div></div>
                    <div class="report-block-header"><span>{{ $block08Header }}</span></div>
                    <div class="report-block-chart-box">

                        <div class="member-task-progress-box">
                            <div class="member-task-progress-data">

                        @if(count($accumulatedTaskCompleteRates) > 0)
                            <table>
                                <tr>
                                    <th>No</th>
                                    <th>集計日</th>
                                    <th>タスク種別</th>
                                    <th>配信済みタスク累積数</th>
                                    <th>完了済みタスク累積数</th>
                                    <th>タスク消化率</th>
                                </tr>
                            @php
                            $totalIdx = 1;
                            $countedAtBreak = '';
                            foreach($accumulatedTaskCompleteRateDatas as $rateDatas){
                                $countedAt = $rateDatas['countedAt'];
                                $rowCount  = $rateDatas['rowCount'];
                                $rowDatas  = $rateDatas['rowDatas'];

                                foreach($rowDatas as $row){
                                    if(empty($countedAtBreak) || ($countedAtBreak !== $row->counted_at) ){
                                        $countedAtBreak = $row->counted_at;
                                        @endphp
                                        {!! '<tr>' !!}
                                        {!! '<td>' . $totalIdx . '</td>' !!}
                                        {!! '<td rowspan="' . $rowCount . '">' . $row->counted_at . '</td>' !!}
                                        {!! '<td class="arrign-left">' . $row->task_type_name . '</td>' !!}
                                        {!! '<td>' . $row->sent_task_count . '</td>' !!}
                                        {!! '<td>' . $row->completed_task_count . '</td>' !!}
                                        {!! '<td>' . $row->completed_task_rate . '</td>' !!}
                                        {!! '</tr>' !!}
                                        @php

                                    }else{
                                        @endphp
                                        {!! '<tr>' !!}
                                        {!! '<td>' . $totalIdx . '</td>' !!}
                                        {!! '<td class="arrign-left">' . $row->task_type_name . '</td>' !!}
                                        {!! '<td>' . $row->sent_task_count . '</td>' !!}
                                        {!! '<td>' . $row->completed_task_count . '</td>' !!}
                                        {!! '<td>' . $row->completed_task_rate . '</td>' !!}
                                        {!! '</tr>' !!}
                                        @php
                                    }//end if

                                    $totalIdx ++;
                                }//foreach
                            }//foreach
                            @endphp
                            </table>

                        @else
                            <span>該当データがありません</span>
                        @endif
                            </div>
                        </div><!-- member-task-progress-box -->

                    </div><!-- report-block-chart-box -->
                </div><!-- report-block -->

            </div><!-- member-report-box-pdf_20180804 -->
        </div><!-- detail-box -->

    </div><!-- overall -->
@endsection

@section('scripts')
<script src="{{ mix('/js/member_report.js') }}"></script>
@endsection

