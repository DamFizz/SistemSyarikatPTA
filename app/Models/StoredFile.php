<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * An uploaded attachment kept in the database (base64) so it survives redeploys.
 * Models reference it from their `attachment` column as "db:{id}"; older rows still
 * hold a path on the public disk and are served from there while it exists.
 */
#[Fillable(['original_name', 'mime', 'size', 'data', 'uploaded_by'])]
#[Hidden(['data'])]
class StoredFile extends Model
{
    public const PREFIX = 'db:';

    /**
     * Saves the upload and returns the reference to put in an `attachment` column.
     */
    public static function storeUpload(?UploadedFile $file): ?string
    {
        if (! $file) {
            return null;
        }

        $stored = static::create([
            'original_name' => mb_substr($file->getClientOriginalName() ?: 'attachment', 0, 255),
            'mime' => $file->getMimeType() ?: 'application/octet-stream',
            'size' => $file->getSize(),
            'data' => base64_encode((string) file_get_contents($file->getRealPath())),
            'uploaded_by' => auth()->id(),
        ]);

        return self::PREFIX.$stored->id;
    }

    public static function fromReference(?string $reference): ?self
    {
        return $reference && str_starts_with($reference, self::PREFIX)
            ? static::find((int) substr($reference, strlen(self::PREFIX)))
            : null;
    }

    public static function deleteReference(?string $reference): void
    {
        if (! $reference) {
            return;
        }

        if ($file = static::fromReference($reference)) {
            $file->delete();
        } elseif (! str_starts_with($reference, self::PREFIX)) {
            Storage::disk('public')->delete($reference);
        }
    }

    /**
     * Streams a referenced attachment, wherever it is stored. Null when it no longer exists.
     */
    public static function responseFor(?string $reference): ?Response
    {
        if ($file = static::fromReference($reference)) {
            // Only passive formats open in the browser; anything else (HTML, SVG…) is downloaded.
            $inline = in_array($file->mime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'], true);

            $headers = [
                'Content-Type' => $inline ? $file->mime : 'application/octet-stream',
                'Content-Disposition' => ($inline ? 'inline' : 'attachment').'; filename="'.addcslashes($file->original_name, '"\\').'"',
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'private, max-age=3600',
            ];

            if ($file->mime !== 'application/pdf') {
                $headers['Content-Security-Policy'] = 'sandbox'; // (a sandboxed PDF would not render in Chrome)
            }

            return response(base64_decode($file->data), 200, $headers);
        }

        if ($reference && ! str_starts_with($reference, self::PREFIX) && Storage::disk('public')->exists($reference)) {
            return Storage::disk('public')->response($reference);
        }

        return null;
    }
}
