<?php
use App\Enums\SessionValiableType;

$activeMenuName = $view_params->get('menuName');
$accountInfo = Session::get(SessionValiableType::LOGIN_ACCOUNT_INFO);
$accountKindId = $accountInfo['account_kind_id'];
//$login_user = (isset($login_user)) ? $login_user : null;
$master_name = (isset($master_name) == true) ? $master_name : null;
$review_name = (isset($review_name) == true) ? $review_name : null;

$coaching_manage_url = env('COACHING_MANAGE_URL');

?>

<div class="sidebar">
    <ul class="sidebar-nav">
    {{--
        <li @if($activeMenuName ==  \App\Enums\MenuNameType::MENU_TOP) id="active" @endif>
            <a href="/top"><i class="fas fa-home"></i> ダッシュボード</a>
        </li>
    --}}

        <li @if($activeMenuName ==  \App\Enums\MenuNameType::MENU_TASK) id="active" @endif>
            <a href="/task"><i class="fas fa-sticky-note"></i> 宿題管理</a>
        </li>

        <li @if($activeMenuName ==  \App\Enums\MenuNameType::MENU_MEMBER) id="active" @endif>
             <a href="/member"><i class="fas fa-graduation-cap"></i> 会員管理</a>
        </li>


        <li @if($activeMenuName ==  \App\Enums\MenuNameType::MENU_COACH) id="active" @endif>
            <a href="{{ $coaching_manage_url }}" target="_blank"><i class="fas fa-comments"></i> コーチング管理</a>
        </li>

        <li @if($activeMenuName ==  \App\Enums\MenuNameType::MENU_PLAN) id="active" @endif>
            <a href="/plan" ><i class="fas fa-list-ol"></i> プラン権限設定</a>
        </li>

        <li>
            <i class="fas fa-star"></i><span style="opacity: 0.7;"> レビュー管理</span>
        </li>

        <li class="master-nav" @if($review_name == 'lessons') id="active" @endif ><a href="/review/lessons">- レッスンレビュー</a></li>
        <li class="master-nav" @if($review_name == 'counselors') id="active" @endif ><a href="/review/counselors">- カウンセラーレビュー</a></li>
        <li class="master-nav" @if($review_name == 'services') id="active" @endif ><a href="/review/services">- サービスレビュー</a></li>


    @if($accountKindId != null && $accountKindId == 1 || $accountKindId == 2)

        {{--
            <li @if($activeMenuName ==  \App\Enums\MenuNameType::MENU_CHATWORK) id="active" @endif>
                <!-- <a href="/message"><i class="fas fa-comments"></i> メッセージ管理</a> -->
                <a href="#"><i class="fas fa-comments"></i> メッセージ管理</a>
            </li>
        --}}


        {{--
            <li @if($activeMenuName ==  \App\Enums\MenuNameType::MENU_ACCOUNT) id="active" @endif>
                <a href="/account"><i class="fas fa-user"></i> アカウント管理</a>
            </li>
        --}}
            <li>
                <i class="fas fa-asterisk"></i><span style="opacity: 0.7;"> マスタ管理</span>
            </li>
            
            <li class="master-nav" @if($master_name == 'wordcard') id="active" @endif ><a href="/master/wordcard">- 単語カード</a></li>
            <li class="master-nav" @if($master_name == 'listeningtask') id="active" @endif ><a href="/master/listeningtask">- リスニング</a></li>
            <li class="master-nav" @if($master_name == 'grammar') id="active" @endif ><a href="/master/grammar">- 瞬間口頭G</a></li>
            <li class="master-nav" @if($master_name == 'vocabulary') id="active" @endif ><a href="/master/vocabulary">- 瞬間口頭V</a></li>
            <li class="master-nav" @if($master_name == 'gsl_verb_matrix') id="active" @endif ><a href="/master/gsl_verb_matrix">- GSL動詞/文型</a></li>
            <li class="master-nav" @if($master_name == 'gsl_word_freq') id="active" @endif ><a href="/master/gsl_word_freq">- GSL単語頻度</a></li>
            <li class="master-nav" @if($master_name == 'speakingrally') id="active" @endif ><a href="/master/speakingrally">- Speaking Rally</a></li>
            <li class="master-nav" @if($master_name == 'sr_category') id="active" @endif ><a href="/master/sr_category">- SRカテゴリ</a></li>
            {{--<li class="master-nav" @if($master_name == 'review') id="active" @endif ><a href="/master/review">- 復習</a></li>
            <li class="master-nav" @if($master_name == 'review_period') id="active" @endif ><a href="/master/review_period">- 復習サイクル</a></li>
            <li class="master-nav" @if($master_name == 'other_task') id="active" @endif ><a href="/master/other_task">- その他宿題</a></li>--}}
            <li class="master-nav" @if($master_name == 'task_setting') id="active" @endif ><a href="/master/task_setting">- 出題ロジック設定</a></li>
            <li class="master-nav" @if($master_name == 'lp_category') id="active" @endif ><a href="/master/lp_category">- 学習方針カテゴリ</a></li>
            <li class="master-nav" @if($master_name == 'wordcardcategory') id="active" @endif ><a href="/master/wordcardcategory">- 単語カードカテゴリ</a></li>

        {{--
            <li @if($activeMenuName ==  \App\Enums\MenuNameType::MENU_BATCH) id="active" @endif>
                <!-- <a href="/batch"><i class="fas fa-cog"></i> バッチ管理</a> -->
                <a href="#"><i class="fas fa-cog"></i> バッチ管理</a>
            </li>
        --}}

        @endif

    </ul>
</div>
