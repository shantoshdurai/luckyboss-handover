# TickBig → Lucky Boss: adaptation plan

Written 2026-09-07, after sir's 2026-09-04 voice note ("இது அப்படியே நம்ம Concept Already அவங்க Implement பண்ணி இருக்கிறாங்க") and his follow-up that we may take UI inspiration from them, not just mechanics.

## 0. Basis of this analysis — read this first

I could **not** browse tickbig.com. The Claude-in-Chrome extension is not connected to this session, and the in-app browser blocks the domain. Everything below is derived from:

- the **four screenshots** provided (AgentAmbo chat home, Subscription, Your Current Plan, Profile + Create Your Resume modal),
- the voice note transcript,
- our own codebase.

So this covers their **monetisation, profile, resume and AI-entry models** with confidence. It does **not** cover their job feed, match display, search filters, application tracker or employer side — I have not seen those pages. Section 7 lists what to re-check once browsing works.

---

## 1. What TickBig actually does

### A. Monetisation is per-action credits, not subscription tiers

They do not sell Bronze/Silver/Gold. They sell **six consumable SKUs** with a quantity and a price, bought through a **cart**:

| SKU | Qty | Price |
|---|---|---|
| Create Job (Manual) | 1 | ₹1,300 |
| Apply Job (Manual) — *on behalf of Institutions* | 15 | ₹1,500 |
| Create Project | 1 | ₹3,500 |
| Apply Project | 15 | ₹1,500 |
| Create staff Augmentation | 1 | ₹5,000 |
| Apply Staff Augmentation | 15 | ₹2,500 |

Two things matter more than the prices:

1. **"(Manual)" is in the SKU name.** They have reserved the namespace for an automatic variant. This is exactly the manual-apply-now / auto-apply-later split sir described.
2. **"Apply" is a paid action bought in blocks of 15, on behalf of institutions.** Their bulk-apply is a *billable employer/institution action*, not a free candidate action.

Below the grid sits a **Monthly Free Tier Offer** — the same SKU list with a per-month free allowance (e.g. Create Job — 1 Nos per Month, ticked).

### B. "Your Current Plan" is a balance sheet, not a badge

A second tab renders one plain table: **Name / Remaining** — "Create Job (Manual) … 1 Left", "Apply Project … 5 Left". No charts, no plan name, no upgrade nag. The user's question is *"how many do I have left"* and the page answers exactly that.

Billing lives inside account settings (left rail: Connections, Subscription, Purchase History, Admin, Security) — not as a separate marketing page.

### C. Profile is view-first and tabbed

Their profile is **not** one long form. It is:

- an identity block (name, headline "Student") with a small **Edit** affordance,
- section tabs across the top: **About · Skills · Achievements · Testimonials · Ratings**,
- a **Resume Builder** card pinned above everything: "Create a job-ready resume in…" with a single **✦ Create Resume** button.

Reading is the default state; editing is something you enter deliberately, per section. Achievements/Testimonials/Ratings turn a CV into social proof.

### D. Resume has two doors, offered as a modal

**Create Your Resume — "Choose how you want to start building your resume."**

- **Build Resume from Scratch** — guided builder. *Primary, filled green.*
- **Upload Existing Resume** — PDF or DOC, "use it for job applications." *Secondary, outlined.*

This is precisely sir's "Resume Upload பண்ணி autofill, இல்ல manually adding". Note the hierarchy: the *builder* is primary, upload is the escape hatch. For our blue-collar users I would invert that — see W1.

### E. One AI entry point, routed by intent

AgentAmbo's home is a chat with a greeting ("Hello, Santosh / What is our mission today?") and **four intent cards**: Find Clients? · Find Vendors? · Find a job? · Hire talent?. One AI, four doorways. It matches sir's standing rule of one AI entry point per screen.

---

## 2. Where we already stand

| Capability | Lucky Boss today | Gap |
|---|---|---|
| Resume upload + AI parse | ✅ `Api/V1/ResumeParseController` (Gemini, flag-gated, returns `requires_review`) | Not the *spine* of onboarding; it is one box on a long form |
| Match scoring | ✅ `JobMatchService` — refuses to guess, caps by evidence | Fine |
| Threshold + Apply All | ✅ `SeekerDashboardController@applyAll`, throttled, capped | **No admin screen** to edit the four values |
| Web seeker profile | ⚠️ `seeker/profile/edit.blade.php` — **396 lines, one mega-form**, all fields always in edit state | No view mode, no sections, no achievements/testimonials |
| Skills | ✅ Taxonomy (212 curated / 1068 relations), `/skills/search`, `/suggested`, `/related`, chip UI with custom entry | Web chips are decent; app parity uneven |
| Resume *builder* (from scratch) | ❌ Does not exist | We only accept a CV, we cannot produce one |
| Employer entitlements | ⚠️ `SubscriptionEntitlementService::allows()` is a **boolean gate only**. `Package`/`Subscription` both carry a JSON `entitlements` column | No consumption, no balance, no purchase, no cart, no history |
| Free tier | ❌ | Nothing decrements or resets monthly |
| AI entry | ✅ `POST /api/ai-chat` copilot | No intent cards |

The good news on billing: `Package.entitlements` and `Subscription.entitlements` are already JSON blobs, and `OperationsController` already writes `['job_posts'=>n, 'candidate_views'=>n, 'ai_matching'=>bool]`. The *shape* for a credit model is there. What is missing is metering.

---

## 3. The plan — five workstreams

Throughout: **adopt their structure, render it in our language.** They are dark; we are light on purpose (candidates outdoors, cheap phones — see CLAUDE.md). Use the navy/emerald/`--color-surface` tokens; never hardcode neutrals.

### W1 — Resume-first onboarding — **DONE 2026-09-07 (web)**

Shipped at `/job-seeker/resume` → `/review` → `/matches`:
`Seeker/ResumeIntakeController`, `Services/ResumeIntakeService` (the Gemini
pipeline, lifted out of `Api/V1/ResumeParseController` so web and app run the
identical code), three views under `seeker/resume/`, and
`tests/Feature/SeekerResumeIntakeTest.php` (10 tests). The readiness prompt and
the seeker sidebar now route here.

Held lines, verified by test:
- **Nothing extracted reaches the profile until the candidate confirms it.**
- **The file is kept in every outcome** — autofill off, no API key, unreadable.
- Skills are written to both `candidate_profiles.skills` and
  `resume_data['skills']`, because `JobMatchService` reads both.

Also fixed in passing: Apply All offered "Apply to all 2" when both matches had
already been applied to. The shared partial now takes `applyAllCount`.

**Still to do:** the Flutter seeker app equivalent of these three screens.

*Original scope, for reference:*

Sir: *"Resume Upload பண்ண உடனே அது parsing பண்ணிட்டு calculate பண்ணிடுது. எந்த Job என்ன role அதுக்கு relevant Job எல்லாமே காட்டுது."*

Build a dedicated **`/job-seeker/resume` route** with TickBig's two-door modal as the entry, but **with our order of priority inverted**:

1. **Upload your CV** — *primary*. PDF/DOC → `ResumeParseController` → a **review screen** showing every extracted field beside an edit box. `requires_review: true` is contractual: never save silently.
2. **Fill it in myself** — *secondary*. Routes into the existing wizard steps.

Why inverted: an electrician with a one-page CV on WhatsApp is our modal user; a student with no CV is theirs.

Then the payoff, immediately after review is confirmed: **"We found 14 jobs above 70% for you"** → the match list → **Apply All**. Do not send them back to a dashboard to discover it. The whole point of the voice note is that upload → parse → matched jobs → one-tap apply is *one continuous motion*.

Files: new `resources/views/seeker/resume/` (choose, review, results), reuse `seeker/partials/match-state.blade.php`, reuse `applyAll`. Flutter: same three screens against `/api/v1/resume/parse` + `/api/v1/job-seeker/dashboard`.

**Guard rail that stays:** matching still refuses to score a thin profile. If the parse yields little, the results screen asks for what `readiness().missing` names — it must not fall back to showing unmatched jobs at a fake 45%.

### W2 — View-first profile — **DONE 2026-09-07**

`/job-seeker/profile` is now a page you read: identity block, completion **ring**
(recomputed live, not read from the stale stored column), resume card pinned on
top, and section tabs — About · Experience · Skills · Documents · Preferences —
each with an Edit that opens the editor at that section. The 396-line form moved
behind it at `/job-seeker/profile/edit`. `tests/Feature/SeekerProfilePageTest.php`.

Achievements / Testimonials / Ratings are deliberately **not** shipped — see §5.3.3.

**Still to do:** per-section inline editing. Today each pencil hands off to the
long form rather than opening that section in place.

*Original scope, for reference:*

Replace the 396-line always-editing form with:

- **Header card**: photo, name, professional title, location, a **completion ring** (not a bar — standing preference), and one `Edit` button.
- **Resume card** pinned top, mirroring theirs: current CV filename + date, or the two-door CTA if none.
- **Section tabs**: `About · Experience · Skills · Documents · Preferences`. Each section renders read-only with a pencil that opens *that section only*.

On the extra tabs they have — **Achievements, Testimonials, Ratings** — take **Ratings/Testimonials only when there is something real to put in them.** An empty "Testimonials" tab is the fake-data failure mode this project keeps repeating. Proposal: derive ratings from completed placements (employer rates the candidate after an offer is accepted), and ship the tab in the same release as that flow — not before.

Keep the existing `PUT /job-seeker/profile` endpoint; this is presentation only, the same way the registration wizard was.

### W3 — Admin screen for the four matching values — **DONE 2026-09-07**

Shipped at `/admin/job-matching` (Settings & Branding → Job Matching & Apply All):
`app/Http/Controllers/Admin/JobMatchingController.php`,
`resources/views/admin/job-matching/edit.blade.php`,
`tests/Feature/AdminJobMatchingTest.php` (6 tests). Verified end to end in the
browser: saving 80 / 15 was read back by `SiteSettingsService::matching()`, then
restored to the 60 / 25 defaults.

Design notes worth keeping:
- Its own route, not folded into `SiteSettingsController`, because that
  controller's `update()` is CSRF-exempt — not a property to give the switch that
  can auto-apply on a candidate's behalf.
- The auto-apply toggle is labelled **"Not wired up yet"** on the screen itself,
  because nothing reads it. A switch that looks live and does nothing is the same
  defect as a fabricated match score.
- Threshold capped at 95, with the reason shown inline: our scorer caps a match by
  how much of the profile it could compare, so 100 empties the list.

*Original scope, for reference:*

`SiteSettingsService::matching()` reads `minimum_match_score` (60), `bulk_apply_enabled`, `bulk_apply_limit` (25), `auto_apply_enabled` (false) from `AdminRecord` module `matching`, slug `job-matching`, and currently always falls back to defaults because nothing writes them.

Sir explicitly asked for this: *"80% மேல fit ஆகுற Job, 90% மேல fit ஆகுற Job அப்படிங்கற மாதிரி நாம Admin setup பண்ணிடுவோம்."*

Build one admin form under Site Settings → Job Matching with those four fields. Half a day. It unblocks his ability to demo threshold behaviour live.

`auto_apply_enabled` stays **off** and stays labelled "(Manual)" everywhere, as TickBig does — we turn it on only after someone has watched a run.

### W4 — Entitlement ledger — **DONE 2026-09-07 (metering; pricing UI pending)**

Shipped: `entitlement_ledger` table (polymorphic owner, so seekers meter on the
same path), `EntitlementLedger`, `EntitlementCatalogue` (7 SKUs),
`SubscriptionEntitlementService` grown `balance()` / `consume()` / `grant()` /
`summary()` / `history()` beside the original `allows()`. Consumption is wired
into seeker apply, Apply All, resume parsing, and employer job posting.
`tests/Feature/EntitlementLedgerTest.php` (10) and `EntitlementScreensTest.php` (8).

Screens: **employer** `/employer/subscription` — three tabs, with TickBig's plain
Name / Remaining table as the default; **admin** `/admin/entitlements` — grant
credits, see all employer balances, and the charging switch.

The switch is the important part. **Enforcement defaults to off**, and while it
is off every action is still recorded but nothing is ever refused. That is what
makes sir's "zero rupees at the start, change it in the backend later" a settings
change rather than a release. Seeker SKUs are priced 0 with allowance `null`
(unlimited) and show no billing furniture anywhere in the seeker UI.

**Still to do:** prices per market (§5.3.2), the cart and a payment gateway. No
Buy button ships until it can take money — the page says credits come from your
account manager, which is currently true.

*Original scope, for reference:*

Per §5.1 this covers **both sides** — employers at their real prices, seekers at zero
through the identical code path. The design:

1. **Define SKUs as entitlement keys.** Employer: `job_post`, `bulk_apply`,
   `candidate_view`, `ai_match`, `project_post`, `staff_aug_post`. Seeker (all priced
   0, allowance `null`): `apply`, `bulk_apply`, `resume_parse`, `ai_chat`.
2. **New table `entitlement_ledger`** — `owner_type`/`owner_id` (a company **or** a
   candidate — not `company_id`, or seekers cannot be metered), `key`, `delta`,
   `source` (`purchase|plan_grant|free_tier|admin_grant|consumption`),
   `reference_type/id`, `expires_at` (nullable — see §5.3.1), `period`, timestamps.
   Balance = `SUM(delta)` over unexpired rows. A ledger, not a counter column, so every
   charge is explainable — which matters the first time an employer disputes one, and
   it is what gives sir the usage data he needs before pricing seekers.
3. **`SubscriptionEntitlementService` grows two methods** alongside `allows()`:
   `balance($owner, string $key): ?int` (**`null` = unlimited**, never a large integer)
   and `consume($owner, string $key, int $n, $reference): bool` — atomic, refuses below
   zero, never partially consumes a bulk action. An unlimited key still writes its
   consumption row.
4. **Monthly free tier**: a `free_tier` grant written per company per calendar month on first use of the period. Their model exactly — and it means billing genuinely "starts at zero" as agreed.
5. **Employer UI**, two tabs under a settings rail (their layout, our colours):
   - **Subscription** — SKU cards with qty/price and Add to cart.
   - **Your Current Plan** — the Name/Remaining table, verbatim in concept.
   - **Purchase History** — reads the existing `Payment`/`Invoice` models.
6. **Enforcement at the point of action**, not at page load: `consume()` inside the job-post and bulk-apply controllers, in the same transaction as the action.

**Explicitly out of scope:** payment gateway integration. Ship the ledger with admin-granted credits first; wire a gateway once pricing is signed off.

### W5 — Intent cards — **DONE 2026-09-07**

The Lucky AI drawer's opening message now offers four cards — Find a job ·
Improve my resume · Hire talent · My applications — instead of a flat list of
suggested questions. Written in Blade, not `resources/js`, so they can change
without a bundle rebuild; they call the component's existing `sendSuggestion()`,
so a card is exactly a typed question.

*Original scope, for reference:*

Give `/api/ai-chat`'s front end four cards on its empty state. Ours are not their four — we are recruitment only, both sides:

**Find a job? · Improve my resume? · Hire talent? · Ask about an application?**

Each card sends a seeded first message. Cheap to build, and it fixes the blank "what do I type" problem. Keep it to **one** AI entry point per screen.

---

## 4. What we deliberately do not copy

- **Their dark theme.** Settled: light only, navy-tinted greys.
- **Visibly charging job seekers.** Their "Apply Job" SKU is billed to institutions applying on behalf of others. Seeker actions are **priced at zero**, not hardcoded free — see §5.1. Nothing in the seeker UI mentions price, credits or balance while every SKU is zero.
- **Empty social-proof tabs.** See W2.
- **Anything that touches LinkedIn / Naukri / Monster.** The voice note describes those sites opening listings from LinkedIn/Naukri and auto-applying. We do not. Scraping their listings and submitting forms as the user breaches their terms and gets candidates' accounts banned. Every match and every Apply All stays inside our own job table. This is not a technical limit — it is the decision from 2026-09-04, and it should be repeated to sir when he points at their coverage.

---

## 5. Decisions

### 5.1 Billing model — **ANSWERED 2026-09-07**

Sir's decision:

> Employers are charged, subscription-based. Seekers are free — but "in a way like
> zero rupees at the start", so the backend can be changed any time to roll them
> onto paid without rework. A precaution.

This is a stronger requirement than "seekers are free", and it dictates W4's shape:

- **Seeker actions run through the same entitlement path as employer actions**, with
  their SKUs priced at **0** and granted an unlimited allowance. There is no
  `if (user is seeker) skip billing` branch anywhere. That branch is precisely what
  would make the later flip a rewrite instead of a settings change.
- **Meter from day one, even at zero.** `consume()` writes a ledger row whether the
  price is 0 or 1,300. When sir wants to switch seekers to paid, he will need to know
  what the real usage looks like first — and we will already have a year of it.
- **Unlimited is `null`, not a big number.** Flipping to paid means writing an integer
  where a null was. A seeded 999,999 would silently start refusing people one day.
- **Zero prices stay invisible.** While every seeker SKU is 0, the seeker UI shows no
  price, no credit balance, no "0 remaining". A candidate must never see billing
  furniture for something that is free. Turning it on later is a UI change we make
  deliberately, not something that leaks out on its own.

### 5.2 Design language — **ANSWERED 2026-09-07**

Same design and structure as TickBig, rendered in **light mode**, not their black.
Our adaptation, our tokens. This was already the plan (§3 preamble, §4) and is now
confirmed.

### 5.3 Still open

> **Update 2026-09-07:** the functional specification PDF answers several of
> these. §33–§36 define the base as **recurring packages segmented by company
> type × grade × country**, with consumable credits as add-ons (§66) — so
> "recurring or packs" is *both, in that order*. §62 confirms candidate
> monetisation starts off. §64 gives price anchors and rejects live FX. Full
> reading in **`BUSINESS_MODEL.md`**, which supersedes the pricing questions below.

1. **"Subscription-based" — recurring plan, or consumable packs?** These are not the
   same thing and sir may have used the word loosely. TickBig sells **packs** (buy 15
   applies, cart, no expiry visible). Our `Subscription` model is **recurring**
   (`starts_at`, `expires_at`, `validity_days`). *My proposal: the ledger supports
   both without choosing* — a grant row carries an optional `expires_at`, so a monthly
   plan writes expiring grants and a purchased pack writes non-expiring ones. Then
   this becomes a pricing decision, not an architecture one, and it can change per SKU.
   **What I do need:** when an employer's monthly plan lapses with unspent credits —
   do they burn or carry over? (My default: plan grants burn, purchased packs carry.)
2. **Prices and free-tier allowances** for the six employer SKUs, per market (SG/MY/IN).
   `PackagePrice` already supports per-currency rows.
3. **W2's Ratings/Testimonials** — build the employer-rates-candidate flow, or drop
   those two tabs for now?
4. **Which seeker actions get a metered SKU now**, so they are flip-ready. My list:
   `apply`, `bulk_apply`, `resume_parse`, `ai_chat`. The last two are the honest
   candidates for eventual charging because they cost us real Gemini spend per call;
   the first two cost us nothing and would be the last things to ever price.

## 6. Recommended order

1. ~~**W3** — admin matching screen.~~ **Done 2026-09-07.**
2. ~~**W1** — resume-first onboarding (web).~~ **Done.** Flutter still to do.
3. ~~**W2** — profile rebuild.~~ **Done.** Per-section inline editing still to do.
4. ~~**W5** — intent cards.~~ **Done.**
5. ~~**W4** — the entitlement ledger.~~ **Done**, with enforcement off. Prices and
   checkout wait on §5.3.1 and §5.3.2.

## 6a. What is left after this pass

- **Flutter**: the seeker app has none of W1 or W2 yet.
- **Per-section inline editing** on the profile (W2).
- **Prices, cart, gateway** (W4) — blocked on sir.
- **Ratings / Testimonials** — blocked on the decision in §5.3.3.
- **Bug found while building**: the prebuilt CSS bundle silently drops any Tailwind
  class no view was using on 2026-08-27. Recorded in CLAUDE.md; it bit three times
  during this pass (`right-3`, `sm:block`, `bg-amber-400`).

## 7. To re-check when browsing works

Fix: install/connect the Claude in Chrome extension (https://chromewebstore.google.com/detail/fcoeoabgfenejglbffodgkkbkcdhcgfn) and sign in to the side panel with the same account. Then walk and capture:

- the **job feed** — how match % is displayed per card, and whether there is an Apply All equivalent,
- the **resume builder** itself — how many steps, what it asks, what it outputs,
- **search + filters**,
- the **application tracker** — statuses and what the candidate sees,
- the **employer/institution** side of Create Job and bulk Apply,
- **Purchase History** and the cart/checkout,
- what the **free tier** resets look like mid-month.
