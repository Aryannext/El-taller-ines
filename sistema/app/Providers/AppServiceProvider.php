<?php

namespace App\Providers;

use App\Dominio\Compartido\Reloj;
use App\Dominio\Fotos\AlmacenDeFotos;
use App\Infraestructura\Fotos\AlmacenLocalPrivado;
use App\Infraestructura\Reloj\RelojDeColombia;
use DateTimeInterface;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    private const MESES = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];

    private const DIAS = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];

    public function register(): void
    {
        // Las pruebas lo reemplazan por un reloj fijo (RN-09)
        $this->app->singleton(Reloj::class, RelojDeColombia::class);
        // Las pruebas usan la misma clase sobre Storage::fake('privado'), para medir la imagen de verdad (RNF-03)
        $this->app->bind(AlmacenDeFotos::class, AlmacenLocalPrivado::class);
    }

    public function boot(): void
    {
        // Formatos para mostrar (docs/04-especificacion-tecnica/04-datos-y-modelos.md)
        Blade::directive('celular', fn (string $expresion) => "<?php echo e(preg_replace('/^(\\d{3})(\\d{3})(\\d{4})$/', '\$1 \$2 \$3', (string) ({$expresion}))); ?>");
        Blade::directive('dinero', fn (string $expresion) => "<?php echo e(\\App\\Dominio\\Pagos\\Dinero::pesos((int) ({$expresion}))->formato()); ?>");
        Blade::directive('fecha', fn (string $expresion) => "<?php echo e(\\App\\Providers\\AppServiceProvider::fecha({$expresion})); ?>");
        Blade::directive('fechaConDia', fn (string $expresion) => "<?php echo e(\\App\\Providers\\AppServiceProvider::fecha({$expresion}, true)); ?>");
        Blade::directive('hora', fn (string $expresion) => "<?php echo e(\\App\\Providers\\AppServiceProvider::hora({$expresion})); ?>");

        // RNF-20: 5 intentos por minuto por usuario y dirección IP, contando también el correcto (CA-01.3)
        RateLimiter::for('inicio-de-sesion', function (Request $request) {
            return Limit::perMinute(5)
                ->by(Str::lower((string) $request->input('usuario')).'|'.$request->ip())
                ->response(fn (Request $request, array $cabeceras) => back()
                    ->withInput($request->only('usuario'))
                    ->withErrors(['usuario' => 'Hiciste demasiados intentos. Espera '.$cabeceras['Retry-After'].' segundos y vuelve a intentarlo.']));
        });
    }

    /**
     * RNF-08: «14 sep 2026», o «Sábado 19 sep 2026» con el día. Los meses van escritos aquí para no depender de traducciones.
     */
    public static function fecha(DateTimeInterface $fecha, bool $conDia = false): string
    {
        $texto = $fecha->format('j').' '.self::MESES[(int) $fecha->format('n') - 1].' '.$fecha->format('Y');

        return $conDia ? self::DIAS[(int) $fecha->format('w')].' '.$texto : $texto;
    }

    /**
     * RNF-08: «4:12 p. m.», con doce horas como se dice en Colombia.
     */
    public static function hora(DateTimeInterface $momento): string
    {
        return $momento->format('g:i').($momento->format('A') === 'AM' ? ' a. m.' : ' p. m.');
    }
}
