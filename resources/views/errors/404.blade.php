@extends('errors.layout')

@section('code', '404')
@section('title', 'Page Not Found')
@section('message', "The page you're looking for doesn't exist or may have been moved.")

@section('icon')
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="11" cy="11" r="7"/>
        <path d="m21 21-4.3-4.3M8.5 11h5"/>
    </svg>
@endsection
