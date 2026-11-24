/**
 * Project status, the way oc_custom_projects.status stores it.
 *
 * The codes are projectcreatoraio's ProjectStatus enum (lib/Enum/ProjectStatus.php),
 * the same encoding DeckService::statusLabel and GeocodeService::statusLabel
 * read. Filters and CSS classes work on these codes; only the label shown to
 * the user is translated, so nothing ever compares displayed text.
 */
import { t, tc } from "./l10n.mjs";

export const PROJECT_STATUS = Object.freeze({
  ARCHIVED: 0,
  ACTIVE: 1,
  WAITING: 2,
  ON_HOLD: 3,
  DONE: 4,
});

/** The live statuses, in the order the filters list them. */
export const PROJECT_STATUS_FILTERS = [
  PROJECT_STATUS.ACTIVE,
  PROJECT_STATUS.WAITING,
  PROJECT_STATUS.ON_HOLD,
  PROJECT_STATUS.DONE,
];

/** A status code from a number, a numeric string, or null/"" (no status). */
export function toStatusCode(value) {
  if (value === null || value === undefined || value === "") return null;
  const code = Number(value);
  return Number.isInteger(code) ? code : null;
}

/** The status as the user reads it: "Active", "Actief", … */
export function projectStatusLabel(code) {
  switch (toStatusCode(code)) {
    case PROJECT_STATUS.ARCHIVED:
      return t("Archived");
    case PROJECT_STATUS.ACTIVE:
      return t("Active");
    case PROJECT_STATUS.WAITING:
      return t("Waiting on Customer");
    case PROJECT_STATUS.ON_HOLD:
      return t("On Hold");
    case PROJECT_STATUS.DONE:
      return tc("Done", "project status");
    default:
      return t("Unknown");
  }
}

/**
 * A fixed English slug for CSS classes ("waiting-on-customer"), the class
 * names the stylesheets were written against when they were built from the
 * English label. Never shown.
 */
export function projectStatusSlug(code) {
  switch (toStatusCode(code)) {
    case PROJECT_STATUS.ARCHIVED:
      return "archived";
    case PROJECT_STATUS.ACTIVE:
      return "active";
    case PROJECT_STATUS.WAITING:
      return "waiting-on-customer";
    case PROJECT_STATUS.ON_HOLD:
      return "on-hold";
    case PROJECT_STATUS.DONE:
      return "done";
    default:
      return "unknown";
  }
}
