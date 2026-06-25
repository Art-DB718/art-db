<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ArtistResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'           => $this->id,
            'uuid'         => $this->uuid,
            'slug'         => $this->slug,
            'first_name'   => $this->first_name,
            'last_name'    => $this->last_name,
            'display_name' => $this->display_name,
            'birth_year'   => $this->birth_year,
            'death_year'   => $this->death_year,
            'birth_place'  => $this->birth_place,
            'country'      => $this->whenLoaded('country', fn () => $this->country ? [
                'id'   => $this->country->id,
                'name' => $this->country->name,
                'code' => $this->country->code ?? null,
            ] : null),
            'short_bio'    => $this->short_bio,
            'biography'    => $this->biography,
            'statement'    => $this->statement,
            'website'      => $this->website,
            'social_links' => $this->social_links,
            'profile_image'=> $this->profile_image ? Storage::url($this->profile_image) : null,
            'cover_image'  => $this->cover_image ? Storage::url($this->cover_image) : null,
            'is_published' => (bool) $this->is_published,
            'is_featured'  => (bool) $this->is_featured,
            'created_at'   => $this->created_at?->toIso8601String(),
            'updated_at'   => $this->updated_at?->toIso8601String(),
        ];
    }
}
