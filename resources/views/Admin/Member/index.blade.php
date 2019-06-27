@extends('layouts.Admin.master')

@section('styles')
@endsection
<?php
use App\Enums\SessionValiableType;
use App\Enums\AccountKindType;

$accountInfo = Session::get(SessionValiableType::LOGIN_ACCOUNT_INFO);
$accountKindId = $accountInfo['account_kind_id'];

$backToList = $view_params->get('backToList');
if($backToList){
    //前回検索条件を取得
    $params = $view_params->get('searchCondition');
    $memberId        = $params->get('memberId');
    $memberSrId      = $params->get('memberSrId');
    $memberName      = $params->get('memberName');
    $cwRoomId        = $params->get('cwRoomId');
    $cwRoomName      = $params->get('cwRoomName');
    $mail            = $params->get('mail');
    $phoneNumber     = $params->get('phoneNumber');
    $hasMemberReport = $params->get('hasMemberReport');
}else{
    $params = '';
    $memberId    = '';
    $memberSrId  = '';
    $memberName  = '';
    $cwRoomId    = '';
    $cwRoomName  = '';
    $mail        = '';
    $phoneNumber = '';
    $hasMemberReport = '';
}
?>

@section('body-contents')
        <div class="mainbar">
          <div class="primary-item">
            <span>会員管理</span>
          </div>
          <div class="hero-box">
            <div class="primary-hero-box">
              <div class="hero-section">
                <div class="secondary-item">
                  <span>検索項目</span>
                  <input type="hidden" name="backToList" value="{{$backToList}}">
                  <input type="hidden" name="searchCondition" value="{{$params}}">
                </div>
                <div class="secondary-box">
                  <div class="search-box">

                    <div class="search-secondary-box">
                      <div class="search-item">
                        <div class="form-group">
                          <div class="form-inline">
                              <div class="search-cond-item">
                                <label>会員ID</label>
                                <input type="text" class="form-control input-md" name="memberId" placeholder="検索する" value="{{$memberId}}"/>
                              </div>
                              <div class="search-cond-item">
                                <label>SR-ID</label>
                                <input type="text" class="form-control input-md" name="memberSrId" placeholder="検索する" value="{{$memberSrId}}"/>
                              </div>
                              <div class="search-cond-item">
                                <label>氏名</label>
                                <input type="text" class="form-control input-md" name="memberName" placeholder="検索する" value="{{$memberName}}"/>
                              </div>
                              <div class="search-cond-item">
                                <label>Mail</label>
                                <input type="text" class="form-control input-md" name="mail" placeholder="検索する" value="{{$mail}}"/>
                              </div>
                              <div class="search-cond-item">
                                <label>Phone</label>
                                <input type="text" class="form-control input-md" name="phoneNumber" placeholder="検索する" value="{{$phoneNumber}}"/>
                              </div>
                          </div>
                        </div>
                      </div>
                    </div>

@if($accountKindId != AccountKindType::ACCOUNT_OPERATOR_ID)
                    <div class="search-secondary-box">
                      <div class="search-item">

                        <div class="form-group">
                          <div class="form-inline">
                  @if($hasMemberReport)
                          <span style="margin-top:1px; margin-left:15px;"><input type="checkbox" name="hasMemberReport" id="hasMemberReport" value="1" checked></span>
                  @else
                          <span style="margin-top:1px; margin-left:15px;"><input type="checkbox" name="hasMemberReport" id="hasMemberReport" value="1"></span>
                  @endif
                          <label class="form-check-label" for="hasMemberReport" style="margin-left: 10px;">レッスンレポート出力ありに限定</label>
                          </div><!-- <div class="form-inline"> -->
                        </div><!-- <div class="form-group"> -->

                      </div><!-- <div class="search-item> -->
                    </div><!-- <div class="search-secondary-box"> -->
@else
                    <input type="hidden" name="hasMemberReport" id="hasMemberReport" value="1">
@endif

                    <div class="search-button-box">
                      <div class="search-btn-item">
                        <button class="btn btn-clear">クリア</button>
                      </div>
                      <div class="search-btn-item">
                        <button class="btn btn-search">検索する</button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="hero-section">
                <div class="secondary-item">
                  <span>会員一覧</span>
                </div>

                <div class="panel-scroll">
                    <div class="secondary-box">
                        <div id="member-list"></div>
                    </div>
                </div>
                <div id="member-list-pagination"></div>

              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="counselorModal" role="dialog" aria-hidden="true">
      <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <div class="hero-section">
              <div class="secondary-item">
                <span name="title"></span>
                <input type="hidden" name="userId" value="">
              </div>
              <div class="hero-form">
                <div name="counselorList" class="counselor-list"></div>

                <div class="counselor-asign">
                    <div class="counselor-asign-item">
                        <div name="candidateCounselorList" class="counselor-candidate-list"></div>
                        <div class="counselor-add">
                          <button class="btn btn-add">新しいカウンセラーを追加する</button>
                        </div>
                    </div>
                </div>

              </div>
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-register">登録する</button>
          </div>

        </div>
      </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="chatworkPostModal" tabindex="-1" role="dialog" aria-hidden="true">
      <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <div class="hero-section">
              <div class="secondary-item">
                <span name="title"></span>
              </div>
              <div class="hero-form">
                <div class="hero-form-item">
                  <span>生徒名</span>
                  <div name="memberName"></div>
                  <input type="hidden" name="userId" value="">
                </div>
                <div class="hero-form-item">
                  <span>投稿内容</span>
                  <textarea class="form-control" rows="5" ></textarea>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" name="cwregist" class="btn btn-register">投稿する</button>
          </div>
        </div>
      </div>
    </div>
@endsection
@section('scripts')
<script src="{{ mix('/js/member.js') }}"></script>
@endsection
