<?php

namespace App\Providers;

use App\Aplicacion\Avisos\GenerarAviso;
use App\Aplicacion\Consultas\AvisosPorEnviar;
use App\Aplicacion\Consultas\FotosDeOrden;
use App\Dominio\Avisos\CanalDeAviso;
use App\Dominio\Compartido\Reloj;
use App\Dominio\Fotos\AlmacenDeFotos;
use App\Dominio\Ordenes\OrdenQuedoLista;
use App\Infraestructura\Avisos\EvolutionApiCanal;
use App\Infraestructura\Avisos\WhatsAppCloudApiCanal;
use App\Infraestructura\Fotos\AlmacenLocalPrivado;
use App\Infraestructura\Reloj\RelojDeColombia;
use DateTimeInterface;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
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
        // Las pruebas lo reemplazan por CanalDeAvisoFalso. ADR-007: si Evolution API está configurada se usa esa; si no, la API oficial.
        // Sin ninguna de las dos, EnviarAviso deja el aviso para el envío asistido (RN-40)
        $this->app->bind(CanalDeAviso::class, function (): CanalDeAviso {
            $evolution = new EvolutionApiCanal(
                config('services.evolution.url'),
                config('services.evolution.clave_api'),
                config('services.evolution.instancia'),
            );

            return $evolution->estaDisponible() ? $evolution : new WhatsAppCloudApiCanal(
                config('services.whatsapp.token'),
                config('services.whatsapp.id_numero'),
                config('services.whatsapp.version_api'),
                (string) config('services.whatsapp.plantilla', 'orden_lista'),
                (string) config('services.whatsapp.idioma', 'es'),
            );
        });
    }

    public function boot(): void
    {
        // RN-37: al quedar lista la orden se genera su aviso. El oyente no está en app/Listeners, por eso se registra aquí
        Event::listen(OrdenQuedoLista::class, [GenerarAviso::class, 'handle']);

        // RNF-25: la foto no guarda su negocio; FotosDeOrden la busca a través de su orden y, si es de otro negocio, responde 404 (RNF-22).
        // Va aquí y no en routes/web.php: con las rutas en caché ese archivo no se ejecuta, y {foto} quedaría sin filtro.
        Route::bind('foto', fn (string $valor) => app(FotosDeOrden::class)->foto($valor) ?? abort(404));
        // Igual que la foto, el aviso no guarda su negocio: se busca a través de su orden (HU-29)
        Route::bind('aviso', fn (string $valor) => app(AvisosPorEnviar::class)->aviso($valor) ?? abort(404));

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
