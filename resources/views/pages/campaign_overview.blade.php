@extends('layouts.master')

@section('main_content')

    @include('layouts.header', ['header' => '', 'subtitle' => ''])

    <div class="container -padded">
        <div id="overviewContainer" class="wrapper">
            Loading...
        </div>
    </div>

@stop
