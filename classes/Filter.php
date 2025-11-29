<?php

class Filter
{
    public static function cleanString($str)
    {
        return htmlspecialchars(trim($str), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function filterName($str)
    {
        $str = trim($str);
        return preg_replace('/[^a-zA-Z0-9ąćęłńóśźżĄĆĘŁŃÓŚŹŻ ]/', '', $str);
    }

    public static function filterMessage($str)
    {
        return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
    }

    public static function filterType($type)
    {
        $allowed = ['public', 'private'];
        if (!in_array($type, $allowed, true)) {
            return 'public';
        }
        return $type;
    }

    public static function filterEmail($email)
    {
        return filter_var($email, FILTER_SANITIZE_EMAIL);
    }
}

