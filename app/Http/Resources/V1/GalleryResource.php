<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class GalleryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'            => $this->id,
            'uuid'          => $this->uuid,
            'slug'          => $this->slug,
            'name'          => $this->name,
            'description'   => $this->description,
            'logo'          => $this->logo ? \Illuminate\Support\Facades\Storage::url($this->logo) : null,
            'cover_image'   => $this->cover_image ? \Illuminate\Support\Facades\Storage::url($this->cover_image) : null,
            'city'          => $this->city,
            'country'       => $this->whenLoaded('country', fn () => ['id' => $this->country?->id, 'name' => $this->country?->name]),
            'website'       => $this->website,
            'email'         => $this->email,
            'phone'         => $this->phone,
            'artists_count' => $this->when(isset($this->artists_count), $this->artists_count),
            'is_published'  => (bool) $this->is_published,
            'url'           => route('galleries.show', $this->slug),
            'created_at'    => $this->created_at?->toIso8601String(),
            'updated_at'    => $this->updated_at?->toIso8601String(),
        ];
    }
}
