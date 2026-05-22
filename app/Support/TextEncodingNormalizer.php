<?php

namespace App\Support;

use Throwable;

class TextEncodingNormalizer
{
    /**
     * Common mojibake markers left behind when UTF-8 text is decoded as Latin-1 / Windows-1252.
     *
     * @var list<string>
     */
    private const SUSPICIOUS_MARKERS = [
        'Ã',
        'Â',
        'â',
        'ð',
        '�',
    ];

    /**
     * Encodings that most commonly produce readable mojibake in MySQL / dump restores.
     *
     * @var list<string>
     */
    private const SOURCE_ENCODINGS = [
        'Windows-1252',
        'ISO-8859-1',
    ];

    public static function normalize(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        $normalized = $value;

        for ($attempt = 0; $attempt < 3; $attempt++) {
            if (! self::looksLikeMojibake($normalized)) {
                break;
            }

            $candidate = self::repairOnce($normalized);

            if ($candidate === null || $candidate === $normalized) {
                break;
            }

            $normalized = $candidate;
        }

        return $normalized;
    }

    private static function repairOnce(string $value): ?string
    {
        $currentScore = self::score($value);

        foreach (self::SOURCE_ENCODINGS as $encoding) {
            try {
                $candidate = mb_convert_encoding($value, $encoding, 'UTF-8');
            } catch (Throwable) {
                continue;
            }

            if (! is_string($candidate) || $candidate === '' || $candidate === $value) {
                continue;
            }

            if (! mb_check_encoding($candidate, 'UTF-8')) {
                continue;
            }

            if (str_contains($candidate, "\u{FFFD}")) {
                continue;
            }

            if (self::score($candidate) >= $currentScore) {
                continue;
            }

            return $candidate;
        }

        return null;
    }

    private static function looksLikeMojibake(string $value): bool
    {
        if (self::score($value) > 0) {
            return true;
        }

        return preg_match('/[\x{2500}-\x{257F}]/u', $value) === 1;
    }

    private static function score(string $value): int
    {
        $score = 0;

        foreach (self::SUSPICIOUS_MARKERS as $marker) {
            $score += substr_count($value, $marker);
        }

        $boxDrawingCount = preg_match_all('/[\x{2500}-\x{257F}]/u', $value);

        if (is_int($boxDrawingCount) && $boxDrawingCount > 0) {
            $score += $boxDrawingCount * 2;
        }

        return $score;
    }
}
