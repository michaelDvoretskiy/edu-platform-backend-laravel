<?php

namespace App\Services;

class TranslateService
{
    public function translateArray($data, $locale = null)
    {
        if (is_null($locale)) {
            $locale = app()->getLocale();
        }
        return $data[$locale] ?? $data['default'] ?? $data[config('app.fallback_locale')] ?? "";
    }
}
