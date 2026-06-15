<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <!-- Meta -->
    <meta name="description" content="{{env('APP_NAME')}}">
    <meta name="author" content="{{env('APP_NAME')}}">

    <!-- Favicon -->
    <link rel="shortcut icon" type="image/x-icon" href="{{asset('assets/img/favicon.png')}}">

    <title>{{env('APP_NAME')}}</title>

    <!-- vendor css -->
    <link href="{{asset('assets/lib/@fortawesome/fontawesome-free/css/all.min.css')}}" rel="stylesheet">
    <link href="{{asset('assets/lib/ionicons/css/ionicons.min.css')}}" rel="stylesheet">
    <link href="{{asset('assets/lib/typicons.font/typicons.css')}}" rel="stylesheet">
    <link href="{{asset('assets/lib/prismjs/themes/prism-vs.css')}}" rel="stylesheet">
    <link href="{{asset('assets/lib/datatables.net-dt/css/jquery.dataTables.min.css')}}" rel="stylesheet">
    <link href="{{asset('assets/lib/datatables.net-responsive-dt/css/responsive.dataTables.min.css')}}" rel="stylesheet">
    <link href="{{asset('assets/lib/select2/css/select2.min.css')}}" rel="stylesheet">
    <link href="{{asset('assets/lib/quill/quill.snow.css')}}" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-confirm/3.3.2/jquery-confirm.min.css">

    <!-- DashForge CSS -->
    <link rel="stylesheet" href="{{asset('assets/css/dashforge.css')}}">
    <link rel="stylesheet" href="{{asset('assets/css/custom.css')}}">
    <link rel="stylesheet" href="{{asset('assets/css/dashforge.demo.css')}}">
    <link rel="stylesheet" href="{{asset('assets/css/switch-button.css')}}">
    <link rel="stylesheet" href="{{asset('assets/css/dashforge.dashboard.css')}}">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/fancyapps/fancybox@3.5.7/dist/jquery.fancybox.min.css" />
    {{--    <link id="dfMode" rel="stylesheet" href="{{asset('assets/css/skin.dark.css')}}">--}}

    <link href="{{asset('assets/lib/datatable-button/css/buttons.bootstrap.min.css')}}" rel="stylesheet">
    <link href="{{asset('assets/lib/datatable-button/css/buttons.bootstrap4.min.css')}}" rel="stylesheet">
    <link href="{{asset('assets/lib/datatable-button/css/buttons.dataTables.min.css')}}" rel="stylesheet">
    <link href="{{asset('assets/lib/datatable-button/css/buttons.foundation.min.css')}}" rel="stylesheet">
    <link href="{{asset('assets/lib/datatable-button/css/buttons.jqueryui.min.css')}}" rel="stylesheet">
    <link href="{{asset('assets/lib/datatable-button/css/buttons.semanticui.min.css')}}" rel="stylesheet">
    <link href="{{asset('assets/lib/datatable-button/css/common.css')}}" rel="stylesheet">
    <link href="{{asset('assets/lib/datatable-button/css/mixins.css')}}" rel="stylesheet">

    <link rel="stylesheet" href="{{asset('assets/css/skin.deepblue.css')}}">
{{--
    <link rel="stylesheet" href="{{asset('assets/css/skin.dark.css')}}">
--}}
    <link rel="stylesheet" href="{{asset('css/app.css')}}">
    @stack('style')

</head>

