<x-mail::message>
# Your profile is now yours

Your request to take ownership of **{{ $artist->display_name }}** was approved. The profile is now linked to your {{ $appName }} account.

You can now:

- Update your bio, statement, links, profile image
- Add works, exhibitions, education entries
- Reply directly to inquiries sent through the public page

<x-mail::button :url="$adminUrl">
Open your artist profile
</x-mail::button>

The gallery that used to manage this profile still represents you — nothing is removed from their page.

Thanks,
{{ $appName }}
</x-mail::message>
