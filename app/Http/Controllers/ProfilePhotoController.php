<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\StoredFile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Every account can set its own profile photo. The browser already crops and shrinks
 * it to a square JPEG; the server still validates it and re-encodes when GD is
 * available, which also strips EXIF data such as GPS location.
 */
class ProfilePhotoController extends Controller
{
    public const MAX_KB = 4096;

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'photo' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.self::MAX_KB, 'dimensions:min_width=64,min_height=64'],
        ], [
            'photo.dimensions' => 'The photo is too small. Please choose a picture at least 64×64 pixels.',
        ]);

        $user = $request->user();
        $file = $request->file('photo');
        self::sanitize($file->getRealPath());
        clearstatcache(true, $file->getRealPath());

        $old = $user->avatar;
        $user->forceFill(['avatar' => StoredFile::storeUpload($file)])->save();
        StoredFile::deleteReference($old);

        AuditLog::record('update', 'profile', "{$user->name} updated their profile photo");

        return back()->with('success', 'Profile photo updated.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();
        StoredFile::deleteReference($user->avatar);
        $user->forceFill(['avatar' => null])->save();

        return back()->with('success', 'Profile photo removed.');
    }

    /**
     * Colleagues see each other's photos, so any signed-in user may view one.
     */
    public function show(User $user): Response
    {
        $response = StoredFile::responseFor($user->avatar) ?? abort(404);

        // The URL changes with every new photo, so it can be cached for a long time.
        $response->headers->set('Cache-Control', 'private, max-age=2592000, immutable');

        return $response;
    }

    /**
     * Re-encode to a square-friendly JPEG (max 512 px) when GD is installed. This drops
     * metadata and any non-image payload. Without GD the validated file is kept as is.
     */
    private static function sanitize(string $path): void
    {
        if (! function_exists('imagecreatefromstring')) {
            return;
        }

        $image = @imagecreatefromstring((string) file_get_contents($path));
        if (! $image) {
            return;
        }

        $w = imagesx($image);
        $h = imagesy($image);
        $side = min($w, $h);
        $size = min(512, $side);

        $square = imagecreatetruecolor($size, $size);
        imagefill($square, 0, 0, imagecolorallocate($square, 255, 255, 255));
        imagecopyresampled($square, $image, 0, 0, intdiv($w - $side, 2), intdiv($h - $side, 2), $size, $size, $side, $side);
        imagejpeg($square, $path, 86);
    }
}
