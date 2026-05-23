<template>
  <div :class="['events-panel', { 'events-panel--embedded': embedded }]">
    <!-- Empty state (no events at all) -->
    <div v-if="!events || events.length === 0" class="events-panel__empty">
      <svg
        class="events-panel__empty-check"
        xmlns="http://www.w3.org/2000/svg"
        width="40"
        height="40"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
      >
        <polyline points="20 6 9 17 4 12" />
      </svg>
      <p class="events-panel__empty-text">No upcoming events</p>
    </div>

    <template v-else>
      <!-- ── Filters ── -->
      <div class="events-panel__filters">
        <!-- Time range pills -->
        <div class="events-panel__filter-group">
          <span
            v-for="r in rangeOptions"
            :key="'rf-' + r.value"
            class="events-panel__filter-badge"
            :class="{ 'events-panel__filter-badge--active': rangeFilter === r.value }"
            @click="rangeFilter = r.value; currentPage = 1"
          >{{ r.label }}</span>
        </div>

        <!-- Member dropdown -->
        <div class="events-panel__filter-group">
          <select
            v-model="memberFilter"
            class="events-panel__select"
            @change="currentPage = 1"
          >
            <option value="">All members</option>
            <option v-for="m in memberOptions" :key="'m-' + m" :value="m">{{ m }}</option>
          </select>
        </div>

        <!-- Calendar dropdown -->
        <div class="events-panel__filter-group">
          <select
            v-model="calendarFilter"
            class="events-panel__select"
            @change="currentPage = 1"
          >
            <option value="">All calendars</option>
            <option v-for="c in calendarOptions" :key="'c-' + c" :value="c">{{ c }}</option>
          </select>
        </div>

        <!-- Search -->
        <div class="events-panel__filter-group events-panel__filter-group--grow">
          <input
            v-model="searchFilter"
            type="text"
            class="events-panel__input"
            placeholder="Search events…"
            @input="currentPage = 1"
          />
        </div>
      </div>

      <!-- No results after filtering -->
      <div v-if="filteredEvents.length === 0" class="events-panel__empty">
        <p class="events-panel__empty-text">No events match your filters.</p>
      </div>

      <!-- Event list -->
      <ul v-else class="events-panel__list">
        <li
          v-for="ev in paginatedEvents"
          :key="ev.id + '-' + ev.start"
          class="events-panel__item"
        >
          <span
            class="events-panel__dot"
            :style="{ backgroundColor: dotColor(ev) }"
          ></span>
          <div class="events-panel__item-body">
            <span class="events-panel__item-title">{{ ev.title }}</span>
            <span class="events-panel__item-sub">
              {{ ev.memberName }}<template v-if="ev.calendar"> · {{ ev.calendar }}</template
              ><template v-if="ev.location"> · {{ ev.location }}</template>
            </span>
          </div>
          <div class="events-panel__item-when">
            <span class="events-panel__item-relative">{{ relativeLabel(ev.start) }}</span>
            <span class="events-panel__item-date">{{ whenLabel(ev) }}</span>
          </div>
        </li>
      </ul>

      <!-- Pagination -->
      <div v-if="totalPages > 1" class="events-panel__pagination">
        <button
          class="events-panel__page-btn"
          :disabled="currentPage <= 1"
          @click="currentPage--"
        >&#8249;</button>
        <span class="events-panel__page-info">{{ currentPage }} / {{ totalPages }}</span>
        <button
          class="events-panel__page-btn"
          :disabled="currentPage >= totalPages"
          @click="currentPage++"
        >&#8250;</button>
      </div>
      <div v-if="filteredEvents.length > 0" class="events-panel__showing-hint">
        Showing {{ paginatedEvents.length }} of {{ filteredEvents.length }} events
      </div>
    </template>
  </div>
</template>

<script>
var FALLBACK_COLORS = [
  "#1e4a8a",
  "#0082c9",
  "#6b21a8",
  "#92400e",
  "#166534",
  "#b91c1c",
  "#475569",
];

export default {
  name: "UpcomingEventsPanel",
  props: {
    embedded: {
      type: Boolean,
      default: false,
    },
    events: {
      type: Array,
      default: function () {
        return [];
      },
    },
  },
  data: function () {
    return {
      rangeFilter: "all",
      memberFilter: "",
      calendarFilter: "",
      searchFilter: "",
      currentPage: 1,
      pageSize: 6,
    };
  },
  computed: {
    rangeOptions: function () {
      return [
        { label: "Today", value: "today" },
        { label: "Next 7 days", value: "7d" },
        { label: "Next 30 days", value: "30d" },
        { label: "All", value: "all" },
      ];
    },
    memberOptions: function () {
      var seen = {};
      var out = [];
      for (var i = 0; i < this.events.length; i++) {
        var name = this.events[i].memberName;
        if (name && !seen[name]) {
          seen[name] = true;
          out.push(name);
        }
      }
      return out.sort();
    },
    calendarOptions: function () {
      var seen = {};
      var out = [];
      for (var i = 0; i < this.events.length; i++) {
        var cal = this.events[i].calendar;
        if (cal && !seen[cal]) {
          seen[cal] = true;
          out.push(cal);
        }
      }
      return out.sort();
    },
    filteredEvents: function () {
      var upper = this.rangeUpperBound();
      var member = this.memberFilter;
      var calendar = this.calendarFilter;
      var search = this.searchFilter.trim().toLowerCase();
      var out = [];
      for (var i = 0; i < this.events.length; i++) {
        var ev = this.events[i];
        var startMs = ev.start * 1000;
        if (upper !== null && startMs >= upper) continue;
        if (member && ev.memberName !== member) continue;
        if (calendar && ev.calendar !== calendar) continue;
        if (search && (ev.title || "").toLowerCase().indexOf(search) === -1) continue;
        out.push(ev);
      }
      return out;
    },
    totalPages: function () {
      return Math.max(1, Math.ceil(this.filteredEvents.length / this.pageSize));
    },
    paginatedEvents: function () {
      var start = (this.currentPage - 1) * this.pageSize;
      return this.filteredEvents.slice(start, start + this.pageSize);
    },
  },
  watch: {
    filteredEvents: function () {
      if (this.currentPage > this.totalPages) {
        this.currentPage = this.totalPages;
      }
    },
  },
  methods: {
    rangeUpperBound: function () {
      var now = new Date();
      if (this.rangeFilter === "today") {
        var t = new Date(now.getFullYear(), now.getMonth(), now.getDate() + 1);
        return t.getTime();
      }
      if (this.rangeFilter === "7d") {
        return now.getTime() + 7 * 86400000;
      }
      if (this.rangeFilter === "30d") {
        return now.getTime() + 30 * 86400000;
      }
      return null; // "all"
    },
    relativeLabel: function (startSec) {
      var start = new Date(startSec * 1000);
      var now = new Date();
      var startDay = new Date(start.getFullYear(), start.getMonth(), start.getDate());
      var nowDay = new Date(now.getFullYear(), now.getMonth(), now.getDate());
      var diffDays = Math.round((startDay - nowDay) / 86400000);
      if (diffDays <= 0) return "Today";
      if (diffDays === 1) return "Tomorrow";
      if (diffDays < 7) return "in " + diffDays + " days";
      if (diffDays < 14) return "in 1 week";
      if (diffDays < 30) return "in " + Math.round(diffDays / 7) + " weeks";
      var months = Math.round(diffDays / 30);
      return "in " + months + " month" + (months > 1 ? "s" : "");
    },
    whenLabel: function (ev) {
      var d = new Date(ev.start * 1000);
      var month = d.toLocaleString("en", { month: "short" });
      var day = d.getDate();
      var datePart = month + " " + day;
      if (ev.allDay) {
        return datePart + " · all day";
      }
      var hours = String(d.getHours()).padStart(2, "0");
      var mins = String(d.getMinutes()).padStart(2, "0");
      return datePart + " · " + hours + ":" + mins;
    },
    dotColor: function (ev) {
      if (ev.calendarColor) return ev.calendarColor;
      var key = ev.member || "";
      var hash = 0;
      for (var i = 0; i < key.length; i++) {
        hash = (hash * 31 + key.charCodeAt(i)) % 100000;
      }
      return FALLBACK_COLORS[hash % FALLBACK_COLORS.length];
    },
  },
};
</script>

<style scoped>
.events-panel {
  background: var(--bg-card, #fff);
  border-radius: var(--radius-card, 12px);
  box-shadow: var(--shadow-card, 0 1px 3px rgba(0, 0, 0, 0.08));
  padding: var(--spacing-lg, 24px);
}

.events-panel--embedded {
  background: none;
  border-radius: 0;
  box-shadow: none;
  padding: 0;
}

/* ─── Empty State ─── */
.events-panel__empty {
  text-align: center;
  padding: var(--spacing-lg, 24px) 0;
}

.events-panel__empty-check {
  color: #9ca3af;
  margin-bottom: 8px;
}

.events-panel__empty-text {
  font-size: 13px;
  color: var(--color-text-muted, #9ca3af);
  margin: 0;
}

/* ─── Filters ─── */
.events-panel__filters {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 12px;
  flex-wrap: wrap;
}

.events-panel__filter-group {
  display: flex;
  gap: 5px;
  align-items: center;
  padding-right: 12px;
  border-right: 1px solid #e5e7eb;
}

.events-panel__filter-group:last-child {
  border-right: none;
  padding-right: 0;
}

.events-panel__filter-group--grow {
  flex: 1;
  min-width: 140px;
}

.events-panel__filter-badge {
  font-size: 11px;
  font-weight: 500;
  padding: 3px 10px;
  border-radius: 12px;
  cursor: pointer;
  background: #f0f1f5;
  color: #6b7280;
  transition: all 0.15s ease;
  user-select: none;
  border: 1.5px solid transparent;
}

.events-panel__filter-badge:hover {
  background: #e5e7eb;
}

.events-panel__filter-badge--active {
  font-weight: 600;
  color: #1e4a8a;
  background: #e8f0fe;
  border-color: currentColor;
}

.events-panel__select,
.events-panel__input {
  font-size: 12px;
  color: #374151;
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 4px 8px;
  outline: none;
}

.events-panel__input {
  width: 100%;
}

.events-panel__select:focus,
.events-panel__input:focus {
  border-color: #4a90d9;
}

/* ─── List ─── */
.events-panel__list {
  list-style: none;
  margin: 0;
  padding: 0;
}

.events-panel__item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 4px;
  border-bottom: 1px solid #f3f4f6;
}

.events-panel__item:last-child {
  border-bottom: none;
}

.events-panel__dot {
  flex: 0 0 auto;
  width: 12px;
  height: 12px;
  border-radius: 50%;
}

.events-panel__item-body {
  flex: 1;
  min-width: 0;
}

.events-panel__item-title {
  display: block;
  font-size: 13px;
  font-weight: 600;
  color: var(--color-text-primary, #1a1a2e);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.events-panel__item-sub {
  display: block;
  font-size: 11px;
  color: var(--color-text-muted, #9ca3af);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.events-panel__item-when {
  flex: 0 0 auto;
  text-align: right;
}

.events-panel__item-relative {
  display: block;
  font-size: 12px;
  font-weight: 600;
  color: var(--color-text-secondary, #6b7280);
}

.events-panel__item-date {
  display: block;
  font-size: 11px;
  color: var(--color-text-muted, #9ca3af);
}

/* ─── Pagination ─── */
.events-panel__pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  margin-top: 12px;
}

.events-panel__page-btn {
  width: 28px;
  height: 28px;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
  background: #fff;
  font-size: 16px;
  font-weight: 600;
  color: #4a90d9;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.15s;
}

.events-panel__page-btn:hover:not(:disabled) {
  background: #e8f0fe;
  border-color: #4a90d9;
}

.events-panel__page-btn:disabled {
  opacity: 0.35;
  cursor: not-allowed;
}

.events-panel__page-info {
  font-size: 12px;
  font-weight: 600;
  color: var(--color-text-secondary, #6b7280);
}

.events-panel__showing-hint {
  text-align: center;
  font-size: 11px;
  color: var(--color-text-muted, #9ca3af);
  margin-top: 6px;
}
</style>
