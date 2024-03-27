<?php
//Compatibility with 8.0
function str_starts_with($haystack, $needle)
{
    return substr($haystack, 0, strlen($needle)) === $needle;
}

function str_contains($haystack, $needle)
{
    return $needle !== '' && mb_strpos($haystack, $needle) !== false;
}