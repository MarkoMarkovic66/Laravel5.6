@extends('layouts.Admin.master')

@section('styles')

@endsection

@section('body-contents')
    <div class="mainbar">
        <div class="primary-item">
            <span>プラン権限設定</span>
        </div>
        <div class="hero-box">
            <div class="primary-hero-box">
                <form action="/plan/{{$offset}}" method="post">
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
                                                    <label>パッケージ種別</label>
                                                    <div class="input-daterange input-group dropdown">
                                                        <select name="package_kind" class="form-control select2">
                                                            <option value="">指定なし</option>
                                                            <option value="1" >新規</option>
                                                            <option value="2" >継続</option>
                                                            <option value="3" >メンテナンス</option>
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="search-cond-item">
                                                    <label>パッケージ種別</label>
                                                    <div class="input-daterange input-group dropdown">
                                                        <select name="package_kind" class="form-control select2">
                                                            <option value="">指定なし</option>
                                                            <option value="1" >購入可能</option>
                                                            <option value="2" >購入不可</option>
                                                        </select>
                                                    </div>
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
                        <span>プラン一覧</span>
                    </div>

                    <button class="btn btn-search" style="float: right; margin-bottom: 10px;">保存する</button>

                    <div style="clear: both;"></div>
                    <div class="secondary-box">
                        <div class="hwork-item">
                            <table class="table table-hover core-tbl">
                                <thead>
                                <tr>
                                    <th class="font-weight-bold">プラン名</th>
                                    <th class="font-weight-bold">パッケージ種別</th>
                                    <th class="font-weight-bold">状態</th>
                                    <th class="font-weight-bold">カウンセリング</th>
                                    <th class="font-weight-bold">コーチングチャット</th>
                                    <th class="font-weight-bold">OKAWARI（瞬間口頭・SR）</th>
                                    <th class="font-weight-bold">瞬間口頭G配信上限</th>
                                    <th class="font-weight-bold">瞬間口頭V配信上限</th>
                                    <th class="font-weight-bold">Speaking Rally</th>
                                </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>ALUGO_SMART_ENGLISJ_LITE</td>
                                        <td>新規</td>
                                        <td>購入不可</td>
                                        <td class="text-center">
                                            <input name="counseling_1" id="counseling_1_on" type="radio" checked><label for="counseling_1_on">ON</label><label>&nbsp;/&nbsp;</label><input name="counseling_1" id="counseling_1_off"  type="radio"><label for="counseling_1_off">OFF</label>
                                        </td>
                                        <td class="text-center">
                                            <input name="chatting_1"  id="chatting_1_on" type="radio" checked><label for="chatting_1_on">ON</label><label>&nbsp;/&nbsp;</label><input name="chatting_1" id="chatting_1" type="radio"><label for="chatting_1">OFF</label>
                                        </td>
                                        <td class="text-center">
                                            <input name="okawari_1" id="okawari_1_on" type="radio" checked><label for="okawari_1_on">ON</label><label>&nbsp;/&nbsp;</label><input name="okawari_1" id="okawari_1_off" type="radio"><label for="okawari_1_off">OFF</label>
                                        </td>
                                        <td class="text-center">
                                            <input name="g_limit_1" id="g_limit_1_on" type="radio" checked><label for="g_limit_1_on">ON</label><label>&nbsp;/&nbsp;</label><input name="g_limit_1" id="g_limit_1_off" type="radio"><label for="g_limit_1_off">OFF</label>
                                        </td>
                                        <td class="text-center">
                                            <input name="v_limit_1" id="v_limit_1_on" type="radio" checked><label for="v_limit_1_on">ON</label><label>&nbsp;/&nbsp;</label><input name="v_limit_1" id="v_limit_1_off" type="radio"><label for="v_limit_1_off">OFF</label>
                                        </td>
                                        <td class="text-center">
                                            <input name="speaking_rally_1" id="speaking_rally_1_on" type="radio" checked><label for="speaking_rally_1_on">ON</label><label>&nbsp;/&nbsp;</label><input name="speaking_rally_1" id="speaking_rally_1_off" type="radio"><label for="speaking_rally_1_off">OFF</label>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>メンテナンスプラン</td>
                                        <td>新規</td>
                                        <td>購入不可</td>
                                        <td class="text-center">
                                            <input name="counseling_2" id="counseling_2_on" type="radio" checked><label for="counseling_2_on">ON</label><label>&nbsp;/&nbsp;</label><input name="counseling_2" id="counseling_2_off"  type="radio"><label for="counseling_2_off">OFF</label>
                                        </td>
                                        <td class="text-center">
                                            <input name="chatting_2"  id="chatting_2_on" type="radio" checked><label for="chatting_2_on">ON</label><label>&nbsp;/&nbsp;</label><input name="chatting_2" id="chatting_2" type="radio"><label for="chatting_2">OFF</label>
                                        </td>
                                        <td class="text-center">
                                            <input name="okawari_2" id="okawari_2_on" type="radio" checked><label for="okawari_2_on">ON</label><label>&nbsp;/&nbsp;</label><input name="okawari_2" id="okawari_2_off" type="radio"><label for="okawari_2_off">OFF</label>
                                        </td>
                                        <td class="text-center">
                                            <input name="g_limit_2" id="g_limit_2_on" type="radio" checked><label for="g_limit_2_on">ON</label><label>&nbsp;/&nbsp;</label><input name="g_limit_2" id="g_limit_2_off" type="radio"><label for="g_limit_2_off">OFF</label>
                                        </td>
                                        <td class="text-center">
                                            <input name="v_limit_2" id="v_limit_2_on" type="radio" checked><label for="v_limit_2_on">ON</label><label>&nbsp;/&nbsp;</label><input name="v_limit_2" id="v_limit_2_off" type="radio"><label for="v_limit_2_off">OFF</label>
                                        </td>
                                        <td class="text-center">
                                            <input name="speaking_rally_2" id="speaking_rally_2_on" type="radio" checked><label for="speaking_rally_2_on">ON</label><label>&nbsp;/&nbsp;</label><input name="speaking_rally_2" id="speaking_rally_2_off" type="radio"><label for="speaking_rally_2_off">OFF</label>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>メンテナンスプラン Lite</td>
                                        <td>新規</td>
                                        <td>購入不可</td>
                                        <td class="text-center">
                                            <input name="counseling_3" id="counseling_3_on" type="radio" checked><label for="counseling_3_on">ON</label><label>&nbsp;/&nbsp;</label><input name="counseling_3" id="counseling_3_off"  type="radio"><label for="counseling_3_off">OFF</label>
                                        </td>
                                        <td class="text-center">
                                            <input name="chatting_3"  id="chatting_3_on" type="radio" checked><label for="chatting_3_on">ON</label><label>&nbsp;/&nbsp;</label><input name="chatting_3" id="chatting_3" type="radio"><label for="chatting_3">OFF</label>
                                        </td>
                                        <td class="text-center">
                                            <input name="okawari_3" id="okawari_3_on" type="radio" checked><label for="okawari_3_on">ON</label><label>&nbsp;/&nbsp;</label><input name="okawari_3" id="okawari_3_off" type="radio"><label for="okawari_3_off">OFF</label>
                                        </td>
                                        <td class="text-center">
                                            <input name="g_limit_3" id="g_limit_3_on" type="radio" checked><label for="g_limit_3_on">ON</label><label>&nbsp;/&nbsp;</label><input name="g_limit_3" id="g_limit_3_off" type="radio"><label for="g_limit_3_off">OFF</label>
                                        </td>
                                        <td class="text-center">
                                            <input name="v_limit_3" id="v_limit_3_on" type="radio" checked><label for="v_limit_3_on">ON</label><label>&nbsp;/&nbsp;</label><input name="v_limit_3" id="v_limit_3_off" type="radio"><label for="v_limit_3_off">OFF</label>
                                        </td>
                                        <td class="text-center">
                                            <input name="speaking_rally_3" id="speaking_rally_3_on" type="radio" checked><label for="speaking_rally_3_on">ON</label><label>&nbsp;/&nbsp;</label><input name="speaking_rally_3" id="speaking_rally_3_off" type="radio"><label for="speaking_rally_3_off">OFF</label>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                    </div>

                </div>
            </div>
        </div>
    </div>

@endsection
@section('scripts')
@endsection
