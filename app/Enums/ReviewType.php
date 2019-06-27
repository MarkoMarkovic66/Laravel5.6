<?php
namespace App\Enums;
use App\Enums\BaseEnum;

/**
 * ReviewType アンケート区分
 * 0:受講開始前アンケート 1:カウンセリングレビュー 2:レッスンレビュー 3:サービスレビュー
 */
class ReviewType extends BaseEnum {
    const REVIEW_BEFORE_LESSON      = '0'; //受講開始前アンケート
    const REVIEW_COUNSELING         = '1'; //カウンセリングレビュー
    const REVIEW_LESSON             = '2'; //レッスンレビュー
    const REVIEW_SERVICE            = '3'; //サービスレビュー

    const REVIEW_BEFORE_LESSON_TEXT = '受講開始前アンケート';
    const REVIEW_COUNSELING_TEXT    = 'カウンセリングレビュー';
    const REVIEW_LESSON_TEXT        = 'レッスンレビュー';
    const REVIEW_SERVICE_TEXT       = 'サービスレビュー';

}
