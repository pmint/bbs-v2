<?php

declare(strict_types=1);

namespace App\Support;

final class TextFormatter
{
    public static function linkifyUrls(string $text): string
    {
        $escaped = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

        $pattern = '~(https?://[^\s<>"\']+)~iu';

        $result = preg_replace_callback(
            $pattern,
            static function (array $matches): string {
                $url = $matches[1];
                return sprintf(
                    '<a href="%1$s" target="_blank" rel="noopener noreferrer">%1$s</a>',
                    $url
                );
            },
            $escaped
        );

        if (!is_string($result)) {
            return $escaped;
        }

        return self::linkifyHashtagsOutsideAnchors($result);
    }

    private static function linkifyHashtagsOutsideAnchors(string $html): string
    {
        $parts = preg_split('~(<a\b[^>]*>.*?</a>)~isu', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
        if (!is_array($parts)) {
            return $html;
        }

        $base = Url::to('/posts');

        foreach ($parts as $index => $part) {
            if (preg_match('~^<a\b~i', $part) === 1) {
                continue;
            }
            $parts[$index] = (string) preg_replace_callback(
                '/(?<![\p{L}\p{N}_&])(?:#|＃)([\p{L}\p{N}_]+)/u',
                static function (array $matches) use ($base): string {
                    $tag = (string) ($matches[1] ?? '');
                    if ($tag === '') {
                        return '';
                    }
                    $href = $base . '?q=' . urlencode('#' . $tag);
                    return '<a href="' . $href . '">#' . $tag . '</a>';
                },
                $part
            );
        }

        return implode('', $parts);
    }
}
