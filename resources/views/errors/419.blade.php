@extends('errors.layout')

@section('code', '419')
@section('title', 'Page Expired')
@section('message', "Your session took too long and this page has expired. Please refresh and try again.")

@section('icon')
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="13" r="8"/>
        <path d="M12 9v4l3 2M9 2h6"/>
    </svg>
@endsection

@section('extra_action')
    <a href="javascript:location.reload()" class="btn-error btn-error-ghost">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-3-6.7M21 3v6h-6"/></svg>
        Refresh
    </a>
@endsection
