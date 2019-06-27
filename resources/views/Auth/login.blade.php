@extends('layouts.login')

<?php
$mail = $view_params['mail'];
?>

<!-- コンテンツ内body -->
@section('body-contents')
    <div class="main">
      <div class="login-main">
        <img src="./img/alue_logo.gif" alt="alue"/>
        <div class="login-form">
            <form action="/login" method="POST">
                <div class="form-group">
                  <label for="mail">メールアドレス</label>
                  <input type="mail" id="mail" name="mail" class="form-control" placeholder="登録済みのメールアドレス" value="{{ $mail }}" />
                </div>
                <div class="form-group">
                  <label for="password">パスワード</label>
                  <input type="password" id="password" name="password" class="form-control" placeholder="パスワード8文字以上" />
                </div>
                <div class="form-check">
                </div>
                <button type="submit" class="btn login-btn">ログイン</button>
                {!! csrf_field() !!}
            </form>
        </div>
      </div>
    </div>
@endsection
