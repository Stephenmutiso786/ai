<?php

return [
    // ... keep Laravel's default services.php entries above this (mailgun, postmark, ses, slack) ...

    'broker_credential_cipher_key' => env('BROKER_CREDENTIAL_CIPHER_KEY'),

    // "paper" (default) or "disabled". "live" is intentionally not wired
    // up anywhere in this codebase — see App\Services\Execution\ExecutionEngine.
    'broker_execution_mode' => env('BROKER_EXECUTION_MODE', 'paper'),
];
