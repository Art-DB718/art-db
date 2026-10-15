<?php

/*
 * One-off generator for PWA icons. Writes PNGs into public/pwa/.
 *
 * Design: slate-800 (#1e293b) background with a concentric "canvas" frame
 * in slate-50 (#f8fafc) and a thin horizon line — a minimalist mark meant
 * as a placeholder until the gallery brand gets a real logo. Rerun this
 * script after swapping the colours or geometry below; nothing else in
 * the app points at the pixels.
 *
 *   php scripts/generate_pwa_icons.php
 */

$outDir = __DIR__ . '/../public/pwa';
if (!is_dir($outDir)) {
    mkdir($outDir, 0755, true);
}

function makeIcon(int $size, string $path, bool $maskable = false): void
{
    $img = imagecreatetruecolor($size, $size);
    imagesavealpha($img, true);

    $slate800 = imagecolorallocate($img, 0x1e, 0x29, 0x3b);
    $slate50  = imagecolorallocate($img, 0xf8, 0xfa, 0xfc);
    $slate300 = imagecolorallocate($img, 0xcb, 0xd5, 0xe1);

    imagefilledrectangle($img, 0, 0, $size, $size, $slate800);

    // Maskable icons need a safe zone — platforms crop up to 10% off each edge
    // to fit circular / squircle masks, so geometry sits in the inner 80%.
    $safe = $maskable ? intval($size * 0.12) : intval($size * 0.08);

    // Outer frame — bright rectangle
    $frameMargin = $safe + intval($size * 0.08);
    imagefilledrectangle(
        $img,
        $frameMargin,
        $frameMargin,
        $size - $frameMargin,
        $size - $frameMargin,
        $slate50
    );

    // Inner "canvas" — back to slate so the frame reads as a frame
    $inset = intval($size * 0.04);
    imagefilledrectangle(
        $img,
        $frameMargin + $inset,
        $frameMargin + $inset,
        $size - $frameMargin - $inset,
        $size - $frameMargin - $inset,
        $slate800
    );

    // Horizon line — single stroke across the inner canvas
    $canvasTop    = $frameMargin + $inset;
    $canvasBottom = $size - $frameMargin - $inset;
    $horizonY     = intval($canvasTop + ($canvasBottom - $canvasTop) * 0.62);
    $strokeThick  = max(2, intval($size * 0.015));
    imagesetthickness($img, $strokeThick);
    imageline(
        $img,
        $frameMargin + $inset + intval($size * 0.03),
        $horizonY,
        $size - $frameMargin - $inset - intval($size * 0.03),
        $horizonY,
        $slate300
    );

    imagepng($img, $path);
    imagedestroy($img);

    echo "wrote {$path}\n";
}

makeIcon(192, $outDir . '/icon-192.png');
makeIcon(512, $outDir . '/icon-512.png');
makeIcon(512, $outDir . '/icon-maskable-512.png', true);
makeIcon(1024, $outDir . '/icon-1024.png');
makeIcon(180, $outDir . '/apple-touch-icon.png');
