<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ContactGroup extends Model
{
    use HasFactory;

    protected $fillable = ['uuid', 'parent_id', 'name', 'slug', 'description'];

    protected static function booted(): void
    {
        static::creating(function ($m) {
            $m->uuid ??= (string) Str::uuid();
            $m->slug ??= Str::slug($m->name);
        });
    }

    public function parent()   { return $this->belongsTo(self::class, 'parent_id'); }
    public function children() { return $this->hasMany(self::class, 'parent_id'); }

    /**
     * Legacy 1-N relation (contacts.group_id). Kept because a few code
     * paths still read it; new code should prefer contacts() below.
     */
    public function primaryContacts() { return $this->hasMany(Contact::class, 'group_id'); }

    /** Many-to-many contacts via contact_contact_group pivot. */
    public function contacts()
    {
        return $this->belongsToMany(Contact::class, 'contact_contact_group')->withTimestamps();
    }
}
