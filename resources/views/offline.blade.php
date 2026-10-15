<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Offline — Art-DB</title>
    <x-pwa-head />
    <style>
        html, body { margin: 0; padding: 0; height: 100%; }
        body {
            font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
            background: #1e293b;
            color: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 2rem;
            box-sizing: border-box;
        }
        .wrap { max-width: 24rem; }
        h1 { font-size: 1.5rem; font-weight: 600; margin: 0 0 0.5rem; }
        p  { color: #cbd5e1; line-height: 1.5; margin: 0 0 1.5rem; }
        button {
            background: #f8fafc;
            color: #1e293b;
            border: 0;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font: inherit;
            font-weight: 500;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="wrap">
        <h1>Si offline</h1>
        <p>Art-DB potrebuje pripojenie k internetu, aby zobrazil túto stránku. Keď bude signál späť, skús to znova.</p>
        <button onclick="location.reload()">Skúsiť znova</button>
    </div>
</body>
</html>
