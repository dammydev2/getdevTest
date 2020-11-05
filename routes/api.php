<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('register', 'UserController@register');
Route::post('login', 'UserController@authenticate');
Route::get('open', 'DataController@open');

Route::group(['middleware' => ['jwt.verify','verifyEmail']], function() {
    Route::get('user', 'UserController@getAuthenticatedUser');
    Route::post('submit_article', 'UserController@submitArticle');
    Route::patch('edit_article/{article_id}', 'UserController@editArticle');
    Route::delete('delete_article/{article_id}', 'UserController@deleteArticle');
});

Route::get('email/verify/{id}', 'UserController@verify');
Route::get('view/articles', 'UserController@viewArticles');
Route::get('writers/all', 'UserController@allWriters');

// Route::get('email/resend', 'VerificationController@resend')->name('verification.resend');