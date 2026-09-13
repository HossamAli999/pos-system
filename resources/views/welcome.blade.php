@extends('layouts.home')
@section('title', config('app.name', 'ultimatePOS'))

@section('content')
<section class="hero">
    <div class="hero__inner">
        <span class="hero__eyebrow"><i class="fas fa-bolt"></i> @lang('lang_v1.pos_module')</span>
        <h1 class="hero__title">
            @lang('lang_v1.run_your_shop_with')
            <span class="grad">{{ config('app.name', 'ultimatePOS') }}</span>
        </h1>
        <p class="hero__subtitle">
            @if(!empty(env('APP_TITLE')))
                {{ env('APP_TITLE') }}
            @else
                @lang('lang_v1.pos_hero_subtitle')
            @endif
        </p>
        <div class="hero__actions">
            @if(Route::has('login'))
                @if(!Auth::check())
                    @if(config('constants.allow_registration'))
                        <a href="{{ route('business.getRegister') }}" class="hero__btn-primary">
                            <i class="fas fa-rocket"></i> @lang('lang_v1.get_started')
                        </a>
                    @endif
                    <a href="{{ route('login') }}" class="hero__btn-secondary">
                        <i class="fas fa-sign-in-alt"></i> @lang('lang_v1.login')
                    </a>
                @else
                    <a href="{{ action([\App\Http\Controllers\HomeController::class, 'index']) }}" class="hero__btn-primary">
                        <i class="fas fa-th-large"></i> @lang('home.home')
                    </a>
                @endif
            @endif
        </div>
        <p class="hero__note">@lang('lang_v1.no_card_required')</p>
    </div>
</section>

<section class="features">
    <div class="features__inner">
        <div class="features__head">
            <h2>@lang('lang_v1.everything_you_need')</h2>
            <p>@lang('lang_v1.everything_you_need_subtitle')</p>
        </div>
        <div class="features__grid">
            <div class="feature-card">
                <span class="feature-card__icon"><i class="fas fa-cash-register"></i></span>
                <h3>@lang('sale.pos_sale')</h3>
                <p>@lang('lang_v1.feature_pos_desc')</p>
            </div>
            <div class="feature-card">
                <span class="feature-card__icon"><i class="fas fa-boxes"></i></span>
                <h3>@lang('lang_v1.feature_inventory_title')</h3>
                <p>@lang('lang_v1.feature_inventory_desc')</p>
            </div>
            <div class="feature-card">
                <span class="feature-card__icon"><i class="fas fa-chart-line"></i></span>
                <h3>@lang('report.reports')</h3>
                <p>@lang('lang_v1.feature_reports_desc')</p>
            </div>
            <div class="feature-card">
                <span class="feature-card__icon"><i class="fas fa-store"></i></span>
                <h3>@lang('lang_v1.feature_locations_title')</h3>
                <p>@lang('lang_v1.feature_locations_desc')</p>
            </div>
        </div>
    </div>
</section>

@if(Route::has('login') && !Auth::check() && config('constants.allow_registration'))
<section class="cta-band">
    <h2>@lang('lang_v1.ready_to_get_started')</h2>
    <p>@lang('lang_v1.ready_to_get_started_subtitle')</p>
    <a href="{{ route('business.getRegister') }}" class="hero__btn-primary">
        <i class="fas fa-rocket"></i> @lang('lang_v1.get_started')
    </a>
</section>
@endif

<footer class="landing-footer">
    {{ config('app.name', 'ultimatePOS') }} &copy; {{ date('Y') }}
</footer>
@endsection
