<?php

namespace App\Exports;

use App\Models\Artwork;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ArtworksExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    use Exportable;

    public function __construct(
        protected ?int $artistId = null,
        protected ?bool $publishedOnly = null,
    ) {}

    public function query()
    {
        $q = Artwork::query()->with(['artist', 'medium', 'genre', 'status', 'location']);

        if ($this->artistId) {
            $q->where('artist_id', $this->artistId);
        }
        if ($this->publishedOnly === true) {
            $q->where('is_published', true);
        }

        return $q->orderBy('artist_id')->orderBy('year_created');
    }

    public function headings(): array
    {
        return [
            'Inventory ID', 'Artist', 'Title', 'Year', 'Medium', 'Genre',
            'Height (cm)', 'Width (cm)', 'Depth (cm)',
            'Edition', 'Materials',
            'Price', 'Currency', 'On request',
            'Status', 'Location',
            'Published', 'Featured', 'Created',
        ];
    }

    public function map($a): array
    {
        $edition = $a->edition_number && $a->edition_total
            ? $a->edition_number.'/'.$a->edition_total
            : ($a->edition_notes ?: '');

        return [
            $a->inventory_id,
            $a->artist?->display_name,
            $a->title,
            $a->year_created,
            $a->medium?->name,
            $a->genre?->name,
            $a->height_cm,
            $a->width_cm,
            $a->depth_cm,
            $edition,
            $a->materials,
            $a->price,
            $a->currency,
            $a->price_on_request ? 'yes' : '',
            $a->status?->name,
            $a->location?->name,
            $a->is_published ? 'yes' : 'no',
            $a->is_featured ? 'yes' : '',
            $a->created_at?->format('Y-m-d'),
        ];
    }
}
