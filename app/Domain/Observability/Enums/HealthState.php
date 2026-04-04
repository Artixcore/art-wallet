<?php

declare(strict_types=1);

namespace App\Domain\Observability\Enums;

enum HealthState: string
{
    case Healthy = 'healthy';
    case Degraded = 'degraded';
    case Warning = 'warning';
    case Critical = 'critical';
    case Unknown = 'unknown';
    case Stale = 'stale';

    public function rank(): int
    {
        return match ($this) {
            self::Healthy => 0,
            self::Degraded => 1,
            self::Warning => 2,
            self::Unknown => 3,
            self::Stale => 4,
            self::Critical => 5,
        };
    }

    public static function max(self ...$states): self
    {
        $best = self::Healthy;
        foreach ($states as $s) {
            if ($s->rank() > $best->rank()) {
                $best = $s;
            }
        }

        return $best;
    }
}
