<?php

namespace Tests\Feature\Dashboard\Executive\Observability;

use App\Services\Dashboard\Executive\Observability\ExecutiveDashboardObservability;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class CacheStatsCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    #[Test]
    public function test_comando_json_com_day_fixo_e_counters_conhecidos(): void
    {
        $day = '2026-05-12';
        Cache::put(ExecutiveDashboardObservability::PREFIX.'l1:hit:'.$day, 5, 3600);
        Cache::put(ExecutiveDashboardObservability::PREFIX.'l1:miss:'.$day, 1, 3600);
        Cache::put(ExecutiveDashboardObservability::PREFIX.'l2:hit:'.$day, 3, 3600);
        Cache::put(ExecutiveDashboardObservability::PREFIX.'l2:miss:'.$day, 2, 3600);
        Cache::put(ExecutiveDashboardObservability::PREFIX.'invalidations:'.$day, 7, 3600);

        $exit = Artisan::call('dashboard:cache-stats', [
            '--day' => $day,
            '--json' => true,
        ]);

        $this->assertSame(0, $exit);
        $decoded = json_decode(Artisan::output(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame($day, $decoded['day']);
        $this->assertSame(5, $decoded['l1']['hits']);
        $this->assertSame(1, $decoded['l1']['misses']);
        $this->assertSame(3, $decoded['l2']['hits']);
        $this->assertSame(2, $decoded['l2']['misses']);
        $this->assertSame(7, $decoded['invalidations']);
    }

    #[Test]
    public function test_comando_sem_day_imprime_tabela_e_day_invalido_falha(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-07-01 15:00:00', config('app.timezone')));

        $exitOk = Artisan::call('dashboard:cache-stats', []);
        $this->assertSame(0, $exitOk);
        $out = Artisan::output();
        $this->assertStringContainsString('L1 hits', $out);
        $this->assertStringContainsString('2026-07-01', $out);

        $exitBad = Artisan::call('dashboard:cache-stats', ['--day' => 'banana']);
        $this->assertNotSame(0, $exitBad);

        CarbonImmutable::setTestNow();
    }
}
