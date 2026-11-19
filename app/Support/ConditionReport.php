<?php

namespace App\Support;

/**
 * Centralised vocabulary for the SNG-style Condition Report:
 * the 26 damage categories for the work body and the 6 for the
 * frame, in both Slovak and English. The Filament form and the
 * PDF template both pull from these lists, so adding or editing
 * an item touches one place.
 *
 * Keys are stable slugs used as array keys in the JSON blob
 * (never renumber — existing reports store these keys directly).
 */
class ConditionReport
{
    /**
     * @return array<string, array{sk: string, en: string}>
     */
    public static function bodyDamages(): array
    {
        return [
            'base_level'                 => ['sk' => 'Zmena roviny nosiča',               'en' => 'Differences in base-level'],
            'ground_damage'              => ['sk' => 'Poškodenie nosiča',                 'en' => 'Damages of the ground'],
            'ground_perforation'         => ['sk' => 'a) perforácia',                     'en' => 'a) perforation'],
            'ground_cracks'              => ['sk' => 'b) praskliny',                      'en' => 'b) cracks'],
            'fold_of_support'            => ['sk' => 'Prehnutie nosiča',                  'en' => 'Fold of support'],
            'break'                      => ['sk' => 'Zlom',                               'en' => 'Break'],
            'undulation'                 => ['sk' => 'Zvlnenie',                           'en' => 'Undulation'],
            'crease'                     => ['sk' => 'Záhyb',                              'en' => 'Crease'],
            'bulge'                      => ['sk' => 'Vypuklina',                          'en' => 'Bulge'],
            'diminished_paint_adhesion'  => ['sk' => 'Znížená priľnavosť maľby',          'en' => 'Diminished paint adhesion'],
            'fallen_colour_layer'        => ['sk' => 'Odpadnutá farebná vrstva',          'en' => 'Fallen colour-layer'],
            'damaged_colour_layer'       => ['sk' => 'Porušenie farebnej vrstvy',         'en' => 'Damaged colour-layer'],
            'cracks_with_loose_paint'    => ['sk' => 'Krakely s uvoľnenou maľbou',        'en' => 'Cracks with diminished paint adhesion'],
            'paint_abrasion'             => ['sk' => 'Odretie',                            'en' => 'Paint abrasion'],
            'scrap'                      => ['sk' => 'Vryp',                               'en' => 'Scrap'],
            'dusty_surface'              => ['sk' => 'Zaprášený povrch',                   'en' => 'Dusty surface'],
            'bloom'                      => ['sk' => 'Zákal, závoj laku',                  'en' => 'Bloom'],
            'discoloration'              => ['sk' => 'Vyblednutie',                        'en' => 'Discoloration'],
            'local_stains'               => ['sk' => 'Lokálne škvrny',                     'en' => 'Local stains'],
            'fingerprint'                => ['sk' => 'Otlačok prsta',                      'en' => 'Finger-print'],
            'fly_specks'                 => ['sk' => 'Mušincince',                         'en' => 'Fly specks'],
            'foxing'                     => ['sk' => 'Pliesen',                            'en' => 'Foxing'],
            'previous_treatment'         => ['sk' => 'Predošlé zásahy',                    'en' => 'Previous treatment'],
            'damaged_patina'             => ['sk' => 'Poškodená patina',                   'en' => 'Damaged patina'],
            'yellowish_paper'            => ['sk' => 'Zožltnutý papier',                   'en' => 'Yellowish paper'],
            'loose_parts'                => ['sk' => 'Uvoľnené časti',                     'en' => 'Loose parts'],
            'woodworm_damage'            => ['sk' => 'Poškodenie červotočom',              'en' => 'Damage from woodworm'],
            'fillings_and_retouch'       => ['sk' => 'Tmel a retuše',                      'en' => 'Fillings and retouch'],
        ];
    }

    /**
     * @return array<string, array{sk: string, en: string}>
     */
    public static function frameDamages(): array
    {
        return [
            'frame_abrasion'           => ['sk' => 'Odrenina',             'en' => 'Abrasion'],
            'frame_crackle'            => ['sk' => 'Krakel, prasklina',    'en' => 'Crackle, cleavage'],
            'frame_open_bevel'         => ['sk' => 'Otvorený pokos',       'en' => 'Open bevel (across)'],
            'frame_colour_trace'       => ['sk' => 'Farebné stopy',         'en' => 'Colour trace'],
            'frame_fallen_fragment'    => ['sk' => 'Odpadnutý fragment',    'en' => 'Fallen fragment'],
            'frame_previous_treatment' => ['sk' => 'Predošlé zásahy',       'en' => 'Previous treatment'],
        ];
    }

    /**
     * Options for Filament CheckboxList: key => English label.
     *
     * @param  array<string, array{sk: string, en: string}>  $list
     * @return array<string, string>
     */
    public static function options(array $list): array
    {
        $out = [];
        foreach ($list as $key => $labels) {
            $out[$key] = $labels['en'];
        }
        return $out;
    }
}
