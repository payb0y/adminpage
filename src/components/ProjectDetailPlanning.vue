<template>
  <div class="detail-planning">
    <!-- ── Top Bar / Header ── -->
    <header class="detail-planning__header">
      <div class="detail-planning__title-group">
        <div class="detail-planning__brand-row">
          <span class="detail-planning__brand">InZicht</span>
          <h2 class="detail-planning__title">Detailplanning - {{ project.name }}</h2>
        </div>
        <p class="detail-planning__subtitle">Van intake tot Handover 1 · Initiatiefase</p>
      </div>

      <div class="detail-planning__header-actions">
        <button
          type="button"
          class="iz-btn iz-btn--secondary detail-planning__back-btn"
          @click="$emit('back')"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="detail-planning__btn-icon">
            <polyline points="15 18 9 12 15 6" />
          </svg>
          Terug naar Planningsoverzicht
        </button>
        <span class="detail-planning__last-updated">
          Laatst bijgewerkt {{ lastUpdatedText }}
        </span>
      </div>
    </header>

    <!-- ── Top Project KPI Summary Card ── -->
    <section class="iz-card detail-planning__summary-card">
      <div class="detail-planning__identity-col">
        <div class="detail-planning__thumb-map" ref="thumbMap" title="Projectlocatie">
          <svg v-if="!hasMap" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="detail-planning__thumb-placeholder">
            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 1 1 18 0z" fill="#f3f4f6" />
            <circle cx="12" cy="10" r="3" fill="#ef4444" stroke="none" />
          </svg>
        </div>
        <div class="detail-planning__identity-info">
          <h3 class="detail-planning__project-name">{{ project.name }}</h3>
          <p class="detail-planning__project-city">{{ projectCity }}</p>
          <div class="detail-planning__badges">
            <span class="iz-badge iz-badge--accent">Initiatiefase</span>
            <span class="iz-badge" :class="statusBadgeClass">{{ statusBadgeText }}</span>
          </div>
        </div>
      </div>

      <div class="detail-planning__kpis-strip">
        <!-- 1. Gereedheid -->
        <div class="detail-planning__kpi-tile">
          <span class="detail-planning__kpi-label">Gereedheid</span>
          <strong class="detail-planning__kpi-val">{{ project.completionPct || 0 }}%</strong>
          <span class="iz-badge iz-badge--sm" :class="bucketBadgeClass">{{ project.bucketLabel || '75-99%' }}</span>
        </div>

        <!-- 2. Verwacht 100% -->
        <div class="detail-planning__kpi-tile">
          <span class="detail-planning__kpi-label">Verwacht 100%</span>
          <strong class="detail-planning__kpi-val">{{ formatWeekShort(project.expected100Week) || 'W28' }}</strong>
          <small class="detail-planning__kpi-sub">{{ project.expected100Countdown || 'Nog 0 weken' }}</small>
        </div>

        <!-- 3. Start Werkvoorbereiding -->
        <div class="detail-planning__kpi-tile">
          <span class="detail-planning__kpi-label">Start Werkvoorbereiding</span>
          <strong class="detail-planning__kpi-val">{{ formatWeekShort(project.startPrepWeek) || 'W29' }}</strong>
          <small class="detail-planning__kpi-sub">{{ project.startPrepCountdown || 'Nog 3 weken' }}</small>
        </div>

        <!-- 4. Min. uitvoeringsstart -->
        <div class="detail-planning__kpi-tile">
          <span class="detail-planning__kpi-label">Min. uitvoeringsstart</span>
          <strong class="detail-planning__kpi-val">{{ formatWeekShort(project.minExecutionStartWeek) || 'W33' }}</strong>
          <small class="detail-planning__kpi-sub">{{ project.minExecutionStartCountdown || 'Nog 7 weken' }}</small>
        </div>

        <!-- 5. Wensweek (klant) -->
        <div class="detail-planning__kpi-tile">
          <span class="detail-planning__kpi-label">Wensweek (klant)</span>
          <strong class="detail-planning__kpi-val">{{ formatWeekShort(project.desiredStartWeek) || 'W32' }}</strong>
          <small class="detail-planning__kpi-sub">{{ project.desiredCountdown || 'Nog 6 weken' }}</small>
        </div>

        <!-- 6. Planningsgat -->
        <div class="detail-planning__kpi-tile detail-planning__kpi-tile--gap" :class="{ 'detail-planning__kpi-tile--has-gap': hasGap }">
          <span class="detail-planning__kpi-label">Planningsgat</span>
          <strong class="detail-planning__kpi-val detail-planning__kpi-val--gap">
            {{ gapSummaryText }}
          </strong>
        </div>

        <!-- Acties button -->
        <div class="detail-planning__actions-tile">
          <button
            type="button"
            class="iz-btn iz-btn--secondary iz-btn--sm"
            @click="actionsMenuOpen = !actionsMenuOpen"
          >
            Acties
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="detail-planning__btn-chevron">
              <polyline points="6 9 12 15 18 9" />
            </svg>
          </button>
          <div v-if="actionsMenuOpen" class="detail-planning__actions-menu iz-card" @click="actionsMenuOpen = false">
            <button v-if="project.boardId" type="button" @click="openDeckBoard">
              Open Deck bord ↗
            </button>
            <button type="button" @click="openProjectsApp">
              Open in Projecten ↗
            </button>
          </div>
        </div>
      </div>
    </section>

    <!-- ── Planning Filter & Mode Strip ── -->
    <div class="detail-planning__toolbar iz-card iz-card--flat">
      <div class="detail-planning__toolbar-left">
        <span class="detail-planning__toolbar-heading">Planning</span>
        <div class="detail-planning__pill-group" role="group" aria-label="Planning weergavemodus">
          <button
            type="button"
            class="detail-planning__pill"
            :class="{ 'detail-planning__pill--active': activeMode === 'actueel' }"
            @click="activeMode = 'actueel'"
          >
            Actueel
          </button>
          <button
            type="button"
            class="detail-planning__pill"
            :class="{ 'detail-planning__pill--active': activeMode === 'baseline' }"
            @click="activeMode = 'baseline'"
          >
            Baseline vergelijken
          </button>
          <button
            type="button"
            class="detail-planning__pill"
            :class="{ 'detail-planning__pill--active': activeMode === 'werkelijk' }"
            @click="activeMode = 'werkelijk'"
          >
            Werkelijk
          </button>
          <button
            type="button"
            class="detail-planning__pill detail-planning__pill--driving"
            :class="{ 'detail-planning__pill--active': activeMode === 'driving' }"
            @click="activeMode = 'driving'"
          >
            Driving path → Handover 1
          </button>
          <button
            type="button"
            class="detail-planning__pill"
            :class="{ 'detail-planning__pill--active': activeMode === 'whatif' }"
            @click="activeMode = 'whatif'"
          >
            What-if
          </button>
          <button
            type="button"
            class="detail-planning__pill"
            :class="{ 'detail-planning__pill--active': activeMode === 'historie' }"
            @click="activeMode = 'historie'"
          >
            Historie
          </button>
        </div>
      </div>

      <div class="detail-planning__toolbar-right">
        <button type="button" class="iz-btn iz-btn--secondary iz-btn--sm" title="Filters aanpassen">
          Filters
        </button>
        <div class="detail-planning__week-stepper">
          <button type="button" class="detail-planning__stepper-btn" aria-label="Vorige weken" @click="shiftPeriod(-1)">
            ‹
          </button>
          <span class="detail-planning__week-range">{{ displayedWeekRangeText }}</span>
          <button type="button" class="detail-planning__stepper-btn" aria-label="Volgende weken" @click="shiftPeriod(1)">
            ›
          </button>
        </div>
      </div>
    </div>

    <!-- ── Main 2-Column Grid (Timeline + Sidebar) ── -->
    <div class="detail-planning__main-grid">
      <!-- ── Left Column: Planning - Tijdlijn ── -->
      <section class="iz-card detail-planning__timeline-card">
        <header class="detail-planning__timeline-header">
          <h4 class="iz-panel__title">Planning - Tijdlijn</h4>
        </header>

        <div v-if="loadingTimeline" class="iz-empty detail-planning__timeline-loading">
          Tijdlijn synchroniseren...
        </div>

        <div v-else class="detail-planning__gantt-wrapper">
          <table class="detail-planning__gantt-table">
            <thead>
              <tr>
                <th class="detail-planning__th-task">Taak / Mijlpaal</th>
                <th class="detail-planning__th-col">Start</th>
                <th class="detail-planning__th-col">Eind</th>
                <th class="detail-planning__th-col">Actieve duur</th>
                <th class="detail-planning__th-col">Kalenderdoorlooptijd</th>
                <th
                  v-for="(w, idx) in visibleTimelineWeeks"
                  :key="w.label"
                  class="detail-planning__th-week"
                  :class="{ 'detail-planning__th-week--today': w.isToday }"
                >
                  <div class="detail-planning__week-header-inner">
                    <strong>{{ w.label }}</strong>
                    <small>{{ w.sub }}</small>
                    <span v-if="w.isToday" class="detail-planning__peildatum-tag">Peildatum {{ w.peildatum }}</span>
                  </div>
                </th>
              </tr>
            </thead>
            <tbody>
              <template v-for="phase in displayPhases">
                <!-- Phase Section Row -->
                <tr :key="'ph-' + phase.id" class="detail-planning__row-phase">
                  <td :colspan="5 + visibleTimelineWeeks.length">
                    <strong>{{ phase.name }}</strong>
                  </td>
                </tr>

                <!-- Tasks inside phase -->
                <tr
                  v-for="task in phase.tasks"
                  :key="'t-' + task.id"
                  class="detail-planning__row-task"
                  :class="{ 'detail-planning__row-task--milestone': task.isMilestone }"
                >
                  <td class="detail-planning__td-task">
                    <span class="detail-planning__task-name">{{ task.name }}</span>
                  </td>
                  <td class="detail-planning__td-meta">{{ task.startWeek }}</td>
                  <td class="detail-planning__td-meta">{{ task.endWeek }}</td>
                  <td class="detail-planning__td-meta">{{ task.activeDuration }}</td>
                  <td class="detail-planning__td-meta">{{ task.calendarDuration }}</td>

                  <!-- Gantt Bar Columns -->
                  <td
                    v-for="w in visibleTimelineWeeks"
                    :key="task.id + '-' + w.label"
                    class="detail-planning__td-gantt-cell"
                    :class="{ 'detail-planning__td-gantt-cell--today': w.isToday }"
                  >
                    <!-- Gantt Bar Element -->
                    <div
                      v-if="isTaskInWeek(task, w)"
                      class="detail-planning__bar"
                      :class="getGanttBarClass(task, w)"
                    >
                      <span v-if="task.isMilestone" class="detail-planning__diamond" :class="'detail-planning__diamond--' + (task.milestoneTone || 'default')" />
                      <span v-else-if="task.isDone" class="detail-planning__bar-done-check">✓</span>
                    </div>

                    <!-- Gap Highlight Box -->
                    <div
                      v-if="isGapInWeek(task, w)"
                      class="detail-planning__gap-strip"
                      :title="'Planningsgat: ' + gapSummaryText"
                    >
                      <span v-if="isGapStartWeek(w)" class="detail-planning__gap-label">
                        Planningsgat · {{ gapSummaryText }}
                      </span>
                    </div>
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>

        <!-- Chart Legend Footer -->
        <footer class="detail-planning__legend">
          <div class="detail-planning__legend-item">
            <span class="detail-planning__legend-dot detail-planning__legend-dot--done" />
            <span>Werkelijk afgerond</span>
          </div>
          <div class="detail-planning__legend-item">
            <span class="detail-planning__legend-bar detail-planning__legend-bar--active" />
            <span>Actueel</span>
          </div>
          <div class="detail-planning__legend-item">
            <span class="detail-planning__legend-line detail-planning__legend-line--baseline" />
            <span>Baseline</span>
          </div>
          <div class="detail-planning__legend-item">
            <span class="detail-planning__legend-line detail-planning__legend-line--driving" />
            <span>Driving path → Handover 1</span>
          </div>
          <div class="detail-planning__legend-item">
            <span class="detail-planning__legend-box detail-planning__legend-box--gap" />
            <span>Planningsgat</span>
          </div>
          <div class="detail-planning__legend-item">
            <span class="detail-planning__legend-diamond" />
            <span>Mijlpaal</span>
          </div>
        </footer>
      </section>

      <!-- ── Right Column: Sidebar Widgets ── -->
      <aside class="detail-planning__sidebar">
        <!-- 1. Locatie Card -->
        <div class="iz-card detail-planning__side-card">
          <header class="detail-planning__side-header">
            <h4 class="iz-panel__title">Locatie</h4>
          </header>
          <div class="detail-planning__side-body">
            <div class="detail-planning__side-map" ref="sideMap">
              <svg v-if="!hasMap" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="detail-planning__side-map-placeholder">
                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 1 1 18 0z" fill="#f8fafc" stroke="#94a3b8" />
                <circle cx="12" cy="10" r="3" fill="#ef4444" stroke="none" />
              </svg>
            </div>
            <div class="detail-planning__side-location-info">
              <strong>{{ project.name }}</strong>
              <p>{{ projectCity }}</p>
              <a
                :href="googleMapsUrl"
                target="_blank"
                rel="noopener noreferrer"
                class="detail-planning__side-link"
              >
                Open in Maps →
              </a>
            </div>
          </div>
        </div>

        <!-- 2. What-if Scenario Card -->
        <div class="iz-card detail-planning__side-card">
          <header class="detail-planning__side-header">
            <h4 class="iz-panel__title">What-if scenario</h4>
          </header>
          <div class="detail-planning__side-body">
            <div class="detail-planning__whatif-banner">
              <strong>Conceptscenario — live planning ongewijzigd</strong>
              <p>Pas na 'Scenario toepassen' wordt de live planning gewijzigd.</p>
            </div>

            <div class="detail-planning__whatif-field">
              <span class="detail-planning__whatif-label">Handeling</span>
              <span class="iz-badge iz-badge--sm">Werkvoorbereiding</span>
            </div>

            <div class="detail-planning__whatif-durations">
              <div class="detail-planning__whatif-row">
                <span>Huidige actieve duur</span>
                <strong>{{ prepDurationWeeks }} weken</strong>
              </div>
              <div class="detail-planning__whatif-row detail-planning__whatif-row--stepper">
                <span>Nieuwe actieve duur</span>
                <div class="detail-planning__mini-stepper">
                  <button type="button" :disabled="simulatedPrepWeeks <= 1" @click="simulatedPrepWeeks--">-</button>
                  <span>{{ simulatedPrepWeeks }} weken</span>
                  <button type="button" @click="simulatedPrepWeeks++">+</button>
                </div>
              </div>
            </div>

            <div class="detail-planning__whatif-impact">
              <small class="detail-planning__impact-heading">Voorlopige impact</small>
              <div class="detail-planning__impact-row">
                <span>Min. uitvoeringsstart</span>
                <strong :class="{ 'detail-planning__text--warning': simulatedPrepWeeks > prepDurationWeeks }">
                  {{ simulatedMinExecWeek }} ({{ formatDelta(simulatedPrepWeeks - prepDurationWeeks) }})
                </strong>
              </div>
              <div class="detail-planning__impact-row">
                <span>Wensweek (klant)</span>
                <span>{{ formatWeekShort(project.desiredStartWeek) || 'W32' }} (ongewijzigd)</span>
              </div>
              <div class="detail-planning__impact-row">
                <span>Afwijking t.o.v. wensweek</span>
                <strong>+{{ simulatedDeviationWeeks }} weken</strong>
              </div>
              <div class="detail-planning__impact-row">
                <span>Planningsgat</span>
                <strong :class="simulatedGapWeeks > 0 ? 'detail-planning__text--danger' : 'detail-planning__text--success'">
                  {{ simulatedGapWeeks > 0 ? simulatedGapWeeks + ' weken' : 'Geen gat' }}
                </strong>
              </div>
            </div>

            <div class="detail-planning__whatif-actions">
              <button type="button" class="iz-btn iz-btn--secondary iz-btn--sm" @click="resetSimulation">
                Reset
              </button>
              <button type="button" class="iz-btn iz-btn--primary iz-btn--sm" title="Read-only in analytics dashboard" disabled>
                Toepassen
              </button>
            </div>
          </div>
        </div>

        <!-- 3. Open Kaarten Card -->
        <div class="iz-card detail-planning__side-card">
          <header class="detail-planning__side-header">
            <h4 class="iz-panel__title">Open kaarten</h4>
          </header>
          <div class="detail-planning__side-body detail-planning__open-cards-body">
            <div class="detail-planning__big-number">
              {{ project.openCards !== undefined ? project.openCards : (project.cards ? project.cards.open : 3) }}
            </div>
            <button
              type="button"
              class="iz-btn iz-btn--secondary iz-btn--sm"
              @click="openDeckBoard"
            >
              Naar Procesvoortgang →
            </button>
          </div>
        </div>
      </aside>
    </div>

    <!-- ── Bottom 4 Analytical Insight Cards ── -->
    <footer class="detail-planning__bottom-grid">
      <!-- Card 1: Kritische aandachtspunten -->
      <article class="iz-card detail-planning__bottom-card">
        <header class="detail-planning__bottom-card-header">
          <h4 class="iz-panel__title">Kritische aandachtspunten</h4>
        </header>
        <div class="detail-planning__bottom-card-body">
          <ul class="detail-planning__bullet-list">
            <li v-if="hasGap" class="detail-planning__bullet detail-planning__bullet--danger">
              <span class="detail-planning__bullet-dot" />
              <strong>Planningsgat: {{ gapSummaryText }}</strong>
            </li>
            <li v-if="hasWensweekWarning" class="detail-planning__bullet detail-planning__bullet--warning">
              <span class="detail-planning__bullet-dot" />
              <strong>Wensweek {{ formatWeekShort(project.desiredStartWeek) }} vóór minimale uitvoeringsstart {{ formatWeekShort(project.minExecutionStartWeek) }}</strong>
            </li>
            <li v-if="!hasGap && !hasWensweekWarning" class="detail-planning__bullet detail-planning__bullet--success">
              <span class="detail-planning__bullet-dot" />
              <span>Geen kritieke knelpunten vastgesteld; project ligt op schema.</span>
            </li>
          </ul>
          <small class="detail-planning__bottom-meta">Oorzaak/veroorzaker blijft gelogd in historie.</small>
        </div>
      </article>

      <!-- Card 2: Doorlooptijden per fase -->
      <article class="iz-card detail-planning__bottom-card">
        <header class="detail-planning__bottom-card-header">
          <h4 class="iz-panel__title">Doorlooptijden per fase</h4>
        </header>
        <div class="detail-planning__bottom-card-body">
          <table class="detail-planning__mini-table">
            <thead>
              <tr>
                <th>Fase</th>
                <th class="detail-planning__text-right">Som actieve taakduur</th>
                <th class="detail-planning__text-right">Kalender</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="f in phaseDurations" :key="f.name" :class="{ 'detail-planning__mini-table-total': f.isTotal }">
                <td>{{ f.name }}</td>
                <td class="detail-planning__text-right">{{ f.active }}</td>
                <td class="detail-planning__text-right">{{ f.calendar }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </article>

      <!-- Card 3: Vervolg na Handover — referentie -->
      <article class="iz-card detail-planning__bottom-card">
        <header class="detail-planning__bottom-card-header">
          <h4 class="iz-panel__title">Vervolg na Handover — referentie</h4>
        </header>
        <div class="detail-planning__bottom-card-body">
          <div class="detail-planning__handover-summary">
            <strong>Werkvoorbereiding</strong>
            <div class="detail-planning__handover-metric">
              <span>Actieve duur</span>
              <strong>{{ prepDurationWeeks }} weken</strong>
            </div>
            <div class="detail-planning__handover-metric">
              <span>Kalenderdoorlooptijd</span>
              <strong>{{ Math.max(prepDurationWeeks, prepDurationWeeks + (project.gapWeeks || 0)) }} weken</strong>
            </div>
            <small class="detail-planning__bottom-meta">
              {{ formatWeekShort(project.startPrepWeek) || 'W29' }} → start {{ formatWeekShort(project.minExecutionStartWeek) || 'W33' }}; {{ hasGap ? 'gap ' + (project.gapSpan || 'W32-W33') + ' bevat geen actieve balk.' : 'geen planningsgat.' }}
            </small>
          </div>
        </div>
      </article>

      <!-- Card 4: Baseline / actueel / werkelijk -->
      <article class="iz-card detail-planning__bottom-card">
        <header class="detail-planning__bottom-card-header">
          <h4 class="iz-panel__title">Baseline / actueel / werkelijk</h4>
        </header>
        <div class="detail-planning__bottom-card-body">
          <div class="detail-planning__baseline-rows">
            <div class="detail-planning__baseline-row">
              <span>Baseline Handover 1</span>
              <strong>{{ formatWeekShort(project.startPrepWeek) || 'W29' }}</strong>
            </div>
            <div class="detail-planning__baseline-row">
              <span>Actueel Handover 1</span>
              <strong>{{ formatWeekShort(project.startPrepWeek) || 'W29' }}</strong>
            </div>
            <div class="detail-planning__baseline-row">
              <span>Werkelijk</span>
              <span :class="project.isCompleted ? 'detail-planning__text--success' : 'detail-planning__text--muted'">
                {{ project.isCompleted ? 'Afgerond' : 'nog niet uitgevoerd' }}
              </span>
            </div>
          </div>
        </div>
      </article>
    </footer>
  </div>
</template>

<script>
import axios from "@nextcloud/axios";
import { generateUrl } from "@nextcloud/router";
import L from "leaflet";
import "leaflet/dist/leaflet.css";

export default {
  name: "ProjectDetailPlanning",
  props: {
    project: {
      type: Object,
      required: true,
    },
    organizationId: {
      type: Number,
      default: null,
    },
  },
  data() {
    return {
      activeMode: "actueel",
      weekOffset: 0,
      actionsMenuOpen: false,
      loadingTimeline: false,
      timelineItems: [],
      timelineSummary: null,
      simulatedPrepWeeks: Number(this.project.requiredPrepWeeks || 2),
      miniMapInstance: null,
      sideMapInstance: null,
      hasMap: false,
      lastUpdated: new Date(),
    };
  },
  computed: {
    prepDurationWeeks() {
      return Number(this.project.requiredPrepWeeks || 2);
    },
    lastUpdatedText() {
      var h = String(this.lastUpdated.getHours()).padStart(2, "0");
      var m = String(this.lastUpdated.getMinutes()).padStart(2, "0");
      return "vandaag " + h + ":" + m;
    },
    projectCity() {
      return this.project.city || this.project.address || "Amsterdam";
    },
    statusBadgeText() {
      if (this.project.isCompleted) return "100% gereed";
      if (this.project.bucket === "75-99") return "Aankomend";
      return this.project.bucketLabel || "In voorbereiding";
    },
    statusBadgeClass() {
      if (this.project.isCompleted) return "iz-badge--success";
      if (this.project.bucket === "75-99") return "iz-badge--warning";
      return "";
    },
    bucketBadgeClass() {
      if (this.project.completionPct >= 100) return "iz-badge--success";
      if (this.project.completionPct >= 75) return "iz-badge--warning";
      return "";
    },
    hasGap() {
      return !!(this.project.planningGap && this.project.planningGap.hasGap)
        || (this.project.gapWeeks && this.project.gapWeeks > 0);
    },
    gapSummaryText() {
      if (this.project.planningGapDisplay && this.project.planningGapDisplay !== "None") {
        return this.project.planningGapDisplay;
      }
      if (this.hasGap) {
        var w = this.project.gapWeeks || 2;
        var s = this.project.gapSpan || "W32-W33";
        return w + (w === 1 ? " week · " : " weken · ") + s;
      }
      return "Geen planningsgat";
    },
    hasWensweekWarning() {
      if (!this.project.desiredStartWeek || !this.project.minExecutionStartWeek) return false;
      return this.project.desiredStartWeek < this.project.minExecutionStartWeek;
    },
    googleMapsUrl() {
      var query = encodeURIComponent((this.project.name || "") + " " + (this.projectCity || ""));
      return "https://www.google.com/maps/search/?api=1&query=" + query;
    },
    visibleTimelineWeeks() {
      var baseYear = 2026;
      var startWeek = 24 + (this.weekOffset * 6);
      var weeks = [];
      var months = ["jan", "feb", "mrt", "apr", "mei", "jun", "jul", "aug", "sep", "okt", "nov", "dec"];

      for (var i = 0; i < 13; i++) {
        var wNum = startWeek + i;
        var approxMonthIdx = Math.min(11, Math.max(0, Math.floor((wNum - 1) / 4.33)));
        var startDay = ((wNum * 7) % 28) + 1;
        var endDay = startDay + 6;
        var isToday = (wNum === 31);

        weeks.push({
          num: wNum,
          label: "W" + wNum,
          sub: startDay + "-" + endDay + " " + months[approxMonthIdx],
          isToday: isToday,
          peildatum: isToday ? "3 aug" : null,
        });
      }
      return weeks;
    },
    displayedWeekRangeText() {
      if (!this.visibleTimelineWeeks.length) return "W24-W36 · 2026";
      var first = this.visibleTimelineWeeks[0].label;
      var last = this.visibleTimelineWeeks[this.visibleTimelineWeeks.length - 1].label;
      return first + "-" + last + " · 2026";
    },
    simulatedMinExecWeek() {
      var delta = this.simulatedPrepWeeks - this.prepDurationWeeks;
      var baseWeek = 33;
      var match = String(this.project.minExecutionStartWeek || "").match(/W(\d+)/i);
      if (match) baseWeek = Number(match[1]);
      return "W" + (baseWeek + delta);
    },
    simulatedDeviationWeeks() {
      var delta = this.simulatedPrepWeeks - this.prepDurationWeeks;
      return Math.max(0, 1 + delta);
    },
    simulatedGapWeeks() {
      var delta = this.simulatedPrepWeeks - this.prepDurationWeeks;
      return Math.max(0, 2 + delta);
    },
    phaseDurations() {
      return [
        { name: "1. Intake & Analyse", active: "6 w", calendar: "3 w", isTotal: false },
        { name: "2. Ontwerp", active: "2 w", calendar: "2 w", isTotal: false },
        { name: "3. Afstemming & Toetsing", active: "2 w", calendar: "2 w", isTotal: false },
        { name: "4. Besluitvorming", active: "3 w", calendar: "2 w", isTotal: false },
        { name: "T/m Handover 1", active: "13 w", calendar: "5 w", isTotal: true },
      ];
    },
    displayPhases() {
      // Default initiation template matching the client mockup
      return [
        {
          id: 1,
          name: "1. Intake & Analyse",
          tasks: [
            { id: 101, name: "Intakeformulier", startWeek: "W24", endWeek: "W24", activeDuration: "1 w", calendarDuration: "1 w", startW: 24, endW: 24, isDone: true },
            { id: 102, name: "Piekvermogensformulier", startWeek: "W24", endWeek: "W25", activeDuration: "2 w", calendarDuration: "2 w", startW: 24, endW: 25, isDone: true },
            { id: 103, name: "Quickscan", startWeek: "W25", endWeek: "W25", activeDuration: "1 w", calendarDuration: "1 w", startW: 25, endW: 25, isDone: true },
            { id: 104, name: "Situatietekening", startWeek: "W25", endWeek: "W26", activeDuration: "2 w", calendarDuration: "2 w", startW: 25, endW: 26, isDone: true },
          ],
        },
        {
          id: 2,
          name: "2. Ontwerp",
          tasks: [
            { id: 201, name: "Voorlopig Ontwerp (incl. AVP)", startWeek: "W26", endWeek: "W27", activeDuration: "2 w", calendarDuration: "2 w", startW: 26, endW: 27, isDone: false, isCurrent: true },
          ],
        },
        {
          id: 3,
          name: "3. Afstemming & Toetsing",
          tasks: [
            { id: 301, name: "Netbeheerder(s) beoordeling", startWeek: "W27", endWeek: "W28", activeDuration: "2 w", calendarDuration: "2 w", startW: 27, endW: 28, isDone: false, isUpcoming: true },
          ],
        },
        {
          id: 4,
          name: "4. Besluitvorming",
          tasks: [
            { id: 401, name: "Voorwaarden opstellen", startWeek: "W27", endWeek: "W28", activeDuration: "2 w", calendarDuration: "2 w", startW: 27, endW: 28, isDone: false, isUpcoming: true },
            { id: 402, name: "Ondertekening", startWeek: "W28", endWeek: "W28", activeDuration: "1 w", calendarDuration: "1 w", startW: 28, endW: 28, isDone: false, isUpcoming: true },
            { id: 403, name: "100% gereed - Hard Gate 1", startWeek: "W28", endWeek: "W28", activeDuration: "0 w", calendarDuration: "0 w", startW: 28, endW: 28, isMilestone: true, milestoneTone: "green" },
            { id: 404, name: "Handover 1 - Werkvoorbereiding", startWeek: "W29", endWeek: "W29", activeDuration: "0 w", calendarDuration: "0 w", startW: 29, endW: 29, isMilestone: true, milestoneTone: "dark" },
          ],
        },
        {
          id: 5,
          name: "5. Vervolgplanning na Handover — referentie",
          tasks: [
            { id: 501, name: "Werkvoorbereiding", startWeek: "W29", endWeek: "W33", activeDuration: "2 w", calendarDuration: "4 w", startW: 29, endW: 33, isPrepBlock: true },
            { id: 502, name: "Wensweek (klant)", startWeek: "W32", endWeek: "W32", activeDuration: "0 w", calendarDuration: "0 w", startW: 32, endW: 32, isMilestone: true, milestoneTone: "orange" },
            { id: 503, name: "Min. uitvoeringsstart", startWeek: "W33", endWeek: "W33", activeDuration: "0 w", calendarDuration: "0 w", startW: 33, endW: 33, isMilestone: true, milestoneTone: "green" },
          ],
        },
      ];
    },
  },
  mounted() {
    this.fetchProjectTimeline();
    this.initMaps();
  },
  beforeDestroy() {
    if (this.miniMapInstance) this.miniMapInstance.remove();
    if (this.sideMapInstance) this.sideMapInstance.remove();
  },
  methods: {
    formatWeekShort(weekStr) {
      if (!weekStr || weekStr === "—") return "";
      var match = String(weekStr).match(/W(\d+)/i);
      return match ? "W" + match[1] : weekStr;
    },
    formatDelta(delta) {
      if (delta > 0) return "+" + delta;
      if (delta < 0) return String(delta);
      return "ongewijzigd";
    },
    shiftPeriod(direction) {
      this.weekOffset += direction;
    },
    resetSimulation() {
      this.simulatedPrepWeeks = this.prepDurationWeeks;
    },
    openDeckBoard() {
      if (!this.project.boardId) return;
      window.open(generateUrl("/apps/deck/#/board/" + this.project.boardId), "_blank");
    },
    openProjectsApp() {
      window.open(generateUrl("/apps/projectcreatoraio/"), "_blank");
    },
    async fetchProjectTimeline() {
      if (!this.project.id) return;
      this.loadingTimeline = true;
      try {
        var [timelineRes, summaryRes] = await Promise.all([
          axios.get(generateUrl("/apps/projectcreatoraio/api/v1/projects/" + this.project.id + "/timeline")).catch(function () { return { data: [] }; }),
          axios.get(generateUrl("/apps/projectcreatoraio/api/v1/projects/" + this.project.id + "/timeline/summary")).catch(function () { return { data: null }; }),
        ]);
        this.timelineItems = timelineRes.data || [];
        this.timelineSummary = summaryRes.data || null;
      } catch (e) {
        // keep fallback phases template
      } finally {
        this.loadingTimeline = false;
      }
    },
    isTaskInWeek(task, w) {
      if (task.startW === undefined || task.endW === undefined) return false;
      if (task.isMilestone) {
        return task.startW === w.num;
      }
      return w.num >= task.startW && w.num <= task.endW;
    },
    isGapInWeek(task, w) {
      // Highlight gap in the handover referentie row between W32 and W33
      if (!task.isPrepBlock || !this.hasGap) return false;
      return w.num >= 32 && w.num <= 33;
    },
    isGapStartWeek(w) {
      return w.num === 32;
    },
    getGanttBarClass(task, w) {
      if (task.isMilestone) return "detail-planning__bar--milestone";
      if (task.isDone) return "detail-planning__bar--done";
      if (task.isCurrent) return "detail-planning__bar--current";
      if (task.isPrepBlock) return "detail-planning__bar--prep";
      return "detail-planning__bar--upcoming";
    },
    initMaps() {
      var lat = this.project.lat || 52.3676;
      var lon = this.project.lon || 4.9041;
      var zoom = 14;

      this.$nextTick(() => {
        try {
          if (this.$refs.thumbMap) {
            this.miniMapInstance = L.map(this.$refs.thumbMap, {
              zoomControl: false,
              attributionControl: false,
              dragging: false,
              scrollWheelZoom: false,
              doubleClickZoom: false,
            }).setView([lat, lon], zoom);

            L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
              maxZoom: 19,
            }).addTo(this.miniMapInstance);

            L.circleMarker([lat, lon], {
              radius: 6,
              fillColor: "#ef4444",
              color: "#ffffff",
              weight: 2,
              opacity: 1,
              fillOpacity: 1,
            }).addTo(this.miniMapInstance);

            this.hasMap = true;
          }

          if (this.$refs.sideMap) {
            this.sideMapInstance = L.map(this.$refs.sideMap, {
              zoomControl: false,
              attributionControl: false,
            }).setView([lat, lon], zoom);

            L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
              maxZoom: 19,
            }).addTo(this.sideMapInstance);

            L.marker([lat, lon]).addTo(this.sideMapInstance);
          }
        } catch (e) {
          this.hasMap = false;
        }
      });
    },
  },
};
</script>

<style scoped>
.detail-planning {
  display: grid;
  gap: var(--iz-gap);
  padding: 0;
  margin-bottom: var(--iz-gap);
}

/* ── Top Header ── */
.detail-planning__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: var(--iz-gap);
  flex-wrap: wrap;
}
.detail-planning__title-group { display: grid; gap: 4px; }
.detail-planning__brand-row { display: flex; align-items: baseline; gap: var(--iz-gap-tight); }
.detail-planning__brand {
  font-size: var(--iz-fs-lg);
  font-weight: 800;
  color: var(--iz-accent);
  letter-spacing: -0.02em;
}
.detail-planning__title {
  margin: 0;
  font-size: var(--iz-fs-xl);
  font-weight: 700;
  color: var(--iz-text);
}
.detail-planning__subtitle {
  margin: 0;
  color: var(--iz-text-muted);
  font-size: var(--iz-fs-sm);
}
.detail-planning__header-actions {
  display: flex;
  align-items: center;
  gap: var(--iz-gap);
  flex-wrap: wrap;
}
.detail-planning__last-updated {
  font-size: var(--iz-fs-xs);
  color: var(--iz-text-muted);
}
.detail-planning__btn-icon { width: 16px; height: 16px; }

/* ── Top Summary Card ── */
.detail-planning__summary-card {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--iz-gap);
  padding: var(--iz-pad-card);
  flex-wrap: wrap;
}
.detail-planning__identity-col {
  display: flex;
  align-items: center;
  gap: var(--iz-gap);
  min-width: 240px;
}
.detail-planning__thumb-map {
  width: 72px;
  height: 72px;
  border-radius: var(--iz-radius);
  border: 1px solid var(--iz-border);
  overflow: hidden;
  position: relative;
  background: var(--iz-surface-inset);
  flex-shrink: 0;
}
.detail-planning__thumb-placeholder { width: 100%; height: 100%; }
.detail-planning__identity-info { display: grid; gap: 2px; }
.detail-planning__project-name { margin: 0; font-size: var(--iz-fs-lg); font-weight: 700; color: var(--iz-text); }
.detail-planning__project-city { margin: 0; font-size: var(--iz-fs-sm); color: var(--iz-text-muted); }
.detail-planning__badges { display: flex; gap: 6px; margin-top: 4px; }

/* ── KPIs Strip ── */
.detail-planning__kpis-strip {
  display: flex;
  align-items: center;
  gap: var(--iz-gap);
  flex-wrap: wrap;
}
.detail-planning__kpi-tile {
  display: grid;
  gap: 2px;
  min-width: 100px;
  padding: 6px 10px;
  border-radius: var(--iz-radius);
  background: var(--iz-surface-subtle);
  border: 1px solid var(--iz-border);
}
.detail-planning__kpi-label { font-size: var(--iz-fs-xs); color: var(--iz-text-muted); text-transform: uppercase; letter-spacing: 0.03em; }
.detail-planning__kpi-val { font-size: var(--iz-fs-lg); font-weight: 700; color: var(--iz-text); }
.detail-planning__kpi-sub { font-size: var(--iz-fs-xs); color: var(--iz-text-muted); }
.detail-planning__kpi-tile--gap { min-width: 140px; }
.detail-planning__kpi-tile--has-gap {
  background: #fef2f2;
  border-color: #fecaca;
}
.detail-planning__kpi-val--gap { color: #dc2626; font-size: var(--iz-fs-md); }
.detail-planning__actions-tile { position: relative; }
.detail-planning__actions-menu {
  position: absolute;
  top: calc(100% + 4px);
  right: 0;
  z-index: 100;
  display: grid;
  gap: 2px;
  padding: 6px;
  min-width: 160px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.12);
}
.detail-planning__actions-menu button {
  text-align: left;
  background: transparent;
  border: 0;
  padding: 6px 10px;
  border-radius: var(--iz-radius);
  cursor: pointer;
  font-size: var(--iz-fs-sm);
  color: var(--iz-text);
}
.detail-planning__actions-menu button:hover { background: var(--iz-surface-subtle); }
.detail-planning__btn-chevron { width: 14px; height: 14px; }

/* ── Filter Strip ── */
.detail-planning__toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--iz-gap);
  padding: 8px 12px;
  border-radius: var(--iz-radius);
  flex-wrap: wrap;
}
.detail-planning__toolbar-left { display: flex; align-items: center; gap: var(--iz-gap); flex-wrap: wrap; }
.detail-planning__toolbar-heading { font-weight: 700; color: var(--iz-text); font-size: var(--iz-fs-sm); }
.detail-planning__pill-group { display: flex; gap: 4px; flex-wrap: wrap; }
.detail-planning__pill {
  padding: 5px 12px;
  border-radius: 999px;
  border: 1px solid var(--iz-border);
  background: var(--iz-surface);
  font-size: var(--iz-fs-xs);
  font-weight: 500;
  color: var(--iz-text-secondary);
  cursor: pointer;
}
.detail-planning__pill:hover { background: var(--iz-surface-subtle); color: var(--iz-text); }
.detail-planning__pill--active {
  background: #0f172a;
  color: #ffffff;
  border-color: #0f172a;
}
.detail-planning__pill--driving { border-color: #fca5a5; color: #b91c1c; }
.detail-planning__pill--driving.detail-planning__pill--active { background: #b91c1c; color: #fff; border-color: #b91c1c; }
.detail-planning__toolbar-right { display: flex; align-items: center; gap: var(--iz-gap-tight); }
.detail-planning__week-stepper {
  display: flex;
  align-items: center;
  gap: 4px;
  padding: 4px 8px;
  border-radius: var(--iz-radius);
  border: 1px solid var(--iz-border);
  background: var(--iz-surface);
  font-size: var(--iz-fs-xs);
}
.detail-planning__stepper-btn { background: transparent; border: 0; cursor: pointer; font-size: 16px; line-height: 1; padding: 0 4px; }
.detail-planning__week-range { font-weight: 600; }

/* ── Main Grid (Timeline + Sidebar) ── */
.detail-planning__main-grid {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 280px;
  gap: var(--iz-gap);
}
@media (max-width: 1100px) {
  .detail-planning__main-grid { grid-template-columns: minmax(0, 1fr); }
}

/* ── Gantt Chart Area ── */
.detail-planning__timeline-card { padding: var(--iz-pad-card); overflow: hidden; }
.detail-planning__timeline-header { margin-bottom: var(--iz-gap-tight); }
.detail-planning__timeline-loading { padding: 40px; text-align: center; }
.detail-planning__gantt-wrapper {
  overflow-x: auto;
  border: 1px solid var(--iz-border);
  border-radius: var(--iz-radius);
}
.detail-planning__gantt-table {
  width: 100%;
  border-collapse: collapse;
  font-size: var(--iz-fs-xs);
  min-width: 800px;
}
.detail-planning__gantt-table th,
.detail-planning__gantt-table td {
  padding: 6px 8px;
  border-right: 1px solid var(--iz-border);
  border-bottom: 1px solid var(--iz-border);
  vertical-align: middle;
}
.detail-planning__th-task { text-align: left; width: 220px; min-width: 180px; font-weight: 700; }
.detail-planning__th-col { text-align: center; width: 55px; font-weight: 600; color: var(--iz-text-muted); }
.detail-planning__th-week { text-align: center; width: 50px; min-width: 48px; background: var(--iz-surface-subtle); }
.detail-planning__th-week--today { background: #eff6ff; }
.detail-planning__week-header-inner { display: grid; gap: 2px; position: relative; }
.detail-planning__week-header-inner small { font-size: 10px; color: var(--iz-text-muted); }
.detail-planning__peildatum-tag {
  position: absolute;
  top: -18px;
  left: 50%;
  transform: translateX(-50%);
  background: #3b82f6;
  color: #fff;
  font-size: 9px;
  padding: 1px 4px;
  border-radius: 4px;
  white-space: nowrap;
}

/* Rows */
.detail-planning__row-phase td {
  background: var(--iz-surface-subtle);
  font-weight: 700;
  color: var(--iz-text);
  padding: 8px 10px;
}
.detail-planning__td-task { text-align: left; }
.detail-planning__task-name { font-weight: 500; }
.detail-planning__td-meta { text-align: center; color: var(--iz-text-muted); }
.detail-planning__td-gantt-cell { position: relative; padding: 4px 2px; }
.detail-planning__td-gantt-cell--today { background: rgba(59, 130, 246, 0.04); }

/* Gantt Bars */
.detail-planning__bar {
  height: 16px;
  border-radius: 3px;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}
.detail-planning__bar--done { background: #10b981; color: #ffffff; border: 1px solid #059669; }
.detail-planning__bar--current { background: #3b82f6; color: #ffffff; border: 1px solid #2563eb; }
.detail-planning__bar--upcoming { background: #f59e0b; border: 1px solid #d97706; }
.detail-planning__bar--prep { background: #2563eb; border: 1px dashed #1d4ed8; }
.detail-planning__bar--milestone {
  background: transparent;
  box-shadow: none;
}
.detail-planning__bar-done-check { font-size: 11px; line-height: 1; font-weight: 700; }

/* Milestones */
.detail-planning__diamond {
  width: 12px;
  height: 12px;
  transform: rotate(45deg);
  display: inline-block;
}
.detail-planning__diamond--green { background: #10b981; }
.detail-planning__diamond--dark { background: #0f172a; }
.detail-planning__diamond--orange { background: #f97316; }

/* Gap Highlight Region */
.detail-planning__gap-strip {
  position: absolute;
  top: 2px;
  bottom: 2px;
  left: 0;
  right: 0;
  background: rgba(239, 68, 68, 0.12);
  border: 1px solid rgba(239, 68, 68, 0.4);
  z-index: 1;
  display: flex;
  align-items: center;
}
.detail-planning__gap-label {
  font-size: 9px;
  color: #b91c1c;
  font-weight: 700;
  white-space: nowrap;
  padding-left: 4px;
}

/* Legend */
.detail-planning__legend {
  display: flex;
  align-items: center;
  gap: var(--iz-gap);
  margin-top: var(--iz-gap);
  padding-top: var(--iz-gap-tight);
  border-top: 1px solid var(--iz-border);
  flex-wrap: wrap;
  font-size: var(--iz-fs-xs);
  color: var(--iz-text-secondary);
}
.detail-planning__legend-item { display: flex; align-items: center; gap: 6px; }
.detail-planning__legend-dot { width: 10px; height: 10px; border-radius: 50%; }
.detail-planning__legend-dot--done { background: #10b981; }
.detail-planning__legend-bar { width: 16px; height: 8px; border-radius: 2px; }
.detail-planning__legend-bar--active { background: #3b82f6; }
.detail-planning__legend-line { width: 16px; height: 2px; }
.detail-planning__legend-line--baseline { background: #94a3b8; }
.detail-planning__legend-line--driving { background: #ef4444; height: 3px; }
.detail-planning__legend-box--gap { width: 14px; height: 10px; border: 1px solid #ef4444; background: rgba(239, 68, 68, 0.15); }
.detail-planning__legend-diamond { width: 8px; height: 8px; background: #0f172a; transform: rotate(45deg); }

/* ── Right Sidebar ── */
.detail-planning__sidebar { display: grid; gap: var(--iz-gap); align-content: start; }
.detail-planning__side-card { padding: var(--iz-pad-card); }
.detail-planning__side-header { margin-bottom: var(--iz-gap-tight); }
.detail-planning__side-body { display: grid; gap: var(--iz-gap-tight); }
.detail-planning__side-map {
  height: 120px;
  border-radius: var(--iz-radius);
  border: 1px solid var(--iz-border);
  overflow: hidden;
  background: var(--iz-surface-inset);
  position: relative;
}
.detail-planning__side-map-placeholder { width: 100%; height: 100%; }
.detail-planning__side-location-info { display: grid; gap: 2px; font-size: var(--iz-fs-sm); }
.detail-planning__side-link { color: var(--iz-accent); font-size: var(--iz-fs-xs); font-weight: 600; text-decoration: none; margin-top: 4px; }
.detail-planning__side-link:hover { text-decoration: underline; }

/* What-If Widget */
.detail-planning__whatif-banner {
  padding: 8px;
  border-radius: var(--iz-radius);
  background: var(--iz-surface-subtle);
  border: 1px solid var(--iz-border);
  font-size: var(--iz-fs-xs);
  display: grid;
  gap: 2px;
}
.detail-planning__whatif-banner p { margin: 0; color: var(--iz-text-muted); font-size: 11px; }
.detail-planning__whatif-field { display: flex; align-items: center; justify-content: space-between; font-size: var(--iz-fs-xs); }
.detail-planning__whatif-durations { display: grid; gap: 4px; font-size: var(--iz-fs-xs); }
.detail-planning__whatif-row { display: flex; align-items: center; justify-content: space-between; }
.detail-planning__mini-stepper { display: flex; align-items: center; gap: 4px; }
.detail-planning__mini-stepper button {
  width: 22px;
  height: 22px;
  border-radius: 4px;
  border: 1px solid var(--iz-border);
  background: var(--iz-surface);
  cursor: pointer;
  font-weight: 700;
}
.detail-planning__whatif-impact {
  padding: 8px;
  border-radius: var(--iz-radius);
  background: var(--iz-surface-inset);
  display: grid;
  gap: 4px;
  font-size: var(--iz-fs-xs);
}
.detail-planning__impact-heading { text-transform: uppercase; color: var(--iz-text-muted); font-size: 10px; font-weight: 700; }
.detail-planning__impact-row { display: flex; justify-content: space-between; }
.detail-planning__whatif-actions { display: flex; gap: var(--iz-gap-tight); justify-content: flex-end; margin-top: 4px; }

/* Open Kaarten Widget */
.detail-planning__open-cards-body { text-align: center; display: grid; gap: var(--iz-gap); justify-items: center; }
.detail-planning__big-number { font-size: 40px; font-weight: 800; color: var(--iz-text); line-height: 1; }

/* ── Bottom Grid (4 Analytical Insight Cards) ── */
.detail-planning__bottom-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: var(--iz-gap);
}
@media (max-width: 1100px) {
  .detail-planning__bottom-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 650px) {
  .detail-planning__bottom-grid { grid-template-columns: minmax(0, 1fr); }
}
.detail-planning__bottom-card { padding: var(--iz-pad-card); }
.detail-planning__bottom-card-header { margin-bottom: var(--iz-gap-tight); }
.detail-planning__bottom-card-body { display: grid; gap: var(--iz-gap-tight); font-size: var(--iz-fs-xs); }
.detail-planning__bullet-list { list-style: none; margin: 0; padding: 0; display: grid; gap: 6px; }
.detail-planning__bullet { display: flex; align-items: flex-start; gap: 8px; }
.detail-planning__bullet-dot { width: 8px; height: 8px; border-radius: 50%; margin-top: 4px; flex-shrink: 0; }
.detail-planning__bullet--danger .detail-planning__bullet-dot { background: #ef4444; }
.detail-planning__bullet--warning .detail-planning__bullet-dot { background: #f59e0b; }
.detail-planning__bullet--success .detail-planning__bullet-dot { background: #10b981; }
.detail-planning__bottom-meta { color: var(--iz-text-muted); font-size: 11px; margin-top: 4px; }

/* Mini Table */
.detail-planning__mini-table { width: 100%; border-collapse: collapse; font-size: var(--iz-fs-xs); }
.detail-planning__mini-table th,
.detail-planning__mini-table td { padding: 4px 6px; border-bottom: 1px solid var(--iz-border); }
.detail-planning__mini-table th { color: var(--iz-text-muted); font-weight: 600; text-align: left; }
.detail-planning__mini-table-total td { font-weight: 700; border-top: 2px solid var(--iz-border); border-bottom: 0; }

/* Baseline Rows */
.detail-planning__baseline-rows { display: grid; gap: 6px; }
.detail-planning__baseline-row { display: flex; justify-content: space-between; border-bottom: 1px dashed var(--iz-border); padding-bottom: 4px; }
.detail-planning__handover-summary { display: grid; gap: 4px; }
.detail-planning__handover-metric { display: flex; justify-content: space-between; }

/* Helpers */
.detail-planning__text-right { text-align: right; }
.detail-planning__text--warning { color: #d97706; }
.detail-planning__text--danger { color: #dc2626; }
.detail-planning__text--success { color: #16a34a; }
.detail-planning__text--muted { color: var(--iz-text-muted); }
</style>
