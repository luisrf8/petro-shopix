<?php

namespace App\Console\Commands;

use App\Services\TheFactoryHkaService;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

class TestTfhkaConnectionCommand extends Command
{
    protected $signature = 'tfhka:test
        {--serie= : Serie a consultar}
        {--tipo=01 : Tipo de documento}
        {--refresh-token : Limpia el token en cache antes de probar}';

    protected $description = 'Prueba autenticacion y conectividad con The Factory HKA (ambiente actual)';

    public function __construct(private readonly TheFactoryHkaService $service)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        if (!$this->service->isConfigured()) {
            $this->error('TFHKA no esta configurado. Revisa TFHKA_BASE_URL + credenciales.');
            return self::FAILURE;
        }

        if ((bool) $this->option('refresh-token')) {
            Cache::forget('tfhka.auth.token');
            $this->line('Token en cache eliminado.');
        }

        $serie = trim((string) ($this->option('serie') ?: config('services.thefactory_hka.default_serie', '')));
        $tipo = trim((string) ($this->option('tipo') ?: config('services.thefactory_hka.default_document_type', '01')));

        $this->line('Probando The Factory HKA...');
        $this->newLine();

        $last = $this->service->getLastDocument([
            'serie' => $serie,
            'tipoDocumento' => $tipo,
        ]);

        $numerations = $this->service->listNumerations([
            'serie' => $serie,
            'tipoDocumento' => $tipo,
        ]);

        $this->table(
            ['Prueba', 'OK', 'Codigo', 'Mensaje'],
            [
                [
                    'UltimoDocumento',
                    ($last['ok'] ?? false) ? 'SI' : 'NO',
                    (string) Arr::get($last, 'data.codigo', $last['status'] ?? ''),
                    (string) ($last['message'] ?? ''),
                ],
                [
                    'ConsultaNumeraciones',
                    ($numerations['ok'] ?? false) ? 'SI' : 'NO',
                    (string) Arr::get($numerations, 'data.codigo', $numerations['status'] ?? ''),
                    (string) ($numerations['message'] ?? ''),
                ],
            ]
        );

        if (($last['ok'] ?? false) || ($numerations['ok'] ?? false)) {
            $numero = Arr::get($last, 'data.numeroDocumento');
            if (!is_null($numero)) {
                $this->line('Ultimo numero reportado: ' . $numero);
            }

            $this->info('Conexion API operativa para pruebas.');
            return self::SUCCESS;
        }

        $this->error('No se pudo validar conectividad con TFHKA.');
        return self::FAILURE;
    }
}
