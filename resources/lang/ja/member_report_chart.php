<?php
/**
 * 会員レポート各種グラフについてのキャプション情報
 */
return [

    //グラフ01
    "chart01_title_en" => "Overview of Speaking skill",
    "chart01_title_ja" => "1問あたりの回答に使用した単語数の分布",
    "chart01_remark"   => "",
    "chart01_message"  => "",

    //グラフ02
    "chart02_title_en" => "Overview of Speaking skill",
    "chart02_title_ja" => "1問あたりの回答に使用した語彙数（種類）の分布",
    "chart02_remark"   => "",
    "chart02_message"  => "",

    //グラフ03
    "chart03_title_en" => "Used words",
    "chart03_title_ja" => "あなたが英語レッスンでよく使用した語彙",
    "chart03_remark"   => "",
    "chart03_message"  => "",

    //グラフ04
    "chart04_title_en" => "Recommended words",
    "chart04_title_ja" => "今後のレッスンで使用を推奨する語彙",
    "chart04_remark"   => "",
    "chart04_message"  => "",

    //グラフ05
    "chart05_title_en" => "Grammar Analysis",
    "chart05_title_ja" => "文法項目毎の利用傾向分析",
    "chart05_remark"   => "",
    "chart05_message"  => "",

    //グラフ06
    "chart06_title_en" => "Grammar Analysis(evaluation value)",
    "chart06_title_ja" => "文法項目毎の利用傾向分析(各数値)",
    "chart06_remark"   => "",
    "chart06_message"  => "",

    //グラフ07
    "chart07_title_en" => "",
    "chart07_title_ja" => "",
    "chart07_remark"   => "",
    "chart07_message"  => "",

    //グラフ10
    "chart10_title_en" => "予測グラフ",
    "chart10_title_ja" => "予測グラフ",
    "chart10_remark"   => "",
    "chart10_message"  => "",

    //グラフ20
    "chart20_title_en" => "統計値：セッション数グラフ",
    "chart20_title_ja" => "統計値：セッション数グラフ",
    "chart20_remark"   => "",
    "chart20_message"  => "",

    //グラフ21
    "chart21_title_en" => "統計値：タスク完了率グラフ",
    "chart21_title_ja" => "統計値：タスク完了率グラフ",
    "chart21_remark"   => "",
    "chart21_message"  => "",


    //会員レポートの概要説明
    "member_report_overview" => "当レポートはこれまでのALUGO受講生による発話データを元に、受講生一人ひとりにおける英会話力の傾向を分析して可視化したものです。<br />"
                        . "毎週のレポート結果を比較することでご自身の成長度合いを測ると共に、現状の苦手を克服するための助けとしてもご利用ください。<br />"
                        . "※英会話力の解析にあたっては、直近30問分の英語レッスンでの応答データを参照しております。",

    //グラフBox-01
    "chart_box_title_01" => "◆ 英語レッスンにおけるレスポンス力の推移（overview of Speaking skill）",
    "chart_box_header_01" => "アセスメント受験から本日までの週毎の英語レッスンにおけるレスポンス力を評価・分析しております。<br />"
                           . "ここまでの英語力スコア推移を成長予測と比較するとともに、前週と比較することで直近１週間での伸び幅にも着目しましょう。",

    //グラフBox-01-1
    "chart_wrap_title_01_01" => "１．アセスメント結果に基づいた成長予測と英語レッスンにおける英会話力スコアの比較",
    "chart_wrap_header_01_01" => "アセスメント結果から予測した英会話力スコアの成長曲線と、毎週の英語レッスンでの応答データから算出した英会話力スコアを比較したグラフです。<br />"
                               . "★印で記載する英会話力スコアは、アセスメントの各indexの点数を合計したものです(500点満点)。",

    //グラフBox-01-1 (各グラフタイトル)
    "chart_title_01_01_01" => "受講前のアセスメントスコアから予測する成長予想曲線と英語レッスンでの英会話力の推移",
    "chart_header_01_01_01" => "こちらの成長予測曲線は、これまでの全ALUGO受講生の学習結果に基づいて、ALUGOにてデザインしたものです。<br />"
                             . "そして、棒グラフで表現している毎週のあなたの英会話力のスコアは、英語レッスンの応答内容から算出したもっとも確率の高いスコアです。<br />"
                             . "英語レッスンの受講を継続することで英会話力のスコア算出精度がより高まります。",

    "chart_caption_01_01_01_top" => "（★：アセスメントスコア、"
                                  . "<span style='font-weight:bold;color:red'>－</span>：成長予測曲線(トップ25%)、"
                                  . "<span style='font-weight:bold;color:blue'>－</span>：成長予測曲線(受講生平均)、"
                                  . "<span style='font-weight:bold;color:green'>｜</span>：アセスメントスコアの評価幅(確度70%)、"
                                  . "<span style='font-weight:bold;color:green'>￤</span>：アセスメントスコアの評価幅(確度90%) ）",
    "chart_caption_01_01_01_bottom" => "※英語レッスンでの応答データから算出される英会話力スコアの評価には一定の範囲(幅)があります。そしてほとんどの場合、あなたの英会話力スコアは確度90%の評価幅に収まります。",


    //グラフBox-01-2
    "chart_wrap_title_01_02" => "２．1問あたりの回答に使用した単語数、語彙数（種類）の分布",
    "chart_wrap_header_01_02" => "当レポート発行にあたって分析した直近30問においてあなたが1問あたりの回答に使用した単語数および語彙数(種類)の分布を示したグラフです。<br />"
                        . "（読み方の例：直近30問の中で、XX個の単語を使って回答した問題が、全部でｎ問ありました。等）<br />"
                        . "ALUGOから配信される例文の学習を通じて使える語彙や文法項目を増やしながらより多くの単語数(語彙数)で問題に回答できるようになりましょう。",

    //グラフBox-01-2 (各グラフタイトル)
    "chart_title_01_02_01" => "あなたが1問あたりの回答に使用した単語数の分布",
    "chart_title_01_02_02" => "あなたが1問あたりの回答に使用した語彙数（種類）の分布",


    //グラフBox-02
    "chart_box_title_02" => "◆ スピーキングにおける語彙と文法の特徴（detail）",
    "chart_box_header_02" => "", // 未使用

    //グラフBox-02-1
    "chart_wrap_title_02_01" => "１．語彙（Vocabulaty）に関する傾向分析結果と今後の課題",
    "chart_wrap_header_02_01" => "レッスンの中でよく使われた語彙（左）の中で、大きく表示されているものほど、頻繁に利用されているものです。<br />"
                            . "『よく使用した語彙』（左）と同程度の難易度で使用頻度の高い語彙を『使用を推奨する語彙』（右）として表示しております。<br />"
                            . "右に表示している単語を活用した例文集を次の宿題として配信しますので、着実にマスターしていきましょう。",

    "chart_title_02_01_01" => "あなたが英語レッスンでよく使用した語彙",
    "chart_title_02_01_02" => "今後のレッスンで使用を推奨する語彙",

    //グラフBox-02-2
    "chart_wrap_title_02_02" => "２．文法（Grammar）に関する傾向分析結果と今後の課題",
    "chart_wrap_header_02_02" => "レッスンの中で各文法項目ごとの使用頻度を数値で計算しております。<br />"
                                . "文法項目の値が50以上であれば、他の受講生と比べても頻繁に利用できていると判断できます。<br />"
                                . "例文集を次の宿題として配信していきますので、こちらも着実にマスターしてレッスン内で使いこなしていきましょう。",
    "chart_title_02_02_01" => "あなたの英語レッスンでの文法項目毎の利用傾向分析",

    //g-Sample表
    "g_sample_title_en" => "g-Sample",
    "g_sample_title_ja" => "g-Sample表",
    "g_sample_remark"   => "説明...",

    /**
     * 2018-07-23 v1.2対応
     */
    //会員レポートの概要説明（PDF専用 2ページ目）
    "member_report_overview2" => "2枚目では、1枚目で分析した結果の背景にあるこれまでの学習実績と、あなたの英語レッスンでの発話傾向を分析して可視化しています。<br />"
                               . "現状の苦手を今後、克服していくための助けとしてご活用ください。<br />"
                               . "※1枚目と同様、英会話力の解析にあたっては、直近30問分の英語レッスンでの応答データを参照しております。",

    //グラフBox-03 (統計グラフ)
    "chart_box_title_03" => "◆ 英語レッスンおよび宿題実施状況の推移（detail of Training process）",
    "chart_box_header_03" => "レッスンの中でよく使われた語彙（左）の中で、大きく表示されているものほど、頻繁に利用されているものです。<br />"
                            . "『よく使用した語彙』（左）と同程度の難易度で使用頻度の高い語彙を『使用を推奨する語彙』（右）として表示しております。<br />"
                            . "右に表示している単語を活用した例文集を次の宿題として配信しますので、着実にマスターしていきましょう。",
    //グラフBox-03-1
    "chart_wrap_title_03_01" => "",  // 未使用
    "chart_wrap_header_03_01" => "", // 未使用

    "chart_title_03_01_01" => "週毎の受講レッスン数推移",
    "chart_title_03_01_02" => "週毎の宿題実施率推移",
    "chart_title_03_01_03" => "", // 未使用

    /*
     * 2018-08-04
     * 会員レポート新版デザイン用
     * 固定キャプション
     */
    //会員レポートの概要説明
    "20180804_member_report_overview" =>
            "これまでのALUGO受講データを元に、機械学習を活用して、あなたの英語力を分析します。",

    //ブロック01
    "20180804_member_report_block01_title" => "１．あなたの成長予測",
    "20180804_member_report_block01_header" => "あなたの今後の成長予測と、レッスン内容から算出した現時点での推定の英語力スコアです。",

    "20180804_member_report_block01_remark01" => "■：70％の確率であなたが獲得できる英語力スコアの範囲",
    "20180804_member_report_block01_remark02" => "■：90％の確率であなたが獲得できる英語力スコアの範囲",
    "20180804_member_report_block01_remark03" => "★：直近のアセスメントスコア（ 5つの合計値、500点満点）",
    "20180804_member_report_block01_remark04" => "－：あなたの成長予想曲線（全受講生のTop25%の成長推移）",
    "20180804_member_report_block01_remark05" => "－：あなたの成長予想曲線（全受講生の平均的な成長推移）",

    //ブロック02
    "20180804_member_report_block02_title" => "２．あなたの取り組み状況",
    "20180804_member_report_block02_header" => "あなたの受講レッスン数と宿題実施率の推移です。",

    //ブロック03
    "20180804_member_report_block03_title" => "３．単語数の分析",
    "20180804_member_report_block03_header" => "直近のレッスンの中で発話した単語数の推移です。",

    //ブロック04
    "20180804_member_report_block04_title" => "４．語彙の分析",
    "20180804_member_report_block04_header" => "レッスン中に使用頻度の高い語彙と、表現の幅をもつために使用を推奨する語彙です。",

    "20180804_member_report_block04_remark01" => "大きさは「あなた」が使用している頻度を表現",
    "20180804_member_report_block04_remark02" => "大きさは「一般」に使われる頻度を表現",

    //ブロック05
    "20180804_member_report_block05_title" => "５．文法の分析",
    "20180804_member_report_block05_header" => "レッスン中にあなたが使っている基本6文法の頻度を偏差値にしたものです。(平均＝50)",

    //ブロック06
    "20180804_member_report_block06_title" => "６．g-sample表",
    "20180804_member_report_block06_header" => "",

    //ブロック07
    "20180804_member_report_block07_title" => "７．累積レッスンチケット消化データ",
    "20180804_member_report_block07_header" => "",

    //ブロック08
    "20180804_member_report_block08_title" => "８．累積タスク完了数データ",
    "20180804_member_report_block08_header" => "",

];
