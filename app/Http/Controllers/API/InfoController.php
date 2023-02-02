<?php

namespace App\Http\Controllers\API;

use App\Models\General\Feedback;
use App\Services\AccessService;
use App\Services\InfoService;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Services\MailService;
use Illuminate\Http\Request;
use Validator;

class InfoController extends BaseController
{
    public function __construct(private InfoService $infoService, private MailService $mailService, private AccessService $accessService) {}

    public function getFormText($formName) {
        if ($formName == 'login') {
            return $this->sendResponse(
                __('auth.loginForm'),
                'Login form text was sent successfully.'
            );
        }
        if ($formName == 'forgot-pass') {
            return $this->sendResponse(
                __('auth.forgotPass'),
                'Forgot password form text was sent successfully.'
            );
        }
        if ($formName == 'register') {
            return $this->sendResponse(
                __('auth.registerForm'),
                'Registration form text was sent successfully.'
            );
        }
        return $this->sendError('Wrong form name');
    }

    public function getInfo(Request $request) {
        $user = $request->user('sanctum');
        $userId = null;
        if ($user) {
            $userId = $user->id;
        }
        return $this->sendResponse(
            $this->infoService->getInfo($userId),
            'Info was sent successfully.'
        );
    }

    public function getHomeCarousel() {
        return $this->sendResponse(
            $this->infoService->getCarousel('homePage'),
            'Home carousel was sent successfully.'
        );
    }

    public function reachFeedback(Request $request) {
        $user = $request->user('sanctum');
        if (!$user) {
            return $this->sendError('Unauthorised');
        }
        $validator = Validator::make($request->all(), [
            'subject' => 'required',
            'message' => 'required',
        ]);
        if($validator->fails()){
            return $this->sendError('Validation Error', $validator->errors());
        }
        try {
            Feedback::create($request->all());
            $mailTo = config('mail.feedbackTo');
            $this->mailService->sendMail($mailTo, 'LearnIT user feedback', 'mail.feedback',
                array_merge($request->all(), ['username' => $user->name, 'email' => $user->email]));
        } catch(\Throwable $e) {
            return $this->sendError('Something went wrong', $e->getMessage());
        }
        return $this->sendResponse(
            'success',
            'Feedback was sent successfully.'
        );
    }

    public function getMyCourses(Request $request) {
        $user = $request->user('sanctum');
        if (!$user) {
            return $this->sendError('Unauthorised');
        }
        $data = [];
        $data['courses'] = $this->accessService->getUserCoursesList($user->id);
        $data['categories'] = $this->accessService->getUserCategoriesList($user->id);
        $data['lessons'] = $this->accessService->getUserLessonsList($user->id);

        return $this->sendResponse(
            $data,
            'Data extracted successfully.'
        );
    }

    public function getMyPoints(Request $request) {
        $user = $request->user('sanctum');
        if (!$user) {
            return $this->sendError('Unauthorised');
        }
        $data = [
            'all' => $this->accessService->getUserPoints($user->id),
            'remain' => $this->accessService->getUserPointsRemain($user->id),
        ];

        return $this->sendResponse(
            $data,
            'Data extracted successfully.'
        );
    }
}
