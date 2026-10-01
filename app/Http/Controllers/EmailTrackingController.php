<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\PrivateRoom;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Email open tracking — serves a 1x1 transparent GIF and stamps the
 * contact_private_room.opened_at pivot the first time a recipient's
 * mail client loads the image. The URL is embedded in the email body
 * built by PrivateRoomResource::privateRoomEmailHtml().
 *
 * The route is unsigned on purpose — any email client should be able
 * to GET it without hitting signature checks. Worst case, a stray
 * reference stamps opened_at on a pivot that already has one; we
 * never move the timestamp forward once set.
 */
class EmailTrackingController extends Controller
{
    public function pixel(Request $request, PrivateRoom $room, Contact $contact): Response
    {
        $this->markOpened($room, $contact);

        // 43-byte transparent 1x1 GIF — the smallest valid image we can
        // return without external dependencies.
        $gif = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');

        return response($gif, 200, [
            'Content-Type'  => 'image/gif',
            'Content-Length'=> strlen($gif),
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma'        => 'no-cache',
        ]);
    }

    /**
     * Also stamp opened_at when the recipient clicks the actual private
     * room link — gives us a signal even when image loading is blocked.
     * The handler lives inside PrivateRoomController; this method is a
     * reusable helper.
     */
    public static function markOpened(PrivateRoom $room, Contact $contact): void
    {
        $pivot = $room->recipients()->where('contact_id', $contact->id)->first();
        if (! $pivot) {
            return;
        }
        if ($pivot->pivot->opened_at) {
            return;
        }
        $room->recipients()->updateExistingPivot($contact->id, [
            'opened_at' => now(),
            'status'    => 'viewed',
        ]);
    }
}
