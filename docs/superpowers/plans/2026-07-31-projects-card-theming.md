# Projects KPI Card Theming Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the Projects KPI card's hardcoded stacked bar and dotted legend with four In Zicht `.iz-badge` status chips, so `adminpage` and `superadminpage` share one visual language.

**Architecture:** One Vue 2.7 single-file component, `src/components/ProjectsKpiCard.vue`, is edited in two commits. Chrome (tint, radius, type scale, figure font) comes from `.iz-*` classes defined in the In Zicht Nextcloud theme, not from this app. Layout (the chip row's flex and gap) stays local to the component. No PHP, no data-shape, and no other component changes.

**Tech Stack:** Vue 2.7 (Options API only — no Composition API, NC32 compatibility), webpack 5 via `@nextcloud/webpack-vue-config`, In Zicht theme CSS (`themes/inzicht/core/css/server.css` §8), Playwright MCP for browser verification.

**Spec:** `docs/superpowers/specs/2026-07-31-adminpage-projects-card-theming-design.md`

## Global Constraints

- **This repo has no test framework.** `package.json` defines only `build`, `dev`, and `watch` — no runner, no linter, no test directory. There is no red/green TDD cycle available. Every task's verification is therefore a **build plus an assertion read out of the live browser** via Playwright MCP `browser_evaluate`. Do not invent a test framework; do not claim a test passed.
- **`npm run build` is required after any `src/` change.** Nothing renders until it runs.
- **The app bundle has no `?v=` cache-buster.** A normal reload serves stale JS. Before every verification, clear the cache (Playwright `browser_evaluate` cannot do this; navigate with a cache-busting query string, or use `Ctrl+Shift+R` semantics by calling `browser_press_key` after focusing the page). Confirm the new code is actually live before asserting anything about it.
- **Vue 2.7, Options API only.** No Composition API.
- **Never hardcode a colour or a font-size** in the chip work. Use `--iz-*` tokens and `.iz-*` classes.
- **Never build a class name from data.** No `'iz-badge--' + seg.key`. Tone is an explicit property on each segment object with a neutral fallback.
- **Anything on `<button>` needs a qualified selector** — `button.projects-kpi__chip`, not `.projects-kpi__chip`. Nextcloud core styles bare `button` at specificity 0,1,1 and beats a plain class. Qualify modifiers too.
- **Badges pair a tint background with a separate solid text colour.** The `.iz-badge--*` modifiers already do this. Never set both from one token.
- **Do not touch** `KpiCard.vue`, `AlertsPanel.vue`, `AlertCard.vue`, `OrgHeaderBar.vue` (all dead code), `Dashboard.vue`, `PublicDashboard.vue`, or any other component.
- **Do not add `.iz-app`** to any root element.
- **Branch is `main`.** Commit per task. **Do not push** — the user pushes.
- Commit messages end with:
  `Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>`

## File Structure

| File | Responsibility | Change |
| --- | --- | --- |
| `src/components/ProjectsKpiCard.vue` | The Projects KPI card: header, hero total, per-status breakdown, drill-down events | **Modified.** Template lines 39-68 and style lines 223-292 replaced; `segments` computed reshaped; two classes added. The only source file touched. |
| `js/adminpage-main.js`, `js/adminpage-public.js` (+ `.map`) | webpack build output, committed to the repo | **Regenerated** by `npm run build`. Commit alongside the source, as prior commits in this repo do. |

The component is 318 lines and single-responsibility. No split is warranted.

---

### Task 1: Bring up the dev instance and capture a baseline

Nothing can be verified until the Nextcloud dev instance is running and the current card has been recorded. This task produces a confirmed-working environment plus a "before" reference to compare against. No source changes, no commit.

**Files:**
- Modify: none
- Test: none (no test framework — see Global Constraints)

**Interfaces:**
- Consumes: nothing
- Produces: a running instance at `http://nextcloud.local:8080`, and a baseline screenshot later tasks compare against

- [ ] **Step 1: Check whether the containers are already up**

```bash
docker ps --format '{{.Names}}' | grep -E 'master-nextcloud-1|master-proxy-1|nc_pg'
```

Expected: three lines. If fewer, continue to Step 2. If all three, skip to Step 3.

- [ ] **Step 2: Start the containers**

```bash
cd /home/payboy/src/nextcloud-docker-dev && \
  docker compose up -d proxy redis database-mysql nextcloud && \
  docker start nc_pg
```

Then re-run the Step 1 check and confirm all three names appear.

- [ ] **Step 3: Confirm the app is enabled**

```bash
docker exec -u www-data master-nextcloud-1 php occ app:list | grep -A100 Enabled | grep adminpage
```

Expected: `adminpage` appears under the enabled list. If it appears under `Disabled`, run:

```bash
docker exec -u www-data master-nextcloud-1 php occ app:enable adminpage
```

- [ ] **Step 4: Log in and open the dashboard**

Using Playwright MCP:
1. `browser_navigate` to `http://nextcloud.local:8080/index.php/apps/adminpage/`
2. If a login form appears, `browser_fill_form` with user `admin`, password `Admin12345!`, then submit.
3. `browser_snapshot` to confirm the dashboard rendered and is not an error page or the "Admin access required" empty state.

If the empty state shows, **stop and report it** — the logged-in user does not own an org, and nothing below can be verified. Do not proceed.

- [ ] **Step 5: Confirm the In Zicht theme is active**

This is the precondition for every `.iz-*` class in this plan. `browser_evaluate`:

```js
() => getComputedStyle(document.documentElement).getPropertyValue('--iz-accent').trim()
```

Expected: a non-empty colour value. **If it returns an empty string, stop and report it** — the theme is not loaded, `.iz-badge` will render unstyled, and the whole plan is blocked.

- [ ] **Step 6: Capture the baseline**

1. `browser_take_screenshot` of the KPI strip region — save as `projects-card-before.png`.
2. `browser_evaluate` to record the current bar/legend structure:

```js
() => {
  const card = document.querySelector('.projects-kpi');
  return {
    hasBar: !!card.querySelector('.projects-kpi__bar'),
    legendItems: card.querySelectorAll('.projects-kpi__legend-item').length,
    heroFont: getComputedStyle(card.querySelector('.projects-kpi__hero-value')).fontFamily,
    cardHeight: Math.round(card.getBoundingClientRect().height),
  };
}
```

Expected: `hasBar: true`, `legendItems: 4`, `heroFont` containing `-apple-system` or `system-ui` (**not** Space Grotesk), and a `cardHeight` number. Record all four — Task 2 asserts against them.

---

### Task 2: Replace the bar and legend with four status chips

This is handover changes 1 and 3's font fix, landed together because the chip markup and the numeral class touch the same template block and are not independently reviewable.

**Files:**
- Modify: `src/components/ProjectsKpiCard.vue` — template lines 39-68, `segments` computed lines 109-145, styles lines 172-182 and 223-292
- Test: none (no test framework — verification is Steps 5-8)

**Interfaces:**
- Consumes: the running instance and baseline numbers from Task 1
- Produces: `.projects-kpi__chips` and `button.projects-kpi__chip` in the DOM, and `seg.badgeClass` on each segment object — Task 3 styles both

- [ ] **Step 1: Reshape the `segments` computed**

In `src/components/ProjectsKpiCard.vue`, replace the whole `segments` computed (lines 109-145) with:

```js
    segments: function () {
      // Tone is an explicit property, never `'iz-badge--' + key` — a class
      // built from data silently emits one that may not exist. 'On Hold' uses
      // the neutral .iz-badge base, which is also the fallback tone.
      return [
        {
          key: "active",
          label: "Active",
          statusLabel: "Active",
          value: this.active,
          badgeClass: "iz-badge--success",
        },
        {
          key: "waiting",
          label: "W.o.c.",
          statusLabel: "Waiting on Customer",
          value: this.waiting,
          badgeClass: "iz-badge--warning",
        },
        {
          key: "on_hold",
          label: "On Hold",
          statusLabel: "On Hold",
          value: this.onHold,
          badgeClass: "",
        },
        {
          key: "done",
          label: "Done",
          statusLabel: "Done",
          value: this.done,
          badgeClass: "iz-badge--accent",
        },
      ];
    },
```

`color` and `pct` are gone — they were read only by the bar and legend being deleted in Step 2. `statusLabel` is unchanged and still carries the drill-down filter value.

- [ ] **Step 2: Replace the bar and legend markup**

Replace template lines 39-68 (the `<!-- Stacked bar -->` comment through the closing `</div>` of `projects-kpi__bar-container`) with:

```html
    <!-- Status chips: one per project status, each a drill-down -->
    <div v-if="total > 0" class="projects-kpi__chips">
      <button
        v-for="seg in segments"
        :key="seg.key"
        type="button"
        class="projects-kpi__chip"
        @click="$emit('filter-projects', seg.statusLabel)"
      >
        <span class="iz-badge" :class="seg.badgeClass">
          <strong>{{ seg.value }}</strong> {{ seg.label }}
        </span>
      </button>
    </div>
```

Leave the following `<div v-else class="projects-kpi__empty">No projects yet</div>` exactly as it is.

- [ ] **Step 3: Add `.iz-figure` to the hero numeral**

Change template line 35 from:

```html
      <span class="projects-kpi__hero-value">{{ total }}</span>
```

to:

```html
      <span class="projects-kpi__hero-value iz-figure">{{ total }}</span>
```

Do **not** add a `font-family` declaration — the theme's `.iz-figure` owns it.

- [ ] **Step 4: Replace the bar and legend styles, and retone the icon**

Delete style lines 223-292 entirely — the `/* ── Stacked Bar ── */` comment through `.projects-kpi__legend-text strong { ... }` — and put in their place:

```css
/* ── Status chips ──
   Chrome (tint + text colour + geometry) is the theme's .iz-badge primitive;
   only the row layout is local. The button is deliberately chrome-less: the
   badge is the whole visual. Selectors are qualified on `button` because NC
   core styles bare elements at 0,1,1 and outranks a plain class. */
.projects-kpi__chips {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

button.projects-kpi__chip {
  padding: 0;
  margin: 0;
  border: 0;
  background: transparent;
  border-radius: var(--iz-radius-sm);
  cursor: pointer;
  font: inherit;
  -webkit-appearance: none;
  appearance: none;
}

.projects-kpi__chip .iz-badge {
  font-variant-numeric: tabular-nums;
}
```

Then, in the `.projects-kpi__icon` rule (lines 172-182), replace these two declarations:

```css
  background-color: rgba(74, 144, 217, 0.1);
  color: #4a90d9;
```

with:

```css
  background-color: var(--iz-accent-bg);
  color: var(--iz-accent-text);
```

Leave `.projects-kpi__new-btn`'s `#4a90d9` alone — it is out of scope for this pass.

- [ ] **Step 5: Build**

```bash
npm run build
```

Expected: exits 0. If webpack errors, fix the source and re-run before continuing.

- [ ] **Step 6: Load the new bundle and confirm it is not stale**

Using Playwright MCP, `browser_navigate` to
`http://nextcloud.local:8080/index.php/apps/adminpage/?cachebust=task2`, then `browser_evaluate`:

```js
() => ({
  chips: document.querySelectorAll('.projects-kpi__chip').length,
  barGone: !document.querySelector('.projects-kpi__bar'),
  legendGone: !document.querySelector('.projects-kpi__legend-item'),
})
```

Expected: `{chips: 4, barGone: true, legendGone: true}`. If `chips` is 0 and `barGone` is false, the browser is serving the **old bundle** — hard-refresh and re-run. Do not proceed on a stale bundle.

- [ ] **Step 7: Assert the chips are actually themed, not unstyled**

`browser_evaluate`:

```js
() => [...document.querySelectorAll('.projects-kpi__chip .iz-badge')].map(b => {
  const s = getComputedStyle(b);
  return {
    text: b.textContent.trim().replace(/\s+/g, ' '),
    bg: s.backgroundColor,
    fg: s.color,
    radius: s.borderRadius,
  };
})
```

Expected: four entries. Each `bg` and `fg` must be a real colour — **not** `rgba(0, 0, 0, 0)` and not identical to each other within an entry (identical means the invisible-text bug the spec warns about). The first should read success-green, the second warning-amber, the third neutral, the fourth accent-pink. Report the actual values.

- [ ] **Step 8: Assert the numeral switched to Space Grotesk and the card got shorter**

`browser_evaluate`:

```js
() => {
  const card = document.querySelector('.projects-kpi');
  return {
    heroFont: getComputedStyle(card.querySelector('.projects-kpi__hero-value')).fontFamily,
    cardHeight: Math.round(card.getBoundingClientRect().height),
    iconBg: getComputedStyle(card.querySelector('.projects-kpi__icon')).backgroundColor,
  };
}
```

Expected: `heroFont` **contains** `Space Grotesk`; `cardHeight` is less than the Task 1 baseline; `iconBg` is a pink tint, not `rgba(74, 144, 217, 0.1)`. If `heroFont` still shows the system stack, `.iz-figure` is not reaching the element — check the theme is loaded (Task 1 Step 5) before adding any local `font-family`.

- [ ] **Step 9: Confirm the drill-down still works**

`browser_click` the "W.o.c." chip, then `browser_snapshot`. Expected: the projects list below filters to Waiting-on-Customer projects, exactly as clicking the old legend item did. If nothing happens, the `statusLabel` values no longer match what the list filters on — report it rather than changing the list.

- [ ] **Step 10: Commit**

```bash
git add src/components/ProjectsKpiCard.vue js/adminpage-main.js js/adminpage-main.js.map js/adminpage-public.js js/adminpage-public.js.map
git commit -F - <<'EOF'
feat(kpi): compact Projects card — status chips instead of bar + legend

Replace the stacked bar and dotted legend with four tinted status chips built
on the theme's .iz-badge primitive, so the card drops from three stacked rows
to hero + chips and stops setting the height of the whole KPI strip. All four
statuses stay separately drillable; the tone for each is an explicit property
rather than a class built from data.

Unlike superadminpage, this card shows four project statuses rather than three
task states, so On Hold takes the neutral .iz-badge base — there is no fourth
status tone and inventing one would imply a severity the data doesn't carry.

The hero numeral opts into the theme's .iz-figure (Space Grotesk). This is a
deliberate change, not the bug fix it was in superadminpage: that app had a
[class*="__value"] rule that missed projects-kpi__hero-value, and this app has
no such rule, so nothing was silently falling back here.

The icon square moves off the old #4a90d9 blue onto the accent tokens.

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>
EOF
```

---

### Task 3: Add chip hover feedback

Ported from superadminpage `e4071fe`. Separate commit because it is independently reviewable: a reviewer could accept the chips and reject this interaction.

**Files:**
- Modify: `src/components/ProjectsKpiCard.vue` — the chip style block added in Task 2
- Test: none (no test framework — verification is Steps 3-5)

**Interfaces:**
- Consumes: `button.projects-kpi__chip` and `.projects-kpi__chip .iz-badge` from Task 2
- Produces: nothing consumed downstream

- [ ] **Step 1: Add the hover, focus and focus-visible rules**

In `src/components/ProjectsKpiCard.vue`, append to the chip style block added in Task 2:

```css
/* Hover feedback lives on the badge and only while the pointer is over it, so
   it clears the moment you leave. :focus and :active stay inert — focus
   persists after a mouse click, and a chip that stays highlighted after being
   clicked reads as a selected filter that isn't one. */
button.projects-kpi__chip:hover,
button.projects-kpi__chip:focus,
button.projects-kpi__chip:active {
  background: transparent;
  box-shadow: none;
  outline: none;
}

button.projects-kpi__chip:focus-visible {
  outline: 2px solid var(--iz-accent);
  outline-offset: 2px;
}

button.projects-kpi__chip:hover .iz-badge {
  transform: translateY(-1px);
  filter: brightness(0.96);
}
```

Note `var(--iz-accent)`, **not** superadminpage's `var(--accent)` — that variable does not exist in this app and would leave the focus ring transparent.

- [ ] **Step 2: Add the transition to the existing badge rule**

Change the `.projects-kpi__chip .iz-badge` rule from Task 2 to:

```css
.projects-kpi__chip .iz-badge {
  font-variant-numeric: tabular-nums;
  transition: transform 0.12s ease, filter 0.12s ease;
}
```

- [ ] **Step 3: Build and load**

```bash
npm run build
```

Expected: exits 0. Then `browser_navigate` to
`http://nextcloud.local:8080/index.php/apps/adminpage/?cachebust=task3`.

- [ ] **Step 4: Assert hover lifts and release clears**

`browser_hover` the first chip, then `browser_evaluate`:

```js
() => getComputedStyle(document.querySelector('.projects-kpi__chip .iz-badge')).transform
```

Expected: a matrix with a **-1** y-translation, e.g. `matrix(1, 0, 0, 1, 0, -1)`. If it returns `none`, the hover rule is not winning — check the selector is qualified on `button`.

- [ ] **Step 5: Assert a click leaves nothing persisted**

This is the specific bug the original commit existed to fix. `browser_click` the first chip, then move the pointer away with `browser_hover` over the card title, then `browser_evaluate`:

```js
() => {
  const btn = document.querySelector('.projects-kpi__chip');
  const badge = btn.querySelector('.iz-badge');
  return {
    focused: document.activeElement === btn,
    badgeTransform: getComputedStyle(badge).transform,
    btnBg: getComputedStyle(btn).backgroundColor,
    btnOutline: getComputedStyle(btn).outlineStyle,
  };
}
```

Expected: `badgeTransform` is `none` or an identity matrix, `btnBg` is `rgba(0, 0, 0, 0)`, `btnOutline` is `none` — **even though `focused` may be `true`**. A focused-but-visually-inert chip is the intended behaviour. If any visual remains, the `:focus` reset is not applying.

- [ ] **Step 6: Commit**

```bash
git add src/components/ProjectsKpiCard.vue js/adminpage-main.js js/adminpage-main.js.map js/adminpage-public.js js/adminpage-public.js.map
git commit -F - <<'EOF'
feat(kpi): add hover feedback to Projects status chips

Subtle lift and tint-deepen on :hover only (transform/filter, no colour
tokens), so chips give affordance while pointed at but clear the moment the
pointer leaves. :focus and :active stay inert, so clicking a chip leaves
nothing persisted — focus survives a mouse click, and a chip still highlighted
afterwards reads as a selected filter that isn't one. :focus-visible keeps a
ring for keyboard users.

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>
EOF
```

---

### Task 4: Verify dark mode, the public surface, and narrow widths

No source changes and no commit. This task exists because the spec commits to reporting two known risks honestly rather than assuming them away. Its deliverable is a written report.

**Files:**
- Modify: none
- Test: none (no test framework — this task *is* the verification)

**Interfaces:**
- Consumes: the shipped chips from Tasks 2 and 3
- Produces: a report; possibly a follow-up issue, but no code

- [ ] **Step 1: Check both KPI-strip breakpoints**

The strip regrids at 1200px and 768px (`Dashboard.vue:339-349`). At each width, `browser_resize` then `browser_evaluate`:

```js
() => {
  const chips = document.querySelector('.projects-kpi__chips');
  const first = chips.children[0].getBoundingClientRect();
  const last = chips.children[chips.children.length - 1].getBoundingClientRect();
  return {
    width: window.innerWidth,
    rows: last.top > first.top ? 'wraps to 2+ rows' : 'single row',
    overflows: chips.scrollWidth > chips.clientWidth,
  };
}
```

Run at 1400, 1100 and 700. Expected: `overflows` is `false` at every width — chips may wrap, but must never clip. Report how many rows at each.

- [ ] **Step 2: Check dark mode**

Switch the Nextcloud user theme to dark: `browser_navigate` to
`http://nextcloud.local:8080/index.php/settings/user/theming`, select the dark theme, then return to the dashboard with a cache-busting query string and `browser_evaluate`:

```js
() => {
  const card = document.querySelector('.projects-kpi');
  return {
    cardBg: getComputedStyle(card).backgroundColor,
    badges: [...card.querySelectorAll('.iz-badge')].map(b => {
      const s = getComputedStyle(b);
      return { text: b.textContent.trim().replace(/\s+/g, ' '), bg: s.backgroundColor, fg: s.color };
    }),
  };
}
```

The spec predicts a specific mismatch: the card background stays light (the dashboard forces it) while each badge tint is `color-mix`ed against `--color-main-background`, which is now dark. **Report what you actually observe.** If any badge's text is illegible against its own background, say so plainly and stop — the fix is a theme-level change, not a hardcoded colour in this component. Restore the light theme afterwards.

- [ ] **Step 3: Check the public dashboard**

`PublicDashboard.vue` renders `ProjectsKpiCard` and inherits both commits without being modified.

```bash
docker exec -i nc_pg psql -U nextcloud -d nextcloud -c "SELECT token FROM oc_adminpage_public_links WHERE revoked_at IS NULL LIMIT 1;"
```

If that errors on the column name, inspect the table first:

```bash
docker exec -i nc_pg psql -U nextcloud -d nextcloud -c "\d oc_adminpage_public_links"
```

If no live token exists, create one through the "Public Dashboard Links" panel at the bottom of the authenticated dashboard. Then `browser_navigate` to
`http://nextcloud.local:8080/index.php/apps/adminpage/public/<token>` and `browser_evaluate`:

```js
() => ({
  chips: document.querySelectorAll('.projects-kpi__chip').length,
  themed: getComputedStyle(document.querySelector('.projects-kpi .iz-badge')).backgroundColor,
})
```

Expected: `chips: 4` and a real `themed` colour. **A transparent or default background here means the In Zicht theme does not apply to logged-out pages**, which would make the public card render unstyled chips. That is a shipping blocker — report it immediately rather than working around it.

- [ ] **Step 4: Screenshot and report**

`browser_take_screenshot` of the KPI strip and write up: the before/after card heights, the four resolved chip colours, the observed dark-mode behaviour, the public-surface result, and any width at which the chips wrap. State plainly anything that did not work.

---

## Notes for the implementer

- **`superadminpage`'s version is `src/components/PlatformKpiStrip.vue`** at commits `f494b19` and `e4071fe` — read them with `git -C ../superadminpage show <sha>` if the intent behind a rule is unclear. Do not copy its markup wholesale: it has three chips over task states, this has four over project statuses.
- **The theme's primitives are in** `/home/payboy/src/nextcloud-docker-dev/workspace/server/themes/inzicht/core/css/server.css` §8. Read the comments before adding any CSS — prefer an existing primitive over a new rule.
- **`.gitignore` ignores `*.md`.** The spec and this plan were force-added, following `README.md` and `KPI_REFERENCE.md`. Nothing in these tasks needs a new markdown file.
- **External automation commits this working tree between turns.** Check `git status` before assuming your edit is the only uncommitted change.
