<?php

namespace App\DTOs;

final class AmazonSyncSummaryDTO
{
    public function __construct(
        public readonly int $checked = 0,
        public readonly int $priceUpdated = 0,
        public readonly int $flagged = 0,
        public readonly int $paused = 0,
        public readonly int $baseline = 0,
        public readonly int $unchanged = 0,
        public readonly int $missing = 0,
        public readonly int $failed = 0,
    ) {}

    public static function fromCounts(array $counts): self
    {
        return new self(
            checked:      array_sum($counts),
            priceUpdated: $counts['price_updated'] ?? 0,
            flagged:      $counts['flagged'] ?? 0,
            paused:       $counts['paused'] ?? 0,
            baseline:     $counts['baseline'] ?? 0,
            unchanged:    $counts['unchanged'] ?? 0,
            missing:      $counts['missing'] ?? 0,
            failed:       $counts['failed'] ?? 0,
        );
    }

    public function message(): string
    {
        return "{$this->checked} revisadas: {$this->priceUpdated} precios actualizados, {$this->flagged} para revisión, "
            . "{$this->paused} pausadas, {$this->failed} con error, {$this->missing} sin datos de Amazon";
    }
}
