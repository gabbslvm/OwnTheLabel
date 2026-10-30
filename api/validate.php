<?php

function cleanEmail($email)
{
    return strtolower(trim($email));
}

function isValidEmail($email)
{
    if (strlen($email) > 120) {
        return false;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $domain = substr($email, strpos($email, '@') + 1);
    if (!str_contains($domain, '.')) {
        return false;
    }

    return true;
}