# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What This Is

`adminpage` is a Nextcloud app that renders a SaaS-style analytics dashboard in the top navigation bar. PHP backend (Nextcloud App Framework) + Vue 2.7 frontend bundled with webpack.

## Build Commands

```bash
npm run build          # Production build (required after any src/ change)
npm run dev            # Development build
npm run watch          # Development build with file watching
```

After building, hard-refresh browser with Ctrl+Shift+R.

```bash
# Enable/disable in Nextcloud
docker exec -u www-data nextcloud-dev php occ app:enable adminpage
docker exec -u www-data nextcloud-dev php occ app:disable adminpage
```

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
- **No external CSS frameworks** — all styles are scoped BEM-style per component
- `Dashboard.vue` has an **unscoped** `<style>` block forcing `#app-content` background to `#f0f1f5` to override Nextcloud dark mode
- Charts use **Chart.js 4** via `AreaChart.vue`, `DonutChart.vue`, `BarChart.vue` wrapper components
- `t(app, text) => text` global mixin stub for translation calls (no full l10n package)
- PHP controller annotations: `@NoAdminRequired @NoCSRFRequired` on all API routes
- PHP namespace: `OCA\AdminPage` (PSR-4 autoloaded from `lib/`)

## Database Conventions

- All tables use Nextcloud's `*PREFIX*` macro (resolves to `oc_` by default)
- `oc_custom_projects.board_id` is stored as VARCHAR — must cast with `CAST(cp.board_id AS UNSIGNED)` when joining to `oc_deck_boards`
- Done stack identified by hardcoded title `'Approved/Done'` (used across KpiService, DeckService, OrgOverviewService)
- Soft-delete filters: always check `deleted_at = 0` on boards and cards
- Task "done" means either `c.done IS NOT NULL` or card is in the `'Approved/Done'` stack

## Database Access (Dev Environment)

```bash
# Root access (works reliably)
docker exec -it nc_db mariadb -uroot -prootpass

# One-shot query
docker exec -i nc_db mariadb -uroot -prootpass -D nextcloud -e "SHOW TABLES;"
```

Database name in this environment is `nextcloud` (not `nc_db`).

## API Routes

| Route | Controller | Purpose |
|---|---|---|
| `GET /` | `PageController#index` | Render authenticated dashboard |
| `GET /api/data` | `DashboardController#getData` | Main dashboard JSON |
| `GET /api/upcoming-tasks` | `DashboardController#getUpcomingTasks` | Proxies Deck upcoming API |
| `GET /public/{token}` | `PublicPageController#index` | Render public dashboard page |
| `GET /api/public/{token}` | `PublicDashboardController#getData` | Public dashboard JSON |
| `GET /api/public-links` | `DashboardController#listPublicLinks` | List org's public links |
| `POST /api/public-links` | `DashboardController#createPublicLink` | Create public link |
| `DELETE /api/public-links/{id}` | `DashboardController#revokePublicLink` | Revoke link |
| `POST /api/public-links/{id}/delete` | `DashboardController#deletePublicLink` | Delete link |

## When Changing Data Shape

If you change a service's return shape in PHP, update the corresponding Vue component props/usage in the same change. The `Dashboard.vue` component destructures the single `data` prop into sub-props for child components.

## Related Project

There is a sibling `employee_dashboard` app at `../employee_dashboard` designed for employee task views (vs this admin analytics view). It shares the same visual language and database tables but has its own layout. See `EMPLOYEE_DASHBOARD_HANDOFF.md` for details.
