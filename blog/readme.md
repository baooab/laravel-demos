# Laravel Gate 授权方式的使用指南

> 参考链接：[An Introduction to Laravel Authorization Gates](https://laravel-news.com/authorization-gates)

本文使用 Laravel 的 [Gate 授权方式](https://laravel.com/docs/5.4/authorization#gates) 实现一个基于用户角色的博客发布系统。

在系统包含两个用户角色（`作者` 和 `编辑`），它们对应的角色权限如下：

1. 作者能创建博客
2. 作者能更新自己的博客
3. 作者能发布/不发布自己的博客
4. 作者能删除自己的博客
5. 编辑能更新所有博客
6. 编辑能发布/不发布所有博客
7. 编辑能删除所有博客

## 创建项目

```
laravel new blog

> php artisan -V
Laravel Framework 5.4.21
```
或者使用 composer create-project
 
```
composer create-project --prefer-dist laravel/laravel blog
```

## 配置数据库连接

修改 `.env` 文件中的数据库连接信息。

```
...
APP_URL=http://localhost
...
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=dbname
DB_USERNAME=dbuser
DB_PASSWORD=yoursecretdbuserpassword
...
```

## 数据库部分

创建 Post Model、Post 迁移文件和 Post 控制器。

```
> php artisan make:model Post -m -c
Model created successfully.
Created Migration: 2017_05_07_083335_create_posts_table
Controller created successfully.
```

打开迁移文件，补充 `up` 方法。

```
Schema::create('posts', function (Blueprint $table) {
    $table->increments('id');
    $table->string('title');
    $table->string('slug')->unique();
    $table->text('body');
    $table->boolean('published')->default(false);
    $table->unsignedInteger('user_id');
    $table->timestamps();

    $table->foreign('user_id')->references('id')->on('users');
});
```

添加 `roles` 表和关系表 `user_roles`。

1. `roles` 表

创建 Role Model 和 Role 迁移文件 

```
> php artisan make:model Role -m
Model created successfully.
Created Migration: 2017_05_07_083537_create_roles_table
```

打开迁移文件，补充 `up` 方法。

```
Schema::create('roles', function (Blueprint $table) {
    $table->increments('id');
    $table->string('name');
    $table->string('slug')->unique();
    $table->jsonb('permissions'); // jsonb deletes duplicates
    $table->timestamps();
});
```

2. `user_roles` 表

创建迁移文件

```
php artisan make:migration create_user_roles_table --create=user_roles
```

补充 `up` 方法

```
public function up()
{
    Schema::create('user_roles', function (Blueprint $table) {
        $table->unsignedInteger('user_id');
        $table->unsignedInteger('role_id');
        $table->timestamps();

        $table->unique(['user_id','role_id']);
        $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
    });
}

public function down()
{
    Schema::dropIfExists('user_roles');
}
```

## 种子类

创建 `RolesTableSeeder`。

```
php artisan make:seeder RolesTableSeeder
```

补充 `run` 方法。

```
use App\Role;

class RolesTableSeeder extends Seeder
{

    public function run()
    {
        $author = Role::create([
            'name' => '作家',
            'slug' => 'author',
            'permissions' => [
                'create-post' => true,
            ]
        ]);
        $editor = Role::create([
            'name' => '编辑',
            'slug' => 'editor',
            'permissions' => [
                'update-post' => true,
                'publish-post' => true,
                'delete-post' => true,
            ]
        ]);
    }

}
```

在 `DatabaseSeeder` 中注册 `RolesTableSeeder`。

```
$this->call(RolesTableSeeder::class);
```

## `User` 和 `Role` Model

添加 Role Model 的内容。

```
class Role extends Model
{
    protected $fillable = [
        'name', 'slug', 'permissions',
    ];
    protected $casts = [
        'permissions' => 'array',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_roles', 'role_id', 'user_id');
    }

    public function hasAccess($permission)
    {
        return $this->hasPermission($permission);
    }

    private function hasPermission($permission)
    {
        return $this->permissions[$permission] ?? false;
    }
}
```

添加 User Model 的内容。

```
class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'name', 'email', 'password',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles', 'user_id', 'role_id');
    }

    /**
     * Checks if User has access to $permission.
     */
    public function hasAccess($permission)
    {
        // check if the permission is available in any role
        foreach ($this->roles as $role) {
            if($role->hasAccess($permission)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Checks if the user belongs to role.
     */
    public function inRole($roleSlug)
    {
        return $this->roles()->where('slug', $roleSlug)->count() == 1;
    }
}
```

执行迁移和数据注入

```
php artisan migrate --seed
```

## 认证系统

```
> php artisan make:auth
Authentication scaffolding generated successfully.
```

### 注册

在 `Controllers/Auth/RegisterController.php` 创建 `showRegistrationForm` 方法（覆盖掉在 `RegistersUsers` trait 中定义）。

```
Use App/Role;

...

public function showRegistrationForm()
{
    $roles = Role::orderBy('name')->pluck('name', 'id');
    return view('auth.register', compact('roles'));
}
```

编辑 `resources/views/auth/register.blade.php`，添加角色选择项。

```
...

<div class="form-group{{ $errors->has('role') ? ' has-error' : '' }}">
    <label for="role" class="col-md-4 control-label">User role</label>

    <div class="col-md-6">
        <select id="role" class="form-control" name="role" required>
            @foreach($roles as $id => $role)
                <option value="{{ $id }}">{{ $role }}</option>
            @endforeach
        </select>

        @if ($errors->has('role'))
            <span class="help-block">
                <strong>{{ $errors->first('role') }}</strong>
            </span>
        @endif
    </div>
</div>

...
```

更新 `RegisterController` 中的 `validator `方法。

```
...

protected function validator(array $data)
{
    return Validator::make($data, [
        'name' => 'required|max:255',
        'email' => 'required|email|max:255|unique:users',
        'password' => 'required|min:6|confirmed',
        'role' => 'required|exists:roles,id', // validating role
    ]);
}

...
```

修改 `create` 方法，加入存储用户角色的业务逻辑。

```
...

protected function create(array $data)
{
    $user = User::create([
        'name' => $data['name'],
        'email' => $data['email'],
        'password' => bcrypt($data['password']),
    ]);
    $user->roles()->attach($data['role']);
    return $user;
}

...

```

## 运行项目

浏览器地址栏输入： http://localhost/blog/public/ 。看到欢迎页面、注册用户。

## 定义策略

修改 `app/Providers/AuthServiceProvider.php` 文件。

```
use App\Post;

...

public function boot()
{
    $this->registerPolicies();
    $this->registerPostPolicies();
}

public function registerPostPolicies()
{
    Gate::define('create-post', function ($user) {
        return $user->hasAccess('create-post');
    });
    Gate::define('update-post', function ($user, Post $post) {
        return $user->hasAccess('update-post') or $user->id == $post->user_id;
    });
    Gate::define('publish-post', function ($user, Post $post) {
        return $user->hasAccess('publish-post') or $user->id == $post->user_id;;
    });
    Gate::define('delete-post', function ($user, Post $post) {
        return $user->hasAccess('delete-post') or $user->id == $post->user_id;
    });
    Gate::define('see-all-drafts', function ($user) {
        return $user->inRole('editor');
    });
}
```

## 定义路由

修改 `routes/web.php`。

```
Route::get('/posts', 'PostController@index')->name('list_posts');
Route::group(['prefix' => 'posts'], function () {
    Route::get('/drafts', 'PostController@drafts')
        ->name('list_drafts')
        ->middleware('auth');
    Route::get('/show/{id}', 'PostController@show')
        ->name('show_post');
    Route::get('/create', 'PostController@create')
        ->name('create_post')
        ->middleware('can:create-post');
    Route::post('/create', 'PostController@store')
        ->name('store_post')
        ->middleware('can:create-post');
    Route::get('/edit/{post}', 'PostController@edit')
        ->name('edit_post')
        ->middleware('can:update-post,post');
    Route::post('/edit/{post}', 'PostController@update')
        ->name('update_post')
        ->middleware('can:update-post,post');
    Route::post('/delete/{post}', 'PostController@destory')
        ->name('delete_post')
        ->middleware('can:delete-post,post');
    // using get to simplify
    Route::get('/publish/{post}', 'PostController@publish')
        ->name('publish_post')
        ->middleware('can:publish-post');
    Route::get('/unpublish/{post}', 'PostController@unpublish')
        ->name('unpublish_post')
        ->middleware('can:publish-post,post');
});
```

## 博客

### `Post` Model

```
...

class Post extends Model
{
    protected $fillable = [
        'title', 'slug', 'body', 'user_id',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class);
    }

    public function scopePublished($query)
    {
        return $query->where('published', true);
    }

    public function scopeUnpublished($query)
    {
        return $query->where('published', false);
    }
}
```

### `PostController`

#### 博客列表

```
use App\Post;

...

public function index()
{
    $posts = Post::published()->paginate();
    return view('posts.index', compact('posts'));
}

...
```

创建 `resources/views/posts/index.blade.php`

```
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="panel panel-default">
                <div class="panel-heading">
                    Posts
                    @can('create-post')
                    <a class="pull-right btn btn-sm btn-primary" href="{{ route('create_post') }}">New</a>
                    @endcan
                </div>

                <div class="panel-body">
                    <div class="row">
                    @foreach($posts as $post)
                        <div class="col-sm-6 col-md-4">
                            <div class="thumbnail">
                            <div class="caption">
                                <h3><a href="{{ route('show_post', ['id' => $post->id]) }}">{{ $post->title }}</a></h3>
                                <p>{{ str_limit($post->body, 50) }}</p>
                                @can('update-post', $post)
                                <p>
                                    <a href="{{ route('edit_post', ['id' => $post->id]) }}" class="btn btn-sm btn-default" role="button">Edit</a> 
                                </p>
                                @endcan
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
```

#### 创建博客

```
...

public function create()
{
    return view('posts.create');
}

...
```

创建 `posts\create.blade.php`。

```
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="panel panel-default">
                <div class="panel-heading">New Post</div>

                <div class="panel-body">
                    <form class="form-horizontal" role="form" method="POST" action="{{ route('store_post') }}">
                        {{ csrf_field() }}

                        <div class="form-group{{ $errors->has('title') ? ' has-error' : '' }}">
                            <label for="title" class="col-md-2 control-label">Title</label>

                            <div class="col-md-9">
                                <input id="title" type="text" class="form-control" name="title" value="{{ old('title') }}" required autofocus>
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
                                <textarea name="body" id="body" cols="30" rows="10" class="form-control" required>{{ old('body') }}</textarea>
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
                                    Create
                                </button>
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
```

#### 保存博客

```
use Auth;
use App\Http\Requests\StorePost as StorePostRequest;

...

public function store(StorePostRequest $request)
{
    $data = $request->only('title', 'body');
    $data['slug'] = str_slug($data['title']);
    $data['user_id'] = Auth::user()->id;
    $post = Post::create($data);
    return redirect()->route('edit_post', ['id' => $post->id]);
}

...

```

创建处理存储博客时使用的请求类 `StorePostRequest`。

```
php artisan make:request StorePostRequest
```

编辑 `app/Http/Requests/StorePost.php`。

```
public function authorize()
{
    return true; // gate will be responsible for access
}

public function rules()
{
    return [
        'title' => 'required|unique:posts',
        'body' => 'required',
    ];
}
```

#### 博客草稿列表

```
use Gate;

...

public function drafts()
{
    $postsQuery = Post::unpublished();
    if(Gate::denies('see-all-drafts')) {
        $postsQuery = $postsQuery->where('user_id', Auth::user()->id);
    }
    $posts = $postsQuery->paginate();
    return view('posts.drafts', compact('posts'));
}

...
```

创建 `posts/drafts.blade.php`。

```
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="panel panel-default">
                <div class="panel-heading">
                    Drafts <a class="btn btn-sm btn-default pull-right" href="{{ route('list_posts') }}">Return</a>
                </div>

                <div class="panel-body">
                    <div class="row">
                    @foreach($posts as $post)
                        <div class="col-sm-6 col-md-4">
                            <div class="thumbnail">
                            <div class="caption">
                                <h3><a href="{{ route('show_post', ['id' => $post->id]) }}">{{ $post->title }}</a></h3>
                                <p>{{ str_limit($post->body, 50) }}</p>
                                <p>
                                @can('publish-post', $post)
                                    <a href="{{ route('publish_post', ['id' => $post->id]) }}" class="btn btn-default" role="button">Publish</a> 
                                @endcan
                                    <a href="{{ route('edit_post', ['id' => $post->id]) }}" class="btn btn-default" role="button">Edit</a> 
                                </p>
                            </div>
                            </div>
                        </div>
                    @endforeach
                        <div class="col-sm-12 col-md-12">
                            {{ $posts->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
```

修改 `layouts/app.blade.php`，添加“草稿”菜单。

```
...

<ul class="dropdown-menu" role="menu">
    <li>
        <a href="{{ route('list_drafts') }}">Drafts</a>

...
```

#### 编辑博客

添加编辑博客的方法 `update`。 

```
use App\Http\Requests\UpdatePostRequest as UpdatePostRequest;

...

public function edit(Post $post)
{
    return view('posts.edit', compact('post'));
}

public function update(Post $post, UpdatePostRequest $request)
{
    $data = $request->only('title', 'body');
    $data['slug'] = str_slug($data['title']);
    $post->fill($data)->save();
    return back();
}
```

创建处理更新博客时使用的请求类 `UpdatePostRequest`。

```
php artisan make:request UpdatePostRequest
```

编辑 `app/Http/Requests/UpdatePostRequest.php`

```
use Illuminate\Validation\Rule;

...

public function authorize()
{
    return true;
}

public function rules()
{
    $id = $this->route('post')->id;
    return [
        'title' => [
            'required',
            Rule::unique('posts')->where('id', '<>', $id),
        ],
        'body' => 'required',
    ];
}
```

创建视图 `posts/edit.blade.php`。

```
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="panel panel-default">
                <div class="panel-heading">Update Post</div>

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
                                @can('publish-post')
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
```

#### 发布草稿/不发布草稿

为 `PostController` 添加方法 `publish` 和 `unpublish`。
 
```
...

public function publish(Post $post)
{
    $post->published = true;
    $post->save();
    return back();
}

public function unpublish(Post $post)
{
    $post->published = false;
    $post->save();
    return back();
}

...
```

#### 展示博客

```
public function show(Post $post)
{
    $post = Post::findOrFail($id);
    if ($post->published || $post->user_id == Auth::user()->id) {
        return view('posts.show', compact('post'));
    }
    abort(403, 'Unauthorized.');
}
```

创建 `posts/show.blade.php`。

```
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ $post->title }}
                    <a class="btn btn-sm btn-default pull-right" href="{{ route('list_posts') }}">Return</a>
                </div>

                <div class="panel-body">
                    {{ $post->body }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
```

#### 删除博客

修改 `PostController`，添加 `destory` 方法。

```
public function destory(Post $post)
{
    $post->delete();
    return redirect()->route('list_post');;
}
```

修改 `posts/edit.blade.php`，添加删除按钮。

```
...

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

...
```

## 404 & 403

在 `resources/views` 下新建 `errors` 目录，再在该目录下新建 `404.blade.php` 和 `403.blade.php` 页面。

### 404

```
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="panel panel-default">
                <div class="panel-heading">404</div>

                <div class="panel-body">
                    <h2>Not Found</h2>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
```

### 403

```
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="panel panel-default">
                <div class="panel-heading">403</div>

                <div class="panel-body">
                    <h2>This Action is Unauthorized！</h2>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
```

## 使用 Markdown

这里要安装依赖包 [`erusev/parsedown`](https://packagist.org/packages/erusev/parsedown)，使用它将 Markdown 文本装换为 HTML。

```
composer require erusev/parsedown
```

修改 `posts/show.blade.php`

```
<div class="panel-body">
    {!! Parsedown::instance()->text($post->body) !!}
</div>
```

此刻，你就可以使用 Markdown 写博客了。

###### tags: `Laravel` `项目`