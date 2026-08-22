<?php
namespace Tests\Unit;
use App\Services\RiskDecision;
use PHPUnit\Framework\TestCase;
class RiskEngineDecisionTest extends TestCase
{
    public function test_rejected_decision_has_reason(): void {
        $d = RiskDecision::reject('Safety gate failed');
        $this->assertFalse($d->approved); $this->assertSame('Safety gate failed',$d->reason); $this->assertNull($d->lotSize);
    }
    public function test_approved_decision_carries_execution_values(): void {
        $d = RiskDecision::approve(0.10,100,50);
        $this->assertTrue($d->approved); $this->assertSame(0.10,$d->lotSize); $this->assertSame(100.0,$d->riskMoney);
    }
}
