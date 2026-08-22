<?php
namespace App\Services;

final class RiskDecision
{
    public function __construct(
        public bool $approved,
        public ?string $reason = null,
        public ?float $lotSize = null,
        public ?float $riskMoney = null,
        public ?float $estimatedMargin = null,
    ) {}

    public static function approve(float $lotSize, float $riskMoney = 0, float $estimatedMargin = 0): self
    {
        if (!is_finite($lotSize) || $lotSize <= 0) {
            throw new \InvalidArgumentException('Approved risk decisions require a positive finite lot size.');
        }
        return new self(true, null, $lotSize, $riskMoney, $estimatedMargin);
    }

    public static function reject(string $reason): self
    {
        return new self(false, $reason);
    }
}
