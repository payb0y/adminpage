# Admin Page (Nextcloud 32)

Custom Nextcloud app that renders a SaaS-style analytics dashboard in the top app bar navigation.

## Purpose

`adminpage` is a Vue-based dashboard app for Nextcloud 32 with:

- KPI strip (Operational, Financial, Commercial)
- Alerts and Exceptions cards
- Safety overview cards
- Project safety performance list
- Incident severity donut chart (Chart.js)
- Incident causes tag list

Data is served from a mock PHP controller (`/apps/adminpage/api/data`) with no database.

## Tech Stack

- Nextcloud App Framework (PHP)
- Vue 2.7
- Chart.js
- `@nextcloud/webpack-vue-config`
- `@nextcloud/axios`, `@nextcloud/router`

## App Entry Points

- Top navigation route: `/apps/adminpage/`
- Main template: `templates/index.php`
- Vue mount element: `#adminpage-root`
- API endpoint: `GET /apps/adminpage/api/data`

## Project Structure

```text
adminpage/
├── appinfo/
│   ├── info.xml
│   └── routes.php
├── img/
│   ├── app.svg
│   └── app-dark.svg
├── js/
│   └── adminpage-main.js (generated)
├── lib/
│   ├── Controller/
│   │   ├── DashboardController.php
│   │   └── PageController.php
│   └── Settings/
│       ├── Admin.php
│       └── AdminSection.php
├── src/
│   ├── main.js
│   └── components/
│       ├── Dashboard.vue
│       ├── KpiCard.vue
│       ├── AlertCard.vue
│       ├── SafetyPanel.vue
│       └── DonutChart.vue
├── templates/
│   ├── admin.php
│   └── index.php
├── composer.json
├── package.json
└── webpack.config.js
```

## How It Works

1. User opens app from top bar.
2. Nextcloud resolves `adminpage.page.index`.
3. `PageController::index()` renders `templates/index.php` and loads `adminpage-main.js`.
4. `src/main.js` mounts Vue app and fetches `/apps/adminpage/api/data`.
5. `DashboardController::getData()` returns mock JSON.
6. Vue components render the full dashboard.

## Setup and Run

### 1) Frontend build

```bash
cd adminpage
npm install
npm run build
```

### 2) Install in Nextcloud

Copy or symlink this folder to your Nextcloud `custom_apps/adminpage`.

### 3) Enable app

```bash
php occ app:enable adminpage
```

### 4) Open

Navigate to:

`/apps/adminpage/`

or click **Admin Page** in the top app bar.

## Development Workflow

### Edit + rebuild

```bash
npm run build
```

Then hard refresh browser (`Ctrl+Shift+R`).

### Optional watch mode

```bash
npm run watch
```

## Key Configuration Notes (Important)

### Webpack entry naming

In `webpack.config.js`, entry key must stay `main`:

```js
webpackConfig.entry = {
    main: path.join(__dirname, 'src', 'main.js'),
}
```

`@nextcloud/webpack-vue-config` prefixes output with app name (`adminpage`), producing:

- `js/adminpage-main.js`

This must match:

```php
Util::addScript('adminpage', 'adminpage-main');
```

If you change entry to `adminpage-main`, output becomes `adminpage-adminpage-main.js`, causing blank page/script 404 behavior.

### Dark theme background override

`Dashboard.vue` includes an unscoped style block that forces a light app background (`#f0f1f5`) so cards stay white and the layout matches design even when Nextcloud dark mode is active.

## Backend Mock Data Contract

`DashboardController::getData()` returns:

- `kpis[]`
- `alerts[]`
- `safetyStats[]`
- `projectIncidents[]`
- `severityChart`
- `causes[]`

Frontend assumes these keys exist.

## Troubleshooting

### App shows in admin settings but not top bar

- Ensure `appinfo/info.xml` has `<navigations>` with route `adminpage.page.index`.
- Ensure app is re-enabled or upgraded:

```bash
php occ app:disable adminpage
php occ app:enable adminpage
```

### Blank page

- Check `js/adminpage-main.js` exists in deployed app.
- Confirm `PageController` exists and route `/` is defined in `appinfo/routes.php`.
- Confirm browser hard refresh after rebuild.

### Profiler CSS MIME warning

This can come from Nextcloud profiler app and is unrelated to `adminpage`.

## Guidance for Agents

- Keep Vue at v2.7 for NC32 compatibility.
- Do not replace with React or external CSS frameworks.
- Preserve modular component architecture.
- If changing data shape in PHP, update Vue props/usage accordingly.
- Rebuild frontend after any `src/` change.

