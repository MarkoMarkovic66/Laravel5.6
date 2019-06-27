<?php
namespace App\Enums;

use App\Enums\BaseEnum;

/**
 * ConnectType enum
 */
class ConnectType extends BaseEnum {
    const CONNECT_SEND           = '1';  // 送信
    const CONNECT_RECEIVE        = '2';  // 受信

    const CONNECT_SEND_NAME      = '送信';
    const CONNECT_RECEIVE_NAME   = '受信';

}
