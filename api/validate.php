<?php
function cleanEmail($email)
{
    return strtolower(trim($email));
}

function isValidEmail($email)
{
    return strlen($email) <= 120
        && filter_var($email, FILTER_VALIDATE_EMAIL)
        && preg_match('/@[^@]+\.[a-z]{2,}$/i', $email);
}