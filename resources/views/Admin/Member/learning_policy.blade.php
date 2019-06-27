@extends('layouts.Admin.master')

@section('styles')
@endsection
<?php
use App\Enums\SessionValiableType;
$memberDetail = session(SessionValiableType::MEMBER_DETAIL_INFO);
$userBasicInfo = $memberDetail->userBasicInfo;
$memberName = $userBasicInfo->lastName.' '.$userBasicInfo->firstName;
$memberNameEn = $userBasicInfo->firstNameEn.' '.$userBasicInfo->lastNameEn;

?>

@section('body-contents')
    <div class="main">
      <div class="wrap">

        <div class="mainbar">
          <div class="hero-box">
            <div class="primary-hero-box">
              <div class="hero-section">
                <div class="secondary-item">
                  <span>会員管理TOP > {{$memberName}}さんの詳細 > 学習方針履歴一覧</span>
                </div>
                <div class="master-middle">
                  <div class="btn btn-domain" data-toggle="modal" data-target="#newPolicyModal">新規登録</div>
                </div>
                <div class="memberdetail-middle">
                  <div class="form-group" id="datepicker-daterange">
                    <div class="form-inline">
                      <div class="input-daterange input-group" id="datepicker">
                        <input type="text" class="input-md time-input form-control" name="start" />
                        <span class="input-group-addon">〜</span>
                        <input type="text" class="input-md time-input form-control" name="end" />
                      </div>
                    </div>
                  </div>
                  <div class="dropdown member-detail-search">
                    <button class="btn btn-default btn-md dropdown-toggle" type="button" id="dropdownMenu1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                      投稿カテゴリ
                      <span class="caret"></span>
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="dropdownMenu1">
                      <li><a href="#">投稿カテゴリ</a></li>
                      <li><a href="#">投稿カテゴリ</a></li>
                      <li><a href="#">投稿カテゴリ</a></li>
                    </ul>
                  </div>
                  <div class="form-inline member-detail-search">
                    <input type="text" class="form-control input-md" placeholder="検索する">
                  </div>
                  <div class="search-item">
                    <button class="btn btn-search">検索する</button>
                  </div>
                </div>
                <div class="secondary-box">
                  <div class="hwork-item">
                    <div class="hwork-top-item-box">
                      <div class="hwork-master-item-id hwork-top-item">
                        ID
                      </div>
                      <div class="hwork-master-item hwork-top-item">
                        投稿日
                      </div>
                      <div class="hwork-master-item hwork-top-item">
                        投稿カテゴリ
                      </div>
                      <div class="hwork-master-item hwork-top-item">
                        学習方針
                      </div>
                      <div class="hwork-master-item hwork-top-item">
                        自由テキスト
                      </div>
                    </div>
                  </div>
                  <div class="hwork-top-item-box">
                    <div class="hwork-master-item-id master-item">
                    </div>
                    <div class="hwork-master-item master-item">
                    </div>
                    <div class="hwork-master-item master-item">
                    </div>
                    <div class="hwork-master-item master-item">
                    </div>
                    <div class="hwork-master-item master-item">
                    </div>
                    <div class="master-item-edit">
                      <div class="btn btn-domain" data-toggle="modal" data-target="#editPolicyModal">編集する</div>
                    </div>
                  </div>
                  <div class="page">
                    <ul class="pagination">
                      <li class="page-item">
                        <a class="page-link" href="#" aria-label="Previous">
                          <span aria-hidden="true">&laquo;</span>
                          <span class="sr-only">Previous</span>
                        </a>
                      </li>
                      <li class="page-item"><a class="page-link" href="#">1</a></li>
                      <li class="page-item"><a class="page-link" href="#">2</a></li>
                      <li class="page-item"><a class="page-link" href="#">3</a></li>
                      <li class="page-item">
                        <a class="page-link" href="#" aria-label="Next">
                          <span aria-hidden="true">&raquo;</span>
                          <span class="sr-only">Next</span>
                        </a>
                      </li>
                    </ul>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="newPolicyModal" tabindex="-1" role="dialog" aria-hidden="true">
      <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <div class="hero-section new-policy-section">
              <div class="secondary-item">
                <span>学習方針新規登録</span>
              </div>
              <div class="hero-form">
                <div class="hero-form-item">
                  <span>投稿日</span>
                  <div class="form-group" id="datepicker-daterange">
                    <div class="form-inline">
                      <div class="input-daterange input-group new-policy-date" id="datepicker">
                        <input type="text" class="input-md time-input form-control" name="start" />
                      </div>
                    </div>
                  </div>
                </div>
                <div class="hero-form-item">
                  <span>投稿カテゴリ</span>
                  <div class="dropdown">
                    <button class="btn btn-default btn-md dropdown-toggle" type="button" id="dropdownMenu1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                      投稿カテゴリ
                      <span class="caret"></span>
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="dropdownMenu1">
                      <li><a href="#">投稿カテゴリ</a></li>
                      <li><a href="#">投稿カテゴリ</a></li>
                      <li><a href="#">投稿カテゴリ</a></li>
                    </ul>
                  </div>                </div>
                <div class="hero-form-item">
                  <span>学習方針</span>
                  <textarea class="form-control" rows="5"></textarea>
                </div>
                <div class="hero-form-item">
                  <span>自由テキスト</span>
                  <textarea class="form-control" rows="5"></textarea>
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
    <div class="modal fade" id="editPolicyModal" tabindex="-1" role="dialog" aria-hidden="true">
      <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <div class="hero-section new-policy-section">
              <div class="secondary-item">
                <span>学習方針詳細（No.XXXX）</span>
              </div>
              <div class="hero-form">
                <div class="hero-form-item">
                  <span>ID</span>
                  <input class="form-control" />
                </div>
                <div class="hero-form-item">
                  <span>投稿日</span>
                  <div class="form-group" id="datepicker-daterange">
                    <div class="form-inline">
                      <div class="input-daterange input-group new-policy-date" id="datepicker">
                        <input type="text" class="input-md time-input form-control" name="start" />
                      </div>
                    </div>
                  </div>
                </div>
                <div class="hero-form-item">
                  <span>投稿カテゴリ</span>
                  <div class="dropdown">
                    <button class="btn btn-default btn-md dropdown-toggle" type="button" id="dropdownMenu1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                      投稿カテゴリ
                      <span class="caret"></span>
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="dropdownMenu1">
                      <li><a href="#">投稿カテゴリ</a></li>
                      <li><a href="#">投稿カテゴリ</a></li>
                      <li><a href="#">投稿カテゴリ</a></li>
                    </ul>
                  </div>                </div>
                <div class="hero-form-item">
                  <span>学習方針</span>
                  <textarea class="form-control" rows="5"></textarea>
                </div>
                <div class="hero-form-item">
                  <span>自由テキスト</span>
                  <textarea class="form-control" rows="5"></textarea>
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
    <div class="modal fade" id="deleteMasterModal" tabindex="-1" role="dialog" aria-hidden="true">
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
                <span>アカウント新規登録</span>
              </div>
              <p>以下のアカウント情報を削除します。よろしいですか？</p>
              <div class="hero-form">
                <div class="hero-form-item">
                  <span>氏名</span>
                  <input class="first-name form-control" />
                  <input class="last-name form-control" />
                </div>
                <div class="hero-form-item">
                  <span>氏名（英語表記）</span>
                  <input class="first-name form-control" />
                  <input class="last-name form-control" />
                </div>
                <div class="hero-form-item">
                  <span>メールアドレス</span>
                  <input type="email" class="form-control" />
                </div>
                <div class="hero-form-item">
                  <span>アカウント種別</span>
                  <input class="form-control" />
                </div>
                <div class="hero-form-item">
                  <span>ステータス</span>
                  <input class="form-control" />
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-register">削除する</button>
          </div>
        </div>
      </div>
    </div>
@endsection
@section('scripts')
<script src="{{ mix('/js/learning_policy.js') }}"></script>
@endsection
