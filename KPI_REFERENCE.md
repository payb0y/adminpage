# KPI Cards — Data Reference

This document explains each KPI card displayed on the admin dashboard, what metrics it shows, and how each metric is extracted from the database.

All KPIs are scoped to a single **organization** (`orgId`). The org is resolved from the logged-in user via `OrgOverviewService::resolveOrgId`.

Source: `lib/Service/KpiService.php`  
API endpoint: `GET /apps/adminpage/api/data` → `kpis[]` array

---

## 1. Projects

**Card ID:** `projects`  
**Vue component:** `ProjectsKpiCard.vue`  
**Visualization:** Hero total number + stacked horizontal bar + warning badge

### Metrics

| Metric              | Label        | How it's calculated                                                                                                                                                                                    |
| ------------------- | ------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| Active              | `Active`     | Count of `oc_custom_projects` where `status = 0`, joined to `oc_deck_boards` (non-deleted) for the org                                                                                                 |
| Waiting on Customer | `W.o.c.`     | Count where `status = 1`                                                                                                                                                                               |
| On Hold             | `On Hold`    | Count where `status = 2`                                                                                                                                                                               |
| Done                | `Done`       | Count where `status = 3`                                                                                                                                                                               |
| With Issue          | `With Issue` | Count of **distinct** projects that have at least one overdue task — a card in `oc_deck_cards` where `duedate < NOW()`, `done IS NULL`, `deleted_at = 0`, and the stack title is not `'Approved/Done'` |

### Database tables

- `oc_custom_projects` — project records, linked to org via `organization_id`, linked to Deck via `board_id` (stored as string, cast to unsigned for joins)
- `oc_deck_boards` — only non-deleted boards (`deleted_at = 0`)
- `oc_deck_stacks` — stack (column) within a board
- `oc_deck_cards` — individual task cards

### Status mapping

| `oc_custom_projects.status` | Meaning             |
| --------------------------- | ------------------- |
| `0`                         | Active              |
| `1`                         | Waiting on Customer |
| `2`                         | On Hold             |
| `3`                         | Done                |

---

## 2. Tasks

**Card ID:** `tasks`  
**Vue component:** `TasksKpiCard.vue`  
**Visualization:** Donut chart (by urgency) with center count + secondary stat

### Metrics

| Metric          | Label             | How it's calculated                                                                                  |
| --------------- | ----------------- | ---------------------------------------------------------------------------------------------------- |
| Overdue         | `Overdue`         | Cards where `duedate IS NOT NULL AND duedate < NOW()`                                                |
| Today           | `Today`           | Cards where `duedate IS NOT NULL AND DATE(duedate) = CURDATE()`                                      |
| Upcoming        | `Upcoming`        | Cards where `duedate IS NOT NULL AND duedate > NOW()`                                                |
| In Progress     | `In Progress`     | **All** open cards matching the filters below (total count)                                          |
| Non Due         | `Non Due`         | Cards where `duedate IS NULL`                                                                        |
| Avg Days Active | `Avg Days Active` | `ROUND(AVG(DATEDIFF(NOW(), FROM_UNIXTIME(c.created_at))))` — average age in days since card creation |

### Filters (applied to all metrics)

All task metrics are computed from a single query with these base filters:

- `oc_deck_cards.deleted_at = 0` — not soft-deleted
- `oc_deck_cards.done IS NULL` — not marked done
- `oc_deck_stacks.title <> 'Approved/Done'` — not in the "Done" stack
- Joined through `oc_deck_stacks` → `oc_deck_boards` (non-deleted) → `oc_custom_projects` (matching `organization_id`)

### Donut chart segments

| Segment  | Color             | Description                      |
| -------- | ----------------- | -------------------------------- |
| Overdue  | `#EF4444` (red)   | Past-due tasks needing attention |
| Today    | `#F59E0B` (amber) | Due today                        |
| Upcoming | `#4A90D9` (blue)  | Due in the future                |
| Non Due  | `#94A3B8` (gray)  | No due date set                  |

The donut center displays the **In Progress** count (total open cards).

---

## 3. Resources

**Card ID:** `resources`  
**Vue component:** `ResourcesKpiCard.vue`  
**Visualization:** 2×2 icon grid with public/private breakdown for Files and Notes

### Metrics

| Metric          | Label             | How it's calculated                                                                                                                                                              |
| --------------- | ----------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Whiteboards     | `Whiteboards`     | Count of `oc_custom_projects` where `white_board_id IS NOT NULL AND white_board_id != ''` for the org                                                                            |
| Scrumban Boards | `Scrumban Boards` | Count of distinct directories named `'Scrumban'` in `oc_filecache` whose parent is a project's `folder_id` (mimetype = `httpd/unix-directory`)                                   |
| Files (public)  | Part of `Files`   | Non-directory, non-Excalidraw files in `oc_filecache` directly inside the project's `folder_id`. Excludes mimetypes `httpd/unix-directory` and `application/vnd.excalidraw+json` |
| Files (private) | Part of `Files`   | Distinct shares in `oc_share` with `share_type = 0` (user share) where the shared file's parent is the project's `folder_id`                                                     |
| Notes (public)  | Part of `Notes`   | Non-directory files inside a sub-folder named `'Public Notes'` under the project's folder                                                                                        |
| Notes (private) | Part of `Notes`   | Rows in `oc_private_card_notes` linked through `oc_deck_cards` → `oc_deck_stacks` → `oc_deck_boards` → `oc_custom_projects` for the org                                          |

### Database tables

- `oc_filecache` — Nextcloud file/folder index (`fileid`, `parent`, `name`, `mimetype`)
- `oc_mimetypes` — mimetype ID lookup
- `oc_share` — Nextcloud share records (`share_type = 0` = user-to-user share)
- `oc_custom_projects.folder_id` — the root folder for each project
- `oc_custom_projects.white_board_id` — Excalidraw whiteboard file reference
- `oc_private_card_notes` — custom table for per-card private notes

### Value format

The backend sends Files and Notes as a combined string: `"12 pub / 3 priv"`.  
`ResourcesKpiCard.vue` parses this into separate public/private counts for display.

---

## 4. Timeline

**Card ID:** `timeline`  
**Vue component:** `TimelineKpiCard.vue`  
**Visualization:** Semi-circle gauge (completion rate) + two secondary stat pills

### Metrics

| Metric                   | Label                      | How it's calculated                                                                                                                                                                                                                                                 |
| ------------------------ | -------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Avg Completion Rate      | `Avg Completion Rate`      | For each project: `(done_tasks / total_tasks) × 100`. Then averaged across all projects. A task counts as "done" if `oc_deck_cards.done IS NOT NULL` **or** it sits in a stack titled `'Approved/Done'`. Only non-deleted cards and non-deleted boards are counted. |
| Avg Coordination Pending | `Avg Coordination Pending` | Average weeks from the `request_date` timeline item's `start_date` to `NOW()`: `ROUND(AVG(DATEDIFF(NOW(), start_date) / 7))`. Sourced from `oc_project_timeline_items` where `system_key = 'request_date'`.                                                         |
| Avg Required Prep Time   | `Avg Required Prep Time`   | Average weeks between `start_date` and `end_date` of `required_preparation` timeline items: `ROUND(AVG(DATEDIFF(end_date, start_date) / 7))`. Sourced from `oc_project_timeline_items` where `system_key = 'required_preparation'`.                                 |

### Gauge color coding

| Completion Rate | Color              | Meaning  |
| --------------- | ------------------ | -------- |
| ≥ 75%           | `#22C55E` (green)  | On track |
| ≥ 50%           | `#F59E0B` (amber)  | Moderate |
| ≥ 25%           | `#F97316` (orange) | Behind   |
| < 25%           | `#EF4444` (red)    | At risk  |

### Database tables

- `oc_deck_cards` — task cards (uses `done` column and `created_at` timestamp)
- `oc_deck_stacks` — stack title checked for `'Approved/Done'`
- `oc_project_timeline_items` — custom table with `system_key`, `start_date`, `end_date`, linked to project via `project_id`

---

## Data flow summary

```
Browser GET /apps/adminpage/api/data
  → DashboardController::getData()
    → KpiService::getKpis($orgId)
      → returns array of 4 KPI objects

Dashboard.vue receives kpis[] in `data` prop
  → projectsKpi  → ProjectsKpiCard.vue   (stacked bar)
  → tasksKpi     → TasksKpiCard.vue      (donut chart)
  → resourcesKpi → ResourcesKpiCard.vue  (icon grid)
  → timelineKpi  → TimelineKpiCard.vue   (gauge chart)
```

> **Note:** Subscription and Team data are displayed by the dedicated
> `SubscriptionPanel` and `MembersPanel` components inside `OrgInsightsPanel`.
> They were previously duplicated as KPI cards but have been removed to avoid
> redundancy.

## Key conventions

- All tables use Nextcloud's `*PREFIX*` macro (resolves to `oc_` by default)
- The "Done" stack is identified by the hardcoded title `'Approved/Done'`
- `oc_custom_projects.board_id` is stored as a string and cast with `CAST(cp.board_id AS UNSIGNED)` when joining to `oc_deck_boards`
- All KPI queries filter out soft-deleted boards (`deleted_at = 0`) and soft-deleted cards (`deleted_at = 0`)
