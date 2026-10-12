<?php

namespace App\Support;

/**
 * Word-by-word translation of common Slovak / Czech art terms into
 * English, used across the public web so a browser-side visitor sees
 * "oil on canvas" even when the record is stored as "olej na plátne".
 *
 * This is a cheap, deterministic pass — nothing hits an external API.
 * Unknown words fall through unchanged, so a specialised term the
 * dictionary doesn't cover stays in the original language rather than
 * being silently lost. For a human-quality override, Medium / Genre
 * have a `name_en` column that trumps this helper.
 */
class ArtTermTranslator
{
    /**
     * Normalised lookup — strip accents + lowercase before matching
     * against this map. Keys MUST be ASCII, lowercased, singular when
     * possible. Add entries as Kat spots missing ones.
     */
    protected const DICTIONARY = [
        // Common media
        'akryl'         => 'acrylic',
        'olej'          => 'oil',
        'tempera'       => 'tempera',
        'akvarel'       => 'watercolour',
        'gvas'          => 'gouache',
        'gouache'       => 'gouache',
        'pastel'        => 'pastel',
        'voskovy'       => 'wax',
        'voskovy pastel'=> 'wax pastel',
        'uhol'          => 'charcoal',
        'ceruzka'       => 'pencil',
        'grafit'        => 'graphite',
        'tus'           => 'ink',
        'atrament'      => 'ink',
        'kresba'        => 'drawing',
        'kolaz'         => 'collage',
        'serigrafia'    => 'screenprint',
        'sitotlac'      => 'screenprint',
        'litografia'    => 'lithograph',
        'ofset'         => 'offset',
        'suchá ihla'    => 'drypoint',
        'sucha ihla'    => 'drypoint',
        'lept'          => 'etching',
        'drevoryt'      => 'woodcut',
        'linoryt'       => 'linocut',
        'medirytina'    => 'engraving',
        'fotografia'    => 'photograph',
        'foto'          => 'photo',
        'digitalna tlac'=> 'digital print',
        'tlac'          => 'print',
        'potlac'        => 'print',
        'vysivka'       => 'embroidery',
        'textil'        => 'textile',
        'keramika'      => 'ceramics',
        'bronz'         => 'bronze',
        'drevo'         => 'wood',
        'kov'           => 'metal',
        'kamen'         => 'stone',
        'mramor'        => 'marble',
        'sadra'         => 'plaster',
        'skl'           => 'glass',
        'sklo'          => 'glass',
        'papier'        => 'paper',
        'papieri'       => 'paper',
        'papiera'       => 'paper',
        'platno'        => 'canvas',
        'platne'        => 'canvas',
        'platna'        => 'canvas',
        'drevotrieska'  => 'particle board',
        'doska'         => 'board',
        'doske'         => 'board',
        'dosky'         => 'board',
        'karton'        => 'cardboard',
        'kartone'       => 'cardboard',
        'kartona'       => 'cardboard',
        'latka'         => 'fabric',
        'latke'         => 'fabric',

        // Grammar bridges
        'na'            => 'on',
        'z'             => 'from',
        'zo'            => 'from',
        's'             => 'with',
        'so'            => 'with',
        'a'             => 'and',
        'i'             => 'and',
        'alebo'         => 'or',

        // Common genre names
        'malba'         => 'painting',
        'malbe'         => 'painting',
        'malby'         => 'painting',
        'socha'         => 'sculpture',
        'soche'         => 'sculpture',
        'sochy'         => 'sculpture',
        'plastika'      => 'sculpture',
        'grafika'       => 'print',
        'grafike'       => 'print',
        'grafiky'       => 'print',
        'video'         => 'video',
        'instalacia'    => 'installation',
        'instalacii'    => 'installation',
        'performance'   => 'performance',
        'zatisie'       => 'still life',
        'krajinomalba'  => 'landscape',
        'krajinomal'    => 'landscape',
        'portret'       => 'portrait',
        'portretu'      => 'portrait',
        'abstrakcia'    => 'abstract',
        'abstrakcii'    => 'abstract',
        'figuralna'     => 'figurative',
        'figuralny'     => 'figurative',
        'konceptualne'  => 'conceptual',
    ];

    /**
     * Translate a free-text term (one word or a phrase).
     * Preserves the original token's capitalisation style loosely:
     * an all-lowercase token stays lowercase, a leading-capital token
     * gets its first letter uppercased on the way out.
     */
    public static function translate(?string $text): ?string
    {
        if (! filled($text)) {
            return $text;
        }

        // Split on whitespace / commas while keeping separators so the
        // original punctuation + spacing is preserved on output.
        $parts = preg_split('/([\s,;·•]+)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE);

        $out = [];
        foreach ($parts as $part) {
            if (preg_match('/^[\s,;·•]+$/u', $part) || $part === '') {
                $out[] = $part;
                continue;
            }
            $out[] = static::translateWord($part);
        }

        return implode('', $out);
    }

    protected static function translateWord(string $word): string
    {
        $key = static::normalise($word);
        if ($key === '') {
            return $word;
        }

        $translated = self::DICTIONARY[$key] ?? null;
        if ($translated === null) {
            return $word; // unknown → keep original
        }

        // Preserve leading-capital hint ("Akryl" → "Acrylic").
        if (mb_strtoupper(mb_substr($word, 0, 1)) === mb_substr($word, 0, 1)) {
            return mb_strtoupper(mb_substr($translated, 0, 1)).mb_substr($translated, 1);
        }

        return $translated;
    }

    /**
     * Normalise for dictionary lookup — strip accents, lowercase,
     * trim surrounding non-letter characters.
     */
    protected static function normalise(string $word): string
    {
        $word = trim($word);
        if ($word === '') {
            return '';
        }
        $plain = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $word);
        $plain = $plain !== false ? $plain : $word;
        $plain = mb_strtolower($plain, 'UTF-8');
        return preg_replace('/[^a-z0-9 ]/', '', $plain) ?? '';
    }
}
