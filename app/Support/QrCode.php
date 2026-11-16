<?php

namespace App\Support;

use BaconQrCode\Renderer\Color\Rgb;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\Fill;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

/**
 * Inline SVG QR codes for real, configured URLs only (e.g. app store
 * listings). Rendered server-side — no images, no external services.
 */
class QrCode
{
    public static function svg(string $url, int $size = 160): string
    {
        if (! filter_var($url, FILTER_VALIDATE_URL) || ! str_starts_with($url, 'https://')) {
            throw new \InvalidArgumentException('QR codes are only generated for valid https:// URLs.');
        }

        $style = new RendererStyle($size, 1, null, null, Fill::uniformColor(new Rgb(255, 255, 255), new Rgb(10, 42, 94)));
        $svg = (new Writer(new ImageRenderer($style, new SvgImageBackEnd)))->writeString($url);

        // Drop the XML declaration so the SVG can be inlined in HTML.
        return trim(preg_replace('/^<\?xml[^>]*\?>/', '', $svg));
    }
}
