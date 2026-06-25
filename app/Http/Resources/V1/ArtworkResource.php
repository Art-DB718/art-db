<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ArtworkResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'               => $this->id,
            'uuid'             => $this->uuid,
            'slug'             => $this->slug,
            'inventory_id'     => $this->inventory_id,
            'title'            => $this->title,
            'year_created'     => $this->year_created,
            'year_created_end' => $this->year_created_end,

            'artist' => $this->whenLoaded('artist', fn () => [
                'id'           => $this->artist->id,
                'slug'         => $this->artist->slug,
                'display_name' => $this->artist->display_name,
                'first_name'   => $this->artist->first_name,
                'last_name'    => $this->artist->last_name,
            ]),

            'medium' => $this->whenLoaded('medium', fn () => $this->medium ? [
                'id'   => $this->medium->id,
                'name' => $this->medium->name,
            ] : null),

            'genre' => $this->whenLoaded('genre', fn () => $this->genre ? [
                'id'   => $this->genre->id,
                'name' => $this->genre->name,
            ] : null),

            'status' => $this->whenLoaded('status', fn () => $this->status ? [
                'id'        => $this->status->id,
                'name'      => $this->status->name,
                'is_public' => (bool) $this->status->is_public,
            ] : null),

            'dimensions' => [
                'height_cm' => $this->height_cm,
                'width_cm'  => $this->width_cm,
                'depth_cm'  => $this->depth_cm,
                'weight_kg' => $this->weight_kg,
            ],

            'edition' => [
                'number' => $this->edition_number,
                'total'  => $this->edition_total,
                'notes'  => $this->edition_notes,
            ],

            'is_signed'  => (bool) $this->is_signed,
            'is_dated'   => (bool) $this->is_dated,
            'is_framed'  => (bool) $this->is_framed,
            'materials'  => $this->materials,
            'description'=> $this->description,
            'provenance' => $this->provenance,

            'price'             => $this->price === null ? null : (float) $this->price,
            'currency'          => $this->currency,
            'price_on_request'  => (bool) $this->price_on_request,

            'primary_image'   => $this->primary_image ? Storage::url($this->primary_image) : null,
            'gallery_images'  => collect($this->gallery_images ?? [])
                ->map(fn ($p) => Storage::url($p))->values(),

            'is_published' => (bool) $this->is_published,
            'is_featured'  => (bool) $this->is_featured,
            'created_at'   => $this->created_at?->toIso8601String(),
            'updated_at'   => $this->updated_at?->toIso8601String(),
        ];
    }
}
