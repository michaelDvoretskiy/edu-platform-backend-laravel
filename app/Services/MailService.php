<?php

namespace App\Services;

use App\Models\Course\Lesson;
use App\Models\User;
use App\Models\UserAccess\VerificationCode;
use Illuminate\Support\Facades\DB;

class MailService
{
    public function sendMail($email, $subject, $template, $data) {
        $headers  = 'MIME-Version: 1.0' . "\r\n";
        $headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $to = $email;
        $message = view($template, $data);
        mail($to, $subject, $message, $headers);
    }
}
