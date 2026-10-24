@extends('errors.minimal')

@section('title', __('Page Expired'))
@section('code', '419')
@section('message')
    <p>Your session changed in another tab. Reload this page to use your current login.</p>
    <p>If you still cannot continue, <a href="/login?restart_session=1">sign in again</a>.</p>
@endsection
