<?php

namespace App\Enums;

use App\Enums\BaseEnum;

/**
 * TaskType enum
 */
class TaskType extends BaseEnum
{
    const TASK_GRAMMAR          = '1'; // 1: 文法
    const TASK_VOCABULARY       = '2'; // 2: 語彙
    const TASK_SPEAKING_RALLY   = '3'; // 3: SR
    const TASK_REVIEW           = '4'; // 4: 復習
    const TASK_OTHERS           = '5'; // 5: その他

    const TASK_GRAMMAR_VOCABULARY = '100'; // 1: 文法と2: 語彙


    //レッスン復習
    const TASK_TYPE_RV_LESSON                 = '30';

    //単語カード
    const TASK_TYPE_WORD_CARD                 = '40';

    //リスニングトレーニング：下記を使用
    const TASK_TYPE_LISTENING_B               = '50'; //リスニングトレーニング B
    const TASK_TYPE_LISTENING_C               = '51'; //リスニングトレーニング C
    const TASK_TYPE_LISTENING_D               = '52'; //リスニングトレーニング D

    //事前アンケート
    const TASK_TYPE_REVIEW_BEFORE_LESSON      = '70';

    //レッスンレビュー
    const TASK_TYPE_REVIEW_LESSON             = '71';

    //カウンセラーレビュー
    const TASK_TYPE_REVIEW_COUNSELOR          = '72';

    //サービスレビュー
    const TASK_TYPE_REVIEW_SERVICE            = '73';




    const TASK_GRAMMAR_NAME                     = '瞬間口頭英作 文法'; // 1: 文法
    const TASK_VOCABULARY_NAME                  = '瞬間口頭英作 語彙'; // 2: 語彙
    const TASK_SPEAKING_RALLY_NAME              = 'Speaking Rally'; // 3: SR
    const TASK_REVIEW_NAME                      = 'レッスン復習'; // 4: 復習
    const TASK_OTHERS_NAME                      = 'その他'; // 5: その他

    //レッスン復習
    const TASK_TYPE_RV_LESSON_NAME              = 'レッスン復習';

    //単語カード
    const TASK_TYPE_WORD_CARD_NAME              = '単語カード';

    //リスニングトレーニング：下記を使用
    const TASK_TYPE_LISTENING_B_NAME            = 'リスニングトレーニング B'; //リスニングトレーニング B
    const TASK_TYPE_LISTENING_C_NAME            = 'リスニングトレーニング C'; //リスニングトレーニング C
    const TASK_TYPE_LISTENING_D_NAME            = 'リスニングトレーニング D'; //リスニングトレーニング D

    //事前アンケート
    const TASK_TYPE_REVIEW_BEFORE_LESSON_NAME   = '事前アンケート';

    //レッスンレビュー
    const TASK_TYPE_REVIEW_LESSON_NAME          = 'レッスンレビュー';

    //カウンセラーレビュー
    const TASK_TYPE_REVIEW_COUNSELOR_NAME       = 'カウンセラーレビュー';

    //サービスレビュー
    const TASK_TYPE_REVIEW_SERVICE_NAME         = 'サービスレビュー';


}

