<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // One password policy for registration, password reset and settings.
        // Rejects weak passwords such as "1234567i9" (too short, no mixed case).
        Password::defaults(fn () => Password::min(10)->letters()->numbers()->mixedCase());

        if (app()->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
