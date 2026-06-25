<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Singleton — vždy je iba jeden riadok (id = 1) s nastavením vzhľadu faktúry.
 * Získava sa cez InvoiceSetting::current().
 */
class InvoiceSetting extends Model
{
    protected $table = 'invoice_settings';

    protected $fillable = [
        'logo_path',
        'company_name',
        'business_id', 'tax_id', 'vat_id',
        'address_line1', 'address_line2', 'city', 'postal_code', 'country',
        'email', 'phone', 'website',
        'bank_account', 'bank_name',
        'footer_notes',
        // Printout design (Design printouts page)
        'cert_intro', 'cert_signature_label', 'cert_show_uuid',
        'card_footer_text', 'card_show_provenance', 'card_show_price', 'card_size', 'card_show_gallery',
        'label_show_price', 'label_show_dimensions', 'label_show_inventory', 'label_size', 'label_show_logo',
    ];

    protected $casts = [
        'cert_show_uuid'        => 'boolean',
        'card_show_provenance'  => 'boolean',
        'card_show_price'       => 'boolean',
        'card_show_gallery'     => 'boolean',
        'label_show_price'      => 'boolean',
        'label_show_dimensions' => 'boolean',
        'label_show_inventory'  => 'boolean',
        'label_show_logo'       => 'boolean',
    ];

    public static function current(): self
    {
        return static::firstOrCreate(['id' => 1]);
    }
}
