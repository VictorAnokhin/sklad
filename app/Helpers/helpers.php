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



// ── Date helpers ──────────────────────────────────────────────────────────────

if (!function_exists('nextdate')) {
    /** Y-m-d → unix timestamp */
    function nextdate(string $date): int
    {
        $dt = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        return $dt ? $dt->getTimestamp() : 0;
    }
}

if (!function_exists('curdate')) {
    /** Normalize any supported date input to d-m-Y */
    function curdate(string $date): string
    {
        $date = trim($date);
        if ($date === '') {
            return date('d-m-Y');
        }

        $formats = ['d-m-Y', 'Y-m-d', 'd.m.Y', 'Y/m/d'];
        foreach ($formats as $format) {
            $dt = DateTimeImmutable::createFromFormat($format, $date);
            if ($dt instanceof DateTimeImmutable) {
                return $dt->format('d-m-Y');
            }
        }

        return $date;
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
