@extends('errors.layout')

@section('code', '402')
@section('title', 'Payment Required')
@section('message', "This action requires an active subscription or payment. Please contact your administrator.")

@section('icon')
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <rect x="2" y="5" width="20" height="14" rx="2"/>
        <path d="M2 10h20"/>
    </svg>
@endsection
