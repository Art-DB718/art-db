<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\InvoiceSetting;
use App\Models\PrivateRoom;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class PrivateRoomController extends Controller
{
    public function show(string $token): View|RedirectResponse|Response
    {
        $room = PrivateRoom::query()
            ->where('token', $token)
            ->firstOrFail();

        if ($room->isExpired()) {
            return response()->view('private-room.expired', ['room' => $room], 410);
        }

        // First-view notifikácia: ak view_count bol 0, pošli mail galérii.
        $isFirstView = $room->view_count === 0;

        $room->increment('view_count');
        $room->forceFill(['last_viewed_at' => now()])->save();

        if ($isFirstView) {
            $this->notifyGalleryOfFirstView($room);
        }

        $room->load([
            'artworks' => fn ($q) => $q->with(['artist', 'medium']),
            'recipient',
        ]);

        return view('private-room.show', [
            'room' => $room,
        ]);
    }

    public function inquire(string $token, Request $request): RedirectResponse
    {
        $room = PrivateRoom::query()
            ->where('token', $token)
            ->firstOrFail();

        abort_if($room->isExpired(), 410);
        abort_unless($room->allow_inquiry, 403);

        $data = $request->validate([
            'name'       => ['required', 'string', 'max:255'],
            'email'      => ['required', 'email', 'max:255'],
            'message'    => ['required', 'string', 'max:2000'],
            'artwork_id' => ['nullable', 'integer', 'exists:artworks,id'],
        ]);

        // Uložiť/aktualizovať kontakt.
        $names = preg_split('/\s+/', trim($data['name']), 2);
        Contact::firstOrCreate(
            ['email' => $data['email']],
            [
                'first_name' => $names[0] ?? null,
                'last_name'  => $names[1] ?? null,
                'source'     => 'private room inquiry',
                'notes'      => 'Inquiry via private room: '.$room->title,
            ],
        );

        // Mail galérii.
        $settings = InvoiceSetting::current();
        $to = $settings->email ?: config('mail.from.address');

        $artwork = ($data['artwork_id'] ?? null)
            ? $room->artworks->firstWhere('id', $data['artwork_id'])
            : null;

        $html = '<p>New inquiry from <strong>'.e($data['name']).'</strong> &lt;'.e($data['email']).'&gt;</p>'
            .'<p>Via private room: <strong>'.e($room->title).'</strong></p>'
            .($artwork
                ? '<p>About artwork: <strong>'.e($artwork->title).'</strong> by '.e($artwork->artist?->display_name ?? '—').'</p>'
                : '')
            .'<p><strong>Message:</strong></p>'
            .'<blockquote style="border-left:3px solid #d1d5db;padding-left:1rem;color:#374151;">'
            .nl2br(e($data['message']))
            .'</blockquote>';

        Mail::html($html, function ($m) use ($to, $data, $room) {
            $m->to($to)
              ->replyTo($data['email'], $data['name'])
              ->subject('Private Room inquiry: '.$room->title);
        });

        return redirect()
            ->route('private-room.show', $token)
            ->with('inquiry_message', 'Thank you — your inquiry has been sent. We will be in touch shortly.');
    }

    protected function notifyGalleryOfFirstView(PrivateRoom $room): void
    {
        $settings = InvoiceSetting::current();
        $to = $settings->email ?: config('mail.from.address');

        $clientLabel = $room->recipient_name
            ?: $room->recipient?->display_name
            ?: ($room->recipient_email ?: 'Unknown client');

        $html = '<p>The private room <strong>'.e($room->title).'</strong> was just opened.</p>'
            .'<p>Client: <strong>'.e($clientLabel).'</strong>'
            .($room->recipient_email ? ' &lt;'.e($room->recipient_email).'&gt;' : '')
            .'</p>'
            .'<p><a href="'.e($room->publicUrl()).'">'.e($room->publicUrl()).'</a></p>'
            .'<p style="color:#6b7280;font-size:0.85em">Opened at '.now()->format('d.m.Y H:i').'.</p>';

        try {
            Mail::html($html, function ($m) use ($to, $room) {
                $m->to($to)->subject('Private Room opened: '.$room->title);
            });
        } catch (\Throwable $e) {
            // Notification failure nesmie zhodiť client view; len log.
            logger()->warning('PrivateRoom first-view mail failed: '.$e->getMessage());
        }
    }
}
