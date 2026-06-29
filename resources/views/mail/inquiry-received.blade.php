<x-mail::message>
# New inquiry

**{{ $senderName }}**@if ($senderEmail) ({{ $senderEmail }})@endif has sent you a question about:

@if ($artwork)
**{{ $artwork->title }}**
@if ($artwork->artist){{ $artwork->artist->display_name }}@endif
@if ($artwork->year_created), {{ $artwork->year_created }}@endif
@endif

---

> {{ $inquiry->message }}

---

<x-mail::button :url="$inboxUrl">
Open inquiry in admin
</x-mail::button>

You can also reply directly to this email — it goes back to the sender.

Thanks,
{{ config('app.name') }}
</x-mail::message>
