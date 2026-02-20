<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\View;
use PHPUnit\Framework\TestCase;

final class ViewFlashTest extends TestCase
{
    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
    }

    public function testConsumeFlashMessagesCollectsSessionValues(): void
    {
        $_SESSION['success'] = 'ok';
        $_SESSION['errors'] = ['e1', 'e2'];

        $messages = View::consumeFlashMessages();

        self::assertCount(3, $messages);
        self::assertSame('success', $messages[0]['type']);
        self::assertSame('ok', $messages[0]['text']);
        self::assertSame('error', $messages[1]['type']);
        self::assertSame('e1', $messages[1]['text']);
        self::assertArrayNotHasKey('success', $_SESSION);
        self::assertArrayNotHasKey('errors', $_SESSION);
    }

    public function testConsumeFlashMessagesCollectsDataValues(): void
    {
        $messages = View::consumeFlashMessages([
            'success' => 'saved',
            'errors' => ['bad request'],
        ]);

        self::assertCount(2, $messages);
        self::assertSame('success', $messages[0]['type']);
        self::assertSame('saved', $messages[0]['text']);
        self::assertSame('error', $messages[1]['type']);
        self::assertSame('bad request', $messages[1]['text']);
    }

    public function testConsumeFlashMessagesCollectsNoticeAsInfo(): void
    {
        $messages = View::consumeFlashMessages([
            'notices' => ['reply arrived'],
        ]);

        self::assertCount(1, $messages);
        self::assertSame('info', $messages[0]['type']);
        self::assertSame('reply arrived', $messages[0]['text']);
    }
}
