<header class="landing-header">
  <div class="landing-header__inner">
    <a class="landing-logo" href="/">
      <span class="landing-logo__mark"><i class="fas fa-layer-group"></i></span>
      {{ config('app.name', 'ultimatePOS') }}
    </a>

    <button type="button" class="landing-nav-toggle" data-toggle="collapse" data-target="#landing-nav" aria-expanded="false" aria-controls="landing-nav">
      <span class="sr-only">Toggle navigation</span>
      <i class="fas fa-bars"></i>
    </button>

    <div id="landing-nav" class="landing-nav collapse">
      <ul class="landing-nav__links">
        @if(Auth::check())
            <li><a href="{{ action([\App\Http\Controllers\HomeController::class, 'index']) }}">@lang('home.home')</a></li>
        @endif
        @if(Route::has('frontend-pages') && config('app.env') != 'demo'
        && !empty($frontend_pages))
            @foreach($frontend_pages as $page)
                <li><a href="{{ action([\Modules\Superadmin\Http\Controllers\PageController::class, 'showPage'], $page->slug) }}">{{$page->title}}</a></li>
            @endforeach
        @endif
        @if(Route::has('pricing') && config('app.env') != 'demo')
        <li><a href="{{ action([\Modules\Superadmin\Http\Controllers\PricingController::class, 'index']) }}">@lang('superadmin::lang.pricing')</a></li>
        @endif
        @if(Route::has('repair-status'))
        <li>
          <a href="{{ action([\Modules\Repair\Http\Controllers\CustomerRepairStatusController::class, 'index']) }}">
            @lang('repair::lang.repair_status')
          </a>
        </li>
        @endif
      </ul>

      <div class="landing-nav__cta">
        @if (Route::has('login'))
            @if(!Auth::check())
                <a href="{{ route('login') }}" class="landing-nav__login">@lang('lang_v1.login')</a>
                @if(config('constants.allow_registration'))
                    <a href="{{ route('business.getRegister') }}" class="landing-nav__register">@lang('lang_v1.register')</a>
                @endif
            @endif
        @endif
      </div>
    </div>
  </div>
</header>
