<?php

declare(strict_types=1);

/**
 * helpers.php  — autoloaded via composer.json "files"
 *
 * Migrated from: library/lib.inc (helper functions)
 * Functions that used global $pdo are now moved to Services.
 */

// ── HTML escaping ─────────────────────────────────────────────────────────────

if (!function_exists('h')) {
    function h(mixed $v): string
    {
        return htmlspecialchars((string)$v, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}

// ── Encoding ──────────────────────────────────────────────────────────────────
// Legacy DB stored strings as base64(windows-1251). New rows → UTF-8.

if (!function_exists('convert_from_base')) {
    function convert_from_base(mixed $str): string
    {
        if ($str === null || $str === '') return '';
        $decoded = base64_decode((string)$str, true);
        if ($decoded === false) return (string)$str;
        if (mb_detect_encoding($decoded, ['UTF-8'], true) === false) {
            return mb_convert_encoding($decoded, 'UTF-8', 'windows-1251');
        }
        return $decoded;
    }
}

if (!function_exists('convert_to_base')) {
    function convert_to_base(string $str): string
    {
        return base64_encode($str);
    }
}

// ── Date helpers ──────────────────────────────────────────────────────────────

if (!function_exists('nextdate')) {
    /** Y-m-d → unix timestamp */
    function nextdate(string $date): int
    {
        $dt = DateTimeImmutable::createFromFormat('Y-m-d', $date);
        return $dt ? $dt->getTimestamp() : 0;
    }
}

if (!function_exists('curdate')) {
    /** d-m-Y → Y-m-d */
    function curdate(string $date): string
    {
        $dt = DateTimeImmutable::createFromFormat('d-m-Y', $date);
        return $dt ? $dt->format('Y-m-d') : $date;
    }
}

if (!function_exists('yearFromDMY')) {
    function yearFromDMY(string $date): string
    {
        return strlen($date) >= 10 ? substr($date, 6, 4) : date('Y');
    }
}

// ── Phone ─────────────────────────────────────────────────────────────────────

if (!function_exists('formatPhone')) {
    function formatPhone(string $phone): string
    {
        if ($phone === '') return '';
        $p = preg_replace('/\D/', '', $phone);
        if (strlen($p) === 12 && str_starts_with($p, '38')) {
            return '+38 (' . substr($p, 2, 3) . ') '
                 . substr($p, 5, 3) . '-' . substr($p, 8, 2) . '-' . substr($p, 10, 2);
        }
        return $phone;
    }
}

// ── Numbers ───────────────────────────────────────────────────────────────────

if (!function_exists('hasDecimalPart')) {
    function hasDecimalPart(float $number): bool
    {
        return fmod($number, 1) !== 0.0;
    }
}
