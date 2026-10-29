<?php

namespace App\Support;

/**
 * Detecção simples de dispositivo, navegador e sistema a partir do User-Agent (sem pacotes externos).
 */
class UserAgent
{
    public static function isBot(string $userAgent): bool
    {
        return $userAgent === '' || (bool) preg_match('/bot|crawl|spider|slurp|facebookexternalhit|preview|monitor|curl|wget|python-requests|lighthouse/i', $userAgent);
    }

    /**
     * @return array{device: string, browser: string, os: string}
     */
    public static function parse(string $userAgent): array
    {
        return [
            'device' => self::device($userAgent),
            'browser' => self::browser($userAgent),
            'os' => self::os($userAgent),
        ];
    }

    private static function device(string $ua): string
    {
        return match (true) {
            (bool) preg_match('/ipad|tablet|kindle|silk|(android(?!.*mobile))/i', $ua) => 'tablet',
            (bool) preg_match('/mobi|iphone|ipod|android|windows phone/i', $ua) => 'mobile',
            default => 'desktop',
        };
    }

    private static function browser(string $ua): string
    {
        return match (true) {
            (bool) preg_match('/edg(e|a|ios)?\//i', $ua) => 'Edge',
            (bool) preg_match('/opr\/|opera/i', $ua) => 'Opera',
            (bool) preg_match('/firefox|fxios/i', $ua) => 'Firefox',
            (bool) preg_match('/chrome|crios|chromium/i', $ua) => 'Chrome',
            (bool) preg_match('/safari/i', $ua) => 'Safari',
            default => 'Outro',
        };
    }

    private static function os(string $ua): string
    {
        return match (true) {
            (bool) preg_match('/windows/i', $ua) => 'Windows',
            (bool) preg_match('/iphone|ipad|ipod|ios/i', $ua) => 'iOS',
            (bool) preg_match('/android/i', $ua) => 'Android',
            (bool) preg_match('/mac os|macintosh/i', $ua) => 'macOS',
            (bool) preg_match('/cros/i', $ua) => 'ChromeOS',
            (bool) preg_match('/linux|x11/i', $ua) => 'Linux',
            default => 'Outro',
        };
    }
}
