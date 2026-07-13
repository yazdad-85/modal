<?php

if (! function_exists('format_rupiah')) {
    function format_rupiah(int $amount): string
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }
}

if (! function_exists('name_initials')) {
    function name_initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $initials = '';

        foreach ($parts as $part) {
            if ($part !== '') {
                $initials .= mb_strtoupper(mb_substr($part, 0, 1));
            }
        }

        return mb_substr($initials, 0, 2) ?: '?';
    }
}
