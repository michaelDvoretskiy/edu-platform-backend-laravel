<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\InfoController;
use App\Http\Controllers\API\PageController;
use App\Http\Controllers\API\CourseController;
use App\Http\Controllers\API\PdfViewController;
use App\Http\Controllers\API\TestsController;
use Illuminate\Support\Facades\Mail;

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
    Route::get('verif-code/{type}', 'getVerificationCode');
    Route::post('restore-pass', 'restorePassword');
    Route::get('user-cache/check', 'checkCacheHasToBeCleared');
    Route::post('user-cache/update', 'updateCacheClearedDate');
});

Route::controller(InfoController::class)->prefix('/info')->group(function() {
   Route::get('/get-general', 'getInfo');
   Route::get('/get-home-carousel', 'getHomeCarousel');
   Route::get('/get-form-text/{formName}', 'getFormText');
   Route::post('/feedback', 'reachFeedback');
   Route::get('/my-courses', 'getMyCourses');
   Route::get('/my-points', 'getMyPoints');
});

Route::controller(PageController::class)->prefix('/pages')->group(function() {
    Route::get('/{page}', 'show');
});

Route::get('/get-details', 'App\Http\Controllers\API\PdfViewController@getSubscribeDetails');

Route::controller(PdfViewController::class)->prefix('/pdf')->group(function() {
    Route::get('/show/{id}', 'show');
    Route::get('/get-content/{id}/{type}', 'getContent')->name('get-pdf-content');
});

Route::controller(CourseController::class)->prefix('/courses')->group(function() {
    Route::get('/categories', 'showCategories');
    Route::get('/category/{categoryName}', 'showCategory');
    Route::get('/course/{courseName}', 'showCourse');
    Route::get('/lesson/{lessonName}', 'showLesson');
});

Route::controller(PageController::class)->prefix('/team-members')->group(function() {
    Route::get('/{name}', 'getTeamMember');
});

Route::middleware('auth:sanctum')->group( function () {
    Route::controller(TestsController::class)->prefix('/tests')->group(function() {
        Route::get('/get-question/{id}', 'getQuestion');
        Route::get('/get-test/{id}', 'getUserTest');
        Route::post('/give-answer/{id}', 'giveAnswer');
    });
});

//Route::middleware('auth:sanctum')->group( function () {
    Route::get('/test', function() {
        return [1 => 1/0,2,3];
    });
//});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
