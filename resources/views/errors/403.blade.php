@extends('errors.layout')
@section('code', '403')
@section('title', 'Access denied')
@section('icon', 'bi-shield-lock')
@section('message')
    {{ $exception->getMessage() && $exception->getMessage() !== 'This action is unauthorized.' ? $exception->getMessage() : "You don't have permission to view this page. If you believe this is a mistake, contact your administrator." }}
@endsection
