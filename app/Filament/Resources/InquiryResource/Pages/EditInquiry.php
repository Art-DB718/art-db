<?php

namespace App\Filament\Resources\InquiryResource\Pages;

use App\Filament\Resources\InquiryResource;
use Filament\Resources\Pages\EditRecord;

class EditInquiry extends EditRecord
{
    protected static string $resource = InquiryResource::class;

    /** Auto-mark as read on open. */
    protected function afterFill(): void
    {
        $user = auth()->user();
        if ($user && $this->record->recipient_user_id === $user->id) {
            $this->record->markAsRead();
        }
    }
}
