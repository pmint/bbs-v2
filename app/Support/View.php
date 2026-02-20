<?php

declare(strict_types=1);

namespace App\Support;

final class View
{
    public static function render(string $template, array $data = []): void
    {
        $templateFile = __DIR__ . '/../Views/' . $template . '.php';
        if (!is_file($templateFile)) {
            http_response_code(500);
            echo 'View not found: ' . $template;
            return;
        }

        $flashMessages = self::consumeFlashMessages($data);
        unset($data['errors'], $data['success']);

        extract($data, EXTR_SKIP);
        ob_start();
        require $templateFile;
        $content = (string) ob_get_clean();

        require __DIR__ . '/../Views/layout.php';
    }

    /**
     * @param array<string,mixed> $data
     * @return list<array{type:string,text:string}>
     */
    public static function consumeFlashMessages(array $data = []): array
    {
        $messages = [];

        if (isset($_SESSION['success']) && is_string($_SESSION['success']) && $_SESSION['success'] !== '') {
            $messages[] = [
                'type' => 'success',
                'text' => $_SESSION['success'],
            ];
        }

        if (isset($_SESSION['errors']) && is_array($_SESSION['errors'])) {
            foreach ($_SESSION['errors'] as $error) {
                if (!is_string($error) || $error === '') {
                    continue;
                }
                $messages[] = [
                    'type' => 'error',
                    'text' => $error,
                ];
            }
        }

        if (isset($data['success']) && is_string($data['success']) && $data['success'] !== '') {
            $messages[] = [
                'type' => 'success',
                'text' => $data['success'],
            ];
        }

        if (isset($data['errors']) && is_array($data['errors'])) {
            foreach ($data['errors'] as $error) {
                if (!is_string($error) || $error === '') {
                    continue;
                }
                $messages[] = [
                    'type' => 'error',
                    'text' => $error,
                ];
            }
        }

        if (isset($data['notices']) && is_array($data['notices'])) {
            foreach ($data['notices'] as $notice) {
                if (!is_string($notice) || $notice === '') {
                    continue;
                }
                $messages[] = [
                    'type' => 'info',
                    'text' => $notice,
                ];
            }
        }

        unset($_SESSION['success'], $_SESSION['errors']);

        return $messages;
    }
}
