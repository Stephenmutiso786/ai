<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CurrencyController extends Controller
{
    public function set(Request $request)
    {
        $data = $request->validate([
            'currency' => 'required|string|in:'.implode(',', config('currency.selectable')),
        ]);

        session([
            'currency' => $data['currency'],
            'currency_auto_detected' => false,
        ]);

        return back();
    }
}
