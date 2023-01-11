<?php

namespace App\Http\Controllers\API;

use App\Models\General\Feedback;
use App\Services\InfoService;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Services\MailService;
use Illuminate\Http\Request;
use Validator;

class InfoController extends BaseController
{
    public function __construct(private InfoService $infoService, private MailService $mailService) {}

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

    public function getInfo() {
        return $this->sendResponse(
            $this->infoService->getInfo(),
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
}
