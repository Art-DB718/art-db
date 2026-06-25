# Project Arch — todo.md (master plán pre Claude Code)

> **Tento súbor je „pamäť" projektu medzi sessions.** Vždy ho prečítaj na začiatku, vždy aktualizuj na konci. Bez neho zabudneš, kde sme skončili.

---

## 0. Operačné pravidlá (NEPORUŠOVAŤ)

1. **Branch:** pracuj výhradne na `main`. Žiadne feature branche. Commituj často s krátkymi conventional-commits správami (`feat:`, `fix:`, `chore:`, `docs:`). _Pozn.: git repo zatiaľ NEEXISTUJE — `git init` je v Fáze 9 (alebo skôr na požiadanie). Commity vystavovať pod identitou `Kat <info@schottert-contemporary.com>` (rovnako ako Art DB)._
2. **Port:** lokálny dev server VŽDY na `http://localhost:8002`. Ak je obsadený, ukonči proces (`lsof -ti :8002 | xargs kill -9`) a spusti znova. Nikdy nemeň port — `8000` patrí Art DB, `8001` Art DB Preview.
3. **`todo.md` udržiavaj živé:**
   - Na začiatku sessionu prečítaj celý súbor.
   - Po každom dokončenom kroku zaškrtni `[x]` v sekcii „Roadmap" a doplň krátky komentár (čo, kedy, commit hash ak je).
   - Ak narazíš na blocker, zapíš ho do sekcie „Blockers".
   - Ak objavíš novú prácu, pridaj ju do správnej fázy.
   - Pred koncom sessionu commituj (keď git existuje) zmenený `todo.md` aj kód.
4. **Auto-mód:** máš povolenie samostatne sa rozhodovať pri *technických* otázkach (knižnice, štruktúra kódu, refaktoring, testy, drobné UX detaily). Pri *produktových* otázkach (čo má v UI byť, doménový model, biznis flow) sa **opýtaj Kat** cez sekciu „Questions for Kat".
5. **Bezpečnosť:**
   - `php artisan migrate:fresh --seed` je OK len lokálne — vymaže testovacích userov, treba znova vytvoriť.
   - Žiadne credentials v kóde — všetko cez `.env`.
   - `.env` nikdy necommituj.
6. **Zdroj pravdy:**
   - Doménový spec: [`docs/SPECIFIKACIA.md`](docs/SPECIFIKACIA.md) (draft založený 2026-06-24, iteruje sa s Kat)
   - Referenčný projekt s podobnou doménou: [`art-db`](../art-db/) — komerčný gallery management. Project Arch je **verejnejší archive/community web s SaaS modelom**.

---

## 1. Kontext projektu (jednorazové prečítanie)

**Čo staviame:** Project Arch je verejný web — archív umeleckých diel + komunita pre **galérie, umelcov a zberateľov** s **SaaS predplatným** (14-dňový trial → platba podľa rozsahu DB).

**Tri typy registrovaných používateľov:**
- **Gallery** (samostatná entita s profilom) — najširší dashboard: artists, artworks, collections, inquiries + **plánované: exhibitions, invoices, email kampane, private rooms**.
- **Artist** — 1 vlastný profil + svoje diela. Môže byť zastupovaný **viacerými galériami** (M:N). Tvorí, prijíma aj posiela inquiries.
- **Collector** — má **dashboard rovnakej štruktúry ako Gallery, BEZ** exhibitions/email/invoices/private rooms. Môže mať **vlastnú súkromnú databázu** umelcov a diel zo svojej zbierky (`is_public=false`), vytvárať kolekcie, posielať aj prijímať inquiries.

**Verejné vs. súkromné záznamy:**
- Artist/Artwork majú flag `is_public` / `is_published` (default `false`).
- Gallery a Artist typicky vytvárajú PUBLIC záznamy (do verejného archívu).
- Collector vytvára PRIVATE záznamy (viditeľné iba jemu) — súkromná evidencia zbierky.
- Collector môže Toggle-om časť svojej zbierky zverejniť (napr. po expozícii v múzeu).

**Stack:**
- Laravel 12, PHP 8.4
- Filament 3 (admin panel — single `/admin` s role-based menu a tenancy)
- PostgreSQL 16 (DB `projectarch`)
- Tailwind CSS + Alpine.js (verejný web — Fáza 3)
- Blade šablóny
- Stripe Cashier (subscriptions — Fáza 7)

**Dátový model (cieľový):**
- `users` (s `role` enum + `trial_ends_at`, `subscription_*` fields)
- `galleries` — Gallery profil (logo, kontakt, adresa)
- `artists` — Artist profil (`+ is_public` flag pre public/private rozlíšenie)
- `artist_gallery` pivot — M:N pre PUBLIC artists (jeden umelec pod viacerými galériami)
- `artworks` (`artist_id`, `owner_user_id`, `+ is_published` flag)
- `collections` + `artwork_collection` pivot — kolekcie **akejkoľvek role**
- `inquiries` (sender_user_id, recipient_user_id, artwork_id, message, status) — odosielateľ AJ príjemca môžu byť akákoľvek rola
- `artwork_likes` — wishlist (akákoľvek rola)
- _(plánované po potvrdení)_ `exhibitions`, `invoices`, `private_rooms`, `email_campaigns` — iba pre Gallery
- `subscriptions` (Stripe Cashier tabuľky)

**Architektonické princípy:**
- **Simple enum** pre role (`App\Enums\UserRole`) — 3 fixné role, spatie/permission overkill.
- **Gallery dashboard má rovnakú štruktúru ako admin** (Filament panel scoped na galériu — Gallery user vidí len artists/artworks svojej galérie).
- **Multi-tenant**: Artist môže byť pod viacerými galériami; každá ho vidí v svojom dashboarde.
- Slugy auto-gen v `Model::booted()::creating()`.
- Soft deletes na Gallery, Artist, Artwork.
- Bez UUID a Inventory ID (nie je inventory tool).
- **SaaS model**: 14-dňový trial pri registrácii → potom platba (Stripe Cashier).

---

## 2. Aktuálny stav

**Fáza:** 0 + 1 ✅ DOKONČENÉ. Nasleduje **Fáza 2** (Gallery entita + multi-tenant artist).
**Posledný commit:** (po dokončení Fázy 2 — viď git log)
**Posledná session:** 2026-06-24 — bootstrap + Filament resources + role-based access + seed dáta + spec dokument založený + 6 produktových otázok odpovedaných.

**Ako spustiť server (ďalšia session):**
```bash
cd /Users/katarinaschottertsobolciakova/claude.code/project-archiv
php artisan serve --host=127.0.0.1 --port=8002
```

**Admin login:**
| Rola | Email | Heslo |
|---|---|---|
| Gallery (plný admin) | `info@schottert-contemporary.com` | `9MUVVzp8w1zu53Zo` |
| Artist | `artist@test.com` | `artist123` |
| Collector | `collector@test.com` | `collector123` |

**V DB teraz:** 3 useri (gallery/artist/collector), 6 artists (Bartuszová owned by artist@test.com), 24 artworks (všetky published), 1 prázdna collection. Galleries tabuľka **zatiaľ neexistuje** — pribudne vo Fáze 2.

---

## 3. Roadmap

### Fáza 0 — Bootstrap ✅ DOKONČENÁ (2026-06-24)

- [x] `composer create-project laravel/laravel` (Laravel 12)
- [x] `composer require filament/filament:^3.2` + `php artisan filament:install --panels`
- [x] PostgreSQL DB `projectarch`
- [x] `.env` (APP_URL :8002, DB_CONNECTION=pgsql)
- [x] `php artisan migrate` + `storage:link`
- [x] Admin user

---

### Fáza 1 — Doménové MVP (admin) ✅ DOKONČENÁ (2026-06-24)

- [x] `App\Enums\UserRole` (Gallery/Artist/Collector)
- [x] Migrácia `add_role_to_users_table` + User: cast, helpers, FilamentUser
- [x] Migrácia + model **Artist** (slug, owner_user_id, softdeletes)
- [x] Migrácia + model **Artwork**
- [x] Migrácia + model **Collection** + pivot `artwork_collection`
- [x] `ArtistResource`, `ArtworkResource`, `CollectionResource` (role-scoped)
- [x] Custom Register page s Select pre role
- [x] `ArchiveSeeder` (6 artists + 24 artworks + 1 collection)

---

### Fáza 2 — Gallery entita + multi-tenant artist + public/private rozlíšenie ✅ DOKONČENÁ (2026-06-25)

**Cieľ:** Galéria ako plnohodnotná entita s dashboardom. Artist môže byť pod viacerými galériami.

- [x] Migrácia + model **Gallery** (name, slug, logo, description, address, country, website, email, phone, `owner_user_id`)
- [x] Migrácia pivot **`artist_gallery`** (gallery_id, artist_id, represented_since, is_primary, notes, timestamps)
- [x] _Existujúci `is_published` flag na artists/artworks ponechaný (žiadny duplicitný `is_public` netreba)_
- [x] Vzťahy: `Artist::galleries()`, `Gallery::artists()`, `User::gallery()`
- [x] `GalleryResource` (Filament) — "My gallery" pre Gallery user, jump na vlastný profil; ostatné role nevidia v menu
- [x] **Refactor** ArtistResource: Gallery vidí len cez pivot, Artist len vlastný, Collector published+vlastné
- [x] **Refactor** ArtworkResource: Gallery cez `artist.galleries`, Artist vlastné, Collector published+vlastné
- [x] Test Gallery "Schottert Contemporary" vytvorená cez tinker, Bartuszová napojená (`is_primary=true`)
- [x] Filament action **„+ Represent existing artist"** (header) + **„Stop representing"** (row) v ArtistResource pre Gallery
- [x] CreateArtist `afterCreate` hook — pri Gallery vytvorení nového artistu auto-attach do pivotu

---

### Fáza 3 — Verejný web (frontend)

**Cieľ:** Public web mimo `/admin` — archív verejne viditeľný + galérie a umelci majú verejné profily.

- [ ] `HomeController` + `home.blade.php` — hero + featured galleries + featured artists + featured artworks
- [ ] `GalleryController@index/show` — listing + profil galérie (logo, kontakt, list umelcov, ich diela)
- [ ] `ArtistController@index/show` — listing + profil umelca (bio, country, diela, **list galérií ktoré ho zastupujú**)
- [ ] `ArtworkController@index/show` — listing s filtrami (Medium, Year, Price, Artist, Gallery) + full-text search + detail
- [ ] `CollectionController@show` — verejný detail kolekcie (iba `is_private = false`)
- [ ] Layout `x-layouts.public` s Tailwind theme
- [ ] Responzivita (mobile-first)
- [ ] SEO meta tags + sitemap.xml + robots.txt

---

### Fáza 4 — Collector dashboard parita + univerzálne kolekcie/wishlist

**Cieľ:** Collector dashboard má rovnakú štruktúru ako Gallery (Artists, Artworks, Collections, Inquiries) BEZ exhibitions/email/invoices/private rooms. Kolekcie a wishlist sú dostupné pre všetky role.

- [ ] **Refactor** `CollectionResource.canViewAny()`: pre **všetky 3 role**, nielen Collector (scoped na vlastné `owner_user_id`)
- [ ] Filament navigation pre Collectora: pridať Artists + Artworks do menu (scoped na vlastné private záznamy; v Fáze 2 sú už scoped)
- [ ] Filament navigation: skryť Exhibitions/Invoices/PrivateRooms/EmailCampaigns z menu pre Collectora _(až existujú — Fáza 9)_
- [ ] Pivot `artwork_likes` (user_id, artwork_id) + relácie na User a Artwork
- [ ] „Like" tlačidlo na verejnej detail stránke (akákoľvek rola)
- [ ] „Save to collection" CTA na verejnej detail stránke (akákoľvek rola)
- [ ] `is_private` toggle na Collection: ak false → verejne dostupné na `/collections/{user}/{collection-slug}`
- [ ] Toggle „Make public" / „Make private" na Collectorovom Artwork — Collector môže časť zbierky zverejniť

---

### Fáza 5 — Inquiries (akákoľvek prihlásená rola → akýkoľvek artwork owner)

**Cieľ:** Bez Stripe pre umelecké diela. Sender aj recipient môžu byť akákoľvek rola (Gallery, Artist, Collector).

- [ ] Migrácia + model **Inquiry** (sender_user_id, recipient_user_id, artwork_id, message, status: new/replied/closed)
- [ ] `InquiryResource` v Filament — pre **všetky role**, sekcie „Received" a „Sent"
- [ ] Inquiry form na verejnej detail stránke Artwork (potreba prihlásenia — akákoľvek rola)
- [ ] Email notifikácia recipientovi (`Mail::log` → neskôr Resend)
- [ ] Filament badge `unreadInquiriesCount` v menu
- [ ] (TBD) Interný messaging thread vs. iba „uložená správa, ďalej cez email" — viď Q for Kat

---

### Fáza 6 — Médiá a obrázky

- [ ] Image optimization (`spatie/laravel-image-optimizer` alebo `intervention/image`)
- [ ] Thumbnail generation (small/medium/large) na upload
- [ ] Lazy loading na public listingoch
- [ ] Cover image cropping v Filament (`->imageEditor()`)
- [ ] (optional) S3-compatible storage (R2 / Backblaze) — config v `.env`

---

### Fáza 7 — Subscriptions & Trial (SaaS biznis model)

**Cieľ:** 14-dňový trial pri registrácii, potom platba podľa rozsahu DB. Žiadny payment pre umelecké diela — iba pre SaaS prístup.

**Toto je novo dohodnutá fáza (2026-06-24) — detaily plánov ešte nie sú known, viď „Questions for Kat".**

- [ ] Migrácia: pridať `trial_ends_at` (timestamp), `subscription_plan` (string nullable), `subscription_status` (enum: trial/active/past_due/cancelled) na `users`
- [ ] Pri registrácii nastaviť `trial_ends_at = now()->addDays(14)` a status = `trial`
- [ ] Middleware `EnsureSubscriptionActive` — blokuje prístup do `/admin` (Gallery, Artist) ak trial expired a status nie je active
- [ ] Public detail stránky **nesú obmedzenie subscription** (verejný archív je vždy verejný)
- [ ] **Inštalácia `laravel/cashier-stripe`** (Stripe Cashier)
- [ ] Definovať plans v Stripe Dashboard (manuálne) — názvy a ceny podľa Q&A s Kat
- [ ] Billing page v admine: aktuálny plán, ostávajúce dni triala, „Upgrade" / „Change plan" tlačidlá
- [ ] Webhook handler (`stripe:webhook`) — sync subscription status
- [ ] Email upozornenia: 7/3/1 deň pred koncom triala, pri zmene statusu
- [ ] „Read-only" mode po expirácii: prístup do `/admin` zachovaný, ale `canCreate/canEdit = false` — Kat sa nestratí dáta

---

### Fáza 8 — Ďalšie integrácie (optional)

- [ ] **Resend** — transactional email (inquiry, trial reminders, registrácia)
- [ ] **Mailchimp** — newsletter sync z home page
- [ ] **REST API + Sanctum** — `/api/v1/galleries`, `/artists`, `/artworks`
- [ ] Export endpoint `/api/v1/export/full`

---

### Fáza 9 — Pokročilé Gallery features _(po potvrdení Kat — viď Questions)_

**Cieľ:** Gallery má v Art DB navyše Exhibitions, Invoices, Private Rooms, Email kampane. Kat tieto features spomenula ako veci, ktoré Collector NEMÁ — implikujem že chce mať aj v Project Arch (pre Gallery rolu). **Pred štartom potvrdiť!**

- [ ] **Exhibitions** — model + Filament resource + pivot artwork_exhibition; verejné stránky výstav
- [ ] **Invoices / faktúry** — model + PDF generátor (`barryvdh/laravel-dompdf` ako v Art DB), per-Gallery číslovanie
- [ ] **Private Rooms** — model + tokenovaný verejný link (`/private-room/{token}`), pivot recipients, view tracking
- [ ] **Email kampane / newsletter** — model EmailCampaign + integrácia s Resend pre mass mail; lists z `inquiries`/`contacts`
- [ ] Filament resources skryté pre Artist a Collector (`canViewAny` blokuje)

---

### Fáza 10 — Polish + Production

- [x] **`git init`** + `.gitignore` (.env, vendor, node_modules, public/storage) + first commit ako `Kat <info@schottert-contemporary.com>` _(2026-06-25, commit `caef683`)_
- [ ] Multi-language: `lang/sk` + `lang/en`, language switcher
- [ ] Sitemap.xml (`spatie/laravel-sitemap`), robots.txt
- [ ] Image optimization automatically on upload
- [ ] Cache headers + Laravel cache (HTTP cache na public stránkach)
- [ ] CSP headers (`spatie/laravel-csp`)
- [ ] PHPStan level 6+
- [ ] Pest smoke testy: každá public route + auth flow + subscription middleware
- [ ] Production deploy guide (Forge / DigitalOcean / Hetzner)
- [ ] `.env.production` template
- [ ] Backup script (denný `pg_dump` + storage rsync)

---

## 4. Session protokol

### Začiatok každej session

```bash
# 1. Prečítaj todo.md a spec
cat /Users/katarinaschottertsobolciakova/claude.code/project-archiv/todo.md | head -250
cat /Users/katarinaschottertsobolciakova/claude.code/project-archiv/docs/SPECIFIKACIA.md | head -100

# 2. Skontroluj služby
pg_isready
lsof -i :8002

# 3. Ak server nebeží, spusti
cd /Users/katarinaschottertsobolciakova/claude.code/project-archiv
php artisan serve --host=127.0.0.1 --port=8002 > storage/logs/serve.log 2>&1 &

# 4. Pokračuj prvou nezaškrtnutou položkou z roadmap
```

### Koniec každej session

1. Aktualizuj `todo.md` — zaškrtni dokončené, pridaj nové, doplň poznámky.
2. (keď bude git) `git add -A && git commit -m "<conventional message>"`
3. Aktualizuj sekciu „Aktuálny stav" a „Posledná aktualizácia".

---

## 5. Často používané príkazy

```bash
# Server
php artisan serve --host=127.0.0.1 --port=8002

# DB
php artisan migrate
php artisan migrate:fresh --seed                   # POZOR — vymaže userov
php artisan db:seed --class=ArchiveSeeder
psql projectarch

# Filament
php artisan make:filament-resource <Name>
php artisan make:filament-page <Name>

# Cache & optimizácia
php artisan optimize:clear
php artisan route:list
php artisan about
php artisan tinker

# Postgres služba
brew services start postgresql@16

# Diagnostika portu
lsof -i :8002
lsof -ti :8002 | xargs kill -9
```

---

## 6. Architektonické rozhodnutia (kontext)

### Prečo Laravel 12 + Filament 3?

Rovnaký stack ako [Art DB](../art-db/) — Kat ho ovláda, jednotný hand-off.

### Simple enum vs spatie/laravel-permission

3 fixné role → enum stačí. Ak pribudnú granulárne perms, prepneme.

### Gallery = samostatná entita

Gallery má vlastný profil (logo, adresa, kontakt) a vlastný dashboard so štruktúrou identickou s adminom — vidí len artists/artworks tých umelcov, ktorých zastupuje (cez pivot `artist_gallery`).

### Multi-tenant artist (M:N gallery↔artist)

Jeden Artist môže byť reprezentovaný viacerými galériami. Implementácia cez pivot tabuľku `artist_gallery` (nie cez `gallery_id` na artists). Toto zachováva nezávislosť Artistovho profilu.

### Single Filament panel s tenancy-light scopingom

Namiesto 3 oddelených panelov používame jeden `/admin` panel s `canViewAny/canCreate/canEdit` a scoped `getEloquentQuery` per role. Pre Gallery to znamená: query whereHas('galleries', fn => $q->whereKey($user->gallery->id)).

### Sales = Inquiry only (no Stripe pre umelecké diela)

Project Arch nie je marketplace — kontakt o dielo cez **inquiry**, predaj rieši mimo. Stripe je v projekte len pre **SaaS subscriptions**. Inquiries môže poslať aj prijať **akákoľvek prihlásená rola**.

### Public vs. private záznamy (is_public / is_published)

Každý Artist a Artwork záznam má visibility flag. Gallery a Artist typicky tvoria PUBLIC záznamy (do verejného archívu). Collector tvorí PRIVATE záznamy (vlastná súkromná zbierka). Collector môže toggle-om časť svojej zbierky zverejniť. Scope v Filament Resources kombinuje `owner_user_id` (kto vidí) + `is_public/is_published` (verejnosť archívu).

### Collector dashboard má štruktúru ako Gallery (mínus pokročilé features)

Collector je „mini-gallery" so súkromnou databázou. Má rovnaké menu (Artists, Artworks, Collections, Inquiries) ako Gallery, ale NEMÁ exhibitions, email kampane, invoices, private rooms. Tieto Gallery-only features sú v Fáze 9.

### 14-day trial → paid plans

Pri registrácii každý user dostane `trial_ends_at = now()->addDays(14)` a `subscription_status = 'trial'`. Po expirácii admin prejde do read-only mode (`canCreate/canEdit = false`), aby sa Kat nestratila dáta. Stripe Cashier zabezpečuje platby. Plány podľa rozsahu DB (limitované počty diel / umelcov / storage) — **konkrétne plány a ceny budú definované cez Q&A**.

### Slug auto-gen v `Model::booted()::creating()`

Konzistentne s Art DB. **`DatabaseSeeder` nesmie mať `use WithoutModelEvents`** inak `creating` hook nezbeží → seed spadne na slug NOT NULL.

### Žiadne UUID, žiadne Inventory ID

Project Arch nie je inventory tool. Ak Kat povie že treba (export portability), vieme dorobiť migráciou.

### Port 8002

`8000` = Art DB, `8001` = Art DB Preview, `8002` = Project Arch.

---

## 7. Blockers

> Sem zapisuj veci, ktoré ťa blokujú.

_Aktuálne žiadne aktívne blockery._

---

## 8. Questions for Kat

> Produktové otázky, na ktoré potrebujem odpoveď.

### A. Pokročilé Gallery features (Fáza 9) ⚠️ HLAVNÁ OTÁZKA

Spomenula si že Collector **nemôže** organizovať exhibitions, posielať maily, vystavovať faktúry, mať Private Rooms. Predpokladám že Gallery TIETO **bude** mať v Project Arch (inak by si ich nezmienila). Sú to všetko Art DB features, treba ich preniesť do Project Arch?

- [ ] **Exhibitions** — Gallery organizuje výstavy (kde, kedy, ktoré diela). Verejne na webe.
- [ ] **Email/newsletter kampane** — Gallery posiela mass mail kontaktom (Mailchimp / Resend).
- [ ] **Invoices / faktúry** — Gallery vystavuje PDF faktúry kupujúcim.
- [ ] **Private Rooms** — Gallery zdieľa kurátorský výber cez tokenovaný link konkrétnemu zákazníkovi.

**Default ak Kat povie áno na všetky 4 → Fáza 9** v roadmap. Ak nie všetky, treba povedať ktoré.

### B. Subscription / pricing model (Fáza 7)

- **Koľko plánov?** Návrh: Trial + Collector Free + Starter + Professional + Studio + Enterprise. Súhlasíš?
- **Aké limity per plán?** Návrh v [SPECIFIKACIA.md sekcia 6](docs/SPECIFIKACIA.md#6-saas-predplatné--plány) — schvál alebo uprav.
- **Collector Free** — trvale zadarmo s limitom 50 private diel? Alebo platí ako ostatní?
- **Ročná zľava** (napr. 2 mesiace zdarma)?
- **Read-only po expirácii triala** vs. úplne blokovať login? (návrh: read-only, 30 dní potom archivácia)
- **Cancel anytime** cez Stripe user portal — OK?

### C. Inquiries detail (Fáza 5)

- **Interný messaging thread** v UI (viacero správ tam-naspäť), alebo iba „inquiry uložená, ďalšia komunikácia mimo cez email"? Návrh: pre MVP iba uložiť + notif, thread doplniť neskôr.

### D. Drobnosti

- **Email verification** pri registrácii povinný (cez Resend)?
- **Locale**: zmeniť `APP_LOCALE` z `en` na `sk` ako default? (multi-lang stále Fáza 10)
- **Test data**: do seedera doplniť „Schottert Contemporary" galériu + pripojiť k nej Bartuszovú?

---

## 9. Decisions log

> Stručný záznam dôležitých rozhodnutí.

| Dátum | Rozhodnutie | Dôvod |
|---|---|---|
| 2026-06-24 | Laravel 12 + Filament 3 | Matching Art DB stack — hand-off |
| 2026-06-24 | PostgreSQL DB `projectarch` | Rovnaký engine ako Art DB; izolovaná DB |
| 2026-06-24 | Port `8002` | `8000` Art DB, `8001` Preview |
| 2026-06-24 | Simple `UserRole` enum | MVP, 3 fixné role |
| 2026-06-24 | Single Filament panel s role-based scopingom | Lepšie pre maintenance |
| 2026-06-24 | Žiadne UUID, žiadne Inventory ID | Project Arch nie je inventory tool |
| 2026-06-24 | `WithoutModelEvents` ZMAZANÉ z DatabaseSeeder | Inak `creating` hook nevygeneruje slug |
| 2026-06-24 | `gallery_images` jsonb (array of paths) | Konzistentné s Art DB |
| 2026-06-24 | Soft deletes na Artist/Artwork, nie na Collection | Collection mazať drasticky stačí |
| 2026-06-24 | Bartuszová `owner_user_id = artist@test.com` | Test artist vidí 1 svoj profil + 4 diela |
| **2026-06-24** | **Gallery je samostatná entita** | Kat: má vlastný dashboard so štruktúrou ako admin |
| **2026-06-24** | **Multi-tenant: artist ↔ gallery M:N cez pivot `artist_gallery`** | Kat: jeden umelec môže byť zastupovaný viacerými galériami |
| **2026-06-24** | **Sales = iba Inquiry, žiadny Stripe pre umelecké diela** | Kat: zatiaľ iba inquiry model; predaj sa rieši mimo Project Arch |
| **2026-06-24** | **14-dňový trial + paid plans (Stripe Cashier)** | Kat: každý user musí registrovať, 14 dní trial, potom platba podľa rozsahu DB |
| **2026-06-24** | **Doménový spec v Markdown (`docs/SPECIFIKACIA.md`)** | Lepšie verzuje cez git; ak Kat povie že treba .docx, prekonvertujeme cez docx skill |
| **2026-06-24** | **Git identita commitov: `Kat <info@schottert-contemporary.com>`** | Rovnako ako Art DB |
| 2026-06-25 | **`git init` vykonaný** | Initial commit `caef683` ako `Kat <info@schottert-contemporary.com>`. 332 súborov, žiadne sensitive (`.env`, `vendor/`, `node_modules/` zachytené `.gitignore`) |
| **2026-06-24** | **Collector môže mať vlastnú súkromnú databázu artists/artworks** | Kat: Collector pridáva diela zo svojej zbierky; rieši sa cez `is_public`/`is_published` flag + `owner_user_id` scoping |
| **2026-06-24** | **Kolekcie môže vytvárať akákoľvek rola (Gallery/Artist/Collector)** | Kat: pôvodne iba Collector — rozšírené |
| **2026-06-24** | **Inquiries môže poslať AJ PRIJAŤ akákoľvek prihlásená rola** | Kat: aj umelec môže prijímať/posielať, aj Collector |
| **2026-06-24** | **Collector dashboard má rovnakú štruktúru ako Gallery (mínus 4 features)** | Kat: bez exhibitions / email / invoices / private rooms |
| **2026-06-24** | **Pridať `is_public` na artists a sprehľadniť `is_published` na artworks** | Vyplýva z public vs. private rozlíšenia — bez toho sa nedá scopovať Collectorove private záznamy |
| **2026-06-24** | **Fáza 9 (Exhibitions/Invoices/PrivateRooms/Email) čaká na potvrdenie** | Kat ich zmienila iba ako Collector exclusions — implikujem že Gallery ich má, ale treba potvrdiť pred implementáciou |

---

## 10. Zoznam súborov v projekte (referencia)

**Migrácie (4 + 3 default):**
```
0001_01_01_000000_create_users_table.php             (Laravel default)
0001_01_01_000001_create_cache_table.php             (Laravel default)
0001_01_01_000002_create_jobs_table.php              (Laravel default)
2026_06_24_150000_add_role_to_users_table.php
2026_06_24_150010_create_artists_table.php
2026_06_24_150020_create_artworks_table.php
2026_06_24_150030_create_collections_table.php       (+ pivot)
```

**Modely (4):** User, Artist, Artwork, Collection
**Enums (1):** App\Enums\UserRole
**Filament Resources (3):** Artist, Artwork, Collection
**Filament Pages (1):** Auth\Register (override base)
**Providers:** AdminPanelProvider (Filament)
**Seedery:** DatabaseSeeder, ArchiveSeeder
**Dokumenty:** [`docs/SPECIFIKACIA.md`](docs/SPECIFIKACIA.md)

---

## 11. Posledná aktualizácia

- **Dátum:** 2026-06-24
- **Autor:** Claude Code (session 1)
- **Stav:** **VEĽKÁ ZMENA — BULK PORT Z ART DB DOKONČENÝ.** Kat povedala „preneste celý dashboard adminu z Art DB do Project Arch". Spravené:
  - Inštalované packages: barryvdh/laravel-dompdf, drewm/mailchimp-api, laravel/sanctum, maatwebsite/excel, resend/resend-laravel, stripe/stripe-php
  - Bulk rsync z [art-db/](../art-db/) do project-archiv: `app/Filament/` (16 Resources, 2 Pages — InvoiceSettings, DesignPrintouts), `app/Models/` (19 modelov), `app/Enums/UserRole.php` (rozšírené o Admin a University), `app/Exports/` (2), `app/Services/MailchimpService.php`, `app/Policies/` (7), `app/Http/{Controllers,Requests,Resources}/`, `app/Providers/AppServiceProvider.php` (Gate::before isAdmin), `database/migrations/` (45 spolu vrátane Art DB), `database/seeders/DemoSeeder.php`, `resources/views/` (auth, components, dashboard, filament, layouts, my, onboarding, prints, private-room, profile, public), `config/artdb.php`, `routes/{web,auth,api}.php`
  - Mergnutý `UserRole.php` — `canAccessFilament()` upravený aby všetky role (Gallery, Artist, Collector) mali prístup do `/admin` (Project Arch decision)
  - Mergnutý `AdminPanelProvider.php` — brandName "Project Arch", Slate/Zinc theme, navigationGroups Catalogue/Exhibitions/CRM/Commerce/System, registration page zachovaná
  - Custom Register page recreated v `app/Filament/Pages/Auth/Register.php` (Select s `UserRole::publicRegisterChoices()`)
  - **DB `projectarch` wipnutá a postavená nanovo** zo 45 Art DB migrácií
  - DemoSeeder spustený — 5 artists (Sikora, Bartuszová, Jankovič, Daučíková, Reichel), 15 artworks, 3 collections, 8 contacts, 2 exhibitions, 12 countries
  - 3 test useri recreated s pôvodnými credentials
  - Server beží na :8002, `/admin/login` + `/admin/register` = 200, **53 admin routes** discovered Filamentom
- **Známe drobnosti po porte:**
  - **Verejná stránka `/` = 500** lebo nemá build assets (Vite manifest chýba). Treba `npm install && npm run build` keď chceme verejný web. Pre teraz admin funguje samostatne.
  - **Multi-tenancy NIE JE implementovaná** — všetky resources sú ešte single-tenant (každý prihlásený gallery user vidí všetko). Refactor je vo Fáze 2 (Gallery entita + scoping per gallery).
  - **Žiadne subscription middleware** — všetci useri majú plný prístup bez triala kontroly. Fáza 7.
  - **Collector restrikcie zatiaľ NIE SÚ aplikované** — Collector teraz vidí všetky resources vrátane Exhibitions/Invoices/PrivateRooms (ktoré by mu mali byť skryté). Fáza 4.
- **Nasleduje:** Fáza 2 — Gallery entita + multi-tenancy scoping per resource. Až potom Fáza 4 (Collector dashboard restrictions) a Fáza 7 (Subscriptions).
