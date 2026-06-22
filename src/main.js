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
          h("p", `Error loading data: ${this.error}`),
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
          this.error = e.message || "Unknown error";
        } finally {
          this.loading = false;
        }

        this.fetchBackupJobs();
        this.fetchUpcomingEvents();
        this.fetchProjectGeocodes();
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
