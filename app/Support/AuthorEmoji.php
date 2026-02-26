<?php

declare(strict_types=1);

namespace App\Support;

final class AuthorEmoji
{
    /** @var list<string> */
    private const EMOJI_SET = [
        '😀', '😃', '😄', '😁', '😆', '😅', '🙂', '😉',
        '😊', '😇', '🥰', '😍', '🤩', '😘', '😗', '😚',
        '😋', '😎', '🤓', '🧐', '😺', '😸', '😹', '😻',
        '🐶', '🐱', '🐭', '🐹', '🐰', '🦊', '🐻', '🐼',
        '🐨', '🐯', '🦁', '🐮', '🐷', '🐸', '🐵', '🐔',
        '🐧', '🐦', '🦄', '🐝', '🦋', '🌸', '🌼', '🌻',
        '🍀', '🌈', '⭐', '🌟', '🔥', '⚡', '🎈', '🎉',
        '🎵', '🎶', '🍎', '🍇', '🍓', '🍉', '🍙', '🍵'
    ];

    public static function fromSecret(string $secret): string
    {
        $secret = trim($secret);
        if ($secret === '') {
            return '';
        }

        $hash = hash('sha256', $secret);
        $size = count(self::EMOJI_SET);
        $first = hexdec(substr($hash, 0, 8)) % $size;
        $second = hexdec(substr($hash, 8, 8)) % $size;

        return self::EMOJI_SET[$first] . self::EMOJI_SET[$second];
    }
}
