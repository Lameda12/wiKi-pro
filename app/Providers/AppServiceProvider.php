<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Laravel's stock pagination markup assumes Tailwind; use our own.
        Paginator::defaultView('vendor.pagination.wiki');
        Paginator::defaultSimpleView('vendor.pagination.wiki');
    }
}
