@extends('layouts.guest')
@section('title', $title)
@section('content')

<div class="invoice-page">
    <div class="invoice-page__toolbar no-print">
        <div class="invoice-page__title">{{ $title }}</div>
        <div class="invoice-page__actions">
            @if(!empty($payment_link))
                <a href="{{$payment_link}}" class="btn btn-primary"><i class="fas fa-money-check-alt"></i> @lang('lang_v1.pay')</a>
            @endif
            <button type="button" class="btn btn-default" id="print_invoice" aria-label="Print">
                <i class="fas fa-print"></i> @lang( 'messages.print' )
            </button>
            @auth
                <a href="{{action([\App\Http\Controllers\SellController::class, 'index'])}}" class="btn btn-default" title="@lang('lang_v1.go_back')"><i class="fas fa-backward"></i></a>
            @endauth
        </div>
    </div>

    <div class="invoice-page__card">
        <div id="invoice_content">
            {!! $receipt['html_content'] !!}
        </div>
    </div>
</div>
@stop
@section('javascript')
<script type="text/javascript">
    $(document).ready(function(){
        $(document).on('click', '#print_invoice', function(){
            $('#invoice_content').printThis();
        });
    });
    @if(!empty(request()->input('print_on_load')))
        $(window).on('load', function(){
            $('#invoice_content').printThis();
        });
    @endif
</script>
@endsection
