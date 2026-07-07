<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import {
    CalendarDays,
    Camera,
    Check,
    ChevronLeft,
    ChevronRight,
    Clock3,
    Droplets,
    ImagePlus,
    Leaf,
    LogIn,
    LogOut,
    Plus,
    Scissors,
    ShieldCheck,
    Sparkles,
    Sprout,
    Trash2,
    UserPlus,
} from '@lucide/vue';
import { useBonsaiStore } from './stores/bonsaiStore';

const store = useBonsaiStore();
const screen = ref('landing');
const detailTab = ref('journal');
const collectionMode = ref('list');
const authError = ref('');
const saving = ref(false);
const heroImage = '/images/bonsai-hero.png';
const calendarCursor = ref(new Date(new Date().getFullYear(), new Date().getMonth(), 1));
const selectedCalendarDate = ref(today());

const loginForm = reactive({ email: 'demo@bonsaigest.test', password: 'password' });
const registerForm = reactive({ name: '', email: '', password: '' });

const emptyBonsaiForm = () => ({
    name: '',
    species: '',
    age: '',
    acquired_date: '',
    origin: 'Compra',
    style: 'Moyogi',
    water_level: 'Media',
    location: 'Exterior',
    substrate: '',
    fertilizer: '',
    main_image_file: null,
    description: '',
});

const bonsaiForm = reactive(emptyBonsaiForm());
const eventForm = reactive({ date: today(), type: 'Poda', notes: '', image_file: null, image_description: '' });
const imageForm = reactive({ date: today(), description: '', image_file: null });
const treatmentForm = reactive({ date: today(), problem: '', product: '', result: '', notes: '' });
const taskForm = reactive({ date: today(), bonsai_id: '', title: '', description: '' });
const treeTaskForm = reactive({ date: today(), title: '', description: '' });

const origins = ['Semilla', 'Esqueje', 'Acodo', 'Yamadori', 'Compra', 'Regalo'];
const styles = ['Chokkan', 'Moyogi', 'Shakan', 'Cascada', 'Bosque', 'Otro'];
const waterLevels = ['Baja', 'Media', 'Alta'];
const locations = ['Interior', 'Exterior'];
const workTypes = ['Poda', 'Pinzado', 'Alambrado', 'Trasplante', 'Abonado', 'Tratamiento', 'Riego especial', 'Diseño', 'Otro'];
const weekDays = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];

onMounted(async () => {
    await store.bootstrap();
    if (store.user) {
        screen.value = 'dashboard';
    }
});

const selected = computed(() => store.selectedBonsai);
const dashboard = computed(() => store.dashboard ?? {
    total_bonsais: 0,
    bonsais: [],
    latest_events: [],
    upcoming_tasks: [],
    latest_images: [],
});

const calendarTitle = computed(() => new Intl.DateTimeFormat('es-ES', {
    month: 'long',
    year: 'numeric',
}).format(calendarCursor.value));

const tasksByDate = computed(() => store.tasks.reduce((carry, task) => {
    carry[task.date] = carry[task.date] ?? [];
    carry[task.date].push(task);
    return carry;
}, {}));

const selectedDateTasks = computed(() => tasksByDate.value[selectedCalendarDate.value] ?? []);

const selectedTreeTasks = computed(() => {
    const tasks = selected.value?.calendar_tasks ?? selected.value?.calendarTasks ?? [];

    return [...tasks].sort((a, b) => a.date.localeCompare(b.date));
});

const calendarDays = computed(() => {
    const year = calendarCursor.value.getFullYear();
    const month = calendarCursor.value.getMonth();
    const firstDay = new Date(year, month, 1);
    const mondayOffset = (firstDay.getDay() + 6) % 7;
    const start = new Date(year, month, 1 - mondayOffset);

    return Array.from({ length: 42 }, (_, index) => {
        const date = new Date(start);
        date.setDate(start.getDate() + index);
        const iso = isoDate(date);

        return {
            date: iso,
            day: date.getDate(),
            currentMonth: date.getMonth() === month,
            today: iso === today(),
            selected: iso === selectedCalendarDate.value,
            tasks: tasksByDate.value[iso] ?? [],
        };
    });
});

const timelineGroups = computed(() => {
    if (!selected.value?.events) return [];

    const imagesByDate = (selected.value.images ?? []).reduce((carry, image) => {
        carry[image.date] = carry[image.date] ?? [];
        carry[image.date].push(image);
        return carry;
    }, {});

    const groups = {};
    [...selected.value.events]
        .sort((a, b) => b.date.localeCompare(a.date))
        .forEach((event) => {
            const label = monthLabel(event.date);
            groups[label] = groups[label] ?? [];
            groups[label].push({ ...event, images: imagesByDate[event.date] ?? [] });
        });

    return Object.entries(groups).map(([label, events]) => ({ label, events }));
});

function today() {
    return new Date().toISOString().slice(0, 10);
}

function isoDate(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
}

function asDate(value) {
    return new Date(`${value}T00:00:00`);
}

function formatDate(value) {
    if (!value) return 'Sin fecha';
    return new Intl.DateTimeFormat('es-ES', { day: 'numeric', month: 'short', year: 'numeric' }).format(asDate(value));
}

function monthLabel(value) {
    return new Intl.DateTimeFormat('es-ES', { month: 'long', year: 'numeric' }).format(asDate(value)).toUpperCase();
}

function imageFor(bonsai) {
    return bonsai?.main_image || heroImage;
}

function objectToFormData(payload) {
    const data = new FormData();
    Object.entries(payload).forEach(([key, value]) => {
        if (value !== null && value !== undefined && value !== '') {
            data.append(key, value);
        }
    });
    return data;
}

function resetObject(target, source) {
    Object.keys(target).forEach((key) => delete target[key]);
    Object.assign(target, source);
}

async function submitLogin() {
    authError.value = '';
    try {
        await store.login(loginForm);
        screen.value = 'dashboard';
    } catch (error) {
        authError.value = error.response?.data?.message || 'No se pudo iniciar sesión.';
    }
}

async function submitRegister() {
    authError.value = '';
    try {
        await store.register(registerForm);
        screen.value = 'dashboard';
    } catch (error) {
        authError.value = error.response?.data?.message || 'No se pudo crear la cuenta.';
    }
}

async function logout() {
    await store.logout();
    screen.value = 'landing';
    detailTab.value = 'journal';
}

async function openDetail(bonsai) {
    await store.fetchBonsai(bonsai.id);
    detailTab.value = 'journal';
    screen.value = 'detail';
}

function selectCalendarDay(date) {
    selectedCalendarDate.value = date;
    taskForm.date = date;
}

function moveCalendarMonth(delta) {
    calendarCursor.value = new Date(calendarCursor.value.getFullYear(), calendarCursor.value.getMonth() + delta, 1);
}

function showTodayInCalendar() {
    const date = new Date();
    calendarCursor.value = new Date(date.getFullYear(), date.getMonth(), 1);
    selectCalendarDay(today());
}

async function createBonsai() {
    saving.value = true;
    try {
        const bonsai = await store.createBonsai(objectToFormData(bonsaiForm));
        resetObject(bonsaiForm, emptyBonsaiForm());
        collectionMode.value = 'list';
        await openDetail(bonsai);
    } finally {
        saving.value = false;
    }
}

async function addEvent() {
    if (!selected.value) return;
    saving.value = true;
    try {
        await store.addEvent(selected.value.id, objectToFormData(eventForm));
        resetObject(eventForm, { date: today(), type: 'Poda', notes: '', image_file: null, image_description: '' });
    } finally {
        saving.value = false;
    }
}

async function addImage() {
    if (!selected.value) return;
    saving.value = true;
    try {
        await store.addImage(selected.value.id, objectToFormData(imageForm));
        resetObject(imageForm, { date: today(), description: '', image_file: null });
    } finally {
        saving.value = false;
    }
}

async function addTreatment() {
    if (!selected.value) return;
    saving.value = true;
    try {
        await store.addTreatment(selected.value.id, treatmentForm);
        resetObject(treatmentForm, { date: today(), problem: '', product: '', result: '', notes: '' });
    } finally {
        saving.value = false;
    }
}

async function addTask() {
    saving.value = true;
    try {
        await store.addTask({ ...taskForm, bonsai_id: taskForm.bonsai_id || null });
        resetObject(taskForm, { date: selectedCalendarDate.value, bonsai_id: '', title: '', description: '' });
    } finally {
        saving.value = false;
    }
}

async function addTreeTask() {
    if (!selected.value) return;
    saving.value = true;
    try {
        await store.addTask({
            ...treeTaskForm,
            bonsai_id: selected.value.id,
        });
        await store.fetchBonsai(selected.value.id);
        resetObject(treeTaskForm, { date: today(), title: '', description: '' });
    } finally {
        saving.value = false;
    }
}

async function deleteTreeTask(id) {
    if (!selected.value) return;

    await store.deleteTask(id);
    await store.fetchBonsai(selected.value.id);
}
</script>

<template>
    <div class="min-h-screen bg-[#f8f3ea] text-[#1f241d]">
        <div v-if="!store.ready" class="loading-screen">
            <Leaf class="h-8 w-8" />
            <span>Cuidando el jardín...</span>
        </div>

        <template v-else>
            <header class="fixed left-0 right-0 top-0 z-30 border-b border-black/5 bg-[#f8f3ea]/85 backdrop-blur-xl">
                <nav class="mx-auto flex max-w-7xl items-center justify-between px-5 py-4">
                    <button class="brand-mark" @click="screen = store.user ? 'dashboard' : 'landing'">
                        <span class="brand-symbol"><Leaf class="h-5 w-5" /></span>
                        <span>BonsaiGest</span>
                    </button>

                    <div v-if="store.user" class="hidden items-center gap-2 md:flex">
                        <button class="nav-link" :class="{ active: screen === 'dashboard' }" @click="screen = 'dashboard'">Inicio</button>
                        <button class="nav-link" :class="{ active: screen === 'bonsais' }" @click="screen = 'bonsais'">Colección</button>
                        <button class="nav-link" :class="{ active: screen === 'calendar' }" @click="screen = 'calendar'">Calendario</button>
                    </div>

                    <div class="flex items-center gap-2">
                        <template v-if="store.user">
                            <span class="hidden text-sm text-[#6c6659] sm:inline">{{ store.user.name }}</span>
                            <button class="icon-button" title="Cerrar sesión" @click="logout">
                                <LogOut class="h-4 w-4" />
                            </button>
                        </template>
                        <template v-else>
                            <button class="quiet-button" @click="screen = 'login'">
                                <LogIn class="h-4 w-4" />
                                Login
                            </button>
                            <button class="primary-button" @click="screen = 'register'">
                                <UserPlus class="h-4 w-4" />
                                Registro
                            </button>
                        </template>
                    </div>
                </nav>
            </header>

            <main class="pt-20">
                <section v-if="screen === 'landing'" class="hero-shell">
                    <div class="absolute inset-0 bg-cover bg-center" :style="{ backgroundImage: `url(${heroImage})` }" />
                    <div class="absolute inset-0 bg-gradient-to-r from-[#f8f3ea]/95 via-[#f8f3ea]/78 to-[#1f241d]/10" />
                    <div class="relative mx-auto grid min-h-[calc(100vh-5rem)] max-w-7xl content-center px-5 py-14 lg:grid-cols-[0.9fr_1.1fr]">
                        <div class="max-w-2xl">
                            <p class="eyebrow">Diario privado de bonsáis</p>
                            <h1 class="mt-4 text-5xl font-semibold leading-tight text-[#1f241d] md:text-7xl">El diario digital de tu colección de bonsáis.</h1>
                            <p class="mt-6 max-w-xl text-lg leading-8 text-[#4f5749]">Guarda la historia de cada árbol, sigue su evolución y recuerda los trabajos importantes con una experiencia tranquila y cuidada.</p>
                            <div class="mt-9 flex flex-wrap gap-3">
                                <button class="primary-button px-5 py-3" @click="screen = 'register'">
                                    <Sprout class="h-5 w-5" />
                                    Crear mi colección
                                </button>
                                <button class="quiet-button px-5 py-3" @click="screen = 'login'">
                                    <LogIn class="h-5 w-5" />
                                    Probar demo
                                </button>
                            </div>
                            <div class="mt-12 grid gap-3 sm:grid-cols-2">
                                <div class="benefit"><Sprout class="h-5 w-5" /> Guarda la historia de cada árbol.</div>
                                <div class="benefit"><Camera class="h-5 w-5" /> Sigue su evolución visual.</div>
                                <div class="benefit"><Clock3 class="h-5 w-5" /> Recuerda trabajos importantes.</div>
                                <div class="benefit"><Leaf class="h-5 w-5" /> Organiza tu colección.</div>
                            </div>
                        </div>
                    </div>
                </section>

                <section v-else-if="screen === 'login' || screen === 'register'" class="mx-auto grid min-h-[calc(100vh-5rem)] max-w-7xl items-center gap-12 px-5 py-10 lg:grid-cols-[1fr_0.85fr]">
                    <div class="hidden overflow-hidden rounded-[8px] lg:block">
                        <img :src="heroImage" alt="Bonsái en una mesa japonesa minimalista" class="h-[620px] w-full object-cover" />
                    </div>
                    <div class="auth-panel">
                        <p class="eyebrow">{{ screen === 'login' ? 'Bienvenido de nuevo' : 'Primera maceta' }}</p>
                        <h2 class="mt-3 text-4xl font-semibold">{{ screen === 'login' ? 'Entra a tu colección' : 'Crea tu diario bonsái' }}</h2>
                        <p class="mt-3 text-[#6c6659]">La cuenta demo ya está preparada: demo@bonsaigest.test / password</p>
                        <p v-if="authError" class="mt-5 rounded-[8px] border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ authError }}</p>

                        <form v-if="screen === 'login'" class="mt-8 space-y-4" @submit.prevent="submitLogin">
                            <label class="field-label">Email<input v-model="loginForm.email" type="email" required class="field-input" /></label>
                            <label class="field-label">Contraseña<input v-model="loginForm.password" type="password" required class="field-input" /></label>
                            <button class="primary-button w-full justify-center py-3" type="submit"><LogIn class="h-5 w-5" /> Entrar</button>
                        </form>

                        <form v-else class="mt-8 space-y-4" @submit.prevent="submitRegister">
                            <label class="field-label">Nombre<input v-model="registerForm.name" required class="field-input" /></label>
                            <label class="field-label">Email<input v-model="registerForm.email" type="email" required class="field-input" /></label>
                            <label class="field-label">Contraseña<input v-model="registerForm.password" type="password" minlength="8" required class="field-input" /></label>
                            <button class="primary-button w-full justify-center py-3" type="submit"><UserPlus class="h-5 w-5" /> Crear cuenta</button>
                        </form>
                    </div>
                </section>

                <section v-else-if="store.user" class="mx-auto max-w-7xl px-5 py-8">
                    <div class="mb-6 flex gap-2 md:hidden">
                        <button class="nav-link mobile" :class="{ active: screen === 'dashboard' }" @click="screen = 'dashboard'">Inicio</button>
                        <button class="nav-link mobile" :class="{ active: screen === 'bonsais' }" @click="screen = 'bonsais'">Colección</button>
                        <button class="nav-link mobile" :class="{ active: screen === 'calendar' }" @click="screen = 'calendar'">Calendario</button>
                    </div>

                    <div v-if="screen === 'dashboard'" class="space-y-8">
                        <div class="section-heading">
                            <div>
                                <p class="eyebrow">Colección viva</p>
                                <h2>Tu jardín de hoy</h2>
                            </div>
                            <button class="primary-button" @click="screen = 'bonsais'; collectionMode = 'new'"><Plus class="h-4 w-4" /> Nuevo bonsái</button>
                        </div>

                        <div class="grid gap-4 md:grid-cols-4">
                            <div class="metric-panel">
                                <Leaf class="h-6 w-6 text-[#2f6b4f]" />
                                <span>{{ dashboard.total_bonsais }}</span>
                                <p>Bonsáis registrados</p>
                            </div>
                            <div class="metric-panel">
                                <Scissors class="h-6 w-6 text-[#8a5f35]" />
                                <span>{{ dashboard.latest_events.length }}</span>
                                <p>Últimos trabajos</p>
                            </div>
                            <div class="metric-panel">
                                <CalendarDays class="h-6 w-6 text-[#38483a]" />
                                <span>{{ dashboard.upcoming_tasks.length }}</span>
                                <p>Próximas tareas</p>
                            </div>
                            <div class="metric-panel">
                                <Camera class="h-6 w-6 text-[#705b48]" />
                                <span>{{ dashboard.latest_images.length }}</span>
                                <p>Fotos recientes</p>
                            </div>
                        </div>

                        <div class="grid gap-8 lg:grid-cols-[1.3fr_0.7fr]">
                            <div>
                                <h3 class="panel-title">Árboles destacados</h3>
                                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                                    <button v-for="bonsai in dashboard.bonsais" :key="bonsai.id" class="tree-card text-left" @click="openDetail(bonsai)">
                                        <img :src="imageFor(bonsai)" :alt="bonsai.name" />
                                        <div>
                                            <p class="tree-card-title">{{ bonsai.name }}</p>
                                            <p class="text-sm text-[#6c6659]">{{ bonsai.species }}</p>
                                            <div class="mt-4 flex flex-wrap gap-2">
                                                <span class="tag">{{ bonsai.style }}</span>
                                                <span class="tag">{{ bonsai.age || 'Sin edad' }} años</span>
                                            </div>
                                        </div>
                                    </button>
                                </div>
                            </div>
                            <div class="space-y-6">
                                <div class="soft-panel">
                                    <h3 class="panel-title">Próximos trabajos</h3>
                                    <div class="mt-4 space-y-3">
                                        <div v-for="task in dashboard.upcoming_tasks" :key="task.id" class="list-row">
                                            <CalendarDays class="h-4 w-4" />
                                            <div>
                                                <p>{{ task.title }}</p>
                                                <span>{{ formatDate(task.date) }} · {{ task.bonsai?.name || 'General' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="soft-panel">
                                    <h3 class="panel-title">Últimos trabajos</h3>
                                    <div class="mt-4 space-y-3">
                                        <div v-for="event in dashboard.latest_events" :key="event.id" class="list-row">
                                            <Scissors class="h-4 w-4" />
                                            <div>
                                                <p>{{ event.type }}</p>
                                                <span>{{ formatDate(event.date) }} · {{ event.bonsai?.name }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else-if="screen === 'bonsais'" class="space-y-8">
                        <div class="section-heading">
                            <div>
                                <p class="eyebrow">Colección</p>
                                <h2>Todos tus bonsáis</h2>
                            </div>
                            <button class="primary-button" @click="collectionMode = collectionMode === 'new' ? 'list' : 'new'">
                                <Plus class="h-4 w-4" />
                                {{ collectionMode === 'new' ? 'Ver colección' : 'Nuevo bonsái' }}
                            </button>
                        </div>

                        <form v-if="collectionMode === 'new'" class="form-panel" @submit.prevent="createBonsai">
                            <div class="form-grid">
                                <label class="field-label">Nombre<input v-model="bonsaiForm.name" required class="field-input" /></label>
                                <label class="field-label">Especie<input v-model="bonsaiForm.species" required class="field-input" /></label>
                                <label class="field-label">Edad aproximada<input v-model="bonsaiForm.age" type="number" min="0" class="field-input" /></label>
                                <label class="field-label">Fecha de adquisición<input v-model="bonsaiForm.acquired_date" type="date" class="field-input" /></label>
                                <label class="field-label">Origen<select v-model="bonsaiForm.origin" class="field-input"><option v-for="item in origins" :key="item">{{ item }}</option></select></label>
                                <label class="field-label">Estilo<select v-model="bonsaiForm.style" class="field-input"><option v-for="item in styles" :key="item">{{ item }}</option></select></label>
                                <label class="field-label">Necesidad de agua<select v-model="bonsaiForm.water_level" class="field-input"><option v-for="item in waterLevels" :key="item">{{ item }}</option></select></label>
                                <label class="field-label">Ubicación<select v-model="bonsaiForm.location" class="field-input"><option v-for="item in locations" :key="item">{{ item }}</option></select></label>
                                <label class="field-label">Sustrato<input v-model="bonsaiForm.substrate" class="field-input" /></label>
                                <label class="field-label">Abono<input v-model="bonsaiForm.fertilizer" class="field-input" /></label>
                                <label class="field-label md:col-span-2">Foto principal<input type="file" accept="image/*" class="field-input file-input" @change="bonsaiForm.main_image_file = $event.target.files[0]" /></label>
                                <label class="field-label md:col-span-2">Descripción e historia<textarea v-model="bonsaiForm.description" rows="4" class="field-input" /></label>
                            </div>
                            <button class="primary-button mt-6" type="submit" :disabled="saving"><Check class="h-4 w-4" /> Guardar bonsái</button>
                        </form>

                        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                            <button v-for="bonsai in store.bonsais" :key="bonsai.id" class="tree-card vertical text-left" @click="openDetail(bonsai)">
                                <img :src="imageFor(bonsai)" :alt="bonsai.name" />
                                <div>
                                    <p class="tree-card-title">{{ bonsai.name }}</p>
                                    <p class="mt-1 text-sm text-[#6c6659]">{{ bonsai.species }}</p>
                                    <p class="mt-4 line-clamp-2 text-sm leading-6 text-[#4f5749]">{{ bonsai.description }}</p>
                                    <div class="mt-4 flex flex-wrap gap-2">
                                        <span class="tag">{{ bonsai.origin }}</span>
                                        <span class="tag">{{ bonsai.style }}</span>
                                        <span class="tag">{{ bonsai.water_level }}</span>
                                    </div>
                                </div>
                            </button>
                        </div>
                    </div>

                    <div v-else-if="screen === 'calendar'" class="space-y-8">
                        <div class="section-heading">
                            <div>
                                <p class="eyebrow">Calendario</p>
                                <h2>Agenda mensual</h2>
                            </div>
                            <div class="calendar-actions">
                                <button class="icon-button" title="Mes anterior" @click="moveCalendarMonth(-1)"><ChevronLeft class="h-4 w-4" /></button>
                                <button class="quiet-button" @click="showTodayInCalendar">Hoy</button>
                                <button class="icon-button" title="Mes siguiente" @click="moveCalendarMonth(1)"><ChevronRight class="h-4 w-4" /></button>
                            </div>
                        </div>

                        <div class="calendar-layout">
                            <div class="month-panel">
                                <div class="month-header">
                                    <h3>{{ calendarTitle }}</h3>
                                    <span>{{ store.tasks.length }} tareas en agenda</span>
                                </div>
                                <div class="weekday-grid">
                                    <span v-for="day in weekDays" :key="day">{{ day }}</span>
                                </div>
                                <div class="month-grid">
                                    <button
                                        v-for="day in calendarDays"
                                        :key="day.date"
                                        class="day-cell"
                                        :class="{ muted: !day.currentMonth, today: day.today, selected: day.selected }"
                                        @click="selectCalendarDay(day.date)"
                                    >
                                        <span class="day-number">{{ day.day }}</span>
                                        <span v-if="day.tasks.length" class="task-count">{{ day.tasks.length }}</span>
                                        <span v-for="task in day.tasks.slice(0, 3)" :key="task.id" class="calendar-task-pill" :class="{ general: !task.bonsai }">
                                            {{ task.title }}
                                        </span>
                                        <span v-if="day.tasks.length > 3" class="more-tasks">+{{ day.tasks.length - 3 }} más</span>
                                    </button>
                                </div>
                            </div>

                            <aside class="agenda-panel">
                                <div>
                                    <p class="eyebrow">Día seleccionado</p>
                                    <h3>{{ formatDate(selectedCalendarDate) }}</h3>
                                </div>

                                <div class="selected-day-tasks">
                                    <article v-for="task in selectedDateTasks" :key="task.id" class="agenda-task">
                                        <div>
                                            <p>{{ task.title }}</p>
                                            <span>{{ task.bonsai?.name || 'General' }}</span>
                                            <small v-if="task.description">{{ task.description }}</small>
                                        </div>
                                        <button class="icon-button" title="Eliminar tarea" @click="store.deleteTask(task.id)"><Trash2 class="h-4 w-4" /></button>
                                    </article>
                                    <p v-if="!selectedDateTasks.length" class="empty-note">No hay tareas para este día.</p>
                                </div>

                                <form class="agenda-form" @submit.prevent="addTask">
                                    <h4>Nueva tarea</h4>
                                    <div class="space-y-4">
                                        <label class="field-label">Fecha<input v-model="taskForm.date" type="date" required class="field-input" /></label>
                                        <label class="field-label">Bonsái<select v-model="taskForm.bonsai_id" class="field-input"><option value="">General</option><option v-for="bonsai in store.bonsais" :key="bonsai.id" :value="bonsai.id">{{ bonsai.name }}</option></select></label>
                                        <label class="field-label">Tarea<input v-model="taskForm.title" required class="field-input" placeholder="Ej. revisar riego" /></label>
                                        <label class="field-label">Descripción<textarea v-model="taskForm.description" rows="3" class="field-input" /></label>
                                    </div>
                                    <button class="primary-button mt-5 w-full" type="submit"><CalendarDays class="h-4 w-4" /> Añadir a la agenda</button>
                                </form>
                            </aside>
                        </div>

                        <div class="soft-panel">
                            <h3 class="panel-title">Todas las tareas</h3>
                            <div class="mt-4 grid gap-3 md:grid-cols-2">
                                <div v-for="task in store.tasks" :key="task.id" class="calendar-row">
                                    <div class="date-chip">{{ formatDate(task.date) }}</div>
                                    <div class="min-w-0 flex-1">
                                        <p class="font-semibold">{{ task.title }}</p>
                                        <span>{{ task.bonsai?.name || 'General' }}</span>
                                        <p v-if="task.description" class="mt-1 text-sm leading-6 text-[#5f6658]">{{ task.description }}</p>
                                    </div>
                                    <button class="icon-button" title="Eliminar tarea" @click="store.deleteTask(task.id)"><Trash2 class="h-4 w-4" /></button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else-if="screen === 'detail' && selected" class="space-y-8">
                        <button class="quiet-button" @click="screen = 'bonsais'">Volver a la colección</button>
                        <section class="detail-hero">
                            <img :src="imageFor(selected)" :alt="selected.name" />
                            <div class="detail-copy">
                                <p class="eyebrow">{{ selected.species }}</p>
                                <h2>{{ selected.name }}</h2>
                                <p class="mt-4 text-lg leading-8 text-[#4f5749]">{{ selected.description }}</p>
                                <div class="mt-6 grid gap-3 sm:grid-cols-3">
                                    <div class="fact"><span>Edad</span><strong>{{ selected.age || 'Sin dato' }} años</strong></div>
                                    <div class="fact"><span>Estilo</span><strong>{{ selected.style }}</strong></div>
                                    <div class="fact"><span>Origen</span><strong>{{ selected.origin }}</strong></div>
                                </div>
                            </div>
                        </section>

                        <div class="tabs">
                            <button :class="{ active: detailTab === 'journal' }" @click="detailTab = 'journal'"><Scissors class="h-4 w-4" /> Diario</button>
                            <button :class="{ active: detailTab === 'gallery' }" @click="detailTab = 'gallery'"><Camera class="h-4 w-4" /> Evolución</button>
                            <button :class="{ active: detailTab === 'agenda' }" @click="detailTab = 'agenda'"><CalendarDays class="h-4 w-4" /> Agenda</button>
                            <button :class="{ active: detailTab === 'cultivation' }" @click="detailTab = 'cultivation'"><Droplets class="h-4 w-4" /> Cultivo</button>
                            <button :class="{ active: detailTab === 'treatments' }" @click="detailTab = 'treatments'"><ShieldCheck class="h-4 w-4" /> Plagas</button>
                        </div>

                        <div v-if="detailTab === 'journal'" class="grid gap-8 lg:grid-cols-[0.75fr_1.25fr]">
                            <form class="form-panel" @submit.prevent="addEvent">
                                <h3 class="panel-title mb-4">Registrar trabajo</h3>
                                <div class="space-y-4">
                                    <label class="field-label">Fecha<input v-model="eventForm.date" type="date" required class="field-input" /></label>
                                    <label class="field-label">Tipo<select v-model="eventForm.type" class="field-input"><option v-for="item in workTypes" :key="item">{{ item }}</option></select></label>
                                    <label class="field-label">Notas<textarea v-model="eventForm.notes" rows="5" class="field-input" /></label>
                                    <label class="field-label">Foto del trabajo<input type="file" accept="image/*" class="field-input file-input" @change="eventForm.image_file = $event.target.files[0]" /></label>
                                </div>
                                <button class="primary-button mt-6" type="submit"><Plus class="h-4 w-4" /> Añadir entrada</button>
                            </form>

                            <div class="timeline">
                                <div v-for="group in timelineGroups" :key="group.label" class="timeline-group">
                                    <p class="timeline-month">{{ group.label }}</p>
                                    <article v-for="event in group.events" :key="event.id" class="timeline-entry">
                                        <img v-if="event.images[0]" :src="event.images[0].image" :alt="event.type" />
                                        <div>
                                            <span>{{ formatDate(event.date) }}</span>
                                            <h3>{{ event.type }}</h3>
                                            <p>{{ event.notes }}</p>
                                        </div>
                                    </article>
                                </div>
                            </div>
                        </div>

                        <div v-else-if="detailTab === 'gallery'" class="space-y-6">
                            <form class="form-panel" @submit.prevent="addImage">
                                <div class="form-grid compact">
                                    <label class="field-label">Fecha<input v-model="imageForm.date" type="date" required class="field-input" /></label>
                                    <label class="field-label">Descripción<input v-model="imageForm.description" class="field-input" /></label>
                                    <label class="field-label">Foto<input type="file" accept="image/*" required class="field-input file-input" @change="imageForm.image_file = $event.target.files[0]" /></label>
                                </div>
                                <button class="primary-button mt-5" type="submit"><ImagePlus class="h-4 w-4" /> Subir foto</button>
                            </form>
                            <div class="gallery-grid">
                                <figure v-for="image in selected.images" :key="image.id">
                                    <img :src="image.image" :alt="image.description || selected.name" />
                                    <figcaption>{{ formatDate(image.date) }} · {{ image.description || 'Evolución' }}</figcaption>
                                </figure>
                            </div>
                        </div>

                        <div v-else-if="detailTab === 'cultivation'" class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                            <div class="fact large"><Droplets class="h-5 w-5" /><span>Agua</span><strong>{{ selected.water_level }}</strong></div>
                            <div class="fact large"><Sparkles class="h-5 w-5" /><span>Ubicación</span><strong>{{ selected.location }}</strong></div>
                            <div class="fact large"><Sprout class="h-5 w-5" /><span>Sustrato</span><strong>{{ selected.substrate || 'Sin dato' }}</strong></div>
                            <div class="fact large"><Leaf class="h-5 w-5" /><span>Abono</span><strong>{{ selected.fertilizer || 'Sin dato' }}</strong></div>
                        </div>

                        <div v-else-if="detailTab === 'agenda'" class="grid gap-8 lg:grid-cols-[0.75fr_1.25fr]">
                            <form class="form-panel" @submit.prevent="addTreeTask">
                                <h3 class="panel-title mb-4">Añadir tarea para {{ selected.name }}</h3>
                                <div class="space-y-4">
                                    <label class="field-label">Fecha<input v-model="treeTaskForm.date" type="date" required class="field-input" /></label>
                                    <label class="field-label">Tarea<input v-model="treeTaskForm.title" required class="field-input" placeholder="Ej. revisar alambre" /></label>
                                    <label class="field-label">Descripción<textarea v-model="treeTaskForm.description" rows="4" class="field-input" /></label>
                                </div>
                                <button class="primary-button mt-6" type="submit"><CalendarDays class="h-4 w-4" /> Añadir a la agenda</button>
                            </form>
                            <div class="soft-panel">
                                <h3 class="panel-title">Tareas de {{ selected.name }}</h3>
                                <div class="mt-4 space-y-3">
                                    <article v-for="task in selectedTreeTasks" :key="task.id" class="agenda-task">
                                        <div>
                                            <p>{{ task.title }}</p>
                                            <span>{{ formatDate(task.date) }}</span>
                                            <small v-if="task.description">{{ task.description }}</small>
                                        </div>
                                        <button class="icon-button" title="Eliminar tarea" @click="deleteTreeTask(task.id)"><Trash2 class="h-4 w-4" /></button>
                                    </article>
                                    <p v-if="!selectedTreeTasks.length" class="empty-note">Todavía no hay tareas previstas para este árbol.</p>
                                </div>
                            </div>
                        </div>

                        <div v-else-if="detailTab === 'treatments'" class="grid gap-8 lg:grid-cols-[0.75fr_1.25fr]">
                            <form class="form-panel" @submit.prevent="addTreatment">
                                <h3 class="panel-title mb-4">Registrar tratamiento</h3>
                                <div class="space-y-4">
                                    <label class="field-label">Fecha<input v-model="treatmentForm.date" type="date" required class="field-input" /></label>
                                    <label class="field-label">Problema<input v-model="treatmentForm.problem" required class="field-input" /></label>
                                    <label class="field-label">Producto<input v-model="treatmentForm.product" class="field-input" /></label>
                                    <label class="field-label">Resultado<input v-model="treatmentForm.result" class="field-input" /></label>
                                    <label class="field-label">Notas<textarea v-model="treatmentForm.notes" rows="4" class="field-input" /></label>
                                </div>
                                <button class="primary-button mt-6" type="submit"><ShieldCheck class="h-4 w-4" /> Guardar tratamiento</button>
                            </form>
                            <div class="soft-panel">
                                <div class="space-y-3">
                                    <article v-for="item in selected.treatments" :key="item.id" class="treatment-row">
                                        <span>{{ formatDate(item.date) }}</span>
                                        <h3>{{ item.problem }}</h3>
                                        <p>{{ item.product }} · {{ item.result }}</p>
                                        <p v-if="item.notes">{{ item.notes }}</p>
                                    </article>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </main>
        </template>
    </div>
</template>
