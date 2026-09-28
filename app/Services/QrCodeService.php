<?php

namespace App\Services;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class QrCodeService
{
    /**
     * Render the given data as an inline SVG QR code (no GD/Imagick extension required).
     */
    public static function svg(string $data, int $size = 300): string
    {
        $renderer = new ImageRenderer(new RendererStyle($size), new SvgImageBackEnd());

        return (new Writer($renderer))->writeString($data);
    }
}
