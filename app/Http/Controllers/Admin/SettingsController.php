<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SettingsController extends Controller
{
    public function index()
    {
        $groups = config('integrations');

        $configured = [];
        foreach ($groups as $group) {
            foreach (array_keys($group) as $key) {
                $configured[$key] = Setting::isConfigured($key);
            }
        }

        return view('admin.settings', compact('groups', 'configured'));
    }

    public function update(Request $request)
    {
        $allKeys = collect(config('integrations'))->flatMap(fn ($group) => array_keys($group))->all();

        foreach ($allKeys as $key) {
            if (! $request->has($key)) {
                continue;
            }

            $value = trim((string) $request->input($key));

            // Blank input = leave the previously saved secret untouched
            // (inputs are rendered masked, so an empty submit shouldn't
            // wipe out a real key). Use the explicit "clear" checkbox to
            // actually remove a key.
            if ($value === '' && ! $request->boolean("clear_{$key}")) {
                continue;
            }

            Setting::putValue($key, $value === '' ? null : $value, Auth::id());
        }

        return back()->with('status', 'Settings saved.');
    }
}
