<?php

/*
 * One-off generator for PWA icons. Writes PNGs into public/pwa/.
 *
 * Design: white background with the serif "Art DB" wordmark in near-black,
 * mirroring the brand mark in the top-left of art-db.org (Georgia, regular
 * weight, two lines — "Art" above "DB"). The font path is a macOS system
 * TTF; rerun this script from a Mac after changing the geometry below.
 *
 *   php scripts/generate_pwa_icons.php
 */

const FONT_PATH = '/System/Library/Fonts/Supplemental/Georgia.ttf';

$outDir = __DIR__ . '/../public/pwa';
if (!is_dir($outDir)) {
    mkdir($outDir, 0755, true);
}
if (!is_file(FONT_PATH)) {
    fwrite(STDERR, "Font not found at " . FONT_PATH . " — point FONT_PATH at a serif TTF on this machine.\n");
    exit(1);
}

function makeIcon(int $size, string $path, bool $maskable = false): void
{
    $img = imagecreatetruecolor($size, $size);
    imagesavealpha($img, true);

    $white = imagecolorallocate($img, 0xff, 0xff, 0xff);
    $black = imagecolorallocate($img, 0x1a, 0x1a, 0x1a);

    imagefilledrectangle($img, 0, 0, $size, $size, $white);

    // Maskable platforms crop up to 10% off every edge; keep the wordmark
    // inside an 80% safe zone, slightly tighter for non-maskable so the
    // text still breathes on the home screen.
    $safeFactor = $maskable ? 0.22 : 0.14;
    $safe = (int) ($size * $safeFactor);
    $innerW = $size - 2 * $safe;
    $innerH = $size - 2 * $safe;

    // Two lines ("Art" / "DB") stacked. Pick a font size that fits the
    // longer line ("Art" at the chosen weight is wider than "DB") within
    // the inner box with a modest line gap.
    $lineGap = 0.08; // fraction of font size
    $fontSize = $size; // start big, shrink until it fits

    while ($fontSize > 4) {
        [$lw1, $lh1] = measureText('Art', $fontSize);
        [$lw2, $lh2] = measureText('DB',  $fontSize);
        $totalH = $lh1 + $lh2 + (int) ($fontSize * $lineGap);
        $maxW   = max($lw1, $lw2);
        if ($maxW <= $innerW && $totalH <= $innerH) {
            break;
        }
        $fontSize -= 2;
    }

    [$w1, $h1] = measureText('Art', $fontSize);
    [$w2, $h2] = measureText('DB',  $fontSize);
    $gap = (int) ($fontSize * $lineGap);
    $totalH = $h1 + $h2 + $gap;

    // GD's imagefttext uses the baseline as the y origin.
    $topY = $safe + (int) (($innerH - $totalH) / 2);
    $y1   = $topY + $h1;
    $y2   = $y1 + $gap + $h2;

    $x1 = $safe + (int) (($innerW - $w1) / 2);
    $x2 = $safe + (int) (($innerW - $w2) / 2);

    imagefttext($img, $fontSize, 0, $x1, $y1, $black, FONT_PATH, 'Art');
    imagefttext($img, $fontSize, 0, $x2, $y2, $black, FONT_PATH, 'DB');

    imagepng($img, $path);
    imagedestroy($img);

    echo "wrote {$path}\n";
}

function measureText(string $text, int $fontSize): array
{
    $box = imagettfbbox($fontSize, 0, FONT_PATH, $text);
    $w = abs($box[2] - $box[0]);
    $h = abs($box[7] - $box[1]);
    return [$w, $h];
}

makeIcon(192, $outDir . '/icon-192.png');
makeIcon(512, $outDir . '/icon-512.png');
makeIcon(512, $outDir . '/icon-maskable-512.png', true);
makeIcon(1024, $outDir . '/icon-1024.png');
makeIcon(180, $outDir . '/apple-touch-icon.png');
