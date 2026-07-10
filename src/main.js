import Vue from "vue";
import Dashboard from "./components/Dashboard.vue";
import axios from "@nextcloud/axios";
import { generateUrl } from "@nextcloud/router";

Vue.mixin({
  methods: {
    t: (app, text) => text,
  },
});

const mountEl = document.getElementById("adminpage-root");

if (mountEl) {
  const app = new Vue({
    el: mountEl,
    data() {
      return {
        dashboardData: null,
        backupJobs: [],
        upcomingEvents: [],
        projectGeocodes: { projects: [], geocodingInFlight: 0 },
        projectGeocodesLoading: false,
        loading: true,
        error: null,
      };
    },
    render(h) {
      if (this.loading) {
        return h("div", { class: "adminpage-loading" }, [
          h("div", { class: "adminpage-loading__spinner" }),
          h("p", "Loading dashboard data..."),
        ]);
      }
      if (this.error) {
        return h("div", { class: "adminpage-error" }, [
          h("p", this.error),
          h(
            "button",
            {
              class: "adminpage-error__retry",
              style: {
                marginTop: "12px",
                padding: "8px 18px",
                border: "1px solid #d1d5db",
                borderRadius: "8px",
                background: "#fff",
                color: "#1a1a2e",
                fontWeight: "600",
                cursor: "pointer",
              },
              on: { click: () => this.retry() },
            },
            "Try again",
          ),
        ]);
      }
      return h(Dashboard, {
        props: {
          data: this.dashboardData,
          backupJobs: this.backupJobs,
          upcomingEvents: this.upcomingEvents,
          projectGeocodes: this.projectGeocodes,
          projectGeocodesLoading: this.projectGeocodesLoading,
        },
        on: {
          reload: () => {
            this.fetchData();
          },
        },
      });
    },
    mounted() {
      this.fetchData();
    },
    methods: {
      async fetchData() {
        try {
          const url = generateUrl("/apps/adminpage/api/data");
          const response = await axios.get(url);
          this.dashboardData = response.data;
        } catch (e) {
          console.error("Failed to load dashboard data", e);
          this.error = this.friendlyError(e);
        } finally {
          this.loading = false;
        }

        this.fetchBackupJobs();
        this.fetchUpcomingEvents();
        this.fetchProjectGeocodes();
      },
      retry() {
        this.error = null;
        this.loading = true;
        this.fetchData();
      },
      // Turn an axios error into a message a human can act on, never the raw
      // "Request failed with status code 500". Prefers a message the server
      // deliberately sent (controller error boundary), then status-based copy.
      friendlyError(e) {
        const status = e && e.response && e.response.status;
        const serverMsg =
          e && e.response && e.response.data && e.response.data.message;
        if (status === 403) {
          return "You don't have permission to view this dashboard.";
        }
        if (serverMsg) {
          return serverMsg;
        }
        if (status && status >= 500) {
          return "The server ran into a problem loading the dashboard. Please try again in a moment.";
        }
        if (e && e.request && !e.response) {
          return "Couldn't reach the server. Check your connection and try again.";
        }
        return "Something went wrong loading the dashboard. Please try again.";
      },
      async fetchBackupJobs() {
        try {
          const url = generateUrl("/apps/adminpage/api/backup-jobs");
          const response = await axios.get(url);
          var ocsData = response.data && response.data.ocs && response.data.ocs.data;
          this.backupJobs = (ocsData && ocsData.jobs) || [];
        } catch (e) {
          console.error("Failed to load backup jobs", e);
        }
      },
      async fetchUpcomingEvents() {
        try {
          const url = generateUrl("/apps/adminpage/api/upcoming-events");
          const response = await axios.get(url);
          this.upcomingEvents = (response.data && response.data.events) || [];
        } catch (e) {
          console.error("Failed to load upcoming events", e);
        }
      },
      async fetchProjectGeocodes() {
        this.projectGeocodesLoading = true;
        try {
          const url = generateUrl("/apps/adminpage/api/projects/geocodes");
          const response = await axios.get(url);
          const d = (response && response.data) || {};
          this.projectGeocodes = {
            projects: Array.isArray(d.projects) ? d.projects : [],
            geocodingInFlight: Number(d.geocodingInFlight) || 0,
          };
        } catch (e) {
          console.error("Failed to load project geocodes", e);
          this.projectGeocodes = { projects: [], geocodingInFlight: 0 };
        } finally {
          this.projectGeocodesLoading = false;
        }
      },
    },
  });
}
