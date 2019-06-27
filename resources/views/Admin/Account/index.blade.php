@extends('layouts.Admin.master')

@section('styles')
@endsection

@section('body-contents')
    <div class="mainbar">
        <div class="primary-item">
            <span>アカウント管理</span>
        </div>
        <div class="hero-box">
            <div class="primary-hero-box">
                <form action="/account/{{$offset}}" method="post">
                    {{ csrf_field() }}
                    <div class="hero-section">
                        <div class="secondary-item">
                            <span>検索項目</span>
                        </div>
                        <div class="form-row">
                            @foreach($columns as $key => $val)
                                @if($key != 'id' && $key != 'deleted_at' && $key != 'created_at' && $key != 'updated_at'
                                && $key != 'skill_japanese' && $key != 'last_lesson_at' && $key != 'last_assign_at' && $key != 'password'
                                && $key != 'first_name_en' && $key != 'last_name_en'
                                && $key != 'counseler' && $key != 'work_sec' && $key != 'last_login_at' && $key != 'return_sec')
                                    @if($key == 'account_kind_id')
                                        <div class="form-group col-md-3">
                                            {{--<label for="{{$key}}">{{$val}}</label>--}}
                                            <select class="form-control" name="{{$key}}" id="{{$key}}">
                                                <option value="">{{$val}}</option>
                                                @foreach($account_kinds as $key2 => $val2)
                                                    <option value="{{$val2->id}}" @if(isset($search_params[$key]) && $search_params[$key] == $val2->id) selected="selected" @endif>
                                                        {{$val2->kind_name}}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    @elseif($key == 'assign_flag' || $key == 'authority')
                                        <div class="form-group col-md-3">
                                            {{--<label for="{{$key}}">{{$val}}</label>--}}
                                            <select class="form-control" name="{{$key}}" id="{{$key}}">
                                                <option value="">{{$val}}</option>
                                                <option value="1">有り</option>
                                                <option value="0">無し</option>
                                            </select>
                                        </div>
                                    @else
                                        <div class="form-group col-md-3">
{{--                                            <label for="{{$key}}">{{$val}}</label>--}}
                                            <input type="text" name="{{$key}}" class="form-control" id="{{$key}}"
                                                   placeholder="{{$val}}"
                                                   @if(isset($search_params[$key])) value="{{$search_params[$key]}}" @endif >
                                        </div>
                                    @endif
                                @endif
                            @endforeach
                            <div class="form-group col-md-9">
                                {{--<label for="search_word">フリーワード</label>--}}
                                <input type="text" name="search_word" class="form-control" id="search_word"
                                       placeholder="フリーワード" @if(isset($search_word)) value="{{$search_word}}" @endif >
                            </div>
                        </div>
                        <div class="search-button-box">
                          <div class="search-btn-item">
                            <a href="/account/{{$offset}}" class="btn btn-clear" name="btn-clear">クリア</a>
                          </div>
                          <div class="search-btn-item">
                            <button type="submit" class="btn btn-search" name="btn-search" value="search"
                                    style="width: 120px;">検索する
                            </button>
                          </div>
                        </div>
                    </div>
                </form>
                <div style="clear: both;"></div>

                <div class="hero-section">
                    <div class="secondary-item">
                        <span>アカウント一覧</span>
                    </div>
                    <div class="master-middle">
                        <div class="btn btn-domain open-create-modal" data-toggle="modal" data-target="#newMasterModal">
                            新規登録
                        </div>
                    </div>
                    <div class="secondary-box">

                        @include('layouts.account-paging')

                        {{--'id' => 'ID',--}}
                        {{--'alugo_stuff_id' => 'alugoスタッフID',--}}
                        {{--'sr_stuff_id' => 'SpeakingRallyスタッフID',--}}
                        {{--'account_kind_id' => 'アカウント種別ID',--}}
                        {{--'first_name' => '名',--}}
                        {{--'last_name' => '氏',--}}
                        {{--'first_name_en' => '名英語表記',--}}
                        {{--'last_name_en' => '氏英語表記',--}}
                        {{--'mail' => 'メールアドレス',--}}
                        {{--'phone_number' => '電話番号',--}}
                        {{--'curriculum_id' => '対応カリキュラムID',--}}
                        {{--'skill_japanese' => '日本語スキル',--}}
                        {{--'status' => 'ステータス',--}}
                        {{--'last_lesson_at' => '最終レッスン日時',--}}
                        {{--'last_login_at' => '最終ログイン日時',--}}
                        {{--'work_sec' => '対応時間（秒）',--}}
                        {{--'return_sec' => '差し戻し時間（秒）',--}}
                        {{--'last_assign_at' => '最終アサイン日時',--}}
                        {{--'assign_flag' => '今すぐアサインフラグ',--}}
                        {{--'level' => '熟達度',--}}
                        {{--'counseler' => 'カウンセラー権限',--}}
                        {{--'organization' => '組織名',--}}
                        {{--'password' => 'パスワード',--}}
                        {{--'authority' => '仕事可能フラグ',--}}
                        {{--'created_at' => '作成日',--}}
                        {{--'updated_at' => '最終更新日',--}}
                        {{--'deleted_at' => '削除日',--}}
                        <div class="hwork-item">
                          <table class="table table-hover core-tbl">
                            <thead>
                              <tr>
                                  @foreach($columns as $key => $val)
                                      @if($key != 'curriculum_id' && $key != 'skill_japanese'
                                      && $key != 'alugo_stuff_id' && $key != 'sr_stuff_id'
                                      && $key != 'return_sec' && $key != 'last_login_at' && $key != 'assign_flag'
                                      && $key != 'counseler' && $key != 'level' && $key != 'organization'
                                      && $key != 'authority' && $key != 'password' && $key != 'first_name_en'
                                      && $key != 'last_name_en' && $key != 'deleted_at')
                                          <th class="font-weight-bold">{{ $val }}</th>
                                      @endif
                                  @endforeach
                                  <th></th>
                              </tr>

                            </thead>
                            <tbody>
                              @foreach($dataList as $key => $data)
                                  <tr>
                                      @foreach($data as $column => $val)
                                          @if($column != 'curriculum_id' && $column != 'skill_japanese'
                                              && $column != 'alugo_stuff_id' && $column != 'sr_stuff_id'
                                              && $column != 'return_sec' && $column != 'last_login_at' && $column != 'assign_flag'
                                              && $column != 'counseler' && $column != 'level' && $column != 'organization'
                                              && $column != 'authority' && $column != 'password' && $column != 'remember_token'
                                              && $column != 'first_name_en' && $column != 'last_name_en' && $column != 'is_deleted' && $column != 'deleted_at')
                                              @if($column == 'account_kind_id')
                                                  <td style="max-width: 100px; word-wrap: break-word;">{{ $account_kinds[$val]->kind_name }}</td>
                                              @else
                                                  <td style="max-width: 200px; word-wrap: break-word;">{{ $val }}</td>
                                              @endif
                                          @endif
                                      @endforeach
                                      <td>
                                          <div class="btn btn-domain btn-table open-edit-modal" data-toggle="modal"
                                               data-id="{{ $data->id }}"
                                               data-target="#editMasterModal" >編集する
                                          </div>
                                          @if($login_user['id'] == $data->id)
                                              <div class="btn btn-domain disabled" disabled="disabled" style="cursor: not-allowed" >削除する
                                              </div>
                                          @else
                                              <div class="btn btn-domain btn-table open-delete-modal" data-toggle="modal"
                                                   data-id="{{ $data->id }}"
                                                   data-target="#deleteMasterModal" >削除する
                                              </div>
                                          @endif
                                      </td>
                                  </tr>
                              @endforeach
                            </tbody>
                          </table>

                            <!-- <table class="table table-sm table-hover">
                                <thead class="thead-default">
                                <tr>
                                    @foreach($columns as $key => $val)
                                        @if($key != 'curriculum_id' && $key != 'skill_japanese'
                                        && $key != 'alugo_stuff_id' && $key != 'sr_stuff_id'
                                        && $key != 'return_sec' && $key != 'last_login_at' && $key != 'assign_flag'
                                        && $key != 'counseler' && $key != 'level' && $key != 'organization'
                                        && $key != 'authority' && $key != 'password' && $key != 'first_name_en'
                                        && $key != 'last_name_en' && $key != 'deleted_at')
                                            <th class="font-weight-bold">{{ $val }}</th>
                                        @endif
                                    @endforeach
                                    <th></th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($dataList as $key => $data)
                                    <tr>
                                        @foreach($data as $column => $val)
                                            @if($column != 'curriculum_id' && $column != 'skill_japanese'
                                                && $column != 'alugo_stuff_id' && $column != 'sr_stuff_id'
                                                && $column != 'return_sec' && $column != 'last_login_at' && $column != 'assign_flag'
                                                && $column != 'counseler' && $column != 'level' && $column != 'organization'
                                                && $column != 'authority' && $column != 'password' && $column != 'remember_token'
                                                && $column != 'first_name_en' && $column != 'last_name_en' && $column != 'is_deleted' && $column != 'deleted_at')
                                                @if($column == 'account_kind_id')
                                                    <td style="max-width: 100px; word-wrap: break-word;">{{ $account_kinds[$val]->kind_name }}</td>
                                                @else
                                                    <td style="max-width: 200px; word-wrap: break-word;">{{ $val }}</td>
                                                @endif
                                            @endif
                                        @endforeach
                                        <td>
                                            <div class="btn btn-domain open-edit-modal" data-toggle="modal"
                                                 data-id="{{ $data->id }}"
                                                 data-target="#editMasterModal" >編集する
                                            </div>
                                            @if($login_user['id'] == $data->id)
                                                <div class="btn btn-domain disabled" disabled="disabled" style="cursor: not-allowed" >削除する
                                                </div>
                                            @else
                                                <div class="btn btn-domain open-delete-modal" data-toggle="modal"
                                                     data-id="{{ $data->id }}"
                                                     data-target="#deleteMasterModal" >削除する
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table> -->
                        </div>

                        @include('layouts.account-paging')

                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
    </div>

    <!-- Modal -->
    <form action="/account/{{$offset}}" method="post">
        {{ csrf_field() }}
        <div class="modal fade" id="newMasterModal" tabindex="-1" role="dialog" aria-hidden="true">
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
                            <div class="hero-form row">
                                @foreach($columns as $key => $val)
                                    @if($key != 'id' && $key != 'deleted_at' && $key != 'created_at' && $key != 'updated_at')
                                        @if($key == 'first_name' || $key == 'last_name')
                                            <div class="hero-form-item col-md-6">
                                                <span>{{$val}}<span class="text-danger">*</span></span>
                                                <input name="create-{{$key}}" class="form-control" required />
                                            </div>
                                        @elseif($key == 'mail')
                                                <div class="hero-form-item col-md-6">
                                                    <span>{{$val}}<span class="text-danger">*</span></span>
                                                    <input type="email" name="create-{{$key}}" class="form-control" required />
                                                </div>
                                        @elseif($key == 'password')
                                            <div class="hero-form-item col-md-6">
                                                <span>{{$val}}<span class="text-danger">*</span></span>
                                                <input type="password" name="create-{{$key}}" class="form-control" required />
                                            </div>
                                        @elseif($key == 'account_kind_id')
                                            <div class="hero-form-item col-md-6">
                                                <span>{{$val}}<span class="text-danger">*</span></span>
                                                <select class="form-control" name="create-{{$key}}">
                                                    @foreach($account_kinds as $key2 => $val2)
                                                        <option value="{{$val2->id}}">
                                                            {{$val2->kind_name}}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        @elseif($key == 'assign_flag' || $key == 'authority')
                                            <div class="hero-form-item col-md-6">
                                                <span>{{$val}}</span>
                                                <select class="form-control" name="create-{{$key}}">
                                                    <option value="0">無し</option>
                                                    <option value="1">有り</option>
                                                </select>
                                            </div>
                                        @else
                                            <div class="hero-form-item col-md-6">
                                                <span>{{$val}}</span>
                                                <input name="create-{{$key}}" class="form-control"/>
                                            </div>
                                        @endif
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="btn-create" value="create" class="btn btn-register">登録する
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <!-- Modal -->
    <form action="/account/{{$offset}}" method="post">
        {{ csrf_field() }}
        <div class="modal fade" id="editMasterModal" tabindex="-1" role="dialog" aria-hidden="true">
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
                                <span>アカウント編集</span>
                            </div>
                            <div class="hero-form row">
                                @foreach($columns as $key => $val)
                                    @if($key != 'deleted_at' && $key != 'updated_at')
                                        @if($key == 'id' || $key == 'created_at')
                                            <div class="hero-form-item col-md-6">
                                                <span>{{$val}}</span>
                                                <input type="text" name="edit-{{$key}}" value=""
                                                       class="form-control edit-{{$key}}" readonly="readonly"/>
                                            </div>
                                        @else
                                            @if($key == 'first_name' || $key == 'last_name')
                                                <div class="hero-form-item col-md-6">
                                                    <span>{{$val}}<span class="text-danger">*</span></span>
                                                    <input type="text" name="edit-{{$key}}"
                                                           value="" class="form-control edit-{{$key}}" required />
                                                </div>
                                            @elseif($key == 'account_kind_id')
                                                <div class="hero-form-item col-md-6">
                                                    <span>{{$val}}<span class="text-danger">*</span></span>
                                                    <select class="form-control edit-{{$key}}" name="edit-{{$key}}" id="edit-{{$key}}">
                                                        @foreach($account_kinds as $key2 => $val2)
                                                            <option value="{{$val2->id}}">
                                                                {{$val2->kind_name}}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            @elseif($key == 'mail')
                                                <div class="hero-form-item col-md-6">
                                                    <span>{{$val}}<span class="text-danger">*</span></span>
                                                    <input type="email" name="edit-{{$key}}"
                                                           value="" class="form-control edit-{{$key}}" required />
                                                </div>
                                            @elseif($key == 'password')
                                                <div class="hero-form-item col-md-6">
                                                    <span>{{$val}}<span class="text-danger">*</span></span>
                                                    <input type="password" name="edit-{{$key}}"
                                                           value="" class="form-control edit-{{$key}}" required />
                                                </div>
                                            @elseif($key == 'assign_flag' || $key == 'authority')
                                                <div class="hero-form-item col-md-6">
                                                    <span>{{$val}}</span>
                                                    <select class="form-control edit-{{$key}}" name="edit-{{$key}}">
                                                        <option value="0">無し</option>
                                                        <option value="1">有り</option>
                                                    </select>
                                                </div>
                                            @else
                                                <div class="hero-form-item col-md-6">
                                                    <span>{{$val}}</span>
                                                    <input type="text" name="edit-{{$key}}"
                                                           value="" class="form-control edit-{{$key}}" />
                                                </div>
                                            @endif
                                        @endif
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="btn-update" value="update" class="btn btn-register">更新する</button>
                        <input type="hidden" id="sel_update_id" value="" name="sel_update_id"/>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <!-- Modal -->
    <form action="/account/{{$offset}}" method="post">
        {{ csrf_field() }}
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
                                <span>アカウント削除</span>
                            </div>
                            <p>以下のアカウント情報を削除します。よろしいですか？</p>
                            <div class="hero-form row">
                                @foreach($columns as $key => $val)
                                    @if($key != 'deleted_at' && $key != 'updated_at')
                                        @if($key == 'password')
                                            <div class="hero-form-item col-md-6">
                                                <span>{{$val}}</span>
                                                <input type="password" name="delete-{{$key}}"
                                                       value="" class="form-control delete-{{$key}}" readonly="readonly"/>
                                            </div>
                                        @elseif($key == 'account_kind_id')
                                            <div class="hero-form-item col-md-6">
                                                <span>{{$val}}</span>
                                                <select class="form-control delete-{{$key}}" name="delete-{{$key}}" readonly="readonly">
                                                    @foreach($account_kinds as $key2 => $val2)
                                                        <option value="{{$val2->id}}">
                                                            {{$val2->kind_name}}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        @else
                                            <div class="hero-form-item col-md-6">
                                                <span>{{$val}}</span>
                                                <input type="text" name="delete-{{$key}}" value=""
                                                       class="form-control delete-{{$key}}" readonly="readonly"/>
                                            </div>
                                        @endif
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="btn-delete" value="delete" class="btn btn-register">削除する</button>
                        <input type="hidden" id="sel_delete_id" value="" name="sel_delete_id" />
                    </div>
                </div>
            </div>
        </div>
    </form>

    <script type="text/javascript">

        $(function(){

            //編集モーダル
            $('.open-edit-modal').on('click', function(){

                //idを取得
                data_id = $(this).attr('data-id');

                //該当マスタのデータ取得ajax
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });
                $.ajax({
                    url:'/api/account/getdata',
                    type:'POST',
                    data:{
                        'data_id' : data_id,
                    }
                }).done(function(data){
                    @foreach($columns as $key => $val)
                        @if($key != 'deleted_at' && $key != 'updated_at')
                            @if($key == 'account_kind_id' || $key == 'assign_flag' || $key == 'authority')
                                $('.edit-{{$key}}').val([data['{{$key}}']]);
                            @else
                                $('.edit-{{$key}}').val(data['{{$key}}']);
                            @endif
                        @endif
                    @endforeach
                }).fail(function(){
                    //TODO
                    //console.log("failure");
                });

                //取得したidをフォームにセット
                $('#sel_update_id').val(data_id);
            });

            //削除モーダル
            $('.open-delete-modal').on('click', function(){

                //idを取得
                data_id = $(this).attr('data-id');

                //該当マスタのデータ取得ajax
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });
                $.ajax({
                    url:'/api/account/getdata',
                    type:'POST',
                    data:{
                        'data_id' : data_id,
                    }
                }).done(function(data){
                    @foreach($columns as $key => $val)
                        @if($key != 'deleted_at' && $key != 'updated_at')
                            @if($key == 'account_kind_id' || $key == 'assign_flag' || $key == 'authority')
                                $('.delete-{{$key}}').val([data['{{$key}}']]);
                            @else
                                $('.delete-{{$key}}').val(data['{{$key}}']);
                            @endif
                        @endif
                    @endforeach
                }).fail(function(){
                    //TODO
                    //console.log("failure");
                });

                //取得したidをフォームにセット
                $('#sel_delete_id').val(data_id);
            });
        });

    </script>
@endsection

@section('scripts')
@endsection
