<x-layouts.public
    title="Help centre — Art DB"
    description="How to use Art-DB: adding artworks, managing contacts, sending private rooms, exhibitions, billing.">

    <section class="py-16">
        <div class="max-w-4xl mx-auto px-6">

            <header class="mb-14">
                <p class="text-xs uppercase tracking-[0.24em] text-gray-500 mb-3">Help centre</p>
                <h1 class="font-serif text-4xl mb-4">How can we help?</h1>
                <p class="text-gray-600 max-w-2xl">
                    Short answers to the questions owners ask most. Can't find what you need?
                    Write to <a href="mailto:info@art-db.org?subject=Art-DB%20support%20request" class="underline">info@art-db.org</a>
                    and we usually reply within a working day.
                </p>
            </header>

            {{-- Category index --}}
            <nav class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-16">
                @foreach ([
                    'getting-started' => ['Getting started', 'Create an account, publish your first artwork.'],
                    'artworks'        => ['Artworks', 'Images, provenance, pricing, bulk actions.'],
                    'contacts'        => ['Contacts & groups', 'CRM basics, importing, newsletter.'],
                    'private-rooms'   => ['Private rooms', 'Share a curated selection with a collector.'],
                    'exhibitions'     => ['Exhibitions', 'Catalogues, installation views, PDFs.'],
                    'billing'         => ['Billing & plans', 'Subscriptions, invoices, cancellation.'],
                ] as $slug => [$title, $tagline])
                    <a href="#{{ $slug }}" class="block border border-gray-200 p-5 hover:border-gray-900 transition">
                        <h2 class="font-serif text-lg mb-1">{{ $title }}</h2>
                        <p class="text-sm text-gray-500">{{ $tagline }}</p>
                    </a>
                @endforeach
            </nav>

            {{-- FAQ sections --}}
            @php
                $sections = [
                    'getting-started' => [
                        'title' => 'Getting started',
                        'items' => [
                            ['How do I create an account?', 'Click Sign up on the top right, pick a role (Gallery / Artist / Collector), confirm your email and sign in. The first time you sign in, Art-DB walks you through filling in your profile.'],
                            ['Which role should I choose?', 'Gallery — you represent a brick-and-mortar or online gallery and want to publish artists, exhibitions and run private rooms. Artist — you are an artist managing your own practice. Collector — you want a private database of your own collection with provenance.'],
                            ['Can I change my role later?', 'Yes, write to info@art-db.org and we can switch it. Role determines which dashboard tabs you see.'],
                            ['Is my data private by default?', 'Yes. Artworks and artists are drafts until you explicitly set them to Public. Contacts, private rooms and collections are always private to your account.'],
                        ],
                    ],
                    'artworks' => [
                        'title' => 'Artworks',
                        'items' => [
                            ['How do I add an artwork?', 'Dashboard → Artworks → New artwork. Fill in title, artist, year, medium, dimensions and upload at least one image. Primary image is what shows on the public web.'],
                            ['Can I upload multiple images?', 'Yes — the primary image sits at the top; additional gallery images appear in the lightbox when a visitor opens the artwork.'],
                            ['How do I hide the price?', 'Set Price on request on the artwork, or use the bulk action from the Artworks list to toggle it for many artworks at once.'],
                            ['Why is my provenance not showing on the public web?', 'Provenance is private by default. Open the artwork and tick "Publish provenance" to show it.'],
                            ['How do I mark an artwork as sold?', 'Open it and change Status to Sold, or use the bulk action "Mark as sold" on the Artworks list.'],
                            ['Can I write medium and genre in Slovak and still show English to visitors?', 'Yes. We auto-translate common terms (olej na plátne → oil on canvas). If a term is unusual, fill in the "English name" field on the Medium or Genre record and that wins.'],
                        ],
                    ],
                    'contacts' => [
                        'title' => 'Contacts & groups',
                        'items' => [
                            ['Who else can see my contacts?', 'Only you. Contacts and contact groups are scoped to your account.'],
                            ['How do I import contacts from a CSV?', 'Dashboard → Contacts → Import. Provide a CSV with columns: first_name, last_name, email, phone, city, country. We dedupe on email.'],
                            ['What are contact groups for?', 'Grouping (e.g. VIP collectors, press, Prague clients) makes it fast to pick recipients for a private room or newsletter.'],
                            ['How do newsletter subscriptions work?', 'Contacts can be marked Subscribed / Unsubscribed. Each newsletter email includes a one-click unsubscribe link in the footer.'],
                        ],
                    ],
                    'private-rooms' => [
                        'title' => 'Private rooms',
                        'items' => [
                            ['What is a private room?', 'A curated selection of artworks you share with one or more collectors via an unlisted link. Only recipients with the signed link can open it.'],
                            ['How do I know if a recipient opened the room?', 'Open the private room → Recipients tab. The Opened column shows the first time each recipient viewed the room (via a 1×1 tracking pixel in the email).'],
                            ['Can I send to several contacts at once?', 'Yes — pick individual contacts or a whole contact group when creating the room. Each recipient gets their own signed link.'],
                            ['Does Art-DB keep a copy of what I sent?', 'Yes. Private rooms and their recipients are stored under your account for your own records.'],
                        ],
                    ],
                    'exhibitions' => [
                        'title' => 'Exhibitions',
                        'items' => [
                            ['How do I attach artworks to an exhibition?', 'Open the exhibition → Artworks relation manager → Attach. You can attach existing artworks or create new ones in place.'],
                            ['Can I generate a PDF catalogue?', 'Yes. On an exhibition click "Print catalogue". Pick a layout (1, 2, 4, 6, 8, 9, 10 or 12 works per page) and we render a PDF.'],
                            ['What are installation views?', 'Photos of the physical install — they show up in a lightbox gallery at the bottom of the public exhibition page.'],
                        ],
                    ],
                    'billing' => [
                        'title' => 'Billing & plans',
                        'items' => [
                            ['Which plans are available?', 'Open Dashboard → Billing to see current plans and the number of artworks / contacts each one allows. Monthly or annual cycle.'],
                            ['Where do I get my invoice?', 'Dashboard → Billing → Invoices. Every payment generates a downloadable PDF invoice.'],
                            ['How do I cancel?', 'Dashboard → Billing → Manage subscription → Cancel. You keep access until the end of the current billing period.'],
                            ['I have a promotion code.', 'Enter it on the Stripe Checkout page in the "Add promotion code" field — codes are issued by us individually.'],
                        ],
                    ],
                ];
            @endphp

            <div class="space-y-16" x-data="{ open: null }">
                @foreach ($sections as $slug => $section)
                    <section id="{{ $slug }}" class="scroll-mt-24">
                        <h2 class="font-serif text-2xl mb-6 pb-3 border-b border-gray-200">{{ $section['title'] }}</h2>

                        <div class="divide-y divide-gray-200">
                            @foreach ($section['items'] as $i => [$q, $a])
                                @php $key = $slug . '-' . $i; @endphp
                                <div class="py-4">
                                    <button type="button"
                                            class="w-full flex items-start justify-between text-left gap-6"
                                            @click="open === '{{ $key }}' ? open = null : open = '{{ $key }}'">
                                        <span class="font-medium text-gray-900">{{ $q }}</span>
                                        <span class="text-gray-400 shrink-0" x-text="open === '{{ $key }}' ? '−' : '+'">+</span>
                                    </button>
                                    <div x-show="open === '{{ $key }}'" x-cloak class="mt-3 text-gray-600 leading-relaxed">
                                        {{ $a }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>

            <footer class="mt-20 pt-10 border-t border-gray-200 text-sm text-gray-500">
                <p>
                    Still stuck? Write to
                    <a href="mailto:info@art-db.org?subject=Art-DB%20support%20request" class="underline text-gray-900">info@art-db.org</a>.
                </p>
            </footer>
        </div>
    </section>
</x-layouts.public>
