<x-mail::message>
# Ownership request

**{{ $claimant->name }}** ({{ $claimant->email }}) has just registered as an artist and matched an existing profile you manage:

**{{ $artist->display_name }}**

They're asking you to approve transferring ownership of this profile to their account. If you approve:

- The artist becomes owner of their own profile.
- Your gallery keeps them in your represented-artists roster (nothing disappears from your public page).
- The artist can update their bio, add works, and be reached directly.

If you reject the claim, we'll notify them and they'll be able to create a separate profile instead.

<x-mail::button :url="$reviewUrl">
Review the claim
</x-mail::button>

If you didn't expect this and don't recognise the person, just reject the claim.

Thanks,
{{ $appName }}
</x-mail::message>
