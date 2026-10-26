# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What This Is

`adminpage` is a Nextcloud app that renders a SaaS-style analytics dashboard in the top navigation bar. PHP backend (Nextcloud App Framework) + Vue 2.7 frontend bundled with webpack.

## Build Commands

```bash
npm run build          # Production build (required after any src/ change)
npm run dev            # Development build
npm run watch          # Development build with file watching
npm test               # node --test tests/*.test.mjs
```

Node is not installed inside WSL; run these in the `node:22` image:
`docker run --rm -v "$PWD":/app -w /app node:22 npm run build`.

After building, hard-refresh browser with Ctrl+Shift+R.

```bash
# Enable/disable in Nextcloud (container is nextcloud-app, from
# ../../../docker-compose.yml)
docker exec -u www-data nextcloud-app php occ app:enable adminpage
docker exec -u www-data nextcloud-app php occ app:disable adminpage
```

**A new route needs a version bump.** Nextcloud caches routes per app
version, so a route added to `appinfo/routes.php` 404s until `<version>` in
`appinfo/info.xml` goes up and `php occ upgrade` runs.

Reloading the page does **not** re-fetch the bundle. In a browser session,
`fetch(src, {cache: 'reload'})` for each `script[src*=adminpage]` and then
reload; do the same for the theme's `server.css` after deploying it.

## Architecture

Two entry points in webpack: `main` (authenticated dashboard) and `public` (public share view).

```
src/main.js   → mounts Dashboard.vue on #adminpage-root
src/public.js → mounts PublicDashboard.vue on #adminpage-root (reads token from data attr)
```

**Data flow:** Browser → `PageController` renders template → Vue app fetches `GET /apps/adminpage/api/data` → `DashboardController::getData()` resolves user → org via `OrgOverviewService::resolveOrgId`, then delegates to service classes → returns single JSON object consumed as `data` prop by `Dashboard.vue`.

**Org resolution:** `OrgOverviewService::resolveOrgId(uid)` resolves strictly via `oc_organizations.admin_uid` (org ownership). adminpage is admin-only — plain members get `null` → dashboard renders empty state.

**Services** (all scoped to orgId):
- `KpiService` — Projects, Tasks, Resources, Timeline KPI cards
- `AlertService` — Overdue/unassigned/stalled task alerts
- `DeckService` — Project performance analytics from Deck tables
- `OrgOverviewService` — Org profile, subscription, members
- `FinancialService` — Financial data
- `PublicTokenService` — Public link CRUD (stored in `oc_adminpage_public_links`)

## Critical Webpack Rule

Entry key in `webpack.config.js` **must remain `main`** (and `public` for public entry). `@nextcloud/webpack-vue-config` prefixes output with app name → `js/adminpage-main.js`. This must match `Util::addScript('adminpage', 'adminpage-main')` in `PageController`. Renaming entry keys produces double-prefixed filenames and blank pages.

## Key Conventions

- **Vue 2.7 only** — no Vue 3, no Composition API (NC32 compatibility)
- **No external CSS frameworks.** Chrome comes from the In Zicht theme; see "Styling" below
- Charts use **Chart.js 4** via `AreaChart.vue`, `DonutChart.vue`, `BarChart.vue`. Canvas
  cannot read CSS variables, so colours resolve at render time through `src/lib/izChart.js`
- `t(app, text) => text` global mixin stub for translation calls (no full l10n package)
- PHP controller annotations: `@NoAdminRequired @NoCSRFRequired` on all API routes
- PHP namespace: `OCA\AdminPage` (PSR-4 autoloaded from `lib/`)

## Styling — the In Zicht theme owns it

**Read `/home/payboy/src/inzicht-stack/inzicht-nextcloud-theme/USING-THE-THEME.md` before
touching any style.** It is the canonical guide, shared with `superadminpage`
and `employee_dashboard`, and it is the file to edit when a rule changes.

In short: this app defines no look of its own. `Dashboard.vue`'s root carries
`iz-app`, which supplies the tokens; chrome comes from the theme's `.iz-*`
primitives and only layout stays in a component. Earlier revisions of this file
described a hardcoded palette on `.adminpage-dashboard` and an unscoped rule
forcing `#app-content` to `#f0f1f5` — both are gone. The forced grey was what
stopped the app following dark mode; the backdrop is now
`background: var(--image-background)` with no `!important`.

### Specific to this app

- **Two entry points, two roots.** `Dashboard.vue` and `PublicDashboard.vue`
  both carry `iz-app` and both must define anything the shared child components
  read. The public view is the one that regressed when the token list drifted.
- The public share view keeps `color-scheme: light` and a literal ground: a
  share link is opened by people outside the organisation. Anything overriding
  the theme there must be written `.public-dashboard.iz-app` — theme CSS loads
  after the app bundle and wins at equal specificity.
- **Vendored from the theme:** `ConfirmDialog.vue` (no `alert()`/`confirm()`)
  and `src/lib/izChart.js`. Changes to either must be copied to the other apps.
- **Vendored across the three apps:** `src/components/ProjectTimeline.vue`,
  `src/lib/projectTimeline.mjs` and `tests/projectTimeline.test.mjs` are
  identical in adminpage, superadminpage and employee_dashboard. See
  "Project Timeline" below.
- A handful of documented local overrides remain and are deliberate: the alert
  tints on the Projects and Tasks KPI footers, toolbar control widths, the org
  avatar's 44px scale.
- Dead components were removed in Aug 2026 — `AlertsPanel`, `AlertCard`,
  `OrgHeaderBar`, `KpiCard`, `FinancialPanel`, `OrgOverviewPanel`, `SafetyPanel`
  no longer exist. `lib/Service/FinancialService.php` is now orphaned.

## Project Timeline

The Project Details card shows a project's plan as projectcreatoraio makes it:
the Overview's "5. Timeline" figures (Initiation / Execution / Delivery, the
server's status and float, % of the timeline passed) over the Planning tab's
Gantt, read-only. Nothing is recalculated here; `projectTimeline.mjs` only words
and places projectcreatoraio's data.

**The Gantt is kept identical in logic to the Planning tab** (projectcreatoraio
`src/components/ProjectTimeline/GanttChart.vue` and `rows/SystemPlanningRow.vue`):
the same range and padding, px-per-day zoom (4px default, the same steps),
year/month/ISO-week header, System Planning row with its markers and mini
legend, phase and card rows, custom timeline items, guide lines, today, a
Status column, delay badges, ghost bars, dependency arrows, the hover date
guide and drag-to-scroll. What it leaves out is editing: Add Item, What-If,
the Actions column. When the Planning tab changes, port the change; the tests
name each rule after the Planning method it mirrors.

- **Signed in:** the browser makes the Planning tab's two requests,
  `/apps/projectcreatoraio/api/v1/projects/{id}/timeline/summary` and
  `…/timeline` (the items), which check access themselves (global admin,
  organisation admin, or project member). Like the Planning tab, they also
  sync the calculated card dates to Deck and refresh the system items.
- **Public share view:** no user, so `GET /api/public/{token}/projects/{id}/timeline`
  builds the same summary server-side (`ProjectTimelineService`) with the
  dependencies, delay analysis and items folded in, only for a project of the
  link's organisation, and without either sync.
- Changing the card: edit adminpage's copy, run `npm test`, copy the three
  files to superadminpage and employee_dashboard, rebuild all three.
- `projectTimeline.mjs` also exports `portfolioGanttOf`, several projects'
  System Planning and phase rows on one range, used by employee_dashboard's
  dashboard-wide Project Timeline (its `GanttWidget.vue` carries a copy of
  this card's Gantt styles; keep the two in step).

## Database Conventions

- All tables use Nextcloud's `*PREFIX*` macro (resolves to `oc_` by default)
- `oc_custom_projects.board_id` is stored as VARCHAR — must cast with `CAST(cp.board_id AS UNSIGNED)` when joining to `oc_deck_boards`
- Done stack identified by hardcoded title `'Approved/Done'` (used across KpiService, DeckService, OrgOverviewService)
- Soft-delete filters: always check `deleted_at = 0` on boards and cards
- Task "done" means either `c.done IS NOT NULL` or card is in the `'Approved/Done'` stack

## Database Access (Dev Environment)

The live database is **PostgreSQL in `nextcloud-db`** (it was `nc_pg` before the
stack moved to `../../../docker-compose.yml`). Earlier revisions of this file
pointed at MariaDB in `nc_db`; that is not what the app reads, and
`master-database-mysql-1` holds an empty skeleton of the same schema that is
easy to mistake for the real thing.

```bash
# psql shell
docker exec -it nextcloud-db psql -U nextcloud -d nextcloud

# one-shot query
docker exec nextcloud-db psql -U nextcloud -d nextcloud -c "SELECT ..."
```

Database name is `nextcloud`, table prefix `oc_`.

**Start `nextcloud-db` before `nextcloud-app`.** If Nextcloud boots without its
database it runs `maintenance:install` and overwrites `config.php`, losing
`theme=inzicht`, the pgsql connection and the instance secrets. No data is lost,
but the instance has to be rebuilt by hand.

## API Routes

| Route | Controller | Purpose |
|---|---|---|
| `GET /` | `PageController#index` | Render authenticated dashboard |
| `GET /api/data` | `DashboardController#getData` | Main dashboard JSON |
| `GET /api/upcoming-tasks` | `DashboardController#getUpcomingTasks` | Proxies Deck upcoming API |
| `GET /public/{token}` | `PublicPageController#index` | Render public dashboard page |
| `GET /api/public/{token}` | `PublicDashboardController#getData` | Public dashboard JSON |
| `GET /api/public/{token}/projects/{projectId}/timeline` | `PublicDashboardController#getProjectTimeline` | One project's timeline summary for the public view |
| `GET /api/public-links` | `DashboardController#listPublicLinks` | List org's public links |
| `POST /api/public-links` | `DashboardController#createPublicLink` | Create public link |
| `DELETE /api/public-links/{id}` | `DashboardController#revokePublicLink` | Revoke link |
| `POST /api/public-links/{id}/delete` | `DashboardController#deletePublicLink` | Delete link |

## When Changing Data Shape

If you change a service's return shape in PHP, update the corresponding Vue component props/usage in the same change. The `Dashboard.vue` component destructures the single `data` prop into sub-props for child components.

## Related Project

There is a sibling `employee_dashboard` app at `../employee_dashboard` designed for employee task views (vs this admin analytics view). It shares the same visual language and database tables but has its own layout. See `EMPLOYEE_DASHBOARD_HANDOFF.md` for details.
