# Projects KPI card — In Zicht theming

**Date:** 2026-07-31
**Repo:** `adminpage` (branch `main`)
**Status:** approved, not yet implemented

## Goal

Bring `adminpage`'s Projects KPI card in line with the theming work done in
`../superadminpage`, so the two apps read as one product.

## Background: three of the four handover changes have no target

`THEMING_HANDOVER.md` asked for four superadminpage changes to be mirrored here.
Verification found that only one has a live target — the other three name
components that nothing in this app imports.

| Component | State | Consequence |
| --- | --- | --- |
| `ProjectsKpiCard.vue` | **live** | Bar + legend + dots as described. Handover changes 1 and 2 apply. |
| `KpiCard.vue` | **dead** | No `import KpiCard` anywhere; the four KPI cards are self-contained. The `icon` slot from `b557b57` would have no consumer. |
| `AlertsPanel.vue`, `AlertCard.vue` | **dead** | Unrendered, though `DashboardController.php:133` still ships an `alerts` payload from `AlertService`. Handover change 3 drops out. |
| `OrgHeaderBar.vue` | **dead** | Handover change 4 is a no-op. `Dashboard.vue` has no page header and no tabs. |

Decision: **leave the dead code and the unused `alerts` payload untouched.**
Reviving the alerts UI would be a feature addition, not a theming pass; deleting
the files forecloses that cheaply. Both are separate decisions for later.

## Background: the chip row is not a copy-paste

superadminpage's chips show three **task** states — done, overdue, open.
`ProjectsKpiCard.vue:109-145` shows four **project** statuses — Active, W.o.c.,
On Hold, Done. There is no overdue state and there is a fourth value, so the tone
mapping had to be decided rather than ported.

Three options were mocked up and reviewed. **Option A — four chips, one per
status — was chosen.** Rejected:

- *Three chips*, merging W.o.c. and On Hold into "paused": fits one line at every
  width, but invents a bucket the data does not have and makes those two statuses
  no longer separately drillable. That is a data-meaning change, which is more
  than a theming pass should decide.
- *Retone the bar only*: smallest diff and keeps the proportion-at-a-glance that
  chips lose, but leaves the two apps still looking different, which is the thing
  this work exists to fix.

## Scope

Two commits on `main`, both touching one file: `src/components/ProjectsKpiCard.vue`.

### Commit 1 — chips replace the bar and legend

Remove from template and `<style scoped>`: `projects-kpi__bar-container`,
`__bar`, `__bar-segment`, `__legend`, `__legend-item`, `__legend-dot`,
`__legend-text`.

The `segments` computed keeps its four entries and its `statusLabel` — that value
is what `filter-projects` emits and what the projects list filters on — and loses
`color` and `pct`, which nothing else reads.

Template becomes four chrome-less buttons:

```html
<button type="button" class="projects-kpi__chip"
        @click="$emit('filter-projects', seg.statusLabel)">
  <span class="iz-badge" :class="seg.badgeClass">
    <strong>{{ seg.value }}</strong> {{ seg.label }}
  </span>
</button>
```

Tone is an explicit property on each segment — **never** `'iz-badge--' + seg.key`:

| status | `badgeClass` |
| --- | --- |
| Active | `iz-badge--success` |
| W.o.c. | `iz-badge--warning` |
| On Hold | `''` (the neutral `.iz-badge` base) |
| Done | `iz-badge--accent` |

Also in this commit:

- `projects-kpi__hero-value` gains the theme's `.iz-figure` class, opting the
  numeral into Space Grotesk. **This is a deliberate change, not a bug fix.**
  superadminpage's `b557b57` fixed a silent fallback caused by a
  `[class*="__value"]` rule that missed `projects-kpi__hero-value`; `adminpage`
  has no such rule, so nothing is currently broken. Set the font via `.iz-figure`,
  never via a hardcoded `font-family`.
- The icon square moves from `rgba(74, 144, 217, 0.1)` / `#4a90d9` to
  `var(--iz-accent-bg)` / `var(--iz-accent-text)`.

The `v-else` "No projects yet" empty state is unchanged.

### Commit 2 — chip hover feedback

Ported from superadminpage `e4071fe`:

- `:hover` on the badge only: `transform: translateY(-1px)` and
  `filter: brightness(0.96)`, with a `transform`/`filter` transition. No colour
  tokens change.
- `:focus` and `:active` explicitly reset to transparent background, no box-shadow,
  no outline — so a mouse click leaves nothing persisted.
- `:focus-visible` gets `outline: 2px solid var(--iz-accent); outline-offset: 2px`.
  superadminpage's rule uses `var(--accent)`, which does not exist in this app.
- All chip selectors qualified as `button.projects-kpi__chip`. Nextcloud core
  styles bare `button` at specificity 0,1,1 and beats a plain class.

## Explicitly out of scope

- **Handover changes 3 and 4** — no live target (see table above).
- **`KpiCard.vue`'s `icon` slot** — no consumer; porting it would add an API
  nothing calls.
- **`.iz-app` on the dashboard root.** `.iz-badge` is unqualified in the theme and
  works without it, and `button.projects-kpi__chip` is qualified on the element.
  Adding `.iz-app` would activate the theme's `.iz-app`-scoped button and chip
  rules across the entire dashboard — a far larger blast radius than this change
  earns.
- **The other 24 components**, which stay on the old hardcoded palette.
- **`PublicDashboard.vue`** — not modified. It renders `ProjectsKpiCard`, so it
  inherits both commits and must be verified, but its own shell and palette are
  not converted.

## Dark mode is not fixed by this work

An earlier framing of this task claimed a "foundation" commit would unblock dark
mode by removing the `background-color: #f0f1f5 !important` rules at
`Dashboard.vue:246-253`. **It would not.** Line 283 paints the
`.adminpage-dashboard` container itself with `background-color: var(--bg-page)`
= `#f0f1f5`, so the dashboard stays light regardless of those two rules.
Functioning dark mode requires the 24 panels that hardcode `#fff` surfaces and
`#1a1a2e` text — that is the full migration, which was declined for this pass.

There is therefore **no foundation commit**. Consequence to watch: the chips
*will* adapt to dark mode, because every `--iz-*-bg` token is a `color-mix`
against `--color-main-background`. On a card whose background is forced light,
that produces dark-tinted chips on a white surface. This must be looked at in the
browser in dark mode and reported honestly; if it reads as broken, the fallback is
to raise it rather than to paper over it with a hardcoded colour.

## Verification

After each commit:

1. `npm run build` — required after any `src/` change.
2. Hard-refresh (`Ctrl+Shift+R`). The app bundle has **no `?v=` cache-buster**, so
   a normal reload serves stale JS. Alternatively clear the cache over Playwright
   CDP (`Network.clearBrowserCache`).
3. Check the authenticated dashboard at
   `http://nextcloud.local:8080/index.php/apps/adminpage/` (`admin` / `Admin12345!`)
   in **light and dark**, at **two viewport widths** (the KPI strip regrids at
   1200px and 768px).
4. Check the **public** dashboard renders — it shares `ProjectsKpiCard`.
5. Confirm each chip still filters the projects list by its status, and that
   clicking one leaves **no** persistent highlight.
6. Prefer reading computed styles over eyeballing a screenshot; confirm the change
   actually rendered before calling it done.

## Risks

- **Stale bundle** masking a change, or masking a regression. Mitigated by step 2.
- **NC core button styling** overriding chip resets. Mitigated by qualifying every
  chip selector on `button`.
- **Badge legibility**: `.iz-badge` pairs a tint background with a separate solid
  text colour. Using one token for both renders invisible text — that has shipped
  twice in this codebase's sibling. Use the theme's `--*-bg` / `--*-text` pairs and
  do not hand-mix.
- **The neutral On Hold chip** is the lowest-contrast of the four by design. Verify
  it reads as a status and not as disabled.
