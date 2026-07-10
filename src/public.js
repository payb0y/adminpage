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
        return h(
          "div",
          {
            class: "adminpage-error",
            style: {
              display: "flex",
              flexDirection: "column",
              alignItems: "center",
              justifyContent: "center",
              minHeight: "60vh",
              textAlign: "center",
              padding: "24px",
              color: "#6b7280",
            },
          },
          [
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
            this.error = this.friendlyError(e);
          }
        } finally {
          this.loading = false;
        }
      },
      retry() {
        this.error = null;
        this.loading = true;
        this.fetchData();
      },
      // Turn an axios error into a human-actionable message, never the raw
      // "Request failed with status code 500". Prefers a server-sent message.
      friendlyError(e) {
        const status = e && e.response && e.response.status;
        const serverMsg =
          e && e.response && e.response.data && e.response.data.message;
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
    },
  });
}
