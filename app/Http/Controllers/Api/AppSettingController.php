<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\Screen;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AppSettingController extends Controller
{
    /**
     * Get app configuration, current user access information, and system telemetry.
     */
    public function show(Request $request): JsonResponse
    {
        $settings = AppSetting::instance();
        $user = $request->user();

        return response()->json([
            'settings' => $settings,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'created_at' => $user->created_at,
            ],
            'system_info' => [
                'app_name' => $settings->app_name,
                'business_name' => $settings->business_name,
                'display_domain' => $settings->display_domain,
                'sample_short_url' => "{$settings->display_domain}/v/1",
                'local_display_url' => url('/v/1'),
                'local_dashboard_url' => url('/'),
                'api_endpoint' => url('/api/v1'),
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'server_time' => now()->toIso8601String(),
                'total_screens' => Screen::count(),
            ],
        ]);
    }

    /**
     * Update application informations & defaults.
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $nullableFields = ['contact_email', 'contact_phone', 'instagram_handle', 'business_name', 'master_pin', 'opening_time', 'closing_time'];
        foreach ($nullableFields as $field) {
            if ($request->has($field) && trim((string)$request->input($field)) === '') {
                $request->merge([$field => null]);
            }
        }

        $validated = $request->validate([
            'app_name' => 'required|string|max:100',
            'business_name' => 'nullable|string|max:100',
            'display_domain' => 'required|string|max:100',
            'default_slide_duration' => 'required|integer|min:3|max:300',
            'default_transition' => 'required|string|in:fade,slide,zoom,none',
            'default_orientation' => 'required|string|in:landscape,portrait',
            'operating_hours_enabled' => 'boolean',
            'opening_time' => 'nullable|string|max:10',
            'closing_time' => 'nullable|string|max:10',
            'instagram_handle' => 'nullable|string|max:50',
            'contact_email' => 'nullable|email|max:100',
            'contact_phone' => 'nullable|string|max:50',
            'master_pin' => 'nullable|string|max:10',
        ]);

        // Clean domain format (remove https:// or http:// if user pasted full URL)
        $validated['display_domain'] = preg_replace('#^https?://#', '', rtrim($validated['display_domain'], '/'));

        $settings = AppSetting::instance();
        $settings->update($validated);

        // Touch screens so active displays detect the global update
        Screen::query()->update(['updated_at' => now()]);

        return response()->json([
            'message' => 'Application information and defaults updated successfully.',
            'settings' => $settings->fresh(),
        ]);
    }

    /**
     * Update account access credentials (name & email).
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|string|email|max:100|unique:users,email,' . $user->id,
        ]);

        $user->update($validated);

        return response()->json([
            'message' => 'Profile and login email updated successfully.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'created_at' => $user->created_at,
            ],
        ]);
    }

    /**
     * Update account password.
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        if (!Hash::check($validated['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The provided current password does not match your account password.'],
            ]);
        }

        $user->update([
            'password' => Hash::make($validated['new_password']),
        ]);

        return response()->json([
            'message' => 'Password changed successfully. Please remember your new password.',
        ]);
    }

    /**
     * Upload brand master logo.
     */
    public function uploadLogo(Request $request): JsonResponse
    {
        $request->validate([
            'logo' => 'required|image|mimes:jpeg,png,jpg,webp,svg|max:4096',
        ]);

        $settings = AppSetting::instance();

        // Delete old logo if local
        if ($settings->logo_path && !str_starts_with($settings->logo_path, 'http')) {
            Storage::disk('public')->delete($settings->logo_path);
        }

        $path = $request->file('logo')->store('brand', 'public');
        $settings->update(['logo_path' => $path]);

        return response()->json([
            'message' => 'Master brand logo uploaded successfully.',
            'logo_url' => $settings->fresh()->logo_url,
            'settings' => $settings->fresh(),
        ]);
    }

    /**
     * Remove brand master logo.
     */
    public function removeLogo(): JsonResponse
    {
        $settings = AppSetting::instance();

        if ($settings->logo_path && !str_starts_with($settings->logo_path, 'http')) {
            Storage::disk('public')->delete($settings->logo_path);
        }

        $settings->update(['logo_path' => null]);

        return response()->json([
            'message' => 'Master brand logo removed.',
            'settings' => $settings->fresh(),
        ]);
    }
}
