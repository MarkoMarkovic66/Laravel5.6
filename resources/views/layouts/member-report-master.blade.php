<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="title" content="alue-subsys">
    <meta http-equiv="Pragma" content="no-cache">
  	<meta http-equiv="Cache-Control" content="no-cache">
    <title>アルー｜宿題・会員管理システム</title>

    <!-- Reset -->
    <!-- bootstrap4ではbootstrap-rebootを使用する
    <link href="https://cdnjs.cloudflare.com/ajax/libs/meyer-reset/2.0/reset.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/normalize/7.0.0/normalize.min.css" rel="stylesheet">
    -->
    <link href="/lib/bootstrap-4.0.0/css/bootstrap-reboot.min.css" rel="stylesheet">

    <!-- Bootstrap -->
    <link href="/lib/bootstrap-4.0.0/css/bootstrap.min.css" rel="stylesheet">

    {{-- 下記はboorstrap3のcss(暫定適用) → 2018-02-21 コメントアウト  --}}
    {{-- <link href="/lib/bootstrap-3.3.7/css/bootstrap.min.css" rel="stylesheet"> --}}

    <!-- Latest compiled and minified CSS -->
    <link href="/lib/bootstrap-datepicker-1.7.0/css/bootstrap-datepicker.min.css" rel="stylesheet">

    {{-- bootstrap-select-1.13.0 は bootstrap4 では不具合となるため使用しない。select2を使用する --}}
    {{-- <link href="/lib/bootstrap-select-1.13.0/css/bootstrap-select.min.css" rel="stylesheet"> --}}
    <link href="/lib/select2-4.0.6/css/select2.min.css" rel="stylesheet">

    <!-- {{-- Fontawesome --}}
    <link href="/lib/fontawesome/css/font-awesome.css" rel="stylesheet"> -->

    {{-- jQueryUI Draggable, Droppable --}}
    <link href="/lib/jquery-ui-1.12.1/css/jquery-ui.min.css" rel="stylesheet">

    <!-- 共通CSS include -->
    <link href="/img/favicon.ico" rel="shortcut icon" />
    <link href="{{ mix('/css/style.css') }}" rel="stylesheet" />

    @yield('styles')

    <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
    <script src="/lib/jquery-3.3.1/jquery.min.js"></script>

    <script src="/lib/bootstrap-datepicker-1.7.0/js/bootstrap-datepicker.min.js"></script>
    <script src="/lib/bootstrap-datepicker-1.7.0/js/bootstrap-datepicker.ja.min.js"></script>

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
  </head>
  <body>
    @include('layouts.member-report-header')

    <div class="main">
      <div class="wrap">
        @yield('body-contents')
      </div>
    </div>

    @include('layouts.footer')

    <!-- 汎用ローディングアイコン表示  -->
    <div id="globalOnLoadingIcon" class="onLoadingIcon" style="display:none;"></div>

    <!-- 全画面マスク  -->
    <div id="maskWholeDisplay" class="maskWholeDisplay" style="display:none;"></div>

    <!-- Include all compiled plugins (below), or include individual files as needed -->
    <script src="/lib/bootstrap-4.0.0/js/bootstrap.bundle.min.js"></script>
    <script src="/lib/tether/js/tether.min.js"></script>

    {{-- <script src="/lib/bootstrap-select-1.13.0/js/bootstrap-select.min.js"></script> --}}
    <script src="/lib/select2-4.0.6/js/select2.min.js"></script>
    <script src="/lib/select2-4.0.6/js/i18n/ja.js"></script>

    {{-- Fontawesome --}}
    <script src="/lib/fontawesome/js/fontawesome-all.min.js"></script>

    <!-- 共通JS include -->
    <script src="{{ mix('/js/namespace.js') }}"></script>
    <script src="{{ mix('/js/main.js') }}"></script>
    <script src="{{ mix('/js/utils.js') }}"></script>
    <script src="{{ mix('/js/ajaxUtils.js') }}"></script>

    <!-- 個別JS include -->
    @yield('scripts')

  </body>
</html>
