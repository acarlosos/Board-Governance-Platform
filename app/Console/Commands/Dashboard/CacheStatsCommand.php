<?php

namespace App\Console\Commands\Dashboard;

use App\Services\Dashboard\Executive\Observability\ExecutiveDashboardObservability;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Throwable;

final class CacheStatsCommand extends Command
{
    protected $signature = 'dashboard:cache-stats
                            {--day= : Data YYYY-MM-DD (timezone da app; omissão = hoje)}
                            {--json : Saída JSON em vez de tabela}';

    protected $description = 'Estatísticas diárias do cache do dashboard executivo (L1, L2, invalidações).';

    public function handle(ExecutiveDashboardObservability $obs): int
    {
        $tz = config('app.timezone');

        try {
            $day = $this->option('day')
                ? CarbonImmutable::parse((string) $this->option('day'), $tz)->setTimezone($tz)->startOfDay()
                : CarbonImmutable::now($tz)->startOfDay();
        } catch (Throwable) {
            $this->error('Data inválida: use YYYY-MM-DD.');

            return self::FAILURE;
        }

        $snap = $obs->snapshotFor($day);

        if ($this->option('json')) {
            $this->line(json_encode($snap, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Dia', $snap['day']],
                ['L1 hits', (string) $snap['l1']['hits']],
                ['L1 misses', (string) $snap['l1']['misses']],
                ['L1 hit_ratio', (string) $snap['l1']['hit_ratio']],
                ['L2 hits', (string) $snap['l2']['hits']],
                ['L2 misses', (string) $snap['l2']['misses']],
                ['L2 hit_ratio', (string) $snap['l2']['hit_ratio']],
                ['Invalidações', (string) $snap['invalidations']],
            ],
        );

        return self::SUCCESS;
    }
}
