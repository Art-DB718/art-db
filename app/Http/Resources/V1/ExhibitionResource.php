<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ExhibitionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'            => $this->id,
            'uuid'          => $this->uuid,
            'slug'          => $this->slug,
            'title'         => $this->title,
            'type'          => $this->type,
            'venue'         => $this->venue,
            'curator'       => $this->curator,
            'status'        => $this->status,
            'start_date'    => $this->start_date?->toIso8601String(),
            'end_date'      => $this->end_date?->toIso8601String(),
            'opening_at'    => $this->opening_at?->toIso8601String(),
            'description'   => $this->description,
            'press_release' => $this->press_release,
            'poster_image'  => $this->poster_image ? Storage::url($this->poster_image) : null,
            'gallery_images'=> collect($this->gallery_images ?? [])->map(fn ($p) => Storage::url($p))->values(),
            'artworks'      => ArtworkResource::collection($this->whenLoaded('artworks')),
            'is_published'  => (bool) $this->is_published,
            'created_at'    => $this->created_at?->toIso8601String(),
            'updated_at'    => $this->updated_at?->toIso8601String(),
        ];
    }
}
