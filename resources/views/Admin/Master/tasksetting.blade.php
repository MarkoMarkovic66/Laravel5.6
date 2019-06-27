@extends('layouts.Admin.master')

@section('styles')
@endsection

@section('body-contents')
    <div class="mainbar">
        <div class="primary-item">
            <span>マスタ管理 ＞ {{$name}}</span>
        </div>
        <div class="hero-box">
            <div class="primary-hero-box">
                <form action="/master/{{$master_name}}" method="post">
                    {{ csrf_field() }}
                    <div class="hero-section">
                        <div class="secondary-item">
                            <span>{{$name}}</span>
                        </div>
                        <div class="secondary-box">
                          <table class="table table-hover core-tbl">
                            <thead>
                              <tr>
                                <th class="master-table-primary" colspan="2">アセスメント結果</th>
                                <th class="master-table-secondary" colspan="7">曜日ごとの配信項目</th>
                              </tr>
                              <tr>
                                  <td class="master-table-primary-item" id="first-tbl">アセスメント項目</td>
                                  <td class="master-table-primary-item">Grade</td>
                                  <td class="master-table-secondary-item">月</td>
                                  <td class="master-table-secondary-item">火</td>
                                  <td class="master-table-secondary-item">水</td>
                                  <td class="master-table-secondary-item">木</td>
                                  <td class="master-table-secondary-item">金</td>
                                  <td class="master-table-secondary-item">土</td>
                                  <td class="master-table-secondary-item">日</td>
                              </tr>
                            </thead>
                            <tbody>
                              <tr>
                                  @foreach($task_setting_data as $assessment_item => $grade_arr)
                                      @foreach($grade_arr as $key => $day_number_arr)
                                          <td id="first-tbl">{{$day_number_arr['assessment_item']}}</td>
                                          <td>{{$day_number_arr['grade']}}</td>
                                          @foreach($day_number_arr['day_number'] as $day_number => $val)
                                              <td>
                                                  <select class="form-control" name="{{$val['id']}}" id="{{$key}}">
                                                      @foreach($task_type_arr as $task_type => $task_type_name)
                                                          <option value="{{$task_type}}"
                                                                  @if($task_type == $val['task_type']) selected="selected" @endif >{{$task_type_name}}</option>
                                                      @endforeach
                                                  </select>
                                              </td>
                                          @endforeach
                              </tr>
                              <tr>
                                  @endforeach
                                  @endforeach
                              </tr>
                            </tbody>
                          </table>

                            <!-- <table class="master-table">
                                <tr>
                                    <th class="master-table-primary" colspan="2">アセスメント結果(選択条件)</th>
                                    <th class="master-table-secondary" colspan="7">曜日ごとの配信項目(選択結果項目)</th>
                                </tr>
                                <tr>
                                    <td class="master-table-primary-item">アセスメント項目</td>
                                    <td class="master-table-primary-item">Grade</td>
                                    <td class="master-table-secondary-item">月</td>
                                    <td class="master-table-secondary-item">火</td>
                                    <td class="master-table-secondary-item">水</td>
                                    <td class="master-table-secondary-item">木</td>
                                    <td class="master-table-secondary-item">金</td>
                                    <td class="master-table-secondary-item">土</td>
                                    <td class="master-table-secondary-item">日</td>
                                </tr>
                                <tr>
                                    @foreach($task_setting_data as $assessment_item => $grade_arr)
                                        @foreach($grade_arr as $key => $day_number_arr)
                                            <td>{{$day_number_arr['assessment_item']}}</td>
                                            <td>{{$day_number_arr['grade']}}</td>
                                            @foreach($day_number_arr['day_number'] as $day_number => $val)
                                                <td>
                                                    <select class="form-control" name="{{$val['id']}}" id="{{$key}}">
                                                        @foreach($task_type_arr as $task_type => $task_type_name)
                                                            <option value="{{$task_type}}"
                                                                    @if($task_type == $val['task_type']) selected="selected" @endif >{{$task_type_name}}</option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                            @endforeach
                                </tr>
                                <tr>
                                    @endforeach
                                    @endforeach
                                </tr>
                            </table> -->
                        </div>

                        <div class="master-logic-footer">
                            <button type="reset" class="btn btn-clear">クリア</button>
                            <button type="submit" name="btn-tasksetting" value="tasksetting" class="btn btn-register">
                                設定を保存する
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@section('scripts')
@endsection
