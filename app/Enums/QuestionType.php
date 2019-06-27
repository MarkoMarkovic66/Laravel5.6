<?php
namespace App\Enums;

use App\Enums\BaseEnum;

/**
 * TaskType enum
 * 課題種別
 */
class QuestionType extends BaseEnum
{
    //Used by ALUGO APP
    //瞬間口頭G
    const QUESTION_TYPE_GRAMMAR                   = '1';            // 文法
    const QUESTION_TYPE_GRAMMAR_NAME              = '瞬間口頭英作G'; // 文法
    const QUESTION_TYPE_GRAMMAR_ICON              = 'GRAMMAR';      // 文法

    //瞬間口頭V
    const QUESTION_TYPE_VOCABULARY                = '2';            // 語彙
    const QUESTION_TYPE_VOCABULARY_NAME           = '瞬間口頭英作V'; // 語彙
    const QUESTION_TYPE_VOCABULARY_ICON           = 'VOCABULARY';   // 語彙

    //Speaking Rally
    const QUESTION_TYPE_SPEAKING_RALLY            = '3';
    const QUESTION_TYPE_SPEAKING_RALLY_NAME       = 'Speaking Rally';
    const QUESTION_TYPE_SPEAKING_RALLY_ICON       = 'SPEAKING_RALLY';

    //レッスン復習
    const QUESTION_TYPE_RV_LESSON                 = '30';
    const QUESTION_TYPE_RV_LESSON_NAME            = 'レッスン復習';
    const QUESTION_TYPE_RV_LESSON_ICON            = 'RV_LESSON';

    //単語カード
    const QUESTION_TYPE_WORD_CARD                 = '40';
    const QUESTION_TYPE_WORD_CARD_NAME            = '単語カード';
    const QUESTION_TYPE_WORD_CARD_ICON            = 'WORD_CARD';

    //リスニングトレーニング：下記を使用
    // 2018-10-04 added
    const QUESTION_TYPE_LISTENING_B               = '50'; //リスニングトレーニング B
    const QUESTION_TYPE_LISTENING_C               = '51'; //リスニングトレーニング C
    const QUESTION_TYPE_LISTENING_D               = '52'; //リスニングトレーニング D

    const QUESTION_TYPE_LISTENING_B_NAME          = 'リスニングトレーニング B'; //リスニングトレーニング B
    const QUESTION_TYPE_LISTENING_C_NAME          = 'リスニングトレーニング C'; //リスニングトレーニング C
    const QUESTION_TYPE_LISTENING_D_NAME          = 'リスニングトレーニング D'; //リスニングトレーニング D


    //その他タスク：α版では未使用
    const QUESTION_TYPE_OTHER_05                  = '5';
    const QUESTION_TYPE_OTHER_05_NAME             = 'Listening D';
    const QUESTION_TYPE_OTHER_05_ICON             = 'LISTENING_D';

    const QUESTION_TYPE_OTHER_06                  = '6';
    const QUESTION_TYPE_OTHER_06_NAME             = 'Listening C';
    const QUESTION_TYPE_OTHER_06_ICON             = 'LISTENING_C';

    const QUESTION_TYPE_OTHER_07                  = '7';
    const QUESTION_TYPE_OTHER_07_NAME             = 'Listening B';
    const QUESTION_TYPE_OTHER_07_ICON             = 'LISTENING_B';

    //その他タスク：SpeakingRally投稿：α版では未使用
    const QUESTION_TYPE_OTHER_13                  = '13';
    const QUESTION_TYPE_OTHER_13_NAME             = 'Speaking Rally投稿';
    const QUESTION_TYPE_OTHER_13_ICON             = 'SPEAKING_RALLY';

    //※下記を追加してください

    //事前アンケート
    const QUESTION_TYPE_REVIEW_BEFORE_LESSON      = '70';
    const QUESTION_TYPE_REVIEW_BEFORE_LESSON_NAME = '事前アンケート';
    const QUESTION_TYPE_REVIEW_BEFORE_LESSON_ICON = 'REVIEW_BEFORE_LESSON';

    //レッスンレビュー
    const QUESTION_TYPE_REVIEW_LESSON             = '71';
    const QUESTION_TYPE_REVIEW_LESSON_NAME        = 'レッスンレビュー';
    const QUESTION_TYPE_REVIEW_LESSON_ICON        = 'REVIEW_LESSON';

    //カウンセラーレビュー
    const QUESTION_TYPE_REVIEW_COUNSELOR          = '72';
    const QUESTION_TYPE_REVIEW_COUNSELOR_NAME     = 'カウンセラーレビュー';
    const QUESTION_TYPE_REVIEW_COUNSELOR_ICON     = 'REVIEW_COUNSELOR';

    //サービスレビュー
    const QUESTION_TYPE_REVIEW_SERVICE            = '73';
    const QUESTION_TYPE_REVIEW_SERVICE_NAME       = 'サービスレビュー';
    const QUESTION_TYPE_REVIEW_SERVICE_ICON       = 'REVIEW_SERVICE';


}
