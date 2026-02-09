import Vue from 'vue'
import Dashboard from './components/Dashboard.vue'
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

Vue.mixin({
	methods: {
		t: (app, text) => text,
	},
})

const mountEl = document.getElementById('adminpage-root')

if (mountEl) {
	const app = new Vue({
		el: mountEl,
		data() {
			return {
				dashboardData: null,
				loading: true,
				error: null,
			}
		},
		render(h) {
			if (this.loading) {
				return h('div', { class: 'adminpage-loading' }, [
					h('div', { class: 'adminpage-loading__spinner' }),
					h('p', 'Loading dashboard data...'),
				])
			}
			if (this.error) {
				return h('div', { class: 'adminpage-error' }, [
					h('p', `Error loading data: ${this.error}`),
				])
			}
			return h(Dashboard, {
				props: {
					data: this.dashboardData,
				},
			})
		},
		mounted() {
			this.fetchData()
		},
		methods: {
			async fetchData() {
				try {
					const url = generateUrl('/apps/adminpage/api/data')
					const response = await axios.get(url)
					this.dashboardData = response.data
				} catch (e) {
					console.error('Failed to load dashboard data', e)
					this.error = e.message || 'Unknown error'
				} finally {
					this.loading = false
				}
			},
		},
	})
}
