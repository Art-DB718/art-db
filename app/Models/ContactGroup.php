<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ContactGroup extends Model
{
    use HasFactory;

    protected $fillable = ['uuid', 'parent_id', 'name', 'slug', 'description', 'owner_user_id'];

    protected static function booted(): void
    {
        static::creating(function ($m) {
            $m->uuid ??= (string) Str::uuid();
            $m->slug ??= Str::slug($m->name);
            // Auto-assign owner for non-admin creators — groups are
            // per-tenant and should never leak across accounts.
            if (empty($m->owner_user_id) && ($u = auth()->user()) && ! $u->isAdmin()) {
                $m->owner_user_id = $u->id;
            }
        });
    }

    public function owner() { return $this->belongsTo(\App\Models\User::class, 'owner_user_id'); }

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
