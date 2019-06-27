@extends('layouts.Admin.master')

@section('styles')
<style>
    .select2 .select2-selection {
        width: 100% !important;        
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered{
        font-weight:normal!important;
    }     
    .form-control{
        padding:5px!important;
    }

    html {
        scroll-behavior: smooth;
    }
    
</style>
@endsection

@section('body-contents')
    <div class="mainbar">
        <div class="primary-item">
            <span>マスタ管理 ＞ {{$name}}</span>
        </div>
        <div class="hero-box">
            <div class="primary-hero-box">
                <form action="/master/{{$master_name}}/{{$offset}}" method="post">
                    {{ csrf_field() }}
                    <div class="hero-section">
                        <div class="secondary-item">
                            <span>検索項目</span>
                        </div>
                        
                        @if($master_name == 'wordcardcategory')
                        <!-- <div class="form-row" style="float:left;width:80%;"> -->
                        <div class="form-row">
                        @else
                        <div class="form-row">
                        @endif

                            @foreach($columns as $key => $val)
                                @if($key != 'id' && $key != 'deleted_at' && $key != 'created_at' && $key != 'updated_at' && $key != 'module')
                                    @if($key == 'word_card_category_id')
                                        <div class="form-group col-md-3">
                                            {{--<label for="{{$key}}">{{$val}}</label>--}}
                                            <input type="text" name="{{$key}}" class="form-control" id="{{$key}}"
                                                   placeholder="カテゴリ"
                                                   @if(isset($search_params[$key])) value="{{$search_params[$key]}}" @endif >
                                        </div>
                                    @elseif($key == 'unit_name')                                        
                                        <div class="form-group col-md-3">
                                            {{--<label for="{{$key}}">{{$val}}</label>--}}
                                            <input type="text" name="{{$key}}" class="form-control" id="{{$key}}"
                                                placeholder="Unit名"
                                                @if(isset($search_params[$key])) value="{{$search_params[$key]}}" @endif >
                                        </div>                                        
                                    @elseif($key == 'category')
                                        @if($master_name == 'wordcardcategory')
                                            <div class="form-group col-md-3">
                                                {{--<label for="{{$key}}">{{$val}}</label>--}}
                                                <input type="text" name="{{$key}}" class="form-control" id="{{$key}}"
                                                    placeholder="カテゴリ"
                                                    @if(isset($search_params[$key])) value="{{$search_params[$key]}}" @endif >
                                            </div>
                                        @endif
                                    @elseif($key == 'sr_category_id')
                                        <div class="form-group col-md-3">
                                            {{--<label for="{{$key}}">{{$val}}</label>--}}
                                            <select class="form-control" name="{{$key}}" id="{{$key}}">
                                                <option value="">SRカテゴリタイプ選択</option>
                                                @foreach($sr_categories as $key2 => $val2)
                                                    <option value="{{$val2->id}}"
                                                            @if(isset($search_params[$key]) && $search_params[$key] == $val2->id) selected="selected" @endif>
                                                        {{$val2->category_name}}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    @elseif($key == 'grade')
                                        <div class="form-group col-md-3">
                                            {{--<label for="{{$key}}">{{$val}}</label>--}}
                                            <select class="form-control" name="{{$key}}" id="{{$key}}">
                                                <option value="">グレード選択</option>
                                                @foreach($grade_arr as $key2 => $val2)
                                                    <option value="{{$key2}}"
                                                            @if(isset($search_params[$key]) && $search_params[$key] == $key2) selected="selected" @endif>
                                                        {{$val2}}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    @elseif($key == 'sv' || $key == 'svc' || $key == 'svo' || $key == 'svoo' || $key == 'svoc')
                                        <div class="form-group col-md-3">
                                            {{--<label for="{{$key}}">{{$val}}</label>--}}
                                            <select class="form-control" name="{{$key}}" id="{{$key}}">
                                                <option value="">{{$key}}文型の有無</option>
                                                @foreach($pattern_flg_arr as $key2 => $val2)
                                                    <option value="{{$key2}}"
                                                            @if(isset($search_params[$key]) && $search_params[$key] == $key2) selected="selected" @endif>
                                                        {{$val2}}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    @elseif($key == 'task_type_id')
                                        <div class="form-group col-md-3">
                                            {{--<label for="{{$key}}">{{$val}}</label>--}}
                                            <select class="form-control" name="{{$key}}" id="{{$key}}">
                                                <option value="">宿題タイプ選択</option>
                                                @foreach($task_type_arr as $key2 => $val2)
                                                    <option value="{{$key2}}"
                                                            @if(isset($search_params[$key]) && $search_params[$key] == $key2) selected="selected" @endif>
                                                        {{$key2.' - '.$val2}}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    @elseif($key == 'stage' || $key == 'period' || $key == 'priority' || $key == 'created_stuff_id' || $key == 'freq' || $key == 'flag' || $key == 'seq_number')
                                        @if($master_name != 'wordcardcategory')
                                            <div class="form-group col-md-3">
                                                {{--<label for="{{$key}}">{{$val}}</label>--}}
                                                <input type="number" name="{{$key}}" class="form-control" id="{{$key}}"
                                                    placeholder="{{$val}}"
                                                    @if(isset($search_params[$key])) value="{{$search_params[$key]}}" @endif >
                                            </div>
                                        @endif
                                    @elseif($key =='grades' || $key =='listening_type')
                                        <div class="form-group col-md-3">
                                            {{--<label for="{{$key}}">{{$val}}</label>--}}
                                            <input type="text" name="{{$key}}" class="form-control" id="{{$key}}"
                                                placeholder="{{$val}}"
                                                @if(isset($search_params[$key])) value="{{$search_params[$key]}}" @endif >
                                        </div>                                    
                                    @elseif($key =='answer' || $key='word')
                                        @if($master_name != 'wordcard' && $master_name != 'listeningtask' && $master_name != 'wordcardcategory')
                                            <div class="form-group col-md-3">
                                                {{--<label for="{{$key}}">{{$val}}</label>--}}
                                                <input type="text" name="{{$key}}" class="form-control" id="{{$key}}"
                                                    placeholder="{{$val}}"
                                                    @if(isset($search_params[$key])) value="{{$search_params[$key]}}" @endif >
                                            </div>
                                        @endif                                    
                                    @else
                                        @if($master_name != 'wordcardcategory' && $master_name != 'listeningtask')
                                            <div class="form-group col-md-3">
                                                {{--<label for="{{$key}}">{{$val}}</label>--}}
                                                <input type="text" name="{{$key}}" class="form-control" id="{{$key}}"
                                                    placeholder="{{$val}}"
                                                    @if(isset($search_params[$key])) value="{{$search_params[$key]}}" @endif >
                                            </div>
                                        @endif
                                    @endif
                                @endif
                            @endforeach
                            <!-- @if($master_name != 'wordcardcategory' && $master_name != 'wordcard' && $master_name != 'listeningtask') -->
                            <!-- @endif -->
                            <div class="form-group col-md-6" id = "datepicker-daterange">
                                <div class="form-inline">
                                    <label style="padding-right:10px;">作成日</label>
                                    <div class="input-daterange input-group" id="datepicker" style="width: 70%;">
                                        <input type="text" class="input-md time-input form-control" name="createDateSince" value="{{$createDateSince}}" />
                                        <span class="input-group-addon">〜</span>
                                        <input type="text" class="input-md time-input form-control" name="createDateUntil" value="{{$createDateUntil}}" />
                                    </div>
                                </div>
                            </div>
                            
                            @if($master_name != 'wordcardcategory')
                            <div class="form-group col-md-6">
                                {{--<label for="search_word">フリーワード</label>--}}
                                <input type="text" name="search_word" class="form-control" id="search_word"
                                       placeholder="フリーワード" @if(isset($search_word)) value="{{$search_word}}" @endif >
                            </div>
                            @endif
                        </div>
                        <div class="search-button-box">
                          <div class="search-btn-item">
                            <a href="/master/{{$master_name}}/{{$offset}}" class="btn btn-clear" name="btn-clear">クリア</a>
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
                        <span>{{$name}}一覧</span>
                    </div>
                    @if($master_name != 'wordcardcategory' && $master_name != 'listeningtask')
                    <div class="master-middle">
                        <div class="btn btn-domain open-create-modal" data-toggle="modal"
                                data-target="#newMasterModal">新規登録
                        </div>
                    </div>
                    @endif
                    @include('layouts.master-paging')

                    <div class="secondary-box">

                        <div class="hwork-item">
                          <table class="table table-hover core-tbl">
                            <thead>
                              <tr>
                                  @foreach($columns as $key => $val)
                                      @if($key != 'deleted_at' && $key != 'seq_number' && $key != 'module'  && $key != 'updated_at')                                        
                                          @if($key == 'created_at')
                                            <!-- @if($master_name != 'wordcard' && $master_name != 'wordcardcategory' && $master_name != 'listeningtask')
                                            @endif -->
                                              <th class="font-weight-bold">{{ $val }}/更新日</th>
                                                                                        
                                          @else
                                              <th class="font-weight-bold">{{ $val }}</th>
                                          @endif                                        
                                      @endif
                                  @endforeach
                                  <th></th>
                              </tr>
                            </thead>
                            <tbody>

                              @foreach($dataList as $key => $data)                              
                                  <tr>
                                      @foreach($data as $column => $val)
                                          @if($column != 'is_deleted' && $column != 'deleted_at' && $column != 'seq_number' && $column != 'module'  && $column != 'updated_at')
                                              @if($column == 'sr_category_id')
                                                  <td style="max-width: 100px; word-wrap: break-word;">{{ $sr_categories[$val]->category_name }}</td>
                                              @elseif($column == 'word_card_category_id') 
                                                  <td style="max-width: 100px; word-wrap: break-word;">{{ $data->category }}</td> 
                                              @elseif($column == 'created_at')
                                                  <td style="max-width: 100px; word-wrap: break-word;">{{ $val }}
                                                      <br/>{{ $data->updated_at }}</td>                                                
                                              @elseif($column == 'category')
                                                @if($master_name != 'wordcard')
                                                <td style="max-width: 100px; word-wrap: break-word;">{{ $val }}</td>
                                                @endif                                                                                                 
                                              @elseif($column == 'file_url1' || $column == 'file_url2')
                                                <td style="max-width: 100px; word-wrap: break-word;">
                                                    <a href = "{{ $val }}">{{$val}}</a>
                                                </td>
                                              @else
                                                  <td style="max-width: 100px; word-wrap: break-word;">{{ $val }}</td>
                                              @endif
                                          @endif
                                      @endforeach

                                      <td>
                                          
                                              <div class="btn btn-domain btn-table open-edit-modal" data-toggle="modal"
                                                   data-id="{{ $data->id }}" data-master="{{ $master_name }}"
                                                   data-target="#editMasterModal">編集する
                                              </div>
                                          
                                          @if($master_name != 'review' && $master_name != 'review_period' && $master_name != 'other_task')
                                              <div class="btn btn-domain btn-table open-delete-modal" data-toggle="modal"
                                                   data-id="{{ $data->id }}" data-master="{{ $master_name }}"
                                                   data-target="#deleteMasterModal">削除する
                                              </div>
                                          @endif
                                      </td>
                                  </tr>
                              @endforeach

                            </tbody>
                          </table>

                            
                        </div>
                    </div>

                    @include('layouts.master-paging')
                    <a href ="#" style="float:right;margin:20px;">ページTopへ戻る</a>
                </div>
            </div>
        </div>
    </div>
    </div>
    </div>

    <!-- Modal -->
    <form action="/master/{{$master_name}}/{{$offset}}" method="post">
        {{ csrf_field() }}
        <div class="modal fade" id="newMasterModal" role="dialog" aria-hidden="true">
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
                                <span>{{$name}}新規登録</span>
                            </div>
                            <div class="hero-form">
                                @foreach($columns as $key => $val)
                                    @if($key != 'id' && $key != 'deleted_at' && $key != 'created_at' && $key != 'updated_at')
                                        @if($key == 'focus' || $key == 'tips' || $key == 'comment' || $key == 'remark' || $key == 'sv' || $key == 'svc' || $key == 'svo' || $key == 'svoo' || $key == 'svoc')
                                            <div class="hero-form-item">
                                                <span>{{$val}}</span>
                                                <input name="create-{{$key}}" class="form-control"/>
                                            </div>
                                        @elseif($key == 'task_type_id')
                                            <div class="hero-form-item">
                                                <span>{{$val}}</span>
                                                <input name="create-{{$key}}" class="form-control"
                                                       value="{{$next_task_type_id}}" readonly="readonly"/>
                                            </div>

                                        @elseif($key == 'answer' || $key == 'question' || $key == 'task_context' || $key == 'review_context' || $key == 'context')
                                            <div class="hero-form-item">
                                                <span>{{$val}}<span class="text-danger">*</span></span>
                                                <textarea name="create-{{$key}}" class="form-control create-{{$key}}" value="" required></textarea>
                                            </div>

                                        @elseif($key == 'priority' || $key == 'period')
                                            <div class="hero-form-item">
                                                <span>{{$val}}<span class="text-danger">*</span></span>
                                                <input type="number" name="create-{{$key}}" class="form-control" min="1" required />
                                            </div>
                                        @elseif($key == 'word_card_category_id')
                                            <div class="hero-form-item dropdown">
                                                <span>{{$val}}<span class="text-danger">*</span></span>                                                
                                                <select class="select2" name="create-{{$key}}" id="{{$key}}_update" style='width:100%;'>                                                    
                                                    @foreach($wc_categories as $key2 => $val2)
                                                        <option value="{{$val2->id}}" @if(isset($search_params[$key]) && $search_params[$key] == $val2->id) selected="selected" @endif>
                                                            {{$val2->category}}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        @else
                                            <div class="hero-form-item">
                                                <span>{{$val}}<span class="text-danger">*</span></span>
                                                <input name="create-{{$key}}" class="form-control" required/>
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
    <form action="/master/{{$master_name}}/{{$offset}}" method="post">
        {{ csrf_field() }}
        <div class="modal fade" id="editMasterModal" role="dialog" aria-hidden="true">
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
                                <span>{{$name}}編集</span>
                            </div>
                            <div class="hero-form">
                                @foreach($columns as $key => $val)
                                    @if($key != 'deleted_at' && $key != 'updated_at')
                                        @if($key == 'id' || $key == 'created_at' || $key == 'task_type_id')
                                            <div class="hero-form-item">
                                                <span>{{$val}}</span>
                                                <input type="text" name="edit-{{$key}}" class="form-control edit-{{$key}}" value="" readonly="readonly"/>
                                            </div>

                                        @elseif($key == 'answer' || $key == 'question' || $key == 'task_context' || $key == 'review_context' || $key == 'context')
                                            <div class="hero-form-item">
                                                <span>{{$val}}<span class="text-danger">*</span></span>
                                                <textarea name="edit-{{$key}}" class="form-control edit-{{$key}}" value="" required></textarea>
                                            </div>

                                        @elseif($key == 'priority' || $key == 'period')
                                            <div class="hero-form-item">
                                                <span>{{$val}}<span class="text-danger">*</span></span>
                                                <input type="number" name="edit-{{$key}}" class="form-control edit-{{$key}}" min="1" value="" required />
                                            </div>
                                        @elseif($key == 'word_card_category_id')
                                            <div class="hero-form-item dropdown">
                                                <span>{{$val}}<span class="text-danger">*</span></span>                                                
                                                <select class="edit-{{$key}} select2 form-control" name="edit-{{$key}}" id="{{$key}}" style='width:100%;'>                                                    
                                                    @foreach($wc_categories as $key2 => $val2)
                                                        <option value="{{$val2->id}}"
                                                            @if(isset($search_params[$key]) && $search_params[$key] == $val2->id) selected="selected" @endif>
                                                            {{$val2->category}}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        @else
                                            @if($key == 'focus' || $key == 'tips' || $key == 'comment' || $key == 'remark'  || $key == 'sv' || $key == 'svc' || $key == 'svo' || $key == 'svoo' || $key == 'svoc')
                                                <div class="hero-form-item">
                                                    <span>{{$val}}</span>
                                                    <input type="text" name="edit-{{$key}}" class="edit-{{$key}} form-control"
                                                           value="" class="form-control"/>
                                                </div>
                                            @else
                                                <div class="hero-form-item">
                                                    <span>{{$val}}<span class="text-danger">*</span></span>
                                                    <input type="text" name="edit-{{$key}}" class="edit-{{$key}} form-control"
                                                           value="" class="form-control" required/>
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
    <form action="/master/{{$master_name}}/{{$offset}}" method="post">
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
                                <span>{{$name}}削除</span>
                            </div>
                            <p>以下の{{$name}}情報を削除します。よろしいですか？</p>
                            <div class="hero-form">
                                @foreach($columns as $key => $val)
                                    @if($key != 'deleted_at' && $key != 'updated_at')
                                        <div class="hero-form-item">
                                            <span>{{$val}}</span>
                                            <input type="text" name="delete-{{$key}}" class="delete-{{$key}}" value=""
                                                   class="form-control" readonly="readonly"/>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="btn-delete" value="delete" class="btn btn-register">削除する</button>
                        <input type="hidden" id="sel_delete_id" value="" name="sel_delete_id"/>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <script type="text/javascript">

        $(function () {

//編集モーダル
            $('.open-edit-modal').on('click', function () {

//idを取得
                data_id = $(this).attr('data-id');
                data_master = $(this).attr('data-master');

//該当マスタのデータ取得ajax
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });
                $.ajax({
                    url: '/api/master/getdata',
                    type: 'POST',
                    data: {
                        'data_id': data_id,
                        'data_master': data_master
                    }
                }).done(function (data) {
                    @foreach($columns as $key => $val)
                        @if($key != 'deleted_at' && $key != 'updated_at')                        
                            @if($key=='word_card_category_id')
                                $(".edit-{{$key}}").val(data['{{$key}}']).trigger('change');
                            @else
                                $('.edit-{{$key}}').val(data['{{$key}}']);
                                if (data['listening_type'] == 'D'){
                                    $('input[name=edit-file_url2]').hide();
                                    $('input[name=edit-file_url2]').siblings().hide();
                                }                                
                            @endif
                        @endif
                    @endforeach
                }).fail(function () {
//TODO
//console.log("failure");
                });

//取得したidをフォームにセット
                $('#sel_update_id').val(data_id);
            });

//削除モーダル
            $('.open-delete-modal').on('click', function () {

//idを取得
                data_id = $(this).attr('data-id');
                data_master = $(this).attr('data-master');

//該当マスタのデータ取得ajax
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });
                $.ajax({
                    url: '/api/master/getdata',
                    type: 'POST',
                    data: {
                        'data_id': data_id,
                        'data_master': data_master
                    }
                }).done(function (data) {                    
                    @foreach($columns as $key => $val)
                    @if($key != 'deleted_at' && $key != 'updated_at')
                        @if($key=='word_card_category_id')      
                            var wc_categories = <?php echo json_encode($wc_categories);?>;                        
                            $(".delete-{{$key}}").val(wc_categories[data['{{$key}}']]['category']);
                        @else
                            $('.delete-{{$key}}').val(data['{{$key}}']);
                        @endif
                    @endif
                    @endforeach
                }).fail(function () {
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
