@inject('request', 'Illuminate\Http\Request')
<!-- Main Header -->
  <header class="main-header main-header--no-logo no-print">
    <!-- Header Navbar -->
    <nav class="navbar navbar-static-top" role="navigation">
      <!-- Sidebar toggle button-->
      <a href="#" class="sidebar-toggle" data-toggle="offcanvas" role="button">
        &#9776;
        <span class="sr-only">Toggle navigation</span>
      </a>

      <!-- Navbar Right Menu -->
      <div class="navbar-custom-menu">
        <div class="header-toolbar">

            <a href="{{route('home')}}" class="header-brand">
                <span class="header-brand__mark"><i class="fas fa-layer-group"></i></span>
                <span class="header-brand__name">{{ Session::get('business.name') }}</span>
                <i class="fa fa-circle text-success header-online-indicator" id="online_indicator" title="@lang('lang_v1.connection_status')"></i>
            </a>

            @if(Module::has('Superadmin'))
              @includeIf('superadmin::layouts.partials.active_subscription')
            @endif

            @if(!empty(session('previous_user_id')) && !empty(session('previous_username')))
                <a href="{{route('sign-in-as-user', session('previous_user_id'))}}" class="btn btn-flat btn-danger btn-sm"><i class="fas fa-undo"></i> @lang('lang_v1.back_to_username', ['username' => session('previous_username')] )</a>
            @endif

            @if(Module::has('Essentials'))
              @includeIf('essentials::layouts.partials.header_part')
            @endif

            <div class="header-toolbar__group">
                <div class="btn-group">
                  <button id="header_shortcut_dropdown" type="button" class="header-icon-btn dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="@lang('lang_v1.quick_actions')">
                    <i class="fas fa-plus"></i>
                  </button>
                  <ul class="dropdown-menu">
                    @if(config('app.env') != 'demo')
                      <li><a href="{{route('calendar')}}">
                          <i class="fas fa-calendar-alt" aria-hidden="true"></i> @lang('lang_v1.calendar')
                      </a></li>
                    @endif
                    @if(Module::has('Essentials'))
                      <li><a href="#" class="btn-modal" data-href="{{action([\Modules\Essentials\Http\Controllers\ToDoController::class, 'create'])}}" data-container="#task_modal">
                          <i class="fas fa-clipboard-check" aria-hidden="true"></i> @lang( 'essentials::lang.add_to_do' )
                      </a></li>
                    @endif
                    <!-- Help Button -->
                    @if(auth()->user()->hasRole('Admin#' . auth()->user()->business_id))
                      <li><a id="start_tour" href="#">
                          <i class="fas fa-question-circle" aria-hidden="true"></i> @lang('lang_v1.application_tour')
                      </a></li>
                    @endif
                  </ul>
                </div>

                <button id="btnCalculator" title="@lang('lang_v1.calculator')" type="button" class="header-icon-btn popover-default hidden-xs" data-toggle="popover" data-trigger="click" data-content='@include("layouts.partials.calculator")' data-html="true" data-placement="bottom">
                    <i class="fa fa-calculator" aria-hidden="true"></i>
                </button>

                @if(in_array('hr', $enabled_modules))
                  @php
                    $__hr_employee = auth()->user()->employee;
                    $__hr_today_log = null;
                    if (! empty($__hr_employee)) {
                        $__hr_today_log = \App\AttendanceLog::where('employee_id', $__hr_employee->id)
                            ->where('attendance_date', \Carbon::now()->toDateString())
                            ->first();
                    }
                  @endphp
                  @if(! empty($__hr_employee))
                    @if(empty($__hr_today_log) || empty($__hr_today_log->clock_in))
                      <button type="button" id="hr_clock_in_btn" title="@lang('hr.clock_in')" data-toggle="tooltip" data-placement="bottom" class="header-icon-btn">
                        <i class="fa fas fa-sign-in-alt"></i> @lang('hr.clock_in')
                      </button>
                    @elseif(empty($__hr_today_log->clock_out))
                      <button type="button" id="hr_clock_out_btn" title="@lang('hr.clock_out')" data-toggle="tooltip" data-placement="bottom" class="header-icon-btn header-icon-btn--danger">
                        <i class="fa fas fa-sign-out-alt"></i> @lang('hr.clock_out')
                      </button>
                    @endif
                  @endif
                @endif

                @if($request->segment(1) == 'pos')
                  @can('view_cash_register')
                  <button type="button" id="register_details" title="{{ __('cash_register.register_details') }}" data-toggle="tooltip" data-placement="bottom" class="header-icon-btn btn-modal" data-container=".register_details_modal"
                  data-href="{{ action([\App\Http\Controllers\CashRegisterController::class, 'getRegisterDetails'])}}">
                    <i class="fa fa-briefcase" aria-hidden="true"></i>
                  </button>
                  @endcan
                  @can('close_cash_register')
                  <button type="button" id="close_register" title="{{ __('cash_register.close_register') }}" data-toggle="tooltip" data-placement="bottom" class="header-icon-btn header-icon-btn--danger btn-modal" data-container=".close_register_modal"
                  data-href="{{ action([\App\Http\Controllers\CashRegisterController::class, 'getCloseRegister'])}}">
                    <i class="fa fa-window-close"></i>
                  </button>
                  @endcan
                @endif

                @can('profit_loss_report.view')
                  <button type="button" id="view_todays_profit" title="{{ __('home.todays_profit') }}" data-toggle="tooltip" data-placement="bottom" class="header-icon-btn">
                    <i class="fas fa-money-bill-alt"></i>
                  </button>
                @endcan

                @if(Module::has('Repair'))
                  @includeIf('repair::layouts.partials.header')
                @endif
            </div>

            @if(in_array('pos_sale', $enabled_modules))
              @can('sell.create')
                <a href="{{action([\App\Http\Controllers\SellPosController::class, 'create'])}}" title="@lang('sale.pos_sale')" data-toggle="tooltip" data-placement="bottom" class="header-pos-btn">
                  <i class="fa fa-th-large"></i> @lang('sale.pos_sale')
                </a>
              @endcan
            @endif

            <div class="header-toolbar__date hidden-xs">{{ @format_date('now') }}</div>

            <ul class="nav navbar-nav header-toolbar__end">
              @include('layouts.partials.header-notifications')
              <!-- User Account Menu -->
              <li class="dropdown user user-menu">
                <!-- Menu Toggle Button -->
                <a href="#" class="dropdown-toggle header-user-toggle" data-toggle="dropdown">
                  <!-- The user image in the navbar-->
                  @php
                    $profile_photo = auth()->user()->media;
                  @endphp
                  @if(!empty($profile_photo))
                    <img src="{{$profile_photo->display_url}}" class="user-image" alt="User Image">
                  @else
                    <span class="header-user-toggle__avatar">{{ mb_substr(Auth::User()->first_name, 0, 1) }}</span>
                  @endif
                  <!-- hidden-xs hides the username on small devices so only the image appears. -->
                  <span>{{ Auth::User()->first_name }} {{ Auth::User()->last_name }}</span>
                  <i class="fas fa-chevron-down header-user-toggle__caret"></i>
                </a>
                <ul class="dropdown-menu">
                  <!-- The user image in the menu -->
                  <li class="user-header">
                    @if(!empty(Session::get('business.logo')))
                      <img src="{{ asset( 'uploads/business_logos/' . Session::get('business.logo') ) }}" alt="Logo">
                    @endif
                    <p>
                      {{ Auth::User()->first_name }} {{ Auth::User()->last_name }}
                    </p>
                  </li>
                  <!-- Menu Body -->
                  <!-- Menu Footer-->
                  <li class="user-footer">
                    <div class="pull-left">
                      <a href="{{action([\App\Http\Controllers\UserController::class, 'getProfile'])}}" class="btn btn-default btn-flat">@lang('lang_v1.profile')</a>
                    </div>
                    <div class="pull-right">
                      <a href="{{action([\App\Http\Controllers\Auth\LoginController::class, 'logout'])}}" class="btn btn-default btn-flat">@lang('lang_v1.sign_out')</a>
                    </div>
                  </li>
                </ul>
              </li>
              <!-- Control Sidebar Toggle Button -->
            </ul>
        </div>
      </div>
    </nav>
  </header>
