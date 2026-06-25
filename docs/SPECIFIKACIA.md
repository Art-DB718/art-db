# Project Arch — Doménová špecifikácia

> **Status:** DRAFT v0.2 — 2026-06-24
> **Autor:** Claude Code + Kat (Schottert Contemporary)
> **Vzťah k todo.md:** Tento dokument je „čo budujeme a prečo". [`../todo.md`](../todo.md) je „čo robíme tento týždeň a v akom poradí".

---

## 1. Účel a vízia

**Project Arch** je verejný web-archív umeleckých diel s komunitnou vrstvou pre **galérie, umelcov a zberateľov**. Funguje ako **SaaS** (predplatné) pre profesionálnych používateľov, pričom **verejný archív zostáva voľne dostupný** návštevníkom bez registrácie.

**Ciele:**
1. Dať galériám, umelcom **aj zberateľom** verejnú aj **súkromnú databázu** umeleckých diel.
2. Dať zberateľom nástroj na **kurátorstvo vlastných kolekcií a evidenciu súkromnej zbierky**.
3. Vytvoriť **predplatným udržateľný projekt** — žiadne provízie z predaja, žiadne reklamy.

**Čo Project Arch NIE JE:**
- Nie je marketplace (žiadne provízie z predaja).
- Nie je sociálna sieť (žiadny feed, žiadne komentáre).

**Vzťah k Art DB:**
- [Art DB](../../art-db/) je internal inventory + sales tool pre konkrétnu galériu (Schottert Contemporary).
- Project Arch je **publikovaný SaaS** pre viacero galérií/umelcov/zberateľov, s vlastným verejným webom.
- Niektoré pokročilé features (Exhibitions, Private Rooms, Invoices, Email kampane) sú v Art DB Gallery-only; v Project Arch sa **plánujú podobne, ale len pre Gallery rolu** — viď [otvorené otázky](#8-otvorené-otázky).

---

## 2. Cieľová skupina

| Persóna | Veľkosť | Použitie |
|---|---|---|
| **Galéria** (malá až stredná) | 5–500 zastupovaných umelcov | Verejná prezentácia, sales, exhibitions, communications |
| **Umelec** (samostatný) | 1 profil, 10–500 diel | Verejný portfolio web bez nutnosti vlastnej domény |
| **Zberateľ** | individuálne / inštitucionálne | **Súkromná databáza zbierky** + sledovanie obľúbených umelcov + kurátorstvo kolekcií |
| **Návštevník** (neregistrovaný) | verejnosť | Prezeranie verejného archívu — bez prihlásenia |

---

## 3. Používateľské role a prístupový matrix

Tri registrované role + neprihlásený návštevník. **Kľúčový princíp:** každý prihlásený user (okrem návštevníka) má **vlastnú databázu** umelcov, diel a kolekcií — Collector súkromnú, Artist a Gallery verejnú.

| Akcia | Návštevník | Collector | Artist | Gallery |
|---|---|---|---|---|
| Prezerať verejný archív | ✅ | ✅ | ✅ | ✅ |
| Detail diela / umelca / galérie | ✅ | ✅ | ✅ | ✅ |
| Registrácia | ✅ | — | — | — |
| **Vlastný admin dashboard** (`/admin`) | ❌ | ✅ | ✅ | ✅ |
| Edit vlastný user profil | — | ✅ | ✅ | ✅ |
| **Pridať Artist do svojej databázy** | ❌ | ✅ (private) | iba seba | ✅ (public + represent) |
| **Pridať Artwork do svojej databázy** | ❌ | ✅ (private) | ✅ (svoje, public) | ✅ (zastupovaných, public) |
| **Vytvoriť Collection** | ❌ | ✅ | ✅ | ✅ |
| **Pridať dielo do svojej Collection** | ❌ | ✅ | ✅ | ✅ |
| **Wishlist** (like) na verejné dielo | ❌ | ✅ | ✅ | ✅ |
| **Poslať inquiry** | ❌ | ✅ | ✅ | ✅ |
| **Prijímať inquiries** | — | ✅ (na svoje záznamy) | ✅ (svoje diela) | ✅ (zastupovaných umelcov) |
| **Inbox / správy** | — | ✅ | ✅ | ✅ |
| **Pridať umelca pod galériu** (pivot represent) | ❌ | ❌ | ❌ | ✅ |
| **Organizovať Exhibition** (plánované) | ❌ | ❌ | ❌ | ✅ |
| **Email / newsletter kampaň** (plánované) | ❌ | ❌ | ❌ | ✅ |
| **Vystaviť Invoice / faktúru** (plánované) | ❌ | ❌ | ❌ | ✅ |
| **Private Room** (zdieľanie výberu) (plánované) | ❌ | ❌ | ❌ | ✅ |
| **SaaS predplatné** vyžadované | — | ✅ trial→paid | ✅ trial→paid | ✅ trial→paid |

**Public vs. private záznamy:**
- Artist/Artwork záznam má príznak `is_public` (default `false`).
- **Gallery a Artist** typicky tvoria PUBLIC záznamy (`is_public=true`) → zobrazia sa vo verejnom archíve.
- **Collector** tvorí PRIVATE záznamy (`is_public=false`) → vidí ich iba on (vlastník).
- Toggle môže Collector použiť ak chce zverejniť časť svojej zbierky (napr. expozícia v múzeu).

**Dashboard scope (Filament):**
- **Gallery dashboard** = plný (Artists, Artworks, Collections, Inquiries, Exhibitions, Invoices, Private Rooms, Email).
- **Artist dashboard** = obmedzený (Svoj profil, vlastné Artworks, Collections, Inquiries).
- **Collector dashboard** = **rovnaká štruktúra ako Gallery, ale BEZ:** Exhibitions, Email/newsletter, Invoices, Private Rooms. Inak má: Artists (private), Artworks (private), Collections, Inquiries (sent + received).

**Po expirácii triala** všetky role prejdú do read-only — môžu sa prihlásiť a prezerať svoje dáta, ale nemôžu pridávať/editovať.

---

## 4. Doménový model

### 4.1 Entity (Eloquent modely)

```
┌──────────────────┐
│ User             │  role: gallery|artist|collector
│  - trial_ends_at │  subscription_status: trial|active|past_due|cancelled
│  - subscription  │
└──────────────────┘
        │
        ├─── hasOne ───▶ Gallery       (ak role=gallery)
        ├─── hasMany ──▶ Artist        (cez owner_user_id; Collector môže mať viac private)
        ├─── hasMany ──▶ Artwork       (cez owner_user_id; Collector môže mať viac private)
        └─── hasMany ──▶ Collection    (akákoľvek rola)

┌──────────────────┐
│ Gallery          │  ⟵ owner_user_id → User (rola=gallery)
│  - name, slug    │
│  - logo, contact │
└──────────────────┘
        │
        └─── belongsToMany ⟷ Artist  (cez artist_gallery pivot — len pre PUBLIC artists)

┌──────────────────┐
│ Artist           │  ⟵ owner_user_id → User (kto vytvoril záznam)
│  - first_name    │  - is_public (true = vo verejnom archíve, false = súkromný záznam)
│  - last_name     │
│  - bio, country  │
└──────────────────┘
        │
        └─── hasMany ──▶ Artwork

┌──────────────────┐
│ Artwork          │  ⟵ artist_id → Artist
│  - title, slug   │  ⟵ owner_user_id → User (kto pridal záznam)
│  - price, image  │  - is_published (true = vo verejnom archíve)
└──────────────────┘
        │
        ├─── belongsToMany ⟷ Collection  (cez artwork_collection)
        ├─── hasMany ──▶ ArtworkLike     (wishlist — akákoľvek rola)
        └─── hasMany ──▶ Inquiry

┌──────────────────┐
│ Collection       │  ⟵ owner_user_id → User (akákoľvek rola)
│  - name, slug    │
│  - is_private    │
└──────────────────┘

┌──────────────────┐
│ Inquiry          │  ⟵ sender_user_id → User (ktokoľvek prihlásený)
│  - message       │  ⟵ artwork_id → Artwork
│  - status        │  ⟵ recipient_user_id → User (artwork.owner_user_id)
└──────────────────┘

┌─ PLANNED (viď otvorené otázky) ─┐
│ Exhibition       │  ⟵ gallery_id → Gallery (iba Gallery)
│ Invoice          │  ⟵ gallery_id → Gallery (iba Gallery)
│ PrivateRoom      │  ⟵ gallery_id → Gallery (iba Gallery)
│ EmailCampaign    │  ⟵ gallery_id → Gallery (iba Gallery)
└──────────────────┘
```

### 4.2 Tabuľky (DDL prehľad)

**Hlavné:**
- `users` — Laravel default + `role`, `trial_ends_at`, `subscription_plan`, `subscription_status`
- `galleries` — name, slug, logo, address, country, website, email, phone, owner_user_id, soft deletes
- `artists` — first_name, last_name, slug, bio, birth/death year, country, portrait, owner_user_id, **`is_public`** (bool default false), soft deletes
- `artworks` — title, slug, artist_id, medium, materials, dimensions, price, currency, primary_image, gallery_images jsonb, owner_user_id, **`is_published`** (bool default false), soft deletes
- `collections` — name, slug, description, is_private, owner_user_id
- `inquiries` — sender_user_id, recipient_user_id, artwork_id, message, status enum

**Pivoty:**
- `artist_gallery` — gallery_id, artist_id, represented_since, is_primary
- `artwork_collection` — collection_id, artwork_id, private_note, position
- `artwork_likes` — user_id, artwork_id, timestamps

**Plánované (po potvrdení):**
- `exhibitions`, `artwork_exhibition`
- `invoices`, `invoice_items`
- `private_rooms`, `private_room_artwork`, `private_room_recipients`
- `email_campaigns`, `email_campaign_recipients`

**SaaS / Stripe (cez Cashier):**
- `subscriptions`, `subscription_items` — generované Cashierom

---

## 5. Workflows

### 5.1 Registrácia & onboarding

```
1. Návštevník → /admin/register
2. Vyplní: meno, email, password, role (Gallery / Artist / Collector)
3. User vytvorený s:
   - trial_ends_at = now() + 14 days
   - subscription_status = 'trial'
4. Email verification (Resend; voliteľné v MVP)
5. Per-role onboarding:
   - Gallery → vytvor Gallery profil (logo, kontakt)
   - Artist → vytvor Artist profil (bio, country, portrait)
   - Collector → "Welcome — start building your private archive"
6. Redirect na /admin
```

### 5.2 Multi-tenant artist (Gallery)

```
Scenár A — Galéria pridá EXISTUJÚCEHO public umelca:
1. Gallery admin → Artists → "Add existing artist"
2. Search po public umelcoch v archíve
3. Klik "Represent" → vytvorí riadok v artist_gallery pivot
4. Umelec sa zobrazí v Gallery dashboarde; Gallery môže editovať jeho diela

Scenár B — Galéria pridá NOVÉHO umelca:
1. Gallery admin → Artists → "Create new"
2. Vyplní profil (owner_user_id = gallery_user_id, is_public = true)
3. Auto-pripoji do svojej galérie cez pivot
4. Ak sa umelec neskôr registruje s rovnakým menom, Gallery môže "claim" profil

Scenár C — Umelec sa pridá k novej galérii:
1. Artist admin → Galleries → "Connect to gallery"
2. Search po galériách → "Request representation"
3. Gallery prijme/odmietne
```

### 5.3 Collector — súkromná databáza

```
1. Collector login → /admin
2. Dashboard má: Artists (private), Artworks (private), Collections, Inquiries
3. Pridanie Artistu do súkromnej databázy:
   - Variant A: použije existujúceho PUBLIC artistu (napr. "Bartuszová") — len ho referencuje, neduplikuje
   - Variant B: vytvorí súkromný Artist záznam (is_public=false, owner_user_id=collector)
4. Pridanie Artwork:
   - Variant A: ku public artistu vytvorí Artwork (is_published=false, owner_user_id=collector)
   - Variant B: ku vlastnému súkromnému artistu vytvorí Artwork (is_published=false)
5. Vidí: vlastné súkromné záznamy + public archív (read-only)
6. Môže vytvoriť Collection a pridať do nej (private aj public diela)
7. Môže poslať inquiry o akékoľvek (vrátane public) dielo
```

### 5.4 Inquiry flow (akákoľvek prihlásená rola)

```
1. User na detail stránke Artwork (verejnej alebo svojej)
2. Klik "Inquire about this work"
3. Ak NIE prihlásený → registračný flow
4. Form: message
5. Send → vytvorí Inquiry:
   - sender_user_id = current user (akákoľvek rola)
   - artwork_id
   - recipient_user_id = artwork.owner_user_id
   - status = 'new'
6. Email notifikácia recipientovi
7. Recipient odpovedá cez Filament inbox (status → 'replied')
8. Konverzácia ďalej cez email (mimo systému) alebo cez interný thread (TBD)
```

### 5.5 Subscription lifecycle

```
Day 0   → Registrácia, trial_ends_at = day 14, status = 'trial'
Day 7   → Email reminder: "7 days left"
Day 11  → Email reminder: "3 days left"
Day 13  → Email reminder: "1 day left"
Day 14  → Trial expires → status = 'past_due' (cron job)
         → User môže prihlásiť, ale CRUD je zablokované (read-only)
         → Billing page → Stripe Checkout → webhook → status = 'active'

Cancel: status = 'cancelled', prístup do konca obdobia, potom read-only
Payment fail: status = 'past_due', email + retry
```

---

## 6. SaaS predplatné — plány

> **STATUS: DRAFT — otvorené pre diskusiu s Kat (viď [todo.md → Questions for Kat](../todo.md#8-questions-for-kat))**

**Návrh plánov** (na schválenie):

| Plán | Mesačne | Ročne | Galérie | Umelci | Diela | Storage | Pre koho |
|---|---|---|---|---|---|---|---|
| **Trial** | 0 € | — | 1 | 5 | 50 | 1 GB | všetky role, 14 dní |
| **Collector Free** | 0 € | — | — | 20 | 50 | 0.5 GB | iba Collector (TBD) |
| **Starter** | 9 € | 90 € | 1 | 10 | 100 | 2 GB | Artist, malý Collector |
| **Professional** | 29 € | 290 € | 1 | 50 | 500 | 10 GB | malá galéria, väčší zberateľ |
| **Studio** | 79 € | 790 € | 3 | unlimited | unlimited | 50 GB | väčšia galéria |
| **Enterprise** | custom | custom | unlimited | unlimited | unlimited | custom | múzeum, viacero galérií |

**Pravidlá:**
- Trial je 14 dní pre KAŽDÉHO usera.
- Po expirácii triala: **read-only mode** — user vidí dáta, nemôže pridávať/editovať; po 30 dňoch bez platby sa data archivujú (nie mažú).
- Collector môže mať trvalo zadarmo „Collector Free" plán (TBD — Kat ešte musí potvrdiť).

---

## 7. Tech stack a konvencie

| Vrstva | Voľba | Dôvod |
|---|---|---|
| Framework | Laravel 12 | Matching Art DB stack; PHP 8.4 |
| Admin UI | Filament 3 | Identický UX ako Art DB |
| DB | PostgreSQL 16 | Open, prenosný; vlastná DB `projectarch` |
| Frontend | Tailwind + Alpine + Blade | Žiadny build hell |
| Auth | Laravel default + custom Filament Register | Simple, 3 fixné role |
| Billing | Stripe Cashier (`laravel/cashier-stripe`) | Štandard |
| Email | Mail::log (dev) → Resend (prod) | Trial reminders, inquiry notifs, kampane |
| Storage | Local (dev) → S3-compatible (prod) | R2 / Backblaze v cieli |

**Konvencie:**
- Slugy auto-gen v `Model::booted()::creating()`.
- Soft deletes na hlavných entitách.
- Role-based scoping v každom Filament Resource cez `getEloquentQuery()` + `canViewAny/canCreate/canEdit`.
- **Visibility scoping:** `is_public` na Artist, `is_published` na Artwork → public archív filter; admin scope per `owner_user_id` (+ pivot pre Gallery).
- Žiadne UUID, žiadne Inventory ID generátory.

---

## 8. Otvorené otázky

Viď [`../todo.md` → sekcia 8](../todo.md#8-questions-for-kat) pre úplný zoznam. **Hlavné otvorené body pre tento spec:**

1. **Pokročilé Gallery features** — Kat spomenula 4 features, ktoré Collector NEMÁ:
   - **Exhibitions** — Gallery organizuje výstavy diel (kde, kedy, ktorí umelci)
   - **Email / newsletter kampane** — Gallery posiela mass mail
   - **Invoices / faktúry** — Gallery vystavuje faktúry kupujúcim
   - **Private Rooms** — Gallery zdieľa kurátorský výber konkrétnemu zákazníkovi cez tokenovaný link

   **Otázka:** sú tieto 4 features plánované v Project Arch (Fáza 8+), alebo to bolo len pre porovnanie s Art DB? Default ak Kat povie áno → pridám do roadmap ako 4 nové fázy.

2. **Subscription plány** — ceny, limity, počet (návrh v sekcii 6).
3. **Read-only po triale** alebo úplne blokovať login? (návrh: read-only)
4. **Collector Free plán** — má Collector trvale zadarmo (len kolekcie + 50 private diel), alebo platí ako ostatní?
5. **Email verification** pri registrácii povinný?
6. **Internal messaging thread** pri inquiries (cez UI), alebo iba „inquiry uložená, ďalšia komunikácia mimo"?

---

## 9. Roadmap

Detail rozdelený do fáz v [`../todo.md` → sekcia 3](../todo.md#3-roadmap).

Hrubý prehľad:
- **0–1:** Bootstrap + MVP admin ✅ DOKONČENÉ
- **2:** Gallery entita + multi-tenant artist + `is_public/is_published` rozšírenia
- **3:** Verejný web (frontend)
- **4:** Collector dashboard parita (Collector ako "mini-gallery" so súkromnou databázou)
- **5:** Inquiries (všetky role môžu poslať/prijať)
- **6:** Médiá (image opt, S3)
- **7:** Subscriptions & Trial (Stripe Cashier)
- **8:** Ďalšie integrácie (Resend, Mailchimp, API)
- **9:** Pokročilé Gallery features — Exhibitions, Invoices, Private Rooms, Email kampane (po potvrdení v otvorenej otázke #1)
- **10:** Polish + Production

---

## 10. Glosár

| Pojem | Význam |
|---|---|
| **Gallery** | Komerčná galéria, samostatná entita s vlastným profilom, reprezentuje umelcov, má najširší dashboard |
| **Artist** | Umelec, samostatná entita; môže byť self-registered alebo gallery-managed |
| **Collector** | Zberateľ; má **súkromnú databázu** umelcov a diel zo svojej zbierky + kuruje kolekcie |
| **Inquiry** | Správa od akéhokoľvek prihláseného usera ohľadom konkrétneho diela, smerovaná na ownera diela |
| **Multi-tenant artist** | Jeden public Artist môže byť zastupovaný viacerými galériami cez pivot `artist_gallery` |
| **Public artist/artwork** | `is_public=true` / `is_published=true` — viditeľné vo verejnom archíve |
| **Private artist/artwork** | `is_public=false` / `is_published=false` — viditeľné iba ownerovi (typicky Collector) |
| **Trial** | 14-dňové bezplatné obdobie pri registrácii |
| **Read-only mode** | Po expirácii triala — user sa prihlási a vidí dáta, ale nemôže CRUD |
| **Owner user** | `owner_user_id` na Gallery/Artist/Artwork/Collection — kto záznam vytvoril a má edit práva |

---

## 11. História verzií dokumentu

| Dátum | Verzia | Autor | Zmeny |
|---|---|---|---|
| 2026-06-24 | v0.1 DRAFT | Claude Code + Kat | Prvý draft po dohode o 6 zákl. otázkach |
| 2026-06-24 | **v0.2 DRAFT** | Claude Code + Kat | **Veľká revízia access matrixu:** Collector môže mať vlastnú databázu artists/artworks (private), Collections vytvárať aj Gallery/Artist, Inquiries odosielať/prijímať všetky role. Collector dashboard má rovnakú štruktúru ako Gallery okrem Exhibitions/Email/Invoices/PrivateRooms. Pridaná nová otvorená otázka o pokročilých Gallery features. |
