<?php
namespace App\Services;

/**
 * Chatwork関連サービス
 */
class ChatworkService {

    /**
     * pushToChatworkのラッパー
     * @param array $params
     */
	public function sendChatworkMessage($params) {
        $cwRoomId = $params['roomid'];
        $title    = $params['title'];
        $message  = $params['message'];
        $this->pushToChatwork($cwRoomId, $title, $message);
    }

    /**
     * チャットワークに通知を飛ばす
     * @param type $chatwork_room_id
     * @param string $title
     * @param string $message
     */
	public function pushToChatwork($chatwork_room_id, $title, $message) {

		//利用申請で発行されたチャットワークAPIのtokenを指定(※2)
		$token = env('CW_API_TOKEN');

		if($title != '') {
            $title = "[title]".$title."[/title]";
        }
		$message = "[info]".$title.$message."[/info]";

		header( "Content-type: text/html; charset=utf-8" );
		$data = array(
		  'body' => $message
		);

		$endpoint = "https://api.chatwork.com/v2";
		$opt = array(
		  CURLOPT_URL => $endpoint."/rooms/{$chatwork_room_id}/messages",
		  CURLOPT_HTTPHEADER => array( 'X-ChatWorkToken: ' . $token ),
		  CURLOPT_RETURNTRANSFER => TRUE,
		  CURLOPT_SSL_VERIFYPEER => FALSE,
		  CURLOPT_POST => TRUE,
		  CURLOPT_POSTFIELDS => http_build_query( $data, '', '&' )
		);

		$ch = curl_init();
		curl_setopt_array( $ch, $opt );
		$res = curl_exec( $ch );
		curl_close( $ch );
	}


}
