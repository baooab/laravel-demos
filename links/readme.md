	# 一步一步带你构建第一个 Laravel 项目

> 参考链接：https://laravel-news.com/your-first-laravel-application

## 简介

按照以下的步骤，你会创建一个简易的**链接分享网站**。

## 安装 Laravel 安装器

```
composer global require "laravel/installer"
```

## 创建项目[注一][注一]

[注一]: 项目数据库名使用 `laravel-links`，采用 `utf8mb4_unicode_ci` 校对。修改 MySQL 配置文件 `mysql.ini`（Windows 环境下） 将 `default-storage-engine` 项设置为 `InnoDB`——表示新建数据库表的默认存储引擎使用 InnoDB。

```
laravel new links
```

## 检查是否安装成功

访问地址：http://localhost/links/public/ 。看到欢迎页面，表示安装成功。

## 构建认证系统

执行命令：`php artisan make:auth` 和 `php artisan migrate`。[注二][注二]

[注二]: 对于 Laravel 5.3- 版本，需要修改文件 `resource/views/layouts/app.blade.php`。将引入的 JavaScript 和 CSS 文件的地址改为 `<link href="{{ asset('css/app.css') }}" rel="stylesheet">`
 和 `<script src="{{ asset('js/app.js') }}"></script>`。

```
> php artisan make:auth
Authentication scaffolding generated successfully.
> php artisan migrate
Migration table created successfully.
Migrated: 2014_10_12_000000_create_users_table
Migrated: 2014_10_12_100000_create_password_resets_table
```

现在系统里就有注册、登录功能了。

## 创建 Model & 插入伪数据

### 创建 Model

创建迁移文件

```
php artisan make:migration create_links_table --create=links
```

写迁移文件的 `up` 方法

```
Schema::create('links', function (Blueprint $table) {
      $table->increments('id');
      $table->string('title')->unique();
      $table->string('url')->unique();
      $table->text('description')->nullable();
      $table->timestamps();
});
```

执行迁移

```
php artisan migrate
```

创建 Link Model

```
php artisan make:model Link
```

在 `ModelFactory.php` 中为 Link Model 定义工厂方法

```
$factory->define(App\Link::class, function (Faker\Generator $faker) {
    return [
        'title' => $faker->name,
        'url' => $faker->url,
        'description' => $faker->paragraph,
    ];
});
```

创建种子文件 `LinksTableSeeder`

```
php artisan make:seeder LinksTableSeeder
```

在种子文件中使用工厂方法

```
public function run()
{
    factory(App\Link::class, 10)->create();
}
```

在 `DatabaseSeeder` 中注册种子文件

```
$this->call(LinksTableSeeder::class);
```

执行种子文件[注三][注三]

[注三]: 也可以在迁移时执行种子文件，命令是 `php artisan migrate --seed`。

```
php artisan db:seed
```

### 路由和视图

在 `routes/web.php` 中添加路由——首页、创建页和保存链接。

```
use App\Link;
use Illuminate\Http\Request;

Route::group(['prefix' => 'links'], function () {
    Route::get('', function () {
        $links = Link::paginate(50);
        return view('links.index', compact('links'));
    });
    Route::get('create', function () {
        return view('links.create');
    });
    Route::post('store', function(Request $request) {
        $validator = Validator::make($request->all(), [
            'title' => 'required|max:255',
            'url' => 'required|max:255',
            'description' => 'present|max:255',
        ]);
        if ($validator->fails()) {
            return back()
                ->withInput()
                ->withErrors($validator);
        }
        $link = new Link();
        $link->title = $request->title;
        $link->url = $request->url;
        $link->description = $request->description;
        $link->save();
        return redirect('/links');
    });
});
```

在 `resources/views/links` 添加两个视图文件。

1. 链接分享的首页`index.blade.php`

```
@extends('layouts.app')

@push('styles')
    <style>
        .navbar {
            margin-bottom: 20px;
        }

        .panel-body .thumbnail a {
            display: block;
        }
    </style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-heading text-center">
                    <span>采集<a class="btn btn-primary btn-xs pull-right" href="{{ url('links/create') }}">添加</a></span>
                </div>
                <div class="panel-body">
                    <div class="row">
                        @foreach ($links as $link)
                            <div class="col-sm-4 col-md-3">
                                <div class="thumbnail">
                                    <div class="caption text-center">
                                        <h4><a href="{{ $link->url }}" target="_blank">{{ $link->title }}</a></h3>
                                        <p>{{ $link->description }}</h4>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        <div class="col-md-12">
                            {{ $links->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
```

2. 创建链接页`create.blade.php`

```
@extends('layouts.app')

@push('styles')
    <style>
        .navbar {
            margin-bottom: 20px;
        }
    </style>
@endpush

@section('content')
    <div class="container">
        <div class="row">
            @if (count($errors) > 0)
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <div class="col-md-8 col-md-offset-2">
                <div class="panel panel-default">
                    <div class="panel-heading">新的采集</div>
                    <div class="panel-body">
                        <form action="{{ url('links/store') }}" method="post">
                            {{ csrf_field() }}
                            <div class="form-group">
                                <label for="title">标题</label>
                                <input type="text" class="form-control" id="title" name="title" value="{{ old('title') }}">
                            </div>
                            <div class="form-group">
                                <label for="url">URL</label>
                                <input type="text" class="form-control" id="url" name="url" value="{{ old('url') }}">
                            </div>
                            <div class="form-group">
                                <label for="description">介绍</label>
                                <textarea class="form-control" id="description" name="description">{{ old('description') }}</textarea>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">创建</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
```

###### tags: `Laravel` `项目`
