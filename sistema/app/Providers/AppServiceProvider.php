<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // RNF-20: 5 intentos por minuto por usuario y dirección IP, contando también el correcto (CA-01.3)
        RateLimiter::for('inicio-de-sesion', function (Request $request) {
            return Limit::perMinute(5)
                ->by(Str::lower((string) $request->input('usuario')).'|'.$request->ip())
                ->response(fn (Request $request, array $cabeceras) => back()
                    ->withInput($request->only('usuario'))
                    ->withErrors(['usuario' => 'Hiciste demasiados intentos. Espera '.$cabeceras['Retry-After'].' segundos y vuelve a intentarlo.']));
        });
    }
}
