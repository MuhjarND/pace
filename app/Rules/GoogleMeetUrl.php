<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class GoogleMeetUrl implements Rule
{
    public function passes($attribute, $value)
    {
        return is_string($value) && preg_match(
            '~\Ahttps://meet\.google\.com/(?:meet/)?[a-z]{3}-[a-z]{4}-[a-z]{3}/?(?:\?[^\s#]*)?\z~',
            $value
        ) === 1;
    }

    public function message()
    {
        return 'Masukkan tautan Google Meet dengan format https://meet.google.com/abc-defg-hij.';
    }

    public static function normalize($value)
    {
        if (!(new self)->passes('join_url', $value)) {
            return null;
        }
        $path = trim(parse_url($value, PHP_URL_PATH), '/');

        return 'https://meet.google.com/'.basename($path);
    }
}
