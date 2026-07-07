import { defineStore } from 'pinia';
import axios from 'axios';

const formHeaders = { headers: { 'Content-Type': 'multipart/form-data' } };

export const useBonsaiStore = defineStore('bonsai', {
    state: () => ({
        user: null,
        dashboard: null,
        bonsais: [],
        tasks: [],
        selectedBonsai: null,
        loading: false,
        ready: false,
        error: '',
    }),

    getters: {
        isAuthenticated: (state) => Boolean(state.user),
    },

    actions: {
        async bootstrap() {
            this.loading = true;
            try {
                const { data } = await axios.get('/api/me');
                this.user = data.user;
                if (this.user) {
                    await this.fetchAll();
                }
            } finally {
                this.ready = true;
                this.loading = false;
            }
        },

        async fetchAll() {
            await Promise.all([
                this.fetchDashboard(),
                this.fetchBonsais(),
                this.fetchCalendar(),
            ]);
        },

        async login(credentials) {
            const { data } = await axios.post('/api/login', credentials);
            this.user = data.user;
            await this.fetchAll();
        },

        async register(payload) {
            const { data } = await axios.post('/api/register', payload);
            this.user = data.user;
            await this.fetchAll();
        },

        async logout() {
            await axios.post('/api/logout');
            this.$reset();
            this.ready = true;
        },

        async fetchDashboard() {
            const { data } = await axios.get('/api/dashboard');
            this.dashboard = data;
        },

        async fetchBonsais() {
            const { data } = await axios.get('/api/bonsais');
            this.bonsais = data.bonsais;
        },

        async fetchCalendar() {
            const { data } = await axios.get('/api/calendar-tasks');
            this.tasks = data.tasks;
        },

        async fetchBonsai(id) {
            const { data } = await axios.get(`/api/bonsais/${id}`);
            this.selectedBonsai = data.bonsai;
        },

        async createBonsai(formData) {
            const { data } = await axios.post('/api/bonsais', formData, formHeaders);
            this.selectedBonsai = data.bonsai;
            await this.fetchAll();
            return data.bonsai;
        },

        async addEvent(bonsaiId, formData) {
            const { data } = await axios.post(`/api/bonsais/${bonsaiId}/events`, formData, formHeaders);
            this.selectedBonsai = data.bonsai;
            await this.fetchAll();
        },

        async addImage(bonsaiId, formData) {
            const { data } = await axios.post(`/api/bonsais/${bonsaiId}/images`, formData, formHeaders);
            this.selectedBonsai = data.bonsai;
            await this.fetchAll();
        },

        async addTreatment(bonsaiId, payload) {
            const { data } = await axios.post(`/api/bonsais/${bonsaiId}/treatments`, payload);
            this.selectedBonsai = data.bonsai;
            await this.fetchAll();
        },

        async addTask(payload) {
            await axios.post('/api/calendar-tasks', payload);
            await Promise.all([this.fetchDashboard(), this.fetchCalendar()]);
        },

        async deleteTask(id) {
            await axios.delete(`/api/calendar-tasks/${id}`);
            await Promise.all([this.fetchDashboard(), this.fetchCalendar()]);
        },
    },
});
