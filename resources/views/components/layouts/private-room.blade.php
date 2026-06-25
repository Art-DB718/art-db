@props([
    'title'          => null,
    'recipientLabel' => 'the addressee',
])
<!DOCTYPE html>
<html lang="en" class="bg-white">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title ?? 'Private Room' }} — {{ config('app.name', 'ArtDB') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-white text-gray-900 antialiased font-sans">

    <header class="border-b border-gray-200">
        <div class="max-w-6xl mx-auto px-6 py-6 flex items-center justify-between">
            <p class="font-serif text-xl tracking-tight">{{ config('app.name', 'ArtDB') }}</p>
            <p class="text-xs uppercase tracking-[0.3em] text-gray-400">Private viewing</p>
        </div>
    </header>

    <main>
        {{ $slot }}
    </main>

    <footer class="mt-24 border-t border-gray-200">
        <div class="max-w-6xl mx-auto px-6 py-10 text-center text-xs text-gray-500">
            <p>This page is for {{ $recipientLabel }} only and is not publicly indexed.</p>
            <p class="mt-2">© {{ now()->year }} {{ config('app.name', 'ArtDB') }}.</p>
        </div>
    </footer>
</body>
</html>
