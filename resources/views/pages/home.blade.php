@extends('layouts.master')

@section('main_content')

    @include('layouts.header', ['header' => 'Rogue', 'subtitle' => 'Please log in to continue.'])

    <div class="container -padded">
        <div class="wrapper">
            <div class="container__block -narrow">
                <p>
                    Welcome to <strong>Rogue</strong>, our campaign activity admin tool.
                </p>

                <p>
                    <a href="/login" class="button">Log In</a>
                </p>
        </div>
    </div>

@stop
