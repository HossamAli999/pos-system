@extends('errors.layout')

@section('code', '429')
@section('title', 'Too Many Requests')
@section('message', "You've made too many requests in a short time. Please wait a moment and try again.")

@section('icon')
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M13 2 3 14h8l-1 8 10-12h-8z"/>
    </svg>
@endsection
