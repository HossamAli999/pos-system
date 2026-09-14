@extends('errors.layout')

@section('code', '403')
@section('title', 'Access Forbidden')
@section('message', ($exception->getMessage() && $exception->getMessage() !== 'Forbidden') ? $exception->getMessage() : "You don't have permission to access this page or perform this action.")

@section('icon')
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M12 2 4 5v6c0 5 3.4 8.7 8 11 4.6-2.3 8-6 8-11V5z"/>
        <path d="m9.5 9.5 5 5m0-5-5 5"/>
    </svg>
@endsection
