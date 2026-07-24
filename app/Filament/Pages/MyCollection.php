<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

/**
 * "My Collection" — a Collector-first (also Gallery-friendly) admin page
 * that surfaces the two personal artwork lists built by the public-site
 * like/save actions:
 *   - Liked  → artwork_likes pivot
 *   - Saved  → artwork_saves pivot (a.k.a. "added to my collection")
 *
 * Artist users don't have either action (the buttons are hidden for them
 * on the public detail page), so we skip the navigation entry for them.
 */
class MyCollection extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-heart';
    protected static ?string $navigationLabel = 'My Collection';
    protected static ?string $navigationGroup = 'Catalogue';
    protected static ?int $navigationSort = 20;
    protected static string $view = 'filament.pages.my-collection';

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();
        return $user && ($user->isCollector() || $user->isGallery());
    }

    public static function canAccess(): bool
    {
        return static::shouldRegisterNavigation();
    }

    public function getViewData(): array
    {
        $user = auth()->user();

        return [
            'liked' => $user->likedArtworks()
                ->with(['artist:id,slug,first_name,last_name'])
                ->orderByDesc('artwork_likes.created_at')
                ->get(),
            'saved' => $user->savedArtworks()
                ->with(['artist:id,slug,first_name,last_name'])
                ->orderByDesc('artwork_saves.created_at')
                ->get(),
        ];
    }
}
