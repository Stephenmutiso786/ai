<?php
namespace App\Services\Payments;
use App\Models\PaymentTransaction;
use App\Models\User;
interface PaymentGateway {
 public function create(User $user, PaymentTransaction $transaction, array $payload): array;
 public function verify(string $reference): array;
}
