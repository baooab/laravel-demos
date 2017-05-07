@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="panel panel-default">
                <div class="panel-heading"><h1>Links Sharing</h1></div>

                <div class="panel-body">
                    <div class="row">
                        @foreach ($links as $link)
                            <div class="col-sm-6 col-md-4">
                                <div class="thumbnail">
                                    <div class="caption">
                                        <h2><a href="{{ $link->url }}" target="_blank">{{ $link->title }}</a></h3>
                                        <p>{{ $link->description  }}</p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection