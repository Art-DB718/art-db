<x-layouts.public
    title="Cookie Policy — Art DB"
    description="Which cookies Art DB sets and why.">

    <section class="py-16">
        <div class="max-w-3xl mx-auto px-6 prose prose-neutral">
            <h1 class="font-serif text-4xl mb-8">Cookie Policy</h1>
            <p class="text-sm text-gray-500 mb-10">Last updated: {{ now()->format('d.m.Y') }}</p>

            <h2 class="font-serif text-2xl mt-10 mb-3">1. What are cookies?</h2>
            <p>Cookies are small text files that websites place on your device to remember information about you between page loads and visits. Under EU ePrivacy and GDPR, cookies fall into two categories: <strong>strictly necessary</strong> (functional) and <strong>non-essential</strong> (analytics, marketing, personalisation). Only non-essential cookies require your prior consent.</p>

            <h2 class="font-serif text-2xl mt-10 mb-3">2. Cookies we use</h2>
            <p>Art-DB uses <strong>only strictly necessary cookies</strong>. We do not place any marketing, advertising or third-party tracking cookies.</p>

            <table class="w-full text-sm border border-gray-200 my-4">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="text-left p-3 border-b border-gray-200">Cookie</th>
                        <th class="text-left p-3 border-b border-gray-200">Purpose</th>
                        <th class="text-left p-3 border-b border-gray-200">Lifetime</th>
                        <th class="text-left p-3 border-b border-gray-200">Category</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="p-3 border-b border-gray-200 font-mono text-xs">laravel_session</td>
                        <td class="p-3 border-b border-gray-200">Keeps you signed in, remembers form state and flash messages across page loads.</td>
                        <td class="p-3 border-b border-gray-200">2 hours</td>
                        <td class="p-3 border-b border-gray-200">Strictly necessary</td>
                    </tr>
                    <tr>
                        <td class="p-3 border-b border-gray-200 font-mono text-xs">XSRF-TOKEN</td>
                        <td class="p-3 border-b border-gray-200">CSRF protection — ensures that forms submitted to the site originated from Art-DB and not a third party.</td>
                        <td class="p-3 border-b border-gray-200">2 hours</td>
                        <td class="p-3 border-b border-gray-200">Strictly necessary</td>
                    </tr>
                </tbody>
            </table>

            <h2 class="font-serif text-2xl mt-10 mb-3">3. Analytics</h2>
            <p>Site usage is measured with <a href="https://umami.is" class="underline">Umami</a>, a privacy-focused analytics tool configured in <strong>cookieless mode</strong>. No persistent identifiers are stored on your device; visits are aggregated using a short-lived IP hash. No data is shared with third parties.</p>

            <h2 class="font-serif text-2xl mt-10 mb-3">4. Payments</h2>
            <p>If you proceed to a Stripe-powered checkout flow, Stripe may set its own cookies to secure the transaction. These are only placed <strong>when you open a checkout page</strong>, not on general browsing. See <a href="https://stripe.com/cookie-settings" class="underline">Stripe's cookie policy</a>.</p>

            <h2 class="font-serif text-2xl mt-10 mb-3">5. No consent banner</h2>
            <p>Because Art-DB sets only strictly necessary cookies and uses a cookieless analytics setup, we are not required under the EU ePrivacy Directive to display a cookie consent banner. If we introduce non-essential cookies in the future (e.g. Google Analytics or marketing pixels), a proper consent mechanism will be added.</p>

            <h2 class="font-serif text-2xl mt-10 mb-3">6. Managing cookies</h2>
            <p>You can delete or block cookies at any time through your browser settings. Blocking the strictly necessary cookies above will log you out and may break form submissions, but you can still browse the public parts of the site.</p>

            <h2 class="font-serif text-2xl mt-10 mb-3">7. Contact</h2>
            <p>Questions about this policy: <a href="mailto:info@art-db.org" class="underline">info@art-db.org</a></p>
        </div>
    </section>
</x-layouts.public>
