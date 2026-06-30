<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Artwork extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid', 'inventory_id', 'artist_id', 'title', 'slug',
        'year_created', 'year_created_end',
        'medium_id', 'genre_id', 'status_id',
        'description', 'materials', 'tags',
        'height_cm', 'width_cm', 'depth_cm', 'weight_kg',
        'frame_height_cm', 'frame_width_cm', 'frame_depth_cm',
        'edition_number', 'edition_total', 'edition_notes',
        'is_signed', 'is_dated', 'is_framed', 'signature_description',
        'price', 'currency', 'price_on_request',
        'purchase_date', 'purchase_price', 'insurance_value',
        'condition_notes', 'damage_notes', 'provenance', 'exhibition_history', 'literature',
        'has_certificate_of_authenticity',
        'certificate_of_authenticity_document', 'invoice_document',
        'restoration_date', 'restoration_returned_at',
        'restoration_notes', 'restoration_price',
        'restorer_name', 'restorer_email', 'restorer_phone',
        'restoration_documents', 'restoration_photos',
        'location_id', 'primary_image', 'gallery_images',
        'is_published', 'is_featured', 'owner_user_id',
    ];

    protected $casts = [
        'gallery_images'                  => 'array',
        'restoration_documents'           => 'array',
        'restoration_photos'              => 'array',
        'restoration_date'                => 'date',
        'restoration_returned_at'         => 'date',
        'restoration_price'               => 'decimal:2',
        'tags'                            => 'array',
        'is_signed'                       => 'boolean',
        'is_dated'                        => 'boolean',
        'is_framed'                       => 'boolean',
        'price_on_request'                => 'boolean',
        'has_certificate_of_authenticity' => 'boolean',
        'is_published'                    => 'boolean',
        'is_featured'                     => 'boolean',
        'price'                           => 'decimal:2',
        'purchase_date'                   => 'date',
        'purchase_price'                  => 'decimal:2',
        'insurance_value'                 => 'decimal:2',
        'height_cm'                       => 'decimal:2',
        'width_cm'                        => 'decimal:2',
        'depth_cm'                        => 'decimal:2',
        'weight_kg'                       => 'decimal:2',
        'frame_height_cm'                 => 'decimal:2',
        'frame_width_cm'                  => 'decimal:2',
        'frame_depth_cm'                  => 'decimal:2',
        'year_created'                    => 'integer',
        'year_created_end'                => 'integer',
        'edition_number'                  => 'integer',
        'edition_total'                   => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function ($m) {
            $m->uuid ??= (string) Str::uuid();
            if (empty($m->slug)) {
                $m->slug = Str::slug(($m->title ?? 'untitled').'-'.Str::random(4));
            }
            if (empty($m->inventory_id)) {
                $m->inventory_id = static::generateInventoryId($m->artist_id);
            }
        });

        // After save, optimize newly-uploaded image fields + generate thumb/medium variants.
        static::saved(function (self $m) {
            if ($m->wasChanged('primary_image') && $m->primary_image) {
                \App\Services\ImageProcessor::process($m->primary_image);
            }
            if ($m->wasChanged('gallery_images')) {
                \App\Services\ImageProcessor::processMany($m->gallery_images);
            }
            // Re-tally owner's storage when any image field changes.
            if ($m->wasChanged('primary_image') || $m->wasChanged('gallery_images')) {
                app(\App\Services\StorageAccountant::class)->syncFromModel($m);
            }
        });

        // On delete, owner's storage drops by this artwork's images.
        static::deleted(function (self $m) {
            app(\App\Services\StorageAccountant::class)->syncFromModel($m);
        });
    }

    /**
     * Generuje Inventory ID formátu PREFIX-LASTNAME-0000.
     */
    public static function generateInventoryId(?int $artistId): string
    {
        $prefix  = config('artdb.inventory_prefix', env('ARTDB_INVENTORY_PREFIX', 'INV'));
        $padding = (int) config('artdb.inventory_padding', env('ARTDB_INVENTORY_PADDING', 4));

        $lastname = 'NONE';
        if ($artistId && $artist = Artist::find($artistId)) {
            $clean = preg_replace('/[^A-Za-z]/', '', \Illuminate\Support\Str::ascii($artist->last_name ?? ''));
            $lastname = strtoupper(substr($clean, 0, 4)) ?: 'NONE';
        }

        // Najvyššie aktuálne číslo pre tohto umelca v rovnakom prefixe
        $maxFound = static::query()
            ->when($artistId, fn ($q) => $q->where('artist_id', $artistId))
            ->where('inventory_id', 'like', "{$prefix}-%")
            ->pluck('inventory_id')
            ->map(function ($id) {
                if (preg_match('/-(\d+)$/', $id, $m)) return (int) $m[1];
                return 0;
            })
            ->max();

        $next = ((int) $maxFound) + 1;
        return sprintf('%s-%s-%s', $prefix, $lastname, str_pad((string) $next, $padding, '0', STR_PAD_LEFT));
    }

    // Relácie
    public function artist()      { return $this->belongsTo(Artist::class); }
    public function medium()      { return $this->belongsTo(Medium::class); }
    public function genre()       { return $this->belongsTo(Genre::class); }
    public function status()      { return $this->belongsTo(ArtworkStatus::class, 'status_id'); }
    public function location()    { return $this->belongsTo(Location::class); }
    public function owner()       { return $this->belongsTo(User::class, 'owner_user_id'); }

    public function collections() { return $this->belongsToMany(Collection::class)->withPivot('position')->withTimestamps(); }
    public function exhibitions() { return $this->belongsToMany(Exhibition::class)->withPivot('position','was_sold','notes')->withTimestamps(); }
    public function privateRooms(){ return $this->belongsToMany(PrivateRoom::class, 'private_room_artwork')->withPivot('display_price','currency','position','private_note')->withTimestamps(); }

    /** Users who hearted this artwork. */
    public function likedByUsers()
    {
        return $this->belongsToMany(User::class, 'artwork_likes')->withTimestamps();
    }

    /** Users who saved this artwork to their personal collection. */
    public function savedByUsers()
    {
        return $this->belongsToMany(User::class, 'artwork_saves')->withPivot('private_note')->withTimestamps();
    }

    public function inquiries()
    {
        return $this->hasMany(Inquiry::class)->latest();
    }

    /** Maintenance / restoration records. */
    public function maintenances()
    {
        return $this->hasMany(Maintenance::class)->orderBy('position')->orderByDesc('restoration_date');
    }

    /**
     * Maintenance status — odvodené z maintenances relation.
     *   none         = žiadne maintenance záznamy
     *   in_progress  = aspoň jeden záznam je neuzavretý (restoration_returned_at NULL)
     *   returned     = všetky záznamy sú vrátené (existuje aspoň 1)
     */
    public function getMaintenanceStatusAttribute(): string
    {
        $records = $this->maintenances;

        if ($records->isEmpty()) return 'none';

        return $records->whereNull('restoration_returned_at')->isNotEmpty()
            ? 'in_progress'
            : 'returned';
    }

    /** Sum of restoration costs across all maintenance records. */
    public function getMaintenanceTotalCostAttribute(): ?float
    {
        $sum = $this->maintenances->sum('restoration_price');
        return $sum > 0 ? (float) $sum : null;
    }
}
