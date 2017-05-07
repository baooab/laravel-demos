@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row">
            <h1>Submit a link</h1>

            @if (count($errors) > 0)
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ url('links/store') }}" method="post">
                {{ csrf_field() }}
                <div class="form-group">
                    <label for="title">标题</label>
                    <input type="text" class="form-control" id="title" name="title" placeholder="Title" value="{{ old('title') }}">
                </div>
                <div class="form-group">
                    <label for="url">URL</label>
                    <input type="text" class="form-control" id="url" name="url" placeholder="URL" value="{{ old('url') }}">
                </div>
                <div class="form-group">
                    <label for="description">介绍</label>
                    <textarea class="form-control" id="description" name="description" placeholder="description">{{ old('description') }}</textarea>
                </div>
                <button type="submit" class="btn btn-default">创建</button>
            </form>
        </div>
    </div>
@endsection