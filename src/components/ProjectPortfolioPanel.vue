<template>
  <section class="portfolio iz-panel iz-panel--list">
    <h3 class="portfolio__heading">
      <button
        type="button"
        class="portfolio__toggle"
        :aria-expanded="String(!collapsed)"
        :aria-controls="'portfolio-body-' + _uid"
        @click="toggle"
      >
        <span class="portfolio__toggle-title iz-panel__title">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <rect x="3" y="4" width="18" height="16" rx="2" />
            <path d="M8 2v4M16 2v4M3 9h18" />
          </svg>
          Projectportfolio - Initiatiefase
        </span>
        <span class="portfolio__toggle-meta">28 projecten</span>
        <svg class="portfolio__chevron" :class="{ 'portfolio__chevron--open': !collapsed }" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <polyline points="6 9 12 15 18 9" />
        </svg>
      </button>
    </h3>

    <div v-show="!collapsed" :id="'portfolio-body-' + _uid" class="portfolio__body">
      <div class="portfolio__toolbar iz-card iz-card--flat" aria-label="Portfolio filters">
        <div class="portfolio__filter-group">
          <span class="iz-label">Weergave</span>
          <div class="portfolio__segmented">
            <span class="portfolio__segment">Mijn projecten</span>
            <span class="portfolio__segment portfolio__segment--active">Team</span>
            <span class="portfolio__segment">Alle projecten</span>
          </div>
        </div>
        <div class="portfolio__filter-group">
          <span class="iz-label">Periode</span>
          <span class="portfolio__control">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="4" width="18" height="17" rx="2" /><path d="M8 2v4M16 2v4M3 9h18" /></svg>
            W31 - W36 (6 weken)
          </span>
          <div class="portfolio__segmented portfolio__segmented--compact">
            <span class="portfolio__segment">Vorige</span>
            <span class="portfolio__segment portfolio__segment--active">Deze 6 weken</span>
            <span class="portfolio__segment">Volgende</span>
          </div>
        </div>
        <div class="portfolio__capacity">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="3" /><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1-2.8 2.8-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.6v.2h-4V21a1.7 1.7 0 0 0-1-1.6 1.7 1.7 0 0 0-1.9.3l-.1.1L4.2 17l.1-.1a1.7 1.7 0 0 0 .3-1.9A1.7 1.7 0 0 0 3 14H2.8v-4H3a1.7 1.7 0 0 0 1.6-1 1.7 1.7 0 0 0-.3-1.9L4.2 7 7 4.2l.1.1A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1-1.6v-.2h4V3a1.7 1.7 0 0 0 1 1.6 1.7 1.7 0 0 0 1.9-.3l.1-.1L19.8 7l-.1.1a1.7 1.7 0 0 0-.3 1.9 1.7 1.7 0 0 0 1.6 1h.2v4H21a1.7 1.7 0 0 0-1.6 1z" /></svg>
          <span><strong>Capaciteit (Team)</strong><small>4,0 FTE x 2 projecten/FTE = 8 projecten</small></span>
          <span class="iz-badge iz-badge--muted">Wijzigen</span>
        </div>
      </div>

      <div class="portfolio__kpis iz-stat-grid">
        <article v-for="metric in metrics" :key="metric.label" class="iz-kpi portfolio-kpi">
          <div class="portfolio-kpi__icon" :class="metric.tone">
            <svg v-if="metric.icon === 'folder'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 6h6l2 2h10v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" /></svg>
            <svg v-else-if="metric.icon === 'check'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9" /><path d="m8 12 3 3 5-6" /></svg>
            <svg v-else-if="metric.icon === 'alert'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M10.3 3.5 2.4 18a2 2 0 0 0 1.8 3h15.6a2 2 0 0 0 1.8-3L13.7 3.5a2 2 0 0 0-3.4 0z" /><path d="M12 9v4M12 17h.01" /></svg>
            <span v-else class="portfolio-kpi__ring" />
          </div>
          <div class="portfolio-kpi__copy">
            <strong class="portfolio-kpi__value">{{ metric.value }}</strong>
            <span class="portfolio-kpi__label">{{ metric.label }}</span>
            <small>{{ metric.note }}</small>
          </div>
          <svg class="portfolio__row-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="9 18 15 12 9 6" /></svg>
        </article>
      </div>

      <div class="portfolio__overview-grid">
        <section class="iz-card portfolio__status-card">
          <header class="iz-panel__header">
            <h4 class="iz-panel__title">Processtatus - Initiatiefase</h4>
          </header>
          <div class="portfolio__status-content">
            <div v-if="portfolioLoading" class="portfolio__status-state iz-empty">Projectvoortgang laden...</div>
            <div v-else-if="portfolioError" class="portfolio__status-state iz-error">
              <span>{{ portfolioError }}</span>
              <button type="button" class="iz-btn iz-btn--danger-quiet iz-btn--sm" @click="fetchPortfolio">Opnieuw proberen</button>
            </div>
            <div v-else class="portfolio__donut" :style="donutStyle" role="img" :aria-label="trackedProjects + ' projecten verdeeld over vijf voortgangscategorieën'">
              <span><strong>{{ trackedProjects }}</strong><small>projecten</small></span>
            </div>
            <div v-if="!portfolioLoading && !portfolioError" class="portfolio__legend">
              <div v-for="status in displayStatuses" :key="status.key" class="portfolio__legend-row">
                <span class="portfolio__legend-dot" :class="status.tone" />
                <strong>{{ status.label }}</strong>
                <span v-if="status.badge" class="iz-badge" :class="status.badgeClass">{{ status.badge }}</span>
                <strong class="portfolio__legend-count">{{ status.count }}</strong>
                <span>{{ status.percent }}</span>
                <svg class="portfolio__row-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="9 18 15 12 9 6" /></svg>
              </div>
            </div>
          </div>
          <p v-if="untrackedProjectCount && !portfolioLoading && !portfolioError" class="portfolio__untracked">
            {{ untrackedProjectCount }} {{ untrackedProjectCount === 1 ? 'project kon' : 'projecten konden' }} niet aan een actief Deck-bord worden gekoppeld.
          </p>
        </section>

        <section class="iz-card portfolio__gaps-card">
          <header class="iz-panel__header">
            <h4 class="iz-panel__title portfolio__danger-title">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M10.3 3.5 2.4 18a2 2 0 0 0 1.8 3h15.6a2 2 0 0 0 1.8-3L13.7 3.5a2 2 0 0 0-3.4 0z" /><path d="M12 9v4M12 17h.01" /></svg>
              Open planningsgaten (3)
            </h4>
            <span class="portfolio__link">Bekijk alle projecten</span>
          </header>
          <div class="portfolio__gap-list">
            <div v-for="gap in planningGaps" :key="gap.name" class="iz-row iz-row--card portfolio__gap-row">
              <svg class="portfolio__pin" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a7 7 0 0 0-7 7c0 5.3 7 13 7 13s7-7.7 7-13a7 7 0 0 0-7-7zm0 9.5A2.5 2.5 0 1 1 12 6a2.5 2.5 0 0 1 0 5.5z" /></svg>
              <span class="portfolio__gap-copy"><strong>{{ gap.name }}</strong><small>{{ gap.note }}</small></span>
              <strong>{{ gap.duration }}</strong>
              <span class="iz-badge iz-badge--danger">{{ gap.weeks }}</span>
              <svg class="portfolio__row-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="9 18 15 12 9 6" /></svg>
            </div>
          </div>
        </section>
      </div>

      <section class="iz-card portfolio__workload">
        <header class="iz-panel__header portfolio__workload-header">
          <h4 class="iz-panel__title">Werkvoorbereidingsbelasting per week</h4>
          <span class="portfolio__capacity-note"><strong>Capaciteit (Team)</strong> 4,0 FTE x 2 projecten/FTE = <strong>8 gelijktijdige projecten</strong></span>
        </header>
        <div class="portfolio__weeks">
          <article v-for="week in weeks" :key="week.week" class="iz-card iz-card--flat portfolio-week">
            <header><strong>{{ week.week }}</strong><small>{{ week.range }}</small></header>
            <div v-for="item in week.items" :key="item.label" class="portfolio-week__row">
              <span class="portfolio__legend-dot" :class="item.tone" />
              <span>{{ item.label }}</span>
              <strong>{{ item.value }}</strong>
            </div>
            <div class="portfolio-week__total"><span class="portfolio__legend-dot tone-neutral" /><strong>Totaal actief</strong><strong>{{ week.total }}</strong></div>
            <div class="portfolio-week__capacity" :class="week.over ? 'portfolio-week__capacity--over' : 'portfolio-week__capacity--ok'">
              <strong>{{ week.total }} / 8</strong><span>{{ week.message }}</span>
            </div>
          </article>
        </div>
      </section>
    </div>
  </section>
</template>

<script>
import axios from "@nextcloud/axios";
import { generateUrl } from "@nextcloud/router";

export default {
  name: "ProjectPortfolioPanel",
  data: function () {
    return {
      collapsed: true,
      portfolio: null,
      portfolioLoading: false,
      portfolioError: null,
      metrics: [
        { value: 28, label: "Totaal projecten", note: "Binnen geselecteerde periode", icon: "folder", tone: "tone-accent" },
        { value: 8, label: "Aankomend (75 - 99%)", note: "Wensweek zichtbaar t/m 99%", icon: "progress", tone: "tone-warning" },
        { value: 6, label: "100% gereed voor Handover 1", note: "Werkelijke wensweek leidend", icon: "check", tone: "tone-success" },
        { value: 3, label: "Open planningsgaten", note: "In geselecteerde periode", icon: "alert", tone: "tone-danger" },
      ],
      planningGaps: [
        { name: "Kerkstraat", note: "Geen vervolgactiviteiten gepland", duration: "2 weken", weeks: "W32 - W33" },
        { name: "Stationsgebied", note: "Wacht op externe afstemming", duration: "1 week", weeks: "W34" },
        { name: "Rivierzicht", note: "Nog geen vergunning ontvangen", duration: "3 weken", weeks: "W31 - W33" },
      ],
      weeks: [
        { week: "W31", range: "28 jul - 3 aug", total: 8, message: "Op norm", over: false, items: [{ label: "Op te starten", value: 3, tone: "tone-cat-1" }, { label: "Doorlopend", value: 5, tone: "tone-accent" }, { label: "Eindigend", value: 2, tone: "tone-cat-4" }] },
        { week: "W32", range: "4 aug - 10 aug", total: 10, message: "+2 boven norm", over: true, items: [{ label: "Op te starten", value: 4, tone: "tone-cat-1" }, { label: "Doorlopend", value: 6, tone: "tone-accent" }, { label: "Eindigend", value: 1, tone: "tone-cat-4" }] },
        { week: "W33", range: "11 aug - 17 aug", total: 9, message: "+1 boven norm", over: true, items: [{ label: "Op te starten", value: 2, tone: "tone-cat-1" }, { label: "Doorlopend", value: 7, tone: "tone-accent" }, { label: "Eindigend", value: 2, tone: "tone-cat-4" }] },
        { week: "W34", range: "18 aug - 24 aug", total: 9, message: "+1 boven norm", over: true, items: [{ label: "Op te starten", value: 4, tone: "tone-cat-1" }, { label: "Doorlopend", value: 5, tone: "tone-accent" }, { label: "Eindigend", value: 1, tone: "tone-cat-4" }] },
        { week: "W35", range: "25 aug - 31 aug", total: 7, message: "Ruimte: 1", over: false, items: [{ label: "Op te starten", value: 3, tone: "tone-cat-1" }, { label: "Doorlopend", value: 4, tone: "tone-accent" }, { label: "Eindigend", value: 3, tone: "tone-cat-4" }] },
        { week: "W36", range: "1 sep - 7 sep", total: 6, message: "Ruimte: 2", over: false, items: [{ label: "Op te starten", value: 2, tone: "tone-cat-1" }, { label: "Doorlopend", value: 4, tone: "tone-accent" }, { label: "Eindigend", value: 2, tone: "tone-cat-4" }] },
      ],
    };
  },
  computed: {
    displayStatuses: function () {
      var tones = ["tone-neutral", "tone-cat-1", "tone-accent", "tone-warning", "tone-success"];
      return ((this.portfolio && this.portfolio.buckets) || []).map(function (bucket, index) {
        var badge = null;
        var badgeClass = null;
        if (index === 3) {
          badge = "Aankomend";
          badgeClass = "iz-badge--warning";
        } else if (index === 4) {
          badge = "Gereed voor Handover 1";
          badgeClass = "iz-badge--success";
        }
        return {
          ...bucket,
          tone: tones[index],
          percent: bucket.percent.toLocaleString("nl-NL", { maximumFractionDigits: 1 }) + "%",
          badge: badge,
          badgeClass: badgeClass,
        };
      });
    },
    trackedProjects: function () {
      return (this.portfolio && this.portfolio.trackedProjects) || 0;
    },
    untrackedProjectCount: function () {
      return (this.portfolio && this.portfolio.untrackedProjects && this.portfolio.untrackedProjects.length) || 0;
    },
    donutStyle: function () {
      var buckets = (this.portfolio && this.portfolio.buckets) || [];
      if (!buckets.length || !this.trackedProjects) return { background: "var(--iz-surface-inset)" };

      var colors = ["var(--iz-surface-inset)", "var(--iz-cat-1)", "var(--iz-accent)", "var(--iz-warning)", "var(--iz-success)"];
      var start = 0;
      var stops = buckets.map(function (bucket, index) {
        var end = start + Number(bucket.percent || 0);
        var stop = colors[index] + " " + start + "% " + end + "%";
        start = end;
        return stop;
      });
      return { background: "conic-gradient(" + stops.join(", ") + ")" };
    },
  },
  methods: {
    toggle: function () {
      this.collapsed = !this.collapsed;
      if (!this.collapsed && !this.portfolio && !this.portfolioLoading) this.fetchPortfolio();
    },
    fetchPortfolio: async function () {
      this.portfolioLoading = true;
      this.portfolioError = null;
      try {
        var response = await axios.get(generateUrl("/apps/projectcreatoraio/api/v1/portfolio/completion"));
        this.portfolio = response.data;
      } catch (error) {
        this.portfolioError = error && error.response && error.response.status === 403
          ? "Je hebt geen toegang tot deze projectgegevens."
          : "De projectvoortgang kon niet worden geladen.";
      } finally {
        this.portfolioLoading = false;
      }
    },
  },
};
</script>

<style scoped>
.portfolio { margin-bottom: var(--iz-gap); }
.portfolio__heading { margin: 0; padding: 0; }
button.portfolio__toggle { width: 100%; min-height: 0; margin: 0; padding: var(--iz-pad-card); border: 0; border-radius: 0; background: transparent; color: var(--iz-text); display: flex; align-items: center; gap: var(--iz-gap-tight); text-align: left; cursor: pointer; }
button.portfolio__toggle:hover { background: var(--iz-surface-subtle); }
button.portfolio__toggle:focus-visible { outline: none; box-shadow: inset 0 0 0 2px var(--iz-accent); }
.portfolio__toggle-title { display: flex; align-items: center; gap: var(--iz-gap-tight); }
.portfolio__toggle-title svg { width: 20px; height: 20px; color: var(--iz-accent); }
.portfolio__toggle-meta { margin-left: auto; color: var(--iz-text-secondary); font-size: var(--iz-fs-sm); font-weight: 600; }
.portfolio__chevron { width: 18px; height: 18px; color: var(--iz-text-muted); transition: transform var(--iz-transition), color var(--iz-transition); }
.portfolio__toggle:hover .portfolio__chevron, .portfolio__chevron--open { color: var(--iz-accent); }
.portfolio__chevron--open { transform: rotate(180deg); }
.portfolio__body { display: grid; gap: var(--iz-gap); padding: var(--iz-pad-panel); border-top: 1px solid var(--iz-border); background: var(--iz-surface-subtle); }
.portfolio__toolbar { display: flex; align-items: center; flex-wrap: wrap; gap: var(--iz-gap); }
.portfolio__filter-group { display: flex; align-items: center; gap: var(--iz-gap-tight); min-width: 0; }
.portfolio__segmented { display: flex; overflow: hidden; border: 1px solid var(--iz-border); border-radius: var(--iz-radius); background: var(--iz-surface); }
.portfolio__segment { padding: 7px 12px; color: var(--iz-text-secondary); font-size: var(--iz-fs-sm); font-weight: 600; white-space: nowrap; border-right: 1px solid var(--iz-border); }
.portfolio__segment:last-child { border-right: 0; }
.portfolio__segment--active { background: var(--iz-accent); color: var(--iz-accent-text); }
.portfolio__control { display: flex; align-items: center; gap: 7px; padding: 7px 10px; border: 1px solid var(--iz-border); border-radius: var(--iz-radius); background: var(--iz-surface); color: var(--iz-text); font-size: var(--iz-fs-sm); white-space: nowrap; }
.portfolio__control svg { width: 16px; height: 16px; color: var(--iz-accent); }
.portfolio__capacity { display: flex; align-items: center; gap: var(--iz-gap-tight); margin-left: auto; padding-left: var(--iz-gap); border-left: 1px solid var(--iz-border); color: var(--iz-text); }
.portfolio__capacity > svg { width: 26px; height: 26px; color: var(--iz-accent); }
.portfolio__capacity span { display: grid; gap: 2px; }
.portfolio__capacity small, .portfolio-kpi small, .portfolio__gap-copy small, .portfolio-week small { color: var(--iz-text-secondary); font-size: var(--iz-fs-xs); }
.portfolio__kpis { gap: var(--iz-gap); }
.portfolio-kpi { flex-direction: row; align-items: center; gap: var(--iz-gap); padding: var(--iz-pad-card); }
.portfolio-kpi__icon { width: 48px; height: 48px; flex: 0 0 48px; display: grid; place-items: center; border-radius: var(--iz-radius-pill); background: color-mix(in srgb, currentColor 14%, transparent); }
.portfolio-kpi__icon svg { width: 26px; height: 26px; }
.portfolio-kpi__ring { width: 24px; height: 24px; border: 4px solid currentColor; border-right-color: transparent; border-radius: var(--iz-radius-pill); }
.portfolio-kpi__copy { display: grid; min-width: 0; }
.portfolio-kpi__value { color: var(--iz-text); font-size: var(--iz-fs-2xl); line-height: 1; }
.portfolio-kpi__label { color: var(--iz-text); font-size: var(--iz-fs-md); font-weight: 700; }
.portfolio__row-arrow { width: 16px; height: 16px; flex: 0 0 auto; color: var(--iz-accent); }
.portfolio-kpi > .portfolio__row-arrow { margin-left: auto; }
.portfolio__overview-grid { display: grid; grid-template-columns: minmax(0, 3fr) minmax(360px, 2fr); gap: var(--iz-gap); }
.portfolio__status-content { display: grid; grid-template-columns: 220px minmax(0, 1fr); align-items: center; gap: var(--iz-gap); }
.portfolio__donut { width: 180px; aspect-ratio: 1; margin: auto; display: grid; place-items: center; border-radius: var(--iz-radius-pill); }
.portfolio__donut::before { content: ""; grid-area: 1 / 1; width: 55%; aspect-ratio: 1; border-radius: var(--iz-radius-pill); background: var(--iz-surface); }
.portfolio__donut span { grid-area: 1 / 1; z-index: 1; display: grid; text-align: center; }
.portfolio__donut strong { font-size: var(--iz-fs-2xl); color: var(--iz-text); }
.portfolio__donut small { color: var(--iz-text-secondary); font-size: var(--iz-fs-xs); }
.portfolio__legend { border: 1px solid var(--iz-border); border-radius: var(--iz-radius); overflow: hidden; }
.portfolio__legend-row { display: grid; grid-template-columns: auto minmax(75px, auto) minmax(0, 1fr) 28px 40px auto; align-items: center; gap: var(--iz-gap-tight); min-height: 42px; padding: 0 12px; border-bottom: 1px solid var(--iz-border); color: var(--iz-text-secondary); font-size: var(--iz-fs-sm); }
.portfolio__legend-row:last-child { border-bottom: 0; }
.portfolio__legend-row > strong:first-of-type { color: var(--iz-text); }
.portfolio__legend-count { text-align: right; color: var(--iz-text); }
.portfolio__status-state { grid-column: 1 / -1; display: flex; align-items: center; justify-content: center; gap: var(--iz-gap-tight); min-height: 180px; }
.portfolio__untracked { margin: var(--iz-gap-tight) 0 0; color: var(--iz-text-secondary); font-size: var(--iz-fs-xs); }
.portfolio__legend-dot { width: 10px; height: 10px; flex: 0 0 10px; border-radius: var(--iz-radius-pill); background: currentColor; }
.tone-accent { color: var(--iz-accent); }.tone-warning { color: var(--iz-warning); }.tone-success { color: var(--iz-success); }.tone-danger { color: var(--iz-danger); }.tone-neutral { color: var(--iz-text-muted); }.tone-cat-1 { color: var(--iz-cat-1); }.tone-cat-4 { color: var(--iz-cat-4); }
.portfolio__danger-title { display: flex; align-items: center; gap: var(--iz-gap-tight); }
.portfolio__danger-title svg { width: 22px; height: 22px; color: var(--iz-danger); }
.portfolio__link { color: var(--iz-accent); font-size: var(--iz-fs-sm); font-weight: 600; }
.portfolio__gap-list { display: grid; gap: var(--iz-gap-tight); }
.portfolio__gap-row { display: grid; grid-template-columns: auto minmax(0, 1fr) auto auto auto; gap: var(--iz-gap-tight); padding: var(--iz-pad-row); }
.portfolio__pin { width: 18px; height: 18px; color: var(--iz-accent); }
.portfolio__gap-copy { display: grid; min-width: 0; }
.portfolio__workload-header { align-items: center; }
.portfolio__capacity-note { padding: 8px 12px; border-radius: var(--iz-radius); background: var(--iz-accent-bg); color: var(--iz-accent-bg-text); font-size: var(--iz-fs-sm); }
.portfolio__weeks { display: grid; grid-template-columns: repeat(6, minmax(150px, 1fr)); gap: var(--iz-gap-tight); overflow-x: auto; }
.portfolio-week { display: grid; gap: var(--iz-gap-tight); min-width: 150px; }
.portfolio-week header { display: flex; justify-content: space-between; gap: var(--iz-gap-tight); align-items: baseline; }
.portfolio-week header > strong { color: var(--iz-text); font-size: var(--iz-fs-lg); }
.portfolio-week__row, .portfolio-week__total { display: grid; grid-template-columns: auto minmax(0, 1fr) auto; align-items: center; gap: 7px; color: var(--iz-text-secondary); font-size: var(--iz-fs-xs); }
.portfolio-week__total { padding-top: var(--iz-gap-tight); border-top: 1px solid var(--iz-border); color: var(--iz-text); }
.portfolio-week__capacity { display: flex; justify-content: space-between; gap: 5px; margin-top: auto; padding: 8px 10px; border-radius: var(--iz-radius); font-size: var(--iz-fs-xs); }
.portfolio-week__capacity--ok { background: var(--iz-success-bg); color: var(--iz-success-text); }
.portfolio-week__capacity--over { background: var(--iz-danger-bg); color: var(--iz-danger-text); }
@media (max-width: 1200px) { .portfolio__overview-grid { grid-template-columns: 1fr; }.portfolio__capacity { width: 100%; margin-left: 0; padding: var(--iz-gap-tight) 0 0; border-left: 0; border-top: 1px solid var(--iz-border); } }
@media (max-width: 720px) { .portfolio__body { padding: var(--iz-pad-card); }.portfolio__filter-group { width: 100%; align-items: flex-start; flex-direction: column; }.portfolio__segmented { width: 100%; overflow-x: auto; }.portfolio__segment { flex: 1 0 auto; text-align: center; }.portfolio__status-content { grid-template-columns: 1fr; }.portfolio__overview-grid { grid-template-columns: minmax(0, 1fr); }.portfolio__gap-row { grid-template-columns: auto minmax(0, 1fr) auto; }.portfolio__gap-row > strong { grid-column: 2; }.portfolio__gap-row .iz-badge { grid-column: 2; justify-self: start; }.portfolio__gap-row .portfolio__row-arrow { grid-column: 3; grid-row: 1 / 4; }.portfolio__toggle-meta { display: none; }.portfolio__workload-header { align-items: flex-start; flex-direction: column; } }
@media (prefers-reduced-motion: reduce) { .portfolio__chevron { transition: none; } }
</style>
