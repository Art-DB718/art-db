<?php

use App\Models\Artist;
use App\Models\Artwork;
use App\Models\Contact;
use App\Models\Inquiry;
use Illuminate\Support\Facades\Mail;

// Public artwork-inquiry form (with optional newsletter opt-in) end-to-end.

beforeEach(function () {
    Mail::fake();
    $this->artist = Artist::create([
        'first_name'   => 'Test',
        'last_name'    => 'Artist',
        'is_published' => true,
    ]);
    $this->artwork = Artwork::create([
        'artist_id'    => $this->artist->id,
        'title'        => 'Untitled Test',
        'is_published' => true,
    ]);
});

it('creates an Inquiry + Contact when a guest submits the form', function () {
    $this->post(route('artworks.inquire', $this->artwork), [
        'name'    => 'Jane Guest',
        'email'   => 'jane@example.com',
        'message' => 'Interested in this work.',
    ])->assertRedirect();

    expect(Inquiry::count())->toBe(1);
    $inquiry = Inquiry::first();
    expect($inquiry->guest_email)->toBe('jane@example.com');
    expect($inquiry->guest_name)->toBe('Jane Guest');
    expect($inquiry->artwork_id)->toBe($this->artwork->id);
    expect($inquiry->message)->toBe('Interested in this work.');

    // Contact created but NOT subscribed to newsletter (checkbox off)
    $contact = Contact::where('email', 'jane@example.com')->first();
    expect($contact)->not->toBeNull();
    expect((bool) $contact->subscribed_to_newsletter)->toBeFalse();
});

it('marks the Contact as newsletter-subscribed when checkbox is ticked', function () {
    $this->post(route('artworks.inquire', $this->artwork), [
        'name'                 => 'Sub Guest',
        'email'                => 'sub@example.com',
        'message'              => 'Hi',
        'subscribe_newsletter' => '1',
    ])->assertRedirect();

    $contact = Contact::where('email', 'sub@example.com')->first();
    expect($contact)->not->toBeNull();
    expect((bool) $contact->subscribed_to_newsletter)->toBeTrue();
});

it('validates required fields', function () {
    $this->post(route('artworks.inquire', $this->artwork), [
        'name' => 'no email',
    ])->assertSessionHasErrors(['email', 'message']);

    expect(Inquiry::count())->toBe(0);
});

it('rejects inquiries on unpublished artworks', function () {
    $this->artwork->update(['is_published' => false]);

    $this->post(route('artworks.inquire', $this->artwork), [
        'name'    => 'X',
        'email'   => 'x@example.com',
        'message' => 'hi',
    ])->assertNotFound();
});
