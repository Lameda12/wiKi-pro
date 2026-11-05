<?php

declare(strict_types=1);

use Illuminate\Http\Request;

// Vercel entrypoint. This mirrors public/index.php, which is excluded from the
// upload (.vercelignore) so the static file server cannot expose or shadow it.
define('LARAVEL_START', microtime(true));

require __DIR__.'/../vendor/autoload.php';

(require_once __DIR__.'/../bootstrap/app.php')
    ->handleRequest(Request::capture());
