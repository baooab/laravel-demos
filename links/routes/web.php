<?php

use App\Link;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');

Route::group(['prefix' => 'links'], function () {
    Route::get('', function () {
    	$links = Link::all();
	    return view('links.index', compact('links'));
	});
    Route::get('create', function () {
	    return view('links.create');
	});
	Route::post('store', function(Request $request) {
	    $validator = Validator::make($request->all(), [
	        'title' => 'required|max:255',
	        'url' => 'required|max:255',
	        'description' => 'nullable|max:255',
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
