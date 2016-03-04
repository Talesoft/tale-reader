<?php

if (!function_exists('mb_strlen')) {

    function mb_strlen($string, $encoding = null)
    {

        return strlen($string);
    }

    function mb_substr($string, $start, $length = null, $encoding = null)
    {

        return substr($string, $start, $length);
    }

    function mb_strpos($haystack, $needle, $offset = null, $encoding = null)
    {

        return strpos($haystack, $needle, $offset);
    }

    function mb_strstr($haystack, $needle, $before_needle = false, $encoding = null)
    {

        return strstr($haystack, $needle, $before_needle);
    }

    function mb_substr_count($haystack, $needle, $encoding = null)
    {

        return substr_count($haystack, $needle);
    }
}