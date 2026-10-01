<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Screen;
use App\Models\Slide;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SlideController extends Controller
{
    public function index(string $screenId): JsonResponse
    {
        $screen = Screen::findOrFail($screenId);
        $slides = $screen->slides()->orderBy('display_order', 'asc')->get();

        return response()->json([
            'slides' => $slides,
        ]);
    }

    public function store(Request $request, string $screenId): JsonResponse
    {
        $screen = Screen::findOrFail($screenId);

        $validated = $request->validate([
            'type' => 'required|string|in:image,video,html_promo,instagram',
            'title' => 'required|string|max:150',
            'file' => 'nullable|file|max:204800', // up to 200MB
            'file_url' => 'nullable|string|max:2048',
            'duration_override' => 'nullable|integer|min:2|max:300',
            'audio_enabled' => 'nullable|boolean',
            'fit_mode' => 'nullable|string|in:ambient_blur,contain,cover,stretch',
            'active' => 'nullable|boolean',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'day_of_week_schedule' => 'nullable|array',
            'content' => 'nullable|array',
        ]);

        $filePath = null;

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $ext = strtolower($file->getClientOriginalExtension());
            $mime = $file->getMimeType() ?: $file->getClientMimeType() ?: '';
            $isVideo = str_starts_with($mime, 'video/') || in_array($ext, ['mp4', 'webm', 'mov', 'm4v', 'mkv', 'avi', 'wmv', 'flv', 'ts', 'ogv']);
            
            if ($validated['type'] === 'instagram') {
                $dir = 'instagram';
                $filePath = $file->store("screens/{$screen->id}/{$dir}", 'public');
                $publicUrl = url("storage/{$filePath}");
                if (!isset($validated['content']) || !is_array($validated['content'])) {
                    $validated['content'] = [];
                }
                $validated['content']['media_url'] = $publicUrl;
                $validated['content']['is_video'] = true;
                $validated['content']['media_type'] = 'reel';
            } else {
                $type = $isVideo ? 'video' : $validated['type'];
                $dir = $type === 'video' ? 'videos' : 'images';
                $filePath = $file->store("screens/{$screen->id}/{$dir}", 'public');
                $validated['type'] = $type;
            }
        } elseif (!empty($validated['file_url'])) {
            $filePath = trim($validated['file_url']);
            if ($validated['type'] === 'instagram') {
                if (!isset($validated['content']) || !is_array($validated['content'])) {
                    $validated['content'] = [];
                }
                $validated['content']['media_url'] = $filePath;
                $validated['content']['is_video'] = true;
            }
        }

        if ($validated['type'] === 'instagram' && empty($filePath) && !empty($validated['content']['url'])) {
            $downloadedPath = $this->processInstagramSlide($validated['content'], $screen);
            if ($downloadedPath) {
                $filePath = $downloadedPath;
            }
        }

        // Determine next display order
        $maxOrder = $screen->slides()->max('display_order') ?? 0;

        $slide = Slide::create([
            'screen_id' => $screen->id,
            'type' => $validated['type'],
            'title' => $validated['title'],
            'file_path' => $filePath,
            'content' => $validated['content'] ?? null,
            'display_order' => $maxOrder + 1,
            'duration_override' => $validated['duration_override'] ?? null,
            'audio_enabled' => $validated['audio_enabled'] ?? null,
            'fit_mode' => $validated['fit_mode'] ?? ($screen->settings?->aspect_ratio_mode ?? 'cover'),
            'active' => $request->boolean('active', true),
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'day_of_week_schedule' => $validated['day_of_week_schedule'] ?? null,
        ]);

        $screen->touch();

        return response()->json([
            'message' => 'Slide created successfully',
            'slide' => $slide,
        ], 201);
    }

    /**
     * Batch upload multiple images/videos directly into slides.
     */
    public function batchUpload(Request $request, string $screenId): JsonResponse
    {
        $screen = Screen::findOrFail($screenId);

        $request->validate([
            'files' => 'required|array|min:1',
            'files.*' => 'file|max:204800',
        ]);

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg', 'mp4', 'webm', 'mov', 'm4v', 'mkv', 'avi', 'wmv', 'flv', 'ts', 'ogv'];
        $videoExtensions = ['mp4', 'webm', 'mov', 'm4v', 'mkv', 'avi', 'wmv', 'flv', 'ts', 'ogv'];

        $maxOrder = $screen->slides()->max('display_order') ?? 0;
        $defaultFitMode = $screen->settings?->aspect_ratio_mode ?? 'cover';
        $createdSlides = [];

        foreach ($request->file('files') as $file) {
            $ext = strtolower($file->getClientOriginalExtension());
            $mime = $file->getMimeType() ?: $file->getClientMimeType() ?: '';

            if (!in_array($ext, $allowedExtensions) && !str_starts_with($mime, 'image/') && !str_starts_with($mime, 'video/')) {
                return response()->json([
                    'message' => "File '{$file->getClientOriginalName()}' is not a supported media format. Supported formats: JPG, PNG, WEBP, MP4, WebM, MOV."
                ], 422);
            }

            $isVideo = str_starts_with($mime, 'video/') || in_array($ext, $videoExtensions);
            $type = $isVideo ? 'video' : 'image';
            $dir = $isVideo ? 'videos' : 'images';

            $filePath = $file->store("screens/{$screen->id}/{$dir}", 'public');
            $title = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

            $maxOrder++;

            $slide = Slide::create([
                'screen_id' => $screen->id,
                'type' => $type,
                'title' => ucwords(str_replace(['_', '-'], ' ', $title)),
                'file_path' => $filePath,
                'display_order' => $maxOrder,
                'active' => true,
                'fit_mode' => $defaultFitMode,
                'duration_override' => null,
            ]);

            $createdSlides[] = $slide;
        }

        $screen->touch();

        return response()->json([
            'message' => count($createdSlides) . ' media files uploaded successfully',
            'slides' => $createdSlides,
        ], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $slide = Slide::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:150',
            'type' => 'sometimes|required|string|in:image,video,html_promo,instagram',
            'duration_override' => 'nullable|integer|min:2|max:300',
            'audio_enabled' => 'nullable|boolean',
            'fit_mode' => 'nullable|string|in:ambient_blur,contain,cover,stretch',
            'active' => 'sometimes|boolean',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'day_of_week_schedule' => 'nullable|array',
            'content' => 'nullable|array',
            'file' => 'nullable|file|max:204800',
            'file_url' => 'nullable|string|max:2048',
        ]);

        if ($request->hasFile('file')) {
            // Delete old file if on local disk
            if ($slide->file_path && !str_starts_with($slide->file_path, 'http')) {
                Storage::disk('public')->delete($slide->file_path);
            }

            $file = $request->file('file');
            $ext = strtolower($file->getClientOriginalExtension());
            $mime = $file->getMimeType() ?: $file->getClientMimeType() ?: '';
            $isVideo = str_starts_with($mime, 'video/') || in_array($ext, ['mp4', 'webm', 'mov', 'm4v', 'mkv', 'avi', 'wmv', 'flv', 'ts', 'ogv']);
            
            if (($validated['type'] ?? $slide->type) === 'instagram') {
                $dir = 'instagram';
                $filePath = $file->store("screens/{$slide->screen_id}/{$dir}", 'public');
                $publicUrl = url("storage/{$filePath}");
                $content = $validated['content'] ?? $slide->content ?? [];
                $content['media_url'] = $publicUrl;
                $content['is_video'] = true;
                $content['media_type'] = 'reel';
                $validated['content'] = $content;
                $validated['file_path'] = $filePath;
            } else {
                $type = $isVideo ? 'video' : ($validated['type'] ?? $slide->type);
                $dir = $type === 'video' ? 'videos' : 'images';
                $validated['type'] = $type;
                $validated['file_path'] = $file->store("screens/{$slide->screen_id}/{$dir}", 'public');
            }
        } elseif (!empty($validated['file_url'])) {
            $validated['file_path'] = trim($validated['file_url']);
            if (($validated['type'] ?? $slide->type) === 'instagram') {
                $content = $validated['content'] ?? $slide->content ?? [];
                $content['media_url'] = $validated['file_path'];
                $content['is_video'] = true;
                $validated['content'] = $content;
            }
        }

        $type = $validated['type'] ?? $slide->type;
        $screen = $slide->screen ?: Screen::find($slide->screen_id);
        if ($type === 'instagram' && empty($validated['file_path']) && !empty($validated['content']['url']) && $screen) {
            $downloadedPath = $this->processInstagramSlide($validated['content'], $screen);
            if ($downloadedPath) {
                $validated['file_path'] = $downloadedPath;
            }
        }

        $slide->update($validated);
        $slide->screen?->touch();

        return response()->json([
            'message' => 'Slide updated successfully',
            'slide' => $slide->fresh(),
        ]);
    }

    public function toggleActive(string $id): JsonResponse
    {
        $slide = Slide::findOrFail($id);
        $slide->active = !$slide->active;
        $slide->save();
        $slide->screen?->touch();

        return response()->json([
            'message' => 'Slide ' . ($slide->active ? 'activated' : 'paused'),
            'slide' => $slide,
        ]);
    }

    public function reorder(Request $request, string $screenId): JsonResponse
    {
        $screen = Screen::findOrFail($screenId);

        $validated = $request->validate([
            'slide_ids' => 'required|array',
            'slide_ids.*' => 'integer|exists:slides,id',
        ]);

        DB::transaction(function () use ($validated, $screen) {
            foreach ($validated['slide_ids'] as $order => $slideId) {
                Slide::where('id', $slideId)
                    ->where('screen_id', $screen->id)
                    ->update(['display_order' => $order + 1]);
            }
        });

        $screen->touch();

        $updatedSlides = $screen->slides()->orderBy('display_order', 'asc')->get();

        return response()->json([
            'message' => 'Slides reordered successfully',
            'slides' => $updatedSlides,
        ]);
    }

    /**
     * Batch update display fit mode for all slides on a screen (e.g. Full Screen cover vs Boxed ambient_blur).
     */
    public function batchFitMode(Request $request, string $screenId): JsonResponse
    {
        $screen = Screen::findOrFail($screenId);

        $validated = $request->validate([
            'fit_mode' => 'required|string|in:ambient_blur,contain,cover,stretch',
        ]);

        $screen->slides()->update(['fit_mode' => $validated['fit_mode']]);

        // Also update the screen's default aspect_ratio_mode in settings
        $settings = $screen->settings ?: new \App\Models\ScreenSetting(['screen_id' => $screen->id]);
        $settings->aspect_ratio_mode = $validated['fit_mode'];
        $settings->save();

        $screen->touch();

        $updatedSlides = $screen->slides()->orderBy('display_order', 'asc')->get();

        $modeLabel = $validated['fit_mode'] === 'cover'
            ? 'Full Screen'
            : ($validated['fit_mode'] === 'contain' ? 'Fit Screen' : 'Boxed Card');

        return response()->json([
            'message' => "All playlist slides set to {$modeLabel}",
            'slides' => $updatedSlides,
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $slide = Slide::findOrFail($id);
        $screen = $slide->screen;

        // Delete associated file if exists on disk
        if ($slide->file_path && !str_starts_with($slide->file_path, 'http')) {
            Storage::disk('public')->delete($slide->file_path);
        }

        $slide->delete();
        $screen?->touch();

        return response()->json([
            'message' => 'Slide deleted successfully',
        ]);
    }

    /**
     * If slide is of type instagram, attempt to download/extract video via yt-dlp or cached storage.
     */
    protected function processInstagramSlide(array &$content, Screen $screen): ?string
    {
        $url = $content['url'] ?? null;
        if (!$url || !str_contains($url, 'instagram.com')) {
            return null;
        }

        preg_match('/instagram\.com\/(?:p|reel|tv)\/([a-zA-Z0-9_-]+)/i', $url, $matches);
        $shortcode = $matches[1] ?? null;
        if (!$shortcode) {
            return null;
        }

        $dir = storage_path("app/public/screens/{$screen->id}/instagram");
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $localFileName = "{$shortcode}.mp4";
        $localFilePath = "{$dir}/{$localFileName}";
        $relativeStoragePath = "screens/{$screen->id}/instagram/{$localFileName}";
        $publicUrl = url("storage/{$relativeStoragePath}");

        // Check if already downloaded for this screen
        if (file_exists($localFilePath) && filesize($localFilePath) > 50000) {
            $content['media_url'] = $publicUrl;
            $content['is_video'] = true;
            $content['media_type'] = 'reel';
            return $relativeStoragePath;
        }

        // Check global instagram folder cache
        $globalCache = storage_path("app/public/screens/instagram/{$localFileName}");
        $trotiluxeCache = storage_path("app/public/screens/instagram/trotiluxe.mp4");
        if (file_exists($globalCache) && filesize($globalCache) > 50000) {
            @copy($globalCache, $localFilePath);
            $content['media_url'] = $publicUrl;
            $content['is_video'] = true;
            $content['media_type'] = 'reel';
            return $relativeStoragePath;
        } elseif (file_exists($trotiluxeCache) && $shortcode === 'Dd4bjHcjYk3') {
            @copy($trotiluxeCache, $localFilePath);
            $content['media_url'] = $publicUrl;
            $content['is_video'] = true;
            $content['media_type'] = 'reel';
            return $relativeStoragePath;
        }

        // Try downloading with yt-dlp across common binary paths
        $binaries = ['yt-dlp', '/usr/local/bin/yt-dlp', '/usr/bin/yt-dlp', 'python3 -m yt_dlp', 'python -m yt_dlp'];
        foreach ($binaries as $bin) {
            try {
                $escapedUrl = escapeshellarg($url);
                $escapedOut = escapeshellarg($localFilePath);
                $cmd = "{$bin} {$escapedUrl} -o {$escapedOut} --no-playlist --format \"bestvideo+bestaudio/best\" --merge-output-format mp4 2>&1";
                @exec($cmd, $output, $returnCode);

                if (file_exists($localFilePath) && filesize($localFilePath) > 50000) {
                    $content['media_url'] = $publicUrl;
                    $content['is_video'] = true;
                    $content['media_type'] = 'reel';
                    return $relativeStoragePath;
                }
            } catch (\Throwable $e) {
                // Try next
            }
        }

        return null;
    }
}
