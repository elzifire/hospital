@extends('layouts.app')

@section('title', 'Laporan '.$config['label'])
@section('page-title', 'Monitoring')

@section('content')
    @include('admin.monitoring.partials._page_tabs')
@endsection