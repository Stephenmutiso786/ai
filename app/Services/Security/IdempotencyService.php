<?php
namespace App\Services\Security;
use Illuminate\Support\Facades\DB;

class IdempotencyService
{
    public function acquire(string $scope, string $key, string $requestHash): bool
    {
        return DB::table('idempotency_keys')->insertOrIgnore([
            'scope'=>$scope,'idempotency_key'=>$key,'request_hash'=>$requestHash,
            'status'=>'processing','created_at'=>now(),'updated_at'=>now(),
        ]) === 1;
    }
    public function complete(string $scope, string $key, array $response): void
    {
        DB::table('idempotency_keys')->where(['scope'=>$scope,'idempotency_key'=>$key])
            ->update(['status'=>'completed','response_json'=>json_encode($response),'updated_at'=>now()]);
    }
}
