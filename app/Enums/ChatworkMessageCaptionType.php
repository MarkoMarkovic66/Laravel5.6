<?php
namespace App\Enums;

use App\Enums\BaseEnum;

/**
 * ChatworkMessageCaptionType enum
 * チャットワーク投稿時に設問内容に応じて付加するヘッダのキーを表す。
 * このキーによりlang/ja/chatwork_message_caption.php より
 * 所定のヘッダキャプションを取得する。
 */
class ChatworkMessageCaptionType extends BaseEnum {
    const CW_MESSAGE_HEADER_G       = 'chatwork_message_caption.question_header_g';      //英作文（文法）キャプション
    const CW_MESSAGE_FOOTER_G       = 'chatwork_message_caption.question_footer_g';      //英作文（文法）キャプション

    const CW_MESSAGE_HEADER_V       = 'chatwork_message_caption.question_header_v';      //英作文（語彙）キャプション
    const CW_MESSAGE_FOOTER_V       = 'chatwork_message_caption.question_footer_v';      //英作文（語彙）キャプション

    const CW_MESSAGE_HEADER_SR      = 'chatwork_message_caption.question_header_sr';     //SRキャプション
    const CW_MESSAGE_FOOTER_SR      = 'chatwork_message_caption.question_footer_sr';     //SRキャプション

    const CW_MESSAGE_HEADER_REVIEW  = 'chatwork_message_caption.question_header_review'; //復習キャプション
    const CW_MESSAGE_FOOTER_REVIEW  = 'chatwork_message_caption.question_footer_review'; //復習キャプション

    const CW_MESSAGE_HEADER_OTHERS  = 'chatwork_message_caption.question_header_others'; //その他キャプション
    const CW_MESSAGE_FOOTER_OTHERS  = 'chatwork_message_caption.question_footer_others'; //その他キャプション

    const CW_MEMBER_REPORT_CAPTION  = 'chatwork_message_caption.chatwork_member_report_caption'; //会員レポートCW投稿時のキャプション

}
