<template>
  <div class="project-map">
    <div ref="mapRoot" class="project-map__container"></div>
    <a
      :href="osmLink"
      class="project-map__link"
      target="_blank"
      rel="noopener noreferrer"
    >Open in OpenStreetMap →</a>
  </div>
</template>

<script>
export default {
  name: "ProjectMap",
  props: {
    lat: { type: Number, required: true },
    lng: { type: Number, required: true },
    displayName: { type: String, default: null },
  },
  data() {
    return {
      // Stored as plain (non-reactive) refs to avoid Vue trying to observe
      // Leaflet's internals.
      _map: null,
    };
  },
  computed: {
    osmLink() {
      return (
        "https://www.openstreetmap.org/?mlat=" +
        this.lat +
        "&mlon=" +
        this.lng +
        "#map=16/" +
        this.lat +
        "/" +
        this.lng
      );
    },
  },
  async mounted() {
    // Magic comments give the lazy chunks short, stable filenames
    // (e.g. adminpage-leaflet.js) instead of webpack's verbose
    // auto-generated `vendors-node_modules_leaflet_…` names, which some
    // Nextcloud setups have trouble serving.
    const Lmod = await import(/* webpackChunkName: "leaflet" */ "leaflet");
    await import(/* webpackChunkName: "leaflet-css" */ "leaflet/dist/leaflet.css");

    const iconUrl = (
      await import(/* webpackChunkName: "leaflet-marker" */ "leaflet/dist/images/marker-icon.png")
    ).default;
    const iconRetinaUrl = (
      await import(/* webpackChunkName: "leaflet-marker" */ "leaflet/dist/images/marker-icon-2x.png")
    ).default;
    const shadowUrl = (
      await import(/* webpackChunkName: "leaflet-marker" */ "leaflet/dist/images/marker-shadow.png")
    ).default;

    const L = Lmod.default || Lmod;

    // Webpack mangles the default icon URLs Leaflet ships with; restate them.
    delete L.Icon.Default.prototype._getIconUrl;
    L.Icon.Default.mergeOptions({ iconRetinaUrl, iconUrl, shadowUrl });

    this._map = L.map(this.$refs.mapRoot, { scrollWheelZoom: true }).setView(
      [this.lat, this.lng],
      16
    );
    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
      maxZoom: 19,
      attribution:
        '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener noreferrer">OpenStreetMap</a> contributors',
    }).addTo(this._map);

    const marker = L.marker([this.lat, this.lng]).addTo(this._map);
    if (this.displayName) {
      marker.bindPopup(this.displayName);
    }
  },
  beforeDestroy() {
    if (this._map) {
      this._map.remove();
      this._map = null;
    }
  },
};
</script>

<style scoped>
.project-map {
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.project-map__container {
  height: 280px;
  width: 100%;
  border-radius: 8px;
  overflow: hidden;
  background: #f0f1f5;
}
.project-map__link {
  font-size: 12px;
  color: #4a90d9;
  text-decoration: none;
  align-self: flex-end;
}
.project-map__link:hover {
  text-decoration: underline;
}
</style>
