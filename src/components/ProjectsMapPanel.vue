<template>
  <section class="projects-map">
    <header class="projects-map__header">
      <h3 class="projects-map__title">
        Project Map
        <span class="projects-map__count">{{ filteredProjects.length }}</span>
      </h3>
      <div v-if="geocodingInFlight > 0" class="projects-map__chip">
        Geocoding {{ geocodingInFlight }} more in the background — they'll
        appear next time you load the dashboard.
      </div>
    </header>

    <div class="projects-map__filters">
      <div class="projects-map__pills">
        <button
          v-for="s in statusOptions"
          :key="'st-' + s.key"
          type="button"
          class="projects-map__pill"
          :class="[
            'projects-map__pill--st-' + s.key,
            { 'projects-map__pill--on': statusFilter[s.key] }
          ]"
          @click="toggleStatus(s.key)"
        >{{ s.label }}</button>
      </div>
      <select v-model="assigneeFilter" class="projects-map__select">
        <option value="">All assignees</option>
        <option
          v-for="m in orgMembers"
          :key="'as-' + m.userId"
          :value="m.userId"
        >{{ m.displayName || m.userId }}</option>
      </select>
      <input
        v-model="searchFilter"
        type="search"
        class="projects-map__search"
        placeholder="Search name or number…"
      />
      <label class="projects-map__toggle">
        <input type="checkbox" v-model="overdueOnly" />
        <span>Overdue only</span>
      </label>
      <button
        v-if="anyFilterActive"
        type="button"
        class="projects-map__clear"
        @click="clearFilters"
      >Clear all</button>
    </div>

    <div v-if="loading" class="projects-map__state">Loading project locations…</div>
    <div
      v-else-if="!hasAnyOk"
      class="projects-map__state"
    >No projects with addresses yet.</div>
    <div
      v-else-if="filteredProjects.length === 0"
      class="projects-map__state"
    >
      No projects match these filters.
      <button type="button" class="projects-map__clear" @click="clearFilters">Clear filters</button>
    </div>

    <div v-show="!loading && filteredProjects.length > 0" ref="mapRoot" class="projects-map__container"></div>
  </section>
</template>

<script>
import L from "leaflet";
import "leaflet/dist/leaflet.css";
import "leaflet.markercluster";
import "leaflet.markercluster/dist/MarkerCluster.css";
import "leaflet.markercluster/dist/MarkerCluster.Default.css";

const STATUS_KEY_BY_NUM = { 0: "active", 1: "waiting", 2: "hold" };
const STATUS_COLOR = {
  active:  "#166534",
  waiting: "#b45309",
  hold:    "#6b7280",
};

function pinHtml(project) {
  const key = STATUS_KEY_BY_NUM[project.status] || "active";
  const color = STATUS_COLOR[key];
  const overdue = (project.overdueTasks || 0) > 0;
  const overdueDot = overdue
    ? '<span class="projects-map__pin-dot"></span>'
    : '';
  return (
    '<span class="projects-map__pin-wrap">' +
    '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" ' +
    'fill="' + color + '" stroke="#1f2937" stroke-width="1" stroke-linejoin="round" aria-hidden="true">' +
    '<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 1 1 18 0z"/>' +
    '<circle cx="12" cy="10" r="3" fill="#fff" stroke="#1f2937"/>' +
    '</svg>' +
    overdueDot +
    '</span>'
  );
}

function popupHtml(project) {
  const overdueLine = project.overdueTasks > 0
    ? ' · <span class="projects-map__popup-danger">' + project.overdueTasks + ' overdue</span>'
    : '';
  const number = project.number ? ' <small>' + escapeHtml(project.number) + '</small>' : '';
  return (
    '<div class="projects-map__popup">' +
    '<strong>' + escapeHtml(project.name) + '</strong>' + number +
    '<div class="projects-map__popup-row">' + escapeHtml(project.statusLabel) +
    ' · ' + project.completionPct + '%</div>' +
    '<div class="projects-map__popup-row">' +
    project.doneTasks + '/' + project.totalTasks + ' tasks' + overdueLine +
    '</div>' +
    '<button type="button" data-project-id="' + project.id +
    '" class="projects-map__popup-cta">Open in details →</button>' +
    '</div>'
  );
}

function escapeHtml(s) {
  return String(s == null ? "" : s)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#39;");
}

export default {
  name: "ProjectsMapPanel",
  props: {
    projects: { type: Array, default: function () { return []; } },
    orgMembers: { type: Array, default: function () { return []; } },
    loading: { type: Boolean, default: false },
    geocodingInFlight: { type: Number, default: 0 },
  },
  data() {
    return {
      statusFilter: { active: true, waiting: true, hold: true },
      assigneeFilter: "",
      searchFilter: "",
      overdueOnly: false,
      _map: null,
      _cluster: null,
      _userHasPanned: false,
      _popupClickHandler: null,
    };
  },
  computed: {
    statusOptions() {
      return [
        { key: "active",  label: "Active" },
        { key: "waiting", label: "Waiting on Customer" },
        { key: "hold",    label: "On Hold" },
      ];
    },
    hasAnyOk() {
      for (let i = 0; i < this.projects.length; i++) {
        if (this.projects[i].geocodeStatus === "ok") return true;
      }
      return false;
    },
    filteredProjects() {
      const q = (this.searchFilter || "").trim().toLowerCase();
      const assignee = this.assigneeFilter;
      const overdue = this.overdueOnly;
      const sf = this.statusFilter;
      const out = [];
      for (let i = 0; i < this.projects.length; i++) {
        const p = this.projects[i];
        if (p.geocodeStatus !== "ok") continue;
        const sk = STATUS_KEY_BY_NUM[p.status] || "active";
        if (!sf[sk]) continue;
        if (assignee && (!p.assignees || p.assignees.indexOf(assignee) === -1)) continue;
        if (overdue && !(p.overdueTasks > 0)) continue;
        if (q) {
          const nm = (p.name || "").toLowerCase();
          const num = (p.number || "").toLowerCase();
          if (nm.indexOf(q) === -1 && num.indexOf(q) === -1) continue;
        }
        out.push(p);
      }
      return out;
    },
    anyFilterActive() {
      return (
        !this.statusFilter.active ||
        !this.statusFilter.waiting ||
        !this.statusFilter.hold ||
        this.assigneeFilter !== "" ||
        this.searchFilter !== "" ||
        this.overdueOnly
      );
    },
  },
  watch: {
    filteredProjects() {
      this.$nextTick(this.renderMarkers);
    },
    loading(val) {
      if (!val) this.$nextTick(this.ensureMap);
    },
  },
  mounted() {
    this.$nextTick(this.ensureMap);
  },
  beforeDestroy() {
    if (this._popupClickHandler && this._map) {
      this._map.getContainer().removeEventListener("click", this._popupClickHandler, true);
    }
    if (this._map) {
      this._map.remove();
      this._map = null;
      this._cluster = null;
    }
  },
  methods: {
    toggleStatus(key) {
      this.$set(this.statusFilter, key, !this.statusFilter[key]);
    },
    clearFilters() {
      this.statusFilter = { active: true, waiting: true, hold: true };
      this.assigneeFilter = "";
      this.searchFilter = "";
      this.overdueOnly = false;
    },
    ensureMap() {
      if (this._map) return;
      if (!this.$refs.mapRoot) return;
      if (this.filteredProjects.length === 0) return;
      this._map = L.map(this.$refs.mapRoot, { scrollWheelZoom: true })
        .setView([52.3676, 4.9041], 6);
      L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        maxZoom: 19,
        attribution:
          '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener noreferrer">OpenStreetMap</a> contributors',
      }).addTo(this._map);
      this._cluster = L.markerClusterGroup();
      this._map.addLayer(this._cluster);

      const markUserPan = () => { this._userHasPanned = true; };
      this._map.on("dragstart", markUserPan);
      this._map.on("zoomstart", markUserPan);

      this._popupClickHandler = (e) => {
        const t = e.target;
        if (t && t.classList && t.classList.contains("projects-map__popup-cta")) {
          const id = parseInt(t.getAttribute("data-project-id"), 10);
          if (Number.isFinite(id)) {
            if (this._map && this._map.closePopup) this._map.closePopup();
            this.$emit("select-project", id);
          }
        }
      };
      this._map.getContainer().addEventListener("click", this._popupClickHandler, true);

      this.renderMarkers();
    },
    renderMarkers() {
      if (!this._map || !this._cluster) {
        this.ensureMap();
        return;
      }
      this._cluster.clearLayers();
      const markers = [];
      const list = this.filteredProjects;
      for (let i = 0; i < list.length; i++) {
        const p = list[i];
        const icon = L.divIcon({
          className: "projects-map__pin",
          html: pinHtml(p),
          iconSize: [28, 28],
          iconAnchor: [14, 28],
          popupAnchor: [0, -24],
        });
        const m = L.marker([p.lat, p.lng], { icon })
          .bindPopup(popupHtml(p));
        markers.push(m);
      }
      this._cluster.addLayers(markers);

      if (!this._userHasPanned && markers.length > 0) {
        if (markers.length === 1) {
          this._map.setView([list[0].lat, list[0].lng], 14);
        } else {
          const bounds = this._cluster.getBounds();
          if (bounds.isValid()) {
            this._map.fitBounds(bounds, { padding: [40, 40], maxZoom: 14 });
          }
        }
      }
    },
  },
};
</script>

<style scoped>
.projects-map {
  background: var(--bg-card, #fff);
  border-radius: var(--radius-card, 12px);
  box-shadow: var(--shadow-card, 0 1px 3px rgba(0, 0, 0, 0.08));
  padding: var(--spacing-lg, 24px);
  margin-bottom: var(--spacing-xl, 32px);
}

.projects-map__header {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
  margin-bottom: 12px;
}
.projects-map__title {
  margin: 0;
  font-size: 16px;
  font-weight: 700;
  color: #1a1a2e;
}
.projects-map__count {
  display: inline-block;
  margin-left: 8px;
  font-size: 11px;
  font-weight: 700;
  color: #6b7280;
  background: #f0f1f5;
  padding: 1px 8px;
  border-radius: 999px;
}
.projects-map__chip {
  font-size: 11px;
  color: #92400e;
  background: #fef3cd;
  padding: 4px 10px;
  border-radius: 8px;
}

.projects-map__filters {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
  margin-bottom: 12px;
}
.projects-map__pills {
  display: flex;
  gap: 4px;
}
.projects-map__pill {
  font-size: 11px;
  font-weight: 600;
  border-radius: 12px;
  padding: 3px 10px;
  border: 1.5px solid transparent;
  cursor: pointer;
  background: #f0f1f5;
  color: #6b7280;
  user-select: none;
}
.projects-map__pill:hover {
  filter: brightness(0.97);
}
.projects-map__pill--on {
  background: #fff;
  border-color: currentColor;
}
.projects-map__pill--on.projects-map__pill--st-active { color: #166534; background: #d4edda; }
.projects-map__pill--on.projects-map__pill--st-waiting { color: #b45309; background: #fef3cd; }
.projects-map__pill--on.projects-map__pill--st-hold { color: #6b7280; background: #e5e7eb; }

.projects-map__select,
.projects-map__search {
  font-size: 12px;
  color: #374151;
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 4px 8px;
  outline: none;
}
.projects-map__search { min-width: 160px; }
.projects-map__select:focus,
.projects-map__search:focus { border-color: #4a90d9; }

.projects-map__toggle {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  color: #6b7280;
  cursor: pointer;
}

.projects-map__clear {
  font-size: 11px;
  font-weight: 600;
  background: #fff;
  color: #6b7280;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 3px 10px;
  cursor: pointer;
}
.projects-map__clear:hover { background: #f0f1f5; }

.projects-map__container {
  height: 360px;
  width: 100%;
  border-radius: 8px;
  overflow: hidden;
  background: #f0f1f5;
}

.projects-map__state {
  text-align: center;
  font-size: 13px;
  color: #9ca3af;
  padding: 32px 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
}
</style>

<style>
/* Unscoped — Leaflet renders pins + popups outside the Vue subtree. */
.projects-map__pin {
  background: transparent !important;
  border: none !important;
}
.projects-map__pin-wrap {
  position: relative;
  display: inline-block;
}
.projects-map__pin-dot {
  position: absolute;
  top: -2px;
  right: -2px;
  width: 9px;
  height: 9px;
  border-radius: 50%;
  background: #b91c1c;
  border: 1.5px solid #fff;
}

.projects-map__popup {
  font-size: 12px;
  line-height: 1.4;
  color: #1a1a2e;
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-width: 180px;
}
.projects-map__popup small {
  color: #6b7280;
  font-weight: 500;
  margin-left: 6px;
}
.projects-map__popup-row { color: #4b5563; }
.projects-map__popup-danger { color: #b91c1c; font-weight: 600; }
.projects-map__popup-cta {
  margin-top: 6px;
  font-size: 11px;
  font-weight: 600;
  color: #fff;
  background: #4a90d9;
  border: none;
  border-radius: 8px;
  padding: 6px 10px;
  cursor: pointer;
}
.projects-map__popup-cta:hover { background: #357ec7; }
</style>
