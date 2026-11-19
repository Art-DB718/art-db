<?php

/**
 * Shared vocabulary for the 5 printable PDFs: Artwork card, Certificate
 * of Authenticity, Label, Maintenance report and Invoice. User-entered
 * content (artwork title, description, client name, item descriptions…)
 * stays as typed — only the static chrome around it is translated here.
 *
 * All 11 languages the Print menu offers have a file of their own at
 * lang/{locale}/prints.php; keep the keys in sync.
 */

return [
    'common' => [
        'artist'         => 'Artist',
        'title'          => 'Title',
        'medium'         => 'Medium',
        'materials'      => 'Materials',
        'dimensions'     => 'Dimensions',
        'year'           => 'Year',
        'edition'        => 'Edition',
        'email'          => 'Email',
        'phone'          => 'Phone',
        'notes'          => 'Notes',
        'documents'      => 'Documents',
        'photos'         => 'Photos',
        'price_on_req'   => 'Price on request',
    ],

    'card' => [
        'about'         => 'About',
        'signed_prefix' => 'Signed — :desc',
    ],

    'certificate' => [
        'heading'       => 'Certificate of Authenticity',
        'default_intro' => ':company confirms the authenticity of the artwork described below.',
    ],

    'maintenance' => [
        'heading'                => 'Maintenance report',
        'overall_status'         => 'Overall status',
        'total_cost'             => 'Total cost',
        'no_records'             => 'No maintenance records yet for this artwork.',
        'status_in_restoration'  => 'In restoration — :count open record(s)',
        'status_all_returned'    => 'All records returned (:count)',
        'status_none'            => 'No maintenance records',
        'returned'               => 'Returned',
        'in_restoration'         => 'In restoration',
        'record_no'              => 'Record #:n',
        'sent'                   => 'Sent',
        'still_in_restoration'   => '— still in restoration',
        'cost'                   => 'Cost',
        'restorer'               => 'Restorer',
        'name_studio'            => 'Name / studio',
    ],

    'invoice' => [
        'heading'         => 'INVOICE',
        'issued'          => 'Issued',
        'due'             => 'Due',
        'from'            => 'From',
        'bill_to'         => 'Bill to',
        'no_buyer'        => 'No buyer recorded',
        'description'     => 'Description',
        'qty'             => 'Qty',
        'unit_price'      => 'Unit price',
        'line_total'      => 'Line total',
        'subtotal'        => 'Subtotal',
        'discount'        => 'Discount',
        'vat'             => 'VAT',
        'total'           => 'Total',
        'bank'            => 'Bank',
        'iban'            => 'IBAN',
        'variable_symbol' => 'Variable symbol',
    ],
];
