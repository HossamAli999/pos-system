@extends('errors.layout')

@section('code', '503')
@section('title', 'Under Maintenance')
@section('message', ($exception->getMessage() ?: "We're performing a bit of maintenance right now. We'll be back shortly."))

@section('icon')
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="m14.7 6.3 3 3L8 19H5v-3Z"/>
        <path d="M17.5 3.5 20.5 6.5M2 22l3-3"/>
    </svg>
@endsection
