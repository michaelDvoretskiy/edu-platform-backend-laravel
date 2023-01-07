<?php

namespace App\Http\Controllers\API;

use App\Services\AccessService;
use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Validator;

class AuthController extends BaseController
{

    public function __construct(private AccessService $accessService) {}

    /**
     * Register api
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email',
            'password' => 'required',
            'c_password' => 'required|same:password',
            'appname' => 'required',
            'verif_code' => 'required',
        ]);

        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());
        }

        if ($this->accessService->emailIsBusy($request->email)) {
            return $this->sendError('Email is already registered', ['error'=>'Email is already registered']);
        }

        if(!$this->accessService->checkVerificationCode($request->email, 'registration', $request->verif_code)) {
            return $this->sendError('Verification token error', ['error' => 'wrong varification code']);
        }

        $input = $request->all();
        $input['password'] = bcrypt($input['password']);
        $user = User::create($input);
        $success['token'] =  $user->createToken($request->appname)->plainTextToken;
        $success['name'] =  $user->name;

        return $this->sendResponse($success, 'User register successfully.');
    }

    /**
     * Login api
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request)
    {
        if(Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            $user = Auth::user();
            $user->tokens()->where('name', $request->appname)->delete();
            $success['token'] = $user->createToken($request->appname)->plainTextToken;
            $success['name'] = $user->name;

            return $this->sendResponse($success, 'User login successfully.');
        } else {
            return $this->sendError('Unauthorised.', ['error'=>'Unauthorised']);
        }
    }

    public function getVerificationCode(Request $request, $type) {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());
        }

        if (!in_array($type, ['registration', 'restore-passwd'])) {
            return $this->sendError('Validation Error.', ['error' => 'veerification code type is invalid']);
        }

        $email = $request->query('email');
        if (!$email) {
            return $this->sendError('Not enough data', ['error'=>'lack of email']);
        }

        if ($type == 'registration' && $this->accessService->emailIsBusy($email)) {
            return $this->sendError('Email is already registered', ['error'=>'Email is already registered']);
        }

        if ($type == 'restore-passwd' && !$this->accessService->emailIsBusy($email)) {
            return $this->sendError('Email does not exist', ['error'=>'Email does not exist']);
        }

        if (!$this->accessService->getVerificationCode($email, $type)) {
            return $this->sendError('Error', ['error'=>'Something went wrong']);
        }

        return $this->sendResponse('Success', 'Verification code was sent to email successfully');
    }

    public function restorePassword(Request $request) {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
            'c_password' => 'required|same:password',
            'appname' => 'required',
            'verif_code' => 'required',
        ]);

        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());
        }

        $user = User::firstWhere('email', $request->email);
        if (!$user) {
            return $this->sendError('Email does not exist', ['error'=>'Email does not exist']);
        }

        if(!$this->accessService->checkVerificationCode($request->email, 'restore-passwd', $request->verif_code)) {
            return $this->sendError('Verification token error', ['error' => 'wrong varification code']);
        }

        $user->password = bcrypt($request->password);
        $user->save();

        $user->tokens()->where('name', $request->appname)->delete();
        $success['token'] =  $user->createToken($request->appname)->plainTextToken;
        $success['name'] =  $user->name;

        return $this->sendResponse($success, 'User password was changed successfully.');
    }
}
