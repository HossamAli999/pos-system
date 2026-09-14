@extends('errors.layout')

@section('code', '500')
@section('title', 'Something Went Wrong')
@section('message', "An unexpected error occurred on our end. Please try again, and contact support if the problem continues.")

@section('icon')
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M10.3 3.9 2.5 17a2 2 0 0 0 1.7 3h15.6a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/>
        <path d="M12 9v4M12 17h.01"/>
    </svg>
@endsection
