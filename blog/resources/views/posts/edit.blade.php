@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="panel panel-default">
                <div class="panel-heading">Update Post
                    @can('delete-post', $post)
                        <a class="pull-right btn btn-sm btn-danger" href="{{ route('delete_post', ['id' => $post->id]) }}"
                           onclick="if(confirm('确定删除吗？') === false) { return false; } else {
                           event.preventDefault(); document.getElementById('delete-post-form').submit();}">
                            删除
                        </a>
                        <form id="delete-post-form" action="{{ route('delete_post', ['post' => $post->id]) }}" method="POST" style="display: none;">
                            {{ csrf_field() }}
                        </form>
                    @endcan
                </div>

                <div class="panel-body">
                    <form class="form-horizontal" role="form" method="POST" action="{{ route('update_post', ['post' => $post->id]) }}">
                        {{ csrf_field() }}

                        <div class="form-group{{ $errors->has('title') ? ' has-error' : '' }}">
                            <label for="title" class="col-md-2 control-label">Title</label>

                            <div class="col-md-9">
                                <input id="title" type="text" class="form-control" name="title" value="{{ old('title', $post->title) }}" required autofocus>

                                @if ($errors->has('title'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('title') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="form-group{{ $errors->has('body') ? ' has-error' : '' }}">
                            <label for="body" class="col-md-2 control-label">Body</label>

                            <div class="col-md-9">
                                <textarea name="body" id="body" cols="30" rows="10" class="form-control" required>{{ old('body', $post->body) }}</textarea>
                                @if ($errors->has('body'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('body') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="col-md-6 col-md-offset-2">
                                <button type="submit" class="btn btn-primary">
                                    Update
                                </button>
                                @can('publish-post', $post)
                                    @if (!$post->published)
                                        <a href="{{ route('publish_post', ['post' => $post->id]) }}" class="btn btn-primary">
                                            Publish
                                        </a>
                                    @else
                                        <a href="{{ route('unpublish_post', ['post' => $post->id]) }}" class="btn btn-primary">
                                            Unpublish
                                        </a>
                                    @endif
                                @endcan
                                <a href="{{ route('list_posts') }}" class="btn btn-primary">
                                    Cancel
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection