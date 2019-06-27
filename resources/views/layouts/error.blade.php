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
    <!-- 下記はboorstrap3のcss(暫定適用) -->
    <link href="/lib/bootstrap-3.3.7/css/bootstrap.min.css" rel="stylesheet">

    
    <!-- 共通CSS include -->
    <link href="/img/favicon.ico" rel="shortcut icon" />
    <link href="{{ mix('/css/style.css') }}" rel="stylesheet" />

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
  </head>
  <body>
    @include('layouts.header')

    <div class="main">
      <div class="wrap">
        @yield('body-contents')
      </div>
    </div>

    @include('layouts.footer')

    <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
    <script src="/lib/jquery-3.3.1/jquery.min.js"></script>
    <!-- Include all compiled plugins (below), or include individual files as needed -->
    <script src="/lib/bootstrap-4.0.0/js/bootstrap.bundle.min.js"></script>
    <script src="/lib/tether/js/tether.min.js"></script>

    <!-- 共通JS include -->
    <script src="{{ mix('/js/namespace.js') }}"></script>
    <script src="{{ mix('/js/main.js') }}"></script>
    <script src="{{ mix('/js/utils.js') }}"></script>
    <script src="{{ mix('/js/ajaxUtils.js') }}"></script>

    <!-- 個別JS include -->
    @yield('scripts')

  </body>
</html>
