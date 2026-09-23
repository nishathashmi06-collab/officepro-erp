@extends('errors.layout')
@section('code', '429')
@section('title', 'Too many requests')
@section('icon', 'bi-speedometer')
@section('message')
    You're making requests too quickly. Please wait a moment and try again.
@endsection
