<?php
use App\Enums\SessionValiableType;
$currentLoginUser = '';
$currentLoginUserAccountType = '';
$accountInfo = session(SessionValiableType::LOGIN_ACCOUNT_INFO);
if($accountInfo){
    $currentLoginUser = $accountInfo->get('last_name') . ' ' . $accountInfo->get('first_name')
                      . ' (' . $accountInfo->get('account_kind_name') . ')';
    $currentLoginUserAccountType = $accountInfo->get('account_kind_id');
}
?>
<div class="header">
  <div class="wrapper">
    <div class="header-items">
      <div class="title">
        <a href="/top"><img src="/img/alue_logo.gif" alt="alue" /></a>
        <span>{{ $currentLoginUser }}</span>
        <input type="hidden" name="loginUserAccountType" value="{{ $currentLoginUserAccountType }}">
      </div>
      <div class="logout">
        <a href="/logout">
          <button class="btn logout-btn">ログアウト</button>
        </a>
      </div>
    </div>
  </div>
</div>
