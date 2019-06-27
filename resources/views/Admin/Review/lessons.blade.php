@extends('layouts.Admin.master')

@section('styles')

@endsection

@section('body-contents')
    <div class="mainbar">
        <div class="primary-item">
            <span>レビュー管理 ＞ レッスンレビュー</span>
        </div>
        <div class="hero-box">
            <div class="primary-hero-box">
                <form action="/review/lessons/{{$offset}}" method="post">
                    {{ csrf_field() }}
                    <div class="hero-section">
                    <div class="secondary-item">
                        <span>検索項目</span>
                    </div>
                    <div class="secondary-box">
                        <div class="search-box">

                            <div class="search-secondary-box">
                                <div class="search-item">
                                    <div class="form-group" >
                                        <div class="form-inline">
                                            <div class="search-cond-item">
                                                <label for="alugo_user_id">会員ID</label>
                                                <input type="text"  class="form-control" id="alugo_user_id" name="alugo_user_id" placeholder="会員ID"
                                                       value="{{ isset($search_params['alugo_user_id'])? $search_params['alugo_user_id'] : ""}}" >
                                            </div>

                                            <div class="search-cond-item">
                                                <label for="alugo_user_id">講師ID</label>
                                                <input type="text"  class="form-control" id="teacher_name" name="teacher_name" placeholder="講師ID"
                                                       value="{{ isset($search_params['teacher_name']) ? $search_params['teacher_name'] : "" }}" >
                                            </div>

                                            <div class="search-cond-item">
                                                <label>レッスン実施日</label>
                                                <div class="input-daterange input-group" id="datepicker" style="width: 70%;">
                                                    <input type="text" class="input-md time-input form-control" name="questionSetDateSince" value="{{ isset($search_params['teacher_name']) ? $search_params['teacher_name'] : "" }}" />
                                                    <span class="input-group-addon">〜</span>
                                                    <input type="text" class="input-md time-input form-control" name="questionSetDateUntil" value="{{ isset($search_params['teacher_name']) ? $search_params['teacher_name'] : "" }}" />
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-inline" style="margin-top:15px;">
                                            <div class="search-cond-item">
                                                <label class="form-check-label" style="margin-right: 20px;">レビュー</label>

                                                <label class="form-check-label" for="eval_1">1</label>
                                                <span><input type="checkbox" name="eval" id="eval_1" style="height:auto; width:15px; margin-top:5px; margin-right: 20px;" value="1"></span>

                                                <label class="form-check-label" for="eval_2">2</label>
                                                <span><input type="checkbox" name="eval" id="eval_2" style="height:auto; width:15px; margin-top:5px; margin-right: 20px;" value="2"></span>

                                                <label class="form-check-label" for="eval_3">3</label>
                                                <span><input type="checkbox" name="eval" id="eval_3" style="height:auto; width:15px; margin-top:5px; margin-right: 20px;" value="3"></span>

                                                <label class="form-check-label" for="eval_4">4</label>
                                                <span><input type="checkbox" name="eval" id="eval_4" style="height:auto; width:15px; margin-top:5px; margin-right: 20px;" value="4"></span>

                                                <label class="form-check-label" for="eval_5">5</label>
                                                <span><input type="checkbox" name="eval" id="eval_5" style="height:auto; width:15px; margin-top:5px; margin-right: 20px;" value="5"></span>
                                            </div>

                                            <div class="search-cond-item">
                                                <label class="form-check-label" style="margin-right: 20px;">カテゴリ</label>

                                                <label class="form-check-label" for="category_1">音質</label>
                                                <span><input type="checkbox" name="category" id="category_1" style="height:auto; width:15px; margin-top:5px; margin-right: 20px;" value="1"></span>

                                                <label class="form-check-label" for="eval_2">講師</label>
                                                <span><input type="checkbox" name="category" id="category_2" style="height:auto; width:15px; margin-top:5px; margin-right: 20px;" value="2"></span>

                                                <label class="form-check-label" for="eval_3">カリキュラム</label>
                                                <span><input type="checkbox" name="category" id="category_3" style="height:auto; width:15px; margin-top:5px; margin-right: 20px;" value="3"></span>

                                                <label class="form-check-label" for="eval_4">コーチング</label>
                                                <span><input type="checkbox" name="category" id="category_4" style="height:auto; width:15px; margin-top:5px; margin-right: 20px;" value="4"></span>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>

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
                </form>

                <div class="hero-section">
                    <div class="secondary-item">
                        <span>レッスンレビュー一覧</span>
                    </div>
                    @include('layouts.review-paging')

                    <div class="secondary-box">
                        <div class="hwork-item">
                            <table class="table table-hover core-tbl">
                                <thead>
                                    <tr>
                                        <th class="font-weight-bold">No</th>
                                        <th class="font-weight-bold">ALUGO ID</th>
                                        <th class="font-weight-bold">開始日時</th>
                                        <th class="font-weight-bold">実施時間</th>
                                        <th class="font-weight-bold">ビュー</th>
                                        <th class="font-weight-bold">講師</th>
                                        <th class="font-weight-bold">その他</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach($dataList as $key => $data)
                                    <tr>
                                        <td>1</td>
                                        <td>{{ $data->alugo_user_id }}</td>
                                        <td>2019/06/23 23:30</td>
                                        <td>30分</td>
                                        <td>★★★★（講師）</td>
                                        <td>Mata Alex (122)</td>
                                        <td></td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>

                        @include('layouts.review-paging')

                    </div>

                </div>
            </div>
        </div>
    </div>

@endsection
@section('scripts')
@endsection
