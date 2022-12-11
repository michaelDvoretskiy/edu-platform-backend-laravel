<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\InfoController;
use App\Http\Controllers\API\PageController;
use App\Http\Controllers\API\CourseController;

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

Route::controller(AuthController::class)->group(function(){
    Route::post('register', 'register');
    Route::post('login', 'login');
});

Route::controller(InfoController::class)->prefix('/info')->group(function() {
   Route::get('/get-general', 'getInfo');
   Route::get('/get-home-carousel', 'getHomeCarousel');
});

Route::controller(PageController::class)->prefix('/pages')->group(function() {
    Route::get('/{page}', 'show');
});

Route::controller(CourseController::class)->prefix('/courses')->group(function() {
    Route::get('/categories', 'showCategories');
    Route::get('/category/{categoryName}', 'showCategory');
});

Route::controller(PageController::class)->prefix('/team-members')->group(function() {
    Route::get('/{name}', 'getTeamMember');
});


Route::middleware('auth:sanctum')->group( function () {
    Route::get('/test', function() {
        return [1,2,3];
    });
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
