<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class CollectionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'            => $this->id,
            'uuid'          => $this->uuid,
            'slug'          => $this->slug,
            'parent_id'     => $this->parent_id,
            'title'         => $this->title,
            'description'   => $this->description,
            'cover_image'   => $this->cover_image ? Storage::url($this->cover_image) : null,
            'is_public'     => (bool) $this->is_public,
            'position'      => $this->position,
            'artworks_count'=> $this->whenCounted('artworks'),
            'artworks'      => ArtworkResource::collection($this->whenLoaded('artworks')),
            'created_at'    => $this->created_at?->toIso8601String(),
            'updated_at'    => $this->updated_at?->toIso8601String(),
        ];
    }
}
