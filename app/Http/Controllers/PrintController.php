<?php

namespace App\Http\Controllers;

use App\Models\Artwork;
use App\Models\InvoiceSetting;
use App\Models\Sale;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class PrintController extends Controller
{
    /**
     * Pick inline-stream vs attachment-download for a PDF response.
     *
     * Default = stream (Content-Disposition: inline). Browser's PDF
     * viewer opens the file in a new tab with its Print / Save
     * controls — the "normal" desktop web experience.
     *
     * When the request carries ?download=1 the file is forced as an
     * attachment, which on an iOS home-screen PWA triggers the native
     * share sheet (Save to Files / Print / Mail). The admin layout
     * appends that query param only when window.navigator.standalone
     * is true, so desktop admin + public web stay inline while the
     * iOS PWA gets the share sheet. See resources/views/hooks/
     * pwa-download-hint.blade.php.
     */
    protected function deliver(Request $request, $pdf, string $filename): Response
    {
        return $request->boolean('download')
            ? $pdf->download($filename)
            : $pdf->stream($filename);
    }

    public function artworkCard(Artwork $artwork, Request $request): Response
    {
        $artwork->load(['artist', 'medium', 'genre']);

        $pdf = Pdf::loadView('prints.artwork-card-pdf', [
            'artwork'  => $artwork,
            'settings' => InvoiceSetting::current(),
        ])->setPaper($this->cardPaperSize());

        return $this->deliver($request, $pdf, $this->fileName('card', $artwork));
    }

    public function artworkCertificate(Artwork $artwork, Request $request): Response
    {
        $artwork->load(['artist', 'medium']);

        $pdf = Pdf::loadView('prints.artwork-certificate-pdf', [
            'artwork'  => $artwork,
            'settings' => InvoiceSetting::current(),
        ])->setPaper('a4');

        return $this->deliver($request, $pdf, $this->fileName('certificate', $artwork));
    }

    public function artworkLabel(Artwork $artwork, Request $request): Response
    {
        $artwork->load(['artist', 'medium']);

        $settings = InvoiceSetting::current();
        $paper = $this->labelPaperSize($settings->label_size ?? 'standard');

        $pdf = Pdf::loadView('prints.artwork-label-pdf', [
            'artwork'  => $artwork,
            'settings' => $settings,
        ])->setPaper($paper, 'landscape');

        return $this->deliver($request, $pdf, $this->fileName('label', $artwork));
    }

    public function artworkMaintenance(Artwork $artwork, Request $request): Response
    {
        $artwork->load(['artist', 'medium', 'maintenances']);

        $pdf = Pdf::loadView('prints.artwork-maintenance-pdf', [
            'artwork'  => $artwork,
            'settings' => InvoiceSetting::current(),
        ])->setPaper('a4');

        return $this->deliver($request, $pdf, $this->fileName('maintenance', $artwork));
    }

    public function artworkConditionReport(Artwork $artwork, Request $request): Response
    {
        $artwork->load(['artist', 'medium']);

        $pdf = Pdf::loadView('prints.artwork-condition-report-pdf', [
            'artwork'  => $artwork,
            'settings' => InvoiceSetting::current(),
        ])->setPaper('a4');

        return $this->deliver($request, $pdf, $this->fileName('condition-report', $artwork));
    }

    public function saleInvoice(Sale $sale, Request $request): Response
    {
        $sale->load(['buyer', 'lineItems.artwork.artist']);

        $pdf = Pdf::loadView('prints.invoice-pdf', [
            'sale'     => $sale,
            'settings' => InvoiceSetting::current(),
        ])->setPaper('a4');

        $name = 'invoice-'.($sale->invoice_number ?? $sale->id).'-'.optional($sale->sale_date)->format('Ymd').'.pdf';
        return $this->deliver($request, $pdf, $name);
    }

    protected function cardPaperSize(): string
    {
        $size = InvoiceSetting::current()->card_size ?? 'a4';
        return match ($size) {
            'a5'     => 'a5',
            'letter' => 'letter',
            default  => 'a4',
        };
    }

    /**
     * Convert label size enum to Dompdf paper array [w, h] in points (1pt = 1/72 inch).
     */
    protected function labelPaperSize(string $size): string|array
    {
        return match ($size) {
            'small'    => [0, 0, 60  * 2.834, 40  * 2.834], // 60×40 mm
            'large'    => [0, 0, 105 * 2.834, 70  * 2.834], // 105×70 mm
            'a6'       => 'a6',                              // 148×105 mm
            default    => [0, 0, 85  * 2.834, 55  * 2.834], // 85×55 mm — standard
        };
    }

    protected function fileName(string $kind, Artwork $artwork): string
    {
        $slug = Str::slug(($artwork->artist?->last_name ?? 'artwork').'-'.$artwork->title);
        return $kind.'-'.$slug.'.pdf';
    }
}
