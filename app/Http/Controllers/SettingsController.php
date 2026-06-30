<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * @description Handles user profile and preference settings updates.
 * @author ITPTC / Datamatics
 * @since 2.1.0
 */
class SettingsController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Settings/Index', [
            'preferences' => (object) ($request->user()->preferences ?? []),
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'display_name' => ['required', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:100'],
            'organization' => ['nullable', 'string', 'max:255'],
            'telephone' => ['nullable', 'string', 'max:50'],
        ]);

        $request->user()->update($validated);

        return back()->with('status', 'profile-updated');
    }

    public function updatePreferences(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'theme' => ['nullable', 'string', 'in:light,dark,auto'],
            'timezone' => ['nullable', 'string', 'max:100'],
            'rows_per_page' => ['nullable', 'integer', 'in:10,25,50,100'],
            'default_country' => ['nullable', 'string', 'max:100'],
            'notifications' => ['nullable', 'array'],
            'notifications.measurement_reviewed' => ['boolean'],
            'notifications.threshold_exceeded' => ['boolean'],
            'notifications.incident_reported' => ['boolean'],
            'notifications.incident_status_changed' => ['boolean'],
            'notifications.pin_used' => ['boolean'],
        ]);

        $user = $request->user();
        $current = $user->preferences ?? [];

        // Deep-merge notifications so a partial update doesn't wipe other keys
        if (isset($validated['notifications'])) {
            $validated['notifications'] = array_merge(
                $current['notifications'] ?? [],
                $validated['notifications']
            );
        }

        $user->preferences = array_merge($current, $validated);
        $user->save();

        return back()->with('status', 'preferences-updated');
    }
}
