<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Support\View;

final class PageController
{
    public function press(): void
    {
        View::render('pages/press', [
            'title' => '広報室',
        ]);
    }
}
