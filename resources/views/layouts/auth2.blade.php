<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <!-- Tell the browser to be responsive to screen width -->
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title') - {{ config('app.name', 'POS') }}</title>

    @include('layouts.partials.css')

    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
</head>

<body class="auth-body">
    @inject('request', 'Illuminate\Http\Request')
    @if (session('status') && session('status.success'))
        <input type="hidden" id="status_span" data-status="{{ session('status.success') }}" data-msg="{{ session('status.msg') }}">
    @endif

    <div class="auth-shell">
        <div class="auth-shell__brand">
            <div class="auth-shell__brand-inner">
                <a href="/" class="auth-shell__logo">
                    @if(file_exists(public_path('uploads/logo.png')))
                        <img src="/uploads/logo.png" alt="Logo">
                    @else
                        <span class="auth-shell__logo-mark"><i class="fas fa-layer-group"></i></span>
                        {{ config('app.name', 'POS') }}
                    @endif
                </a>
                @if(!empty(config('constants.app_title')))
                    <p class="auth-shell__tagline">{{config('constants.app_title')}}</p>
                @endif

                <ul class="auth-shell__points">
                    <li><i class="fas fa-check"></i> @lang('lang_v1.feature_pos_desc')</li>
                    <li><i class="fas fa-check"></i> @lang('lang_v1.feature_inventory_desc')</li>
                    <li><i class="fas fa-check"></i> @lang('lang_v1.feature_reports_desc')</li>
                </ul>
            </div>
        </div>

        <div class="auth-shell__panel">
            <div class="auth-shell__topbar">
                <select class="auth-lang-select" id="change_lang">
                    @foreach(config('constants.langs') as $key => $val)
                        <option value="{{$key}}"
                            @if( (empty(request()->lang) && config('app.locale') == $key)
                            || request()->lang == $key)
                                selected
                            @endif
                        >
                            {{$val['full_name']}}
                        </option>
                    @endforeach
                </select>

                <div class="auth-shell__topbar-links">
                    @if(!($request->segment(1) == 'business' && $request->segment(2) == 'register'))
                        @if(config('constants.allow_registration'))
                            <a href="{{ route('business.getRegister') }}@if(!empty(request()->lang)){{'?lang=' . request()->lang}} @endif" class="auth-shell__link-ghost">
                                {{ __('business.not_yet_registered')}} <strong>{{ __('business.register_now') }}</strong>
                            </a>
                            @if(Route::has('pricing') && config('app.env') != 'demo' && $request->segment(1) != 'pricing')
                                <a href="{{ action([\Modules\Superadmin\Http\Controllers\PricingController::class, 'index']) }}" class="auth-shell__link-plain">@lang('superadmin::lang.pricing')</a>
                            @endif
                        @endif
                    @endif
                    @if($request->segment(1) != 'login')
                        <span class="auth-shell__link-plain">{{ __('business.already_registered')}}
                            <a href="{{ action([\App\Http\Controllers\Auth\LoginController::class, 'login']) }}@if(!empty(request()->lang)){{'?lang=' . request()->lang}} @endif">{{ __('business.sign_in') }}</a>
                        </span>
                    @endif
                </div>
            </div>

            <div class="auth-shell__content">
                @yield('content')
            </div>
        </div>
    </div>

    @include('layouts.partials.javascripts')

    <!-- Scripts -->
    <script src="{{ asset('js/login.js?v=' . $asset_v) }}"></script>

    @yield('javascript')

    <script type="text/javascript">
        $(document).ready(function(){
            $('.select2_register').select2();

            $('input').iCheck({
                checkboxClass: 'icheckbox_square-blue',
                radioClass: 'iradio_square-blue',
                increaseArea: '20%' // optional
            });
        });
    </script>
</body>

</html>
