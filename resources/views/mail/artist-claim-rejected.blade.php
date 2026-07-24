<x-mail::message>
# Ownership request declined

Your request to take ownership of **{{ $artist->display_name }}** was declined by the current owner.
@if ($reason)

Reason given: *{{ $reason }}*
@endif

You can still use your {{ $appName }} account to create a separate artist profile.

<x-mail::button :url="$adminUrl">
Create your own profile
</x-mail::button>

If you believe this decision was made in error, reply to this email and we'll help sort it out.

Thanks,
{{ $appName }}
</x-mail::message>
