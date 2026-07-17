<?php

namespace App\Providers;

use App\Models\Agence;
use App\Models\AgenceUser;
use App\Models\Client;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();

        Relation::enforceMorphMap([
            'agence' => Agence::class,
            'agence_user' => AgenceUser::class,
            'client' => Client::class,
            'user' => User::class,
        ]);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        $this->configureRateLimiting();

        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api-agence-login', function (Request $request) {
            $email = Str::transliterate(Str::lower($request->input('email', '')));

            return Limit::perMinute(5)->by($email.'|'.$request->ip());
        });

        RateLimiter::for('api-agence-register', function (Request $request) {
            $email = Str::transliterate(Str::lower($request->input('gerant_email', '')));

            return Limit::perMinute(3)->by($email.'|'.$request->ip());
        });

        RateLimiter::for('api-client-login', function (Request $request) {
            $email = Str::transliterate(Str::lower($request->input('email', '')));

            return Limit::perMinute(5)->by($email.'|'.$request->ip());
        });

        RateLimiter::for('api-client-register', function (Request $request) {
            $email = Str::transliterate(Str::lower($request->input('email', '')));

            return Limit::perMinute(3)->by($email.'|'.$request->ip());
        });
    }
}
