<?php

namespace App\Services;

use App\Models\Course\Lesson;
use App\Models\User;
use App\Models\UserAccess\VerificationCode;
use Illuminate\Support\Facades\DB;

class AccessService
{
    const CourseEnabled = 0;
    const CourseDisabled = 1;

    public function emailIsBusy($email) {
        $user = User::firstWhere('email', $email);
        if (!$user) {
            return false;
        }
        return true;
    }

    public function checkVerificationCode($email, $type, $code) {
        $code = VerificationCode::where('email', $email)
            ->where('type', $type)
            ->where('approve_code', $code)->first();
        if (!$code) {
            return false;
        }
        return true;
    }

    public function getVerificationCode($email, $type) {
        $code = VerificationCode::where('email', $email)
            ->where('type', $type)->first();
        if (!$code) {
            $code = new VerificationCode();
            $code->email = $email;
            $code->type = $type;
        }

        try {
            $code->approve_code = $token = bin2hex(random_bytes(5));
            $code->save();
            $this->sendMail($email, 'registration code', 'mail.verifcode', [
                'code' => $code->approve_code,
                'actionText' =>  $this->getActionText($type)
            ]);
        } catch (\Throwable $e) {
            return false;
        }

        return true;
    }

    private function getActionText($type) {
        $text = [
            'registration' => 'You are trying to register on education platforn LernIT',
            'restore-passwd' => 'You are trying to change password on education platforn LernIT',
        ];
        return $text[$type];
    }

    public function getAllowedLessons($course, $user) {
        $publicLessons = $course->lessons->filter(function ($elem, $key) {
            return $elem['access_type'] == 'public';
        })->map(function($elem) {
            return $elem['id'];
        })->toArray();

        if ($user) {
            $courseIsOpenedForUser = $this->isCourseForUser($course->id, $user->id, self::CourseEnabled);
            if ($courseIsOpenedForUser) {
                $allowedLessons = $course->lessons->map(function($elem) {
                    return $elem['id'];
                })->toArray();
            } else {
                $allowedLessons = $this->lessonsForUser($course->id, $user->id, self::CourseEnabled);
                $allowedLessons = array_merge($allowedLessons, $publicLessons);
            }
        } else {
            $allowedLessons = $publicLessons;
        }

        return $allowedLessons;
    }

    public function isLessonAvailableForUser($lesson, $user = null) {
        if ($user) {
            if ($this->isCourseForUser($lesson->course_id, $user->id, self::CourseEnabled)) {
                return true;
            }
            $allowedLessons = $this->lessonsForUser($lesson->course_id, $user->id, self::CourseEnabled);
            return in_array($lesson->id, $allowedLessons);
        }
        return $lesson->access_type == 'public';
    }

    public function isPdfAvailableForUser($pdfId, $user) {
        $sqlPublic = "SELECT m.pdf_storage_id
                FROM lessons l inner join materials m on l.id = m.lesson_id
                where m.pdf_storage_id = :pdfId and l.access_type = 'public'";
        $res = DB::select($sqlPublic, [
            'pdfId' => $pdfId,
        ]);
        if (count($res) > 0) {
            return true;
        }

        if (!$user) {
            return false;
        }

        $sqlRoles = "SELECT m.pdf_storage_id
                FROM course_accesses a inner join courses c on a.course_id = c.id
                inner join lessons l on c.id = l.course_id inner join materials m on l.id = m.lesson_id
                inner join user_roles ur on a.role_id = ur.role_id
                where m.pdf_storage_id = :pdfId and a.disable = 0 and ur.user_id = :userId
                union
                SELECT m.pdf_storage_id
                FROM course_accesses a inner join course_categories cat on a.category_id = cat.id
                inner JOIN course_category_courses cc on cat.id = cc.course_category_id
                inner join courses c on cc.course_id = c.id
                inner join lessons l on c.id = l.course_id inner join materials m on l.id = m.lesson_id
                inner join user_roles ur on a.role_id = ur.role_id
                where m.pdf_storage_id = :pdfId and a.disable = 0 and ur.user_id = :userId
                union
                SELECT m.pdf_storage_id
                FROM course_accesses a inner join lessons l on a.lesson_id = l.id inner join materials m on l.id = m.lesson_id
                inner join user_roles ur on a.role_id = ur.role_id
                where m.pdf_storage_id = :pdfId and a.disable = 0 and ur.user_id = :userId";
        $res = DB::select($sqlRoles, [
            'userId' => $user->id,
            'pdfId' => $pdfId,
        ]);
        if (count($res) > 0) {
            return true;
        }

        $sqlUsers = "SELECT m.pdf_storage_id
                FROM course_accesses a inner join courses c on a.course_id = c.id
                inner join lessons l on c.id = l.course_id inner join materials m on l.id = m.lesson_id
                where m.pdf_storage_id = :pdfId and a.disable = 0 and (a.user_id = :userId or (a.user_id is null and a.role_id is null))
                union
                SELECT m.pdf_storage_id
                FROM course_accesses a inner join course_categories cat on a.category_id = cat.id
                inner JOIN course_category_courses cc on cat.id = cc.course_category_id
                inner join courses c on cc.course_id = c.id
                inner join lessons l on c.id = l.course_id inner join materials m on l.id = m.lesson_id
                where m.pdf_storage_id = :pdfId and a.disable = 0 and (a.user_id = :userId or (a.user_id is null and a.role_id is null))
                union
                SELECT m.pdf_storage_id
                FROM course_accesses a inner join lessons l on a.lesson_id = l.id inner join materials m on l.id = m.lesson_id
                where m.pdf_storage_id = :pdfId and a.disable = 0 and (a.user_id = :userId or (a.user_id is null and a.role_id is null))";
        $res = DB::select($sqlUsers, [
            'userId' => $user->id,
            'pdfId' => $pdfId,
        ]);

        return count($res) > 0 ? true : false;
    }

    private function isCourseForUser($courseId, $userId, $type = self::CourseEnabled) {
        $sql = "SELECT c.id FROM courses c inner join course_accesses a on c.id = a.course_id
            where c.id = :courseId and a.disable = :disabled and (a.user_id = :userId or (a.user_id is null and a.role_id is null))
            union
            SELECT c.id FROM courses c inner JOIN course_category_courses cc on c.id = cc.course_id
            inner join course_categories cat on cc.course_category_id = cat.id
            inner join course_accesses a on cat.id = a.category_id
            where c.id = :courseId and a.disable = :disabled and (a.user_id = :userId or (a.user_id is null and a.role_id is null))
            union
            SELECT c.id FROM courses c inner join course_accesses a on c.id = a.course_id
            inner join user_roles ur on a.role_id = ur.role_id
            where c.id = :courseId and a.disable = :disabled and ur.user_id = :userId
            union
            SELECT c.id FROM courses c inner JOIN course_category_courses cc on c.id = cc.course_id
            inner join course_categories cat on cc.course_category_id = cat.id
            inner join course_accesses a on cat.id = a.category_id
            inner join user_roles ur on a.role_id = ur.role_id
            where c.id = :courseId and a.disable = :disabled and ur.user_id = :userId";
        $res = DB::select($sql, [
            'courseId' => $courseId,
            'userId' => $userId,
            'disabled' => $type == self::CourseDisabled ? 1 : 0
        ]);

        return count($res) > 0 ? true : false;
    }

    private function lessonsForUser($courseId, $userId, $type = 'enabled') {
        $sql = 'SELECT l.id FROM courses c inner join lessons l on c.id = l.course_id inner join course_accesses a on l.id = a.lesson_id
            where c.id = :courseId and a.disable = :disabled and (a.user_id = :userId or (a.user_id is null and a.role_id is null))
            union
            SELECT l.id FROM courses c inner join lessons l on c.id = l.course_id inner join course_accesses a on l.id = a.lesson_id
            inner join user_roles ur on a.role_id = ur.role_id
            where c.id = :courseId and a.disable = :disabled and ur.user_id = :userId';
        $res = DB::select($sql, [
            'courseId' => $courseId,
            'userId' => $userId,
            'disabled' => $type == self::CourseDisabled ? 1 : 0
        ]);
        $lessonIds = [];
        foreach ($res as $row) {
            $lessonIds[] = $row->id;
        }

        return $lessonIds;
    }

    private function sendMail($email, $subject, $template, $data) {
        $headers  = 'MIME-Version: 1.0' . "\r\n";
        $headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $to = $email;
        $message = view($template, $data);
        mail($to, $subject, $message, $headers);
    }
}
