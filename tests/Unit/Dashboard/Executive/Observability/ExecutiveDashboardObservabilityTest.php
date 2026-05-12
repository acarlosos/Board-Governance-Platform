<?php

namespace Tests\Unit\Dashboard\Executive\Observability;

use App\Services\Dashboard\Executive\Observability\ExecutiveDashboardObservability;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ExecutiveDashboardObservabilityTest extends TestCase
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
    public function test_record_l1_hit_incrementa_chave_diaria(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-05-10', config('app.timezone')));
        $obs = app(ExecutiveDashboardObservability::class);

        $obs->recordL1Hit();
        $obs->recordL1Hit();

        $key = ExecutiveDashboardObservability::PREFIX.'l1:hit:2026-05-10';
        $this->assertSame(2, (int) Cache::get($key));

        CarbonImmutable::setTestNow();
    }

    #[Test]
    public function test_record_l1_miss_l2_hit_l2_miss_e_invalidation_incrementam_buckets_distintos(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-05-11', config('app.timezone')));
        $obs = app(ExecutiveDashboardObservability::class);

        $obs->recordL1Miss();
        $obs->recordL2Hit();
        $obs->recordL2Miss();
        $obs->recordInvalidation();

        $this->assertSame(1, (int) Cache::get(ExecutiveDashboardObservability::PREFIX.'l1:miss:2026-05-11'));
        $this->assertSame(1, (int) Cache::get(ExecutiveDashboardObservability::PREFIX.'l2:hit:2026-05-11'));
        $this->assertSame(1, (int) Cache::get(ExecutiveDashboardObservability::PREFIX.'l2:miss:2026-05-11'));
        $this->assertSame(1, (int) Cache::get(ExecutiveDashboardObservability::PREFIX.'invalidations:2026-05-11'));

        CarbonImmutable::setTestNow();
    }

    #[Test]
    public function test_snapshot_for_devolve_shape_e_hit_ratio_tres_casas(): void
    {
        $day = CarbonImmutable::parse('2026-05-12', config('app.timezone'));
        Cache::put(ExecutiveDashboardObservability::PREFIX.'l1:hit:2026-05-12', 1840, 3600);
        Cache::put(ExecutiveDashboardObservability::PREFIX.'l1:miss:2026-05-12', 22, 3600);
        Cache::put(ExecutiveDashboardObservability::PREFIX.'l2:hit:2026-05-12', 1320, 3600);
        Cache::put(ExecutiveDashboardObservability::PREFIX.'l2:miss:2026-05-12', 19, 3600);
        Cache::put(ExecutiveDashboardObservability::PREFIX.'invalidations:2026-05-12', 47, 3600);

        $snap = app(ExecutiveDashboardObservability::class)->snapshotFor($day);

        $this->assertSame('2026-05-12', $snap['day']);
        $this->assertSame(1840, $snap['l1']['hits']);
        $this->assertSame(22, $snap['l1']['misses']);
        $this->assertSame(0.988, $snap['l1']['hit_ratio']);
        $this->assertSame(1320, $snap['l2']['hits']);
        $this->assertSame(19, $snap['l2']['misses']);
        $this->assertSame(0.986, $snap['l2']['hit_ratio']);
        $this->assertSame(47, $snap['invalidations']);
    }

    #[Test]
    public function test_snapshot_com_counters_zero_hit_ratio_zero(): void
    {
        $day = CarbonImmutable::parse('2026-01-01', config('app.timezone'));
        $snap = app(ExecutiveDashboardObservability::class)->snapshotFor($day);

        $this->assertSame(0.0, $snap['l1']['hit_ratio']);
        $this->assertSame(0.0, $snap['l2']['hit_ratio']);
        $this->assertSame(0, $snap['invalidations']);
    }

    #[Test]
    public function test_counter_expira_apos_ttl_sete_dias(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-05-01 10:00:00', config('app.timezone')));
        $obs = app(ExecutiveDashboardObservability::class);
        $obs->recordL1Hit();

        $key = ExecutiveDashboardObservability::PREFIX.'l1:hit:2026-05-01';
        $this->assertSame(1, (int) Cache::get($key));

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-05-09 10:00:00', config('app.timezone')));
        $this->assertNull(Cache::get($key));

        CarbonImmutable::setTestNow();
    }

    #[Test]
    public function test_d15_chaves_canonicas_sem_tenant_nem_padroes_proibidos(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-05-15', config('app.timezone')));
        $obs = app(ExecutiveDashboardObservability::class);
        $obs->recordL1Hit();
        $obs->recordL1Miss();
        $obs->recordL2Hit();
        $obs->recordL2Miss();
        $obs->recordInvalidation();

        $bogus = ExecutiveDashboardObservability::PREFIX.'l1:hit:t_42:2026-05-15';
        $this->assertNull(Cache::get($bogus));

        foreach ([
            ExecutiveDashboardObservability::PREFIX.'l1:hit:2026-05-15',
            ExecutiveDashboardObservability::PREFIX.'l1:miss:2026-05-15',
            ExecutiveDashboardObservability::PREFIX.'l2:hit:2026-05-15',
            ExecutiveDashboardObservability::PREFIX.'l2:miss:2026-05-15',
            ExecutiveDashboardObservability::PREFIX.'invalidations:2026-05-15',
        ] as $key) {
            $this->assertNotNull(Cache::get($key), 'chave '.$key);
            $this->assertDoesNotMatchRegularExpression('/t_/', $key);
            $this->assertDoesNotMatchRegularExpression('/user_/', $key);
            $this->assertStringNotContainsString('@', $key);
            $this->assertStringNotContainsString('email', $key);
            $this->assertDoesNotMatchRegularExpression('/\d{5,}/', $key, 'sem sequências de 5+ dígitos na chave');
        }

        CarbonImmutable::setTestNow();
    }
}
