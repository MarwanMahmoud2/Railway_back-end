<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Handles file uploads to Supabase Storage.
 * Falls back to local storage when Supabase is not configured.
 */
class SupabaseStorageService
{
    private ?string $url;
    private ?string $key;

    public function __construct()
    {
        $this->url = config('services.supabase.url');
        $this->key = config('services.supabase.storage_key');
    }

    /**
     * Check if Supabase Storage is configured and reachable.
     */
    public function isAvailable(): bool
    {
        return !empty($this->url) && !empty($this->key);
    }

    /**
     * Upload a file to a Supabase Storage bucket.
     *
     * @param string $bucket  Bucket name (e.g., 'child-photos', 'footprints')
     * @param UploadedFile $file  The uploaded file
     * @param string|null $path  Optional subdirectory inside the bucket
     * @return string|null  The public URL of the uploaded file, or null on failure
     */
    public function upload(string $bucket, UploadedFile $file, ?string $path = null): ?string
    {
        if (!$this->isAvailable()) {
            return $this->uploadLocal($bucket, $file);
        }

        $fileName = Str::random(20) . '.' . $file->getClientOriginalExtension();
        $objectPath = $path ? "{$path}/{$fileName}" : $fileName;

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->key}",
                'apikey' => $this->key,
                'Content-Type' => $file->getMimeType(),
            ])->withBody(
                file_get_contents($file->getRealPath()),
                $file->getMimeType()
            )->post("{$this->url}/storage/v1/object/{$bucket}/{$objectPath}");

            if ($response->successful()) {
                return $this->getPublicUrl($bucket, $objectPath);
            }

            Log::error('Supabase Storage upload failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return $this->uploadLocal($bucket, $file);
        } catch (\Exception $e) {
            Log::error('Supabase Storage upload exception', [
                'error' => $e->getMessage(),
            ]);

            return $this->uploadLocal($bucket, $file);
        }
    }

    /**
     * Delete a file from Supabase Storage.
     */
    public function delete(string $bucket, string $objectPath): bool
    {
        if (!$this->isAvailable()) {
            return false;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->key}",
                'apikey' => $this->key,
            ])->delete("{$this->url}/storage/v1/object/{$bucket}/{$objectPath}");

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Supabase Storage delete exception', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get the public URL for a file in Supabase Storage.
     * For private buckets, returns a signed URL valid for 1 hour.
     */
    public function getPublicUrl(string $bucket, string $objectPath): string
    {
        if ($this->isPrivateBucket($bucket)) {
            return $this->getSignedUrl($bucket, $objectPath);
        }

        return "{$this->url}/storage/v1/object/public/{$bucket}/{$objectPath}";
    }

    /**
     * Check if a bucket should be treated as private.
     */
    private function isPrivateBucket(string $bucket): bool
    {
        $privateBuckets = ['footprints'];
        return in_array($bucket, $privateBuckets);
    }

    /**
     * Generate a signed URL for private bucket access (valid for 1 hour).
     */
    private function getSignedUrl(string $bucket, string $objectPath): string
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->key}",
                'apikey' => $this->key,
            ])->post("{$this->url}/storage/v1/object/sign/{$bucket}/{$objectPath}", [
                'expiresIn' => 3600, // 1 hour
            ]);

            if ($response->successful() && isset($response['signedURL'])) {
                return "{$this->url}{$response['signedURL']}";
            }
        } catch (\Exception $e) {
            Log::error('Failed to generate signed URL', ['error' => $e->getMessage()]);
        }

        // Fallback to public URL if signing fails
        return "{$this->url}/storage/v1/object/public/{$bucket}/{$objectPath}";
    }

    /**
     * Fallback: store file locally when Supabase is unavailable.
     */
    private function uploadLocal(string $bucket, UploadedFile $file): ?string
    {
        try {
            $path = $file->store($bucket, 'public');
            return asset('storage/' . $path);
        } catch (\Exception $e) {
            Log::error('Local file upload failed', ['error' => $e->getMessage()]);
            return null;
        }
    }
}
