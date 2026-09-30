<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Art-DB — {{ $action === 'subscribe' ? 'Subscribed' : 'Unsubscribed' }}</title>
    <style>
        html, body { margin: 0; padding: 0; background: #f5f5f4; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Helvetica, Arial, sans-serif; color: #1f2937; }
        .brand { text-align: center; padding: 2.5rem 1.5rem 0; }
        .brand a { text-decoration: none; color: #1f2937; font-family: Georgia, 'Times New Roman', serif; font-size: 1.4rem; letter-spacing: 0.28em; text-transform: uppercase; }
        .brand .tagline { display: block; margin-top: 0.4rem; font-size: 0.75rem; letter-spacing: 0.18em; color: #6b7280; text-transform: uppercase; }
        .wrap { max-width: 520px; margin: 2.5rem auto 4rem; background: #fff; padding: 3rem 2.5rem; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.06); text-align: center; }
        h1 { font-family: 'Georgia', serif; font-size: 1.6rem; margin: 0 0 0.8rem; letter-spacing: 0.02em; }
        p  { line-height: 1.55; color: #374151; margin: 0.4rem 0; }
        .icon { font-size: 2.2rem; margin-bottom: 1rem; }
        .muted { color: #6b7280; font-size: 0.9rem; margin-top: 2rem; }
        .cta { display: inline-block; margin-top: 2rem; padding: 0.7rem 1.4rem; background: #1f2937; color: #fff; text-decoration: none; border-radius: 4px; font-size: 0.9rem; letter-spacing: 0.04em; }
        .cta:hover { background: #111827; }
    </style>
</head>
<body>
    <div class="brand">
        <a href="{{ url('/') }}">Art-DB<span class="tagline">for galleries &amp; collectors</span></a>
    </div>

    <div class="wrap">
        <div class="icon">{{ $action === 'subscribe' ? '✓' : '✕' }}</div>
        @if ($action === 'subscribe')
            <h1>Ďakujeme za prihlásenie</h1>
            <p>{{ $contact->email ?: 'Váš kontakt' }} je zapísaný na odber našich noviniek.</p>
        @else
            <h1>Odhlásené</h1>
            <p>{{ $contact->email ?: 'Váš kontakt' }} už nebude dostávať naše e-maily.</p>
            <p class="muted">Ak sa jedná o omyl, kontaktujte nás priamo — radi vás znova prihlásime.</p>
        @endif

        <a class="cta" href="{{ url('/') }}">Prejsť na art-db.org →</a>
    </div>
</body>
</html>
