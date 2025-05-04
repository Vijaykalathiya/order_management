<?php

if (!function_exists('safeInt')) {
    function safeInt($value) {
        return is_numeric($value) ? (int) $value : null;
    }
}

if (!function_exists('safeDecimal')) {
    function safeDecimal($value) {
        return is_numeric($value) ? (float) $value : null;
    }
}
