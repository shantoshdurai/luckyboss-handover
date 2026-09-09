# Lucky Boss — the business model

Read from **`Lucky Boss Portal Functional Specification.pdf`** (49 pages, 100
sections), against TickBig and against what is now built. Section numbers below
are sir's own, so any of this can be checked against the source.

---

## 0. The headline

**Sir's spec already contains a complete, coherent business model — and it is a
better one than TickBig's.** It has five revenue streams, an admin switch on
every one of them, and it deliberately starts with the candidate side free.

TickBig sells **consumables** (buy 15 applies, cart, checkout). Sir's spec sells
**recurring packages** segmented by company type, grade and country (§33–§36),
with consumable credits as *add-ons on top* (§66). Those are different
businesses. The spec's is the right one for this market, and my earlier open
question — "recurring or packs?" — is answered by it: **both, in that order.**

One line from §69 deserves to be quoted back whenever LinkedIn/Naukri comes up,
because it is sir's own instruction:

> Do not design the business model around unauthorized scraping.

---

## 1. The five revenue streams in the spec

| # | Stream | Where | Who pays | Status today |
|---|---|---|---|---|
| 1 | **Employer subscription packages** | §33–§38 | Employer | **Built 2026-09-07** — 3 tiers × SGD/INR/MYR, plan allowances, §38 expiry |
| 2 | **Candidate contact credits** | §71–§73 | Employer | SKU + §71 allowances built; **§72 reveal confirm and §73 organic rule still to build** ← next |
| 3 | **AI credits / AI tiers** | §4, §8, §12 | Employer | Metering built; **§67 cost accounting built 2026-09-07** — tier not yet sold |
| 4 | **Job promotion** — Featured, Urgent, Sponsored, Apply Soon | §61 | Employer | Not built |
| 5 | **Paid Apply** — per-job application fee | §62–§63 | Candidate | Deliberately **off**; §62 says start OFF |

Plus one cost-avoidance lever, not revenue:

- **BYOAI** (§5–§6, §99) — an employer plugs in their own OpenAI key. Lucky Boss
  stops paying for that employer's AI while still selling them everything else.
  Already built.

### Stream 1 — the base: recurring packages

§33 defines a package across **nineteen** dimensions, not three: company type,
grade, country, currency, employer users, active jobs, job posting limit,
candidate view limit, resume download limit, contact view limit, email limit,
WhatsApp limit, interview limit, featured jobs, AI features, AI usage, external
candidate access, reporting, validity.

Packages are then **filtered at registration** by country + company type (§36),
and admin can override any of it manually (§37) — including SGD 0 for a
promotional trial, which is how sales actually gets done.

§64 gives sir's own price anchor, and explicitly rejects live FX:

> Professional: SGD 299 / INR 18,000 / MYR 999
> This is better than forcing real-time currency conversion.

### Stream 2 — the sharpest lever in the whole document

§71–§73 is the best commercial idea in the spec, and it is easy to miss.

- Contact views are metered (§71: Starter 20/month, Professional 250, Enterprise custom).
- Revealing a contact shows a confirm — "*This will use 1 candidate contact credit. Continue?*" (§72).
- **But §73: if the candidate applied to that employer directly, phone and email
  are free.** Credits are only consumed for candidates *Lucky Boss recommended*
  or sourced externally.

That rule is worth defending. It charges for the value Lucky Boss adds — finding
someone the employer could not — and never charges for what the employer earned
on their own. It is fair, it explains itself in one sentence to a customer, and
it scales with our usefulness rather than with their headcount.

### Stream 4 — the highest margin, and the cheapest to build

§61: Featured, Urgent, Sponsored, Apply Soon, with start/end date, priority and
home-page position. Near-zero marginal cost, a purchase employers already
understand from every job board, and it needs no AI and no gateway complexity
beyond what streams 1–2 need anyway. **It is not built at all**, and it is
probably the best effort-to-revenue ratio on this list.

### Stream 5 — the one I would keep switched off

§62 is unambiguous:

> Candidate Monetization OFF. Initially all standard applications remain free.
> Later Admin switches ON.

This matches exactly what sir told me on 2026-09-07 and exactly what is built.
My recommendation is to **hold the switch and not pull it**, for a marketplace
reason rather than a moral one: our candidates are electricians, warehouse
staff, drivers, maids. They are the scarce side of this market and the side with
no budget. Charging them shrinks the candidate pool, which is the only thing
that makes the employer product worth buying. Every rupee taken from that side
costs more than a rupee on the other.

The lever still exists per §63 (job-level `Paid Apply`, currency, fee), so if a
premium employer wants a paid-application filter to cut noise on one specific
job, we can do it without a rebuild.

---

## 2. Spec vs TickBig vs what we built

| Question | Sir's spec | TickBig | Built today |
|---|---|---|---|
| Base model | Recurring packages (§33) | Consumable packs + cart | Ledger supports both |
| Segmentation | Type × Grade × Country (§34–36) | None visible | 3 tiers × 3 countries; type/grade columns unused |
| Credits | Add-on on top of package (§66) | *Is* the product | Built |
| Free tier | Package-defined limits | Monthly free tier per SKU | Built (monthly) |
| Candidate pays | Off, switchable (§62) | Institutions pay to bulk-apply | Off, switchable ✓ |
| On expiry | Restrict, don't lock out (§38) | Unknown | **Built** — gated on the charging switch |
| Currency | Manual per market (§64) | INR only | **Built** — 9 stored prices, no live FX |

**Where TickBig is genuinely better and worth copying:** their *Name / Remaining*
table — which is now built — and putting billing inside account settings rather
than on a marketing page. **Where the spec is better:** everything about how the
package itself is shaped.

---

## 3. Recommendation

**Sell to employers on a recurring package; meter the two things that cost us
money; sell promotion as pure margin; keep candidates free.**

Concretely, in the order I would build:

1. ~~**Package matrix (§33–§36).**~~ **Done 2026-09-07** — three tiers priced in
   SGD/INR/MYR from the §64 anchor, package allowances issued monthly into the
   ledger and expiring with the plan. Company type/grade segmentation deferred.
2. ~~**Expiry behaviour (§38).**~~ **Done 2026-09-07** — the recruitment surface
   closes, billing and renewal stay open, and the whole thing is gated on the
   charging switch so it changes nothing until we mean it.
3. **The §73 contact rule.** ← *next* Split `candidate_view` into "organic — free" and
   "sourced — 1 credit", with the §72 confirm before the credit is spent.
4. **Job promotion (§61).** Cheapest build, highest margin.
5. ~~**AI accounting (§67) before selling any AI tier.**~~ **Done 2026-09-07.**
6. **Payment gateway last.** Every stream above works with admin-granted credits
   and an invoice, which is how B2B recruitment in SG/MY is actually paid anyway.

### Pricing shape

Use sir's own anchor from §64 (Professional SGD 299 / INR 18,000 / MYR 999) as
the middle tier, priced **per market, manually, never converted live**. Three
tiers, matching the AI matrix already drawn in §12:

- **Starter** — no Lucky Boss AI, BYOAI allowed, small contact allowance.
- **Professional** — the §12 numbers: 20 interview letters, 10 offer letters,
  100 AI candidate searches, 250 contact views.
- **Enterprise** — custom, manually assigned (§37).

I am not proposing numbers of my own. §12 and §64 are sir's; they need his
confirmation, not my invention.

---

## 4. What was blocking the model — now cleared

**AI was an uncapped cost with no attribution.** §67 requires every AI call to
record employer, user, job, candidate, feature, provider, model, **tokens**,
**estimated cost**, date and status — "critical for billing and auditing".
`ai_usage_log` had seven columns and none of the money ones.

**Built 2026-09-07.** Every Gemini call now records its model, prompt and
completion tokens, and an estimated USD cost, and `/admin/entitlements` carries
the §79 AI Cost view — this month, last month, per feature, failures, and BYOAI
calls counted separately so an employer's own spend is never booked as ours.

A real bug surfaced while wiring it: `ai_usage_log.company_id` was **NOT NULL**,
so every seeker-side AI call — resume parsing and the copilot, the two that cost
us money on the free side — threw on insert and recorded nothing. The write is
wrapped in a try/catch so accounting can never break the feature it measures,
which is exactly why nobody had noticed. Fixed in the same migration.

**Remaining gaps:** the §72 reveal confirm and §73 organic-free rule (next), job
promotion (§61), company type/grade segmentation, and a payment gateway.

---

## 4a. Decisions taken 2026-09-07, and what was built on them

Shantosh selected: **build packages + expiry next**, **3 tiers × 3 countries**,
**adopt the §64 price anchors now**, and **Paid Apply stays off**.

Built on that same day:

- **Three tiers priced in three markets.** Professional carries §64's own anchor
  (SGD 299 / INR 18,000 / MYR 999); Starter and Enterprise are scaled from it and
  rounded to figures that read naturally per market. All admin-editable. Package
  allowances aligned to the spec's numbers — §71's contact views (20 / 250 /
  custom) and §12's AI limits — replacing the seeded guesses.
- **Packages now become real balances.** `Package.entitlements` was a JSON blob
  nothing spent: admin could sell a plan with 25 job posts and the employer's
  balance would not move. A `plan_grant` is now issued once per month, expiring
  with the month **or with the subscription, whichever comes first**. That expiry
  is the entire difference between a plan allowance and a purchased pack, and it
  answers §5.3.3 below in code: plan allowance burns, bought credits carry.
- **Expiry behaviour (§38).** An expired employer keeps their dashboard, billing,
  subscription and profile, and loses applicants, candidate contacts and
  interview history. A dead `SubscriptionCheck` middleware already existed,
  attached to no routes and with no enforcement guard — wiring it up as it stood
  would have locked every current employer out of their own candidates. It is now
  gated on the same charging switch and named per route.
- **AI cost accounting (§67).** `ai_usage_log` gained job, candidate, provider,
  model, prompt/completion tokens, `estimated_cost_usd` and status, with a single
  `AiUsageRecorder` so the arithmetic is identical everywhere. BYOAI spend is
  recorded but costed at zero to us — an employer on their own key is our
  cheapest customer, not our most expensive. **This was the blocker on selling any
  AI tier**, and it is now cleared.

Unlimited is represented as `null` throughout; the seeded Enterprise plan's `-1`
is converted at the boundary. A finite total cannot express unlimited, and a
large placeholder starts refusing people on the day they reach it.

## 5. Doubts — what I need from sir

1. **Confirm the base is recurring, not packs.** §33 says recurring packages;
   TickBig's screens (which prompted this) sell consumables. I have read the spec
   as: recurring package for access and limits, consumable credits as top-ups.
   The ledger supports both, so this is a pricing decision, not an architecture
   one — but the sales story is completely different and needs deciding once.
2. **Prices per market.** §64's SGD 299 / INR 18,000 / MYR 999 — is that current,
   or an illustration from when the spec was written?
3. **On expiry, do unspent credits burn or carry?** §38 covers *access* but not
   *balances*. My proposal: package allowance burns with validity; separately
   purchased add-on credits carry over. That distinction already exists in the
   ledger as a nullable expiry on each grant.
4. **The §73 organic-free rule — confirm.** It costs us revenue on every direct
   applicant and I think it is worth it. Worth sir agreeing explicitly, because
   it is money.
5. **Company Type × Grade × Country in v1, or start simpler?** The full matrix is
   §33–§36, but three tiers × three countries would launch far sooner and can
   grow into the matrix without rework.
6. **Ratings / Testimonials on the candidate profile** — worth knowing this is a
   **TickBig idea, not in sir's spec at all**. It appears nowhere in the 100
   sections. Still worth building eventually, but it is not a spec obligation and
   should not displace anything above.
7. **Paid Apply stays off?** My recommendation is yes, indefinitely. Confirming it
   once means we stop revisiting it.

---

## 6. Where the spec and our build already agree

Worth recording, because it means no rework:

- **Candidate monetisation starts off and is a backend switch** — §62, and sir's
  2026-09-07 instruction, and `EntitlementCatalogue::enforcementEnabled()`.
- **Every commercial feature is admin-controlled** — §3 lists 24 switches
  including "Job Seeker Payment", "Paid Job Applications", "Employer
  Subscription", "Employer Contact Credits". This is the spec's stated *most
  important design principle* (§1), and it is what the enforcement switch and the
  job-matching screen follow.
- **Server-side enforcement only** — §93. Hiding a button in Flutter is not a
  control, which is why the AI gates and the entitlement checks are all in
  Laravel.
- **No scraping-based model** — §69.
- **Manual multi-currency, no live FX** — §64; `PackagePrice` is already shaped
  for it.
