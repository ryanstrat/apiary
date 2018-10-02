@extends('layouts/app')

@section('title')
    Error | {{ config('app.name') }}
@endsection

@section('content')

    @component('layouts/title')
        You've already paid dues for this semester!
    @endcomponent

    <div class="row">
        <div class="col-md-12">
            We have you on record as having already paid dues for this semester. You do not need to pay again. If you
            believe you haven't paid, please email
                <a href="mailto:treasurer@robojackets.org">treasurer@robojackets.org</a>.
        </div>
    </div>

@endsection
