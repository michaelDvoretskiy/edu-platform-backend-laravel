<?php

namespace App\Services;

use App\Models\Course\Course;
use App\Models\Course\CourseCategory;
use App\Models\Course\Lesson;
use App\Models\User;
use App\Models\UserAccess\VerificationCode;
use Illuminate\Support\Facades\DB;

class AccessService
{
    const CourseEnabled = 0;
    const CourseDisabled = 1;

    public function __construct(private MailService $mailService) {
    }

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
            $this->sendMail($email, $this->getVerificationText($type, 'subject'), 'mail.verifcode', [
                'code' => $code->approve_code,
                'actionText' =>  $this->getVerificationText($type, 'action-text')
            ]);
        } catch (\Throwable $e) {
            return false;
        }

        return true;
    }

    private function getVerificationText($verifType, $textType) {
        $text = [
            'registration' => [
                'action-text' => __('mailtemplate.regVerifText'),
                'subject' => __('mailtemplate.regVerifSbj'),
            ],
            'restore-passwd' => [
                'action-text' => __('mailtemplate.passVerifText'),
                'subject' => __('mailtemplate.passVerifSbj'),
            ]
        ];
        return $text[$verifType][$textType];
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
            return in_array($lesson->id, $allowedLessons) || $lesson->access_type == 'public';
        }
        return $lesson->access_type == 'public';
    }

    public function getUserCacheList($user) {
        if (is_null($user->cache_updated)) {
            $sql = "select distinct type from cache_dates where user_id is null";
            $res = DB::select($sql);
        } else {
            $sql = "select distinct type from cache_dates
                where (user_id is null or user_id = :useId) and cache_data_updated > :date";
            $res = DB::select($sql, [
                'useId' => $user->id,
                'date' => $user->cache_updated,
            ]);
        }
        return array_map(function($elem) {
            return $elem->type;
        }, $res);
    }

    public function updateCacheClearedDate($user, $date = null) {
        if (is_null($date)) {
            $date = new \DateTime();
        }
        $user->cache_updated = $date;
        $user->save();
    }

    public function isPdfAvailableForUser($pdfId, $user, $type) {
        $tables = [
            'material' => 'materials',
            'task' => 'tasks'
        ];
        $table = $tables[$type] ?? 'materials';
        $sqlPublic = "SELECT m.pdf_storage_id
                FROM lessons l inner join $table m on l.id = m.lesson_id
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
                inner join lessons l on c.id = l.course_id inner join $table m on l.id = m.lesson_id
                inner join user_roles ur on a.role_id = ur.role_id
                where m.pdf_storage_id = :pdfId and a.disable = 0 and ur.user_id = :userId and a.expired > now()
                union
                SELECT m.pdf_storage_id
                FROM course_accesses a inner join course_categories cat on a.category_id = cat.id
                inner JOIN course_category_courses cc on cat.id = cc.course_category_id
                inner join courses c on cc.course_id = c.id
                inner join lessons l on c.id = l.course_id inner join $table m on l.id = m.lesson_id
                inner join user_roles ur on a.role_id = ur.role_id
                where m.pdf_storage_id = :pdfId and a.disable = 0 and ur.user_id = :userId and a.expired > now()
                union
                SELECT m.pdf_storage_id
                FROM course_accesses a inner join lessons l on a.lesson_id = l.id inner join $table m on l.id = m.lesson_id
                inner join user_roles ur on a.role_id = ur.role_id
                where m.pdf_storage_id = :pdfId and a.disable = 0 and ur.user_id = :userId and a.expired > now()";
        $res = DB::select($sqlRoles, [
            'userId' => $user->id,
            'pdfId' => $pdfId,
        ]);
        if (count($res) > 0) {
            return true;
        }

        $sqlUsers = "SELECT m.pdf_storage_id
                FROM course_accesses a inner join courses c on a.course_id = c.id
                inner join lessons l on c.id = l.course_id inner join $table m on l.id = m.lesson_id
                where m.pdf_storage_id = :pdfId and a.disable = 0 and (a.user_id = :userId or (a.user_id is null and a.role_id is null))
                and a.expired > now()
                union
                SELECT m.pdf_storage_id
                FROM course_accesses a inner join course_categories cat on a.category_id = cat.id
                inner JOIN course_category_courses cc on cat.id = cc.course_category_id
                inner join courses c on cc.course_id = c.id
                inner join lessons l on c.id = l.course_id inner join $table m on l.id = m.lesson_id
                where m.pdf_storage_id = :pdfId and a.disable = 0 and (a.user_id = :userId or (a.user_id is null and a.role_id is null))
                and a.expired > now()
                union
                SELECT m.pdf_storage_id
                FROM course_accesses a inner join lessons l on a.lesson_id = l.id inner join $table m on l.id = m.lesson_id
                where m.pdf_storage_id = :pdfId and a.disable = 0 and (a.user_id = :userId or (a.user_id is null and a.role_id is null))
                and a.expired > now()";
        $res = DB::select($sqlUsers, [
            'userId' => $user->id,
            'pdfId' => $pdfId,
        ]);

        return count($res) > 0 ? true : false;
    }

    public function getUserCoursesList($userId) {
        $sql = "select id, max(`name`) `name`, max(title) title, max(expired) expired, max(is_enabled) is_enabled
            from
            (SELECT c.id, c.name, c.title, a.expired, a.expired > now() is_enabled
                        FROM courses c inner join course_accesses a on c.id = a.course_id
                        where a.disable = 0 and (a.user_id = :userId or (a.user_id is null and a.role_id is null))
            UNION
            SELECT c.id, c.name, c.title, a.expired, a.expired > now() is_enabled
            FROM courses c inner join course_accesses a on c.id = a.course_id inner join user_roles ur on a.role_id = ur.role_id
            where a.disable = 0 and ur.user_id = :userId) title
            group by id
            order by expired desc";
        $res = DB::select($sql, [
            'userId' => $userId,
        ]);
        $courses = Course::hydrate($res);
        return $courses->map(function($elem) {
            return [
                'name' => $elem->name,
                'title' => $elem->title,
                'expired' => $elem->expired,
                'isEnabled' => $elem->is_enabled,
            ];
        });
    }

    public function getUserLessonsList($userId) {
        $sql = "select id, max(`name`) `name`, max(title) title, max(expired) expired, max(is_enabled) is_enabled
            from
            (SELECT l.id, l.name, l.title, a.expired, a.expired > now() is_enabled
                        FROM lessons l inner join course_accesses a on l.id = a.lesson_id
                        where a.disable = 0 and (a.user_id = :userId or (a.user_id is null and a.role_id is null))
            UNION
            SELECT l.id, l.name, l.title, a.expired, a.expired > now() is_enabled
            FROM lessons l inner join course_accesses a on l.id = a.lesson_id inner join user_roles ur on a.role_id = ur.role_id
            where a.disable = 0 and ur.user_id = :userId) title
            group by id
            order by expired desc";
        $res = DB::select($sql, [
            'userId' => $userId,
        ]);
        $courses = Course::hydrate($res);
        return $courses->map(function($elem) {
            return [
                'name' => $elem->name,
                'title' => $elem->title,
                'expired' => $elem->expired,
                'isEnabled' => $elem->is_enabled,
            ];
        });
    }

    public function getUserCategoriesList($userId) {
        $sql = "select id, max(`name`) `name`, max(title) title, max(expired) expired, max(is_enabled) is_enabled
            from
            (SELECT cat.id, cat.name, cat.title, a.expired, a.expired > now() is_enabled
                        FROM course_categories cat inner join course_accesses a on cat.id = a.category_id
                        where a.disable = 0 and (a.user_id = :userId or (a.user_id is null and a.role_id is null))
            UNION
            SELECT cat.id, cat.name, cat.title, a.expired, a.expired > now() is_enabled
            FROM course_categories cat inner join course_accesses a on cat.id = a.category_id inner join user_roles ur on a.role_id = ur.role_id
            where a.disable = 0 and ur.user_id = :userId) title
            group by id
            order by expired desc";
        $res = DB::select($sql, [
            'userId' => $userId,
        ]);
        $cat = CourseCategory::hydrate($res);
        return $cat->map(function($elem) {
            return [
                'name' => $elem->name,
                'title' => $elem->title,
                'expired' => $elem->expired,
                'isEnabled' => $elem->is_enabled,
            ];
        });
    }

    public function getUserPoints($userId) {
        $sql = "select operation_date, points, description
            from `points`
            where user_id = :userId
            order by operation_date desc";
        return DB::select($sql, [
            'userId' => $userId,
        ]);
    }

    public function getUserPointsRemain($userId) {
        $sql = "select sum(points) p
            from `points`
            where user_id = :userId";
        return DB::select($sql, [
            'userId' => $userId,
        ])[0]->p;
    }

    private function isCourseForUser($courseId, $userId, $type = self::CourseEnabled) {
        $sql = "SELECT c.id FROM courses c inner join course_accesses a on c.id = a.course_id
            where c.id = :courseId and a.disable = :disabled and (a.user_id = :userId or (a.user_id is null and a.role_id is null))
            and a.expired > now()
            union
            SELECT c.id FROM courses c inner JOIN course_category_courses cc on c.id = cc.course_id
            inner join course_categories cat on cc.course_category_id = cat.id
            inner join course_accesses a on cat.id = a.category_id
            where c.id = :courseId and a.disable = :disabled and (a.user_id = :userId or (a.user_id is null and a.role_id is null))
            and a.expired > now()
            union
            SELECT c.id FROM courses c inner join course_accesses a on c.id = a.course_id
            inner join user_roles ur on a.role_id = ur.role_id
            where c.id = :courseId and a.disable = :disabled and ur.user_id = :userId
            and a.expired > now()
            union
            SELECT c.id FROM courses c inner JOIN course_category_courses cc on c.id = cc.course_id
            inner join course_categories cat on cc.course_category_id = cat.id
            inner join course_accesses a on cat.id = a.category_id
            inner join user_roles ur on a.role_id = ur.role_id
            where c.id = :courseId and a.disable = :disabled and ur.user_id = :userId
            and a.expired > now()";
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
            and a.expired > now()
            union
            SELECT l.id FROM courses c inner join lessons l on c.id = l.course_id inner join course_accesses a on l.id = a.lesson_id
            inner join user_roles ur on a.role_id = ur.role_id
            where c.id = :courseId and a.disable = :disabled and ur.user_id = :userId
            and a.expired > now()';
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

    public function testForUser($testId, $userId) {
        $sql = 'SELECT a.test_id FROM test_accesses a
            where a.test_id = :testId and (a.user_id = :userId or (a.user_id is null and a.role_id is null))
            and a.expired > now()';
        $res = DB::select($sql, [
            'testId' => $testId,
            'userId' => $userId
        ]);

        return count($res) > 0 ? true : false;
    }

    private function sendMail($email, $subject, $template, $data) {
        $this->mailService->sendMail($email, $subject, $template, $data);
    }
}
