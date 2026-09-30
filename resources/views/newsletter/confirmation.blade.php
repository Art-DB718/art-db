<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Newsletter — {{ $action === 'subscribe' ? 'Subscribed' : 'Unsubscribed' }}</title>
    <style>
        html, body { margin: 0; padding: 0; background: #f5f5f4; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Helvetica, Arial, sans-serif; color: #1f2937; }
        .wrap { max-width: 520px; margin: 6rem auto 4rem; background: #fff; padding: 3rem 2.5rem; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.06); text-align: center; }
        h1 { font-family: 'Georgia', serif; font-size: 1.6rem; margin: 0 0 0.8rem; letter-spacing: 0.02em; }
        p  { line-height: 1.55; color: #374151; }
        .icon { font-size: 2.2rem; margin-bottom: 1rem; }
        .muted { color: #6b7280; font-size: 0.9rem; margin-top: 2rem; }
    </style>
</head>
<body>
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
    </div>
</body>
</html>
