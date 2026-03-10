import Vue from "vue";
import PublicDashboard from "./components/PublicDashboard.vue";
import axios from "@nextcloud/axios";
import { generateUrl } from "@nextcloud/router";

Vue.mixin({
  methods: {
    t: (app, text) => text,
  },
});

const mountEl = document.getElementById("adminpage-root");

if (mountEl) {
  const token = mountEl.dataset.token;

  const app = new Vue({
    el: mountEl,
    data() {
      return {
        dashboardData: null,
        loading: true,
        error: null,
        expired: false,
      };
    },
    render(h) {
      if (this.loading) {
        return h("div", { class: "adminpage-loading" }, [
          h("div", { class: "adminpage-loading__spinner" }),
          h("p", "Loading dashboard…"),
        ]);
      }
      if (this.expired) {
        return h("div", { class: "adminpage-expired" }, [
          h("h2", "Link Unavailable"),
          h(
            "p",
            "This public dashboard link is invalid, has expired, or has been revoked.",
          ),
        ]);
      }
      if (this.error) {
        return h("div", { class: "adminpage-error" }, [
          h("p", `Error loading data: ${this.error}`),
        ]);
      }
      return h(PublicDashboard, {
        props: {
          data: this.dashboardData,
        },
      });
    },
    mounted() {
      this.fetchData();
    },
    methods: {
      async fetchData() {
        try {
          const url = generateUrl(`/apps/adminpage/api/public/${token}`);
          const response = await axios.get(url);
          this.dashboardData = response.data;
        } catch (e) {
          if (e.response && e.response.status === 403) {
            this.expired = true;
          } else {
            console.error("Failed to load public dashboard data", e);
            this.error = e.message || "Unknown error";
          }
        } finally {
          this.loading = false;
        }
      },
    },
  });
}
