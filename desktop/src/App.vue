<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import {
  CalendarDays,
  Camera,
  ChevronLeft,
  ChevronRight,
  Download,
  Droplets,
  FileUp,
  Leaf,
  Plus,
  Scissors,
  ShieldCheck,
  Sprout,
  Trash2,
} from '@lucide/vue';
import { fileToDataUrl, loadState, saveState } from './lib/storage';

const heroImage = '/bonsai-hero.png';
const activeView = ref('dashboard');
const activeDetailTab = ref('journal');
const selectedTreeId = ref(null);
const showTreeForm = ref(false);
const ready = ref(false);
const saving = ref(false);
const message = ref('');
const calendarCursor = ref(new Date(new Date().getFullYear(), new Date().getMonth(), 1));
const selectedCalendarDate = ref(today());

const state = reactive({
  bonsais: [],
  tasks: [],
});

const treeForm = reactive(emptyTree());
const eventForm = reactive({ date: today(), type: 'Poda', notes: '', imageFile: null });
const imageForm = reactive({ date: today(), description: '', imageFile: null });
const taskForm = reactive({ date: today(), bonsaiId: '', title: '', description: '' });
const treeTaskForm = reactive({ date: today(), title: '', description: '' });
const treatmentForm = reactive({ date: today(), problem: '', product: '', result: '', notes: '' });

const origins = ['Semilla', 'Esqueje', 'Acodo', 'Yamadori', 'Compra', 'Regalo'];
const styles = ['Chokkan', 'Moyogi', 'Shakan', 'Cascada', 'Bosque', 'Otro'];
const waterLevels = ['Baja', 'Media', 'Alta'];
const locations = ['Interior', 'Exterior'];
const workTypes = ['Poda', 'Pinzado', 'Alambrado', 'Trasplante', 'Abonado', 'Tratamiento', 'Riego especial', 'Diseño', 'Otro'];
const weekDays = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];

onMounted(async () => {
  const saved = await loadState();
  Object.assign(state, saved ?? demoState());
  selectedTreeId.value = state.bonsais[0]?.id ?? null;
  ready.value = true;
});

const selectedTree = computed(() => state.bonsais.find((tree) => tree.id === selectedTreeId.value) ?? null);
const totalEvents = computed(() => state.bonsais.reduce((total, tree) => total + tree.events.length, 0));
const totalPhotos = computed(() => state.bonsais.reduce((total, tree) => total + tree.images.length, 0));

const upcomingTasks = computed(() => [...state.tasks]
  .filter((task) => task.date >= today())
  .sort((a, b) => a.date.localeCompare(b.date))
  .slice(0, 6));

const treeTasks = computed(() => {
  if (!selectedTree.value) return [];

  return state.tasks
    .filter((task) => task.bonsaiId === selectedTree.value.id)
    .sort((a, b) => a.date.localeCompare(b.date));
});

const tasksByDate = computed(() => state.tasks.reduce((carry, task) => {
  carry[task.date] = carry[task.date] ?? [];
  carry[task.date].push(task);
  return carry;
}, {}));

const selectedDateTasks = computed(() => tasksByDate.value[selectedCalendarDate.value] ?? []);

const calendarTitle = computed(() => new Intl.DateTimeFormat('es-ES', {
  month: 'long',
  year: 'numeric',
}).format(calendarCursor.value));

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
  if (!selectedTree.value) return [];

  const imagesByDate = selectedTree.value.images.reduce((carry, image) => {
    carry[image.date] = carry[image.date] ?? [];
    carry[image.date].push(image);
    return carry;
  }, {});

  const groups = {};
  [...selectedTree.value.events]
    .sort((a, b) => b.date.localeCompare(a.date))
    .forEach((event) => {
      const label = monthLabel(event.date);
      groups[label] = groups[label] ?? [];
      groups[label].push({ ...event, images: imagesByDate[event.date] ?? [] });
    });

  return Object.entries(groups).map(([label, events]) => ({ label, events }));
});

function uid() {
  return crypto.randomUUID?.() ?? `${Date.now()}-${Math.random().toString(16).slice(2)}`;
}

function today() {
  return isoDate(new Date());
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
  return new Intl.DateTimeFormat('es-ES', { day: 'numeric', month: 'short', year: 'numeric' }).format(asDate(value));
}

function monthLabel(value) {
  return new Intl.DateTimeFormat('es-ES', { month: 'long', year: 'numeric' }).format(asDate(value)).toUpperCase();
}

function treeName(id) {
  return state.bonsais.find((tree) => tree.id === id)?.name ?? 'General';
}

function treeImage(tree) {
  return tree?.mainImage || heroImage;
}

function emptyTree() {
  return {
    name: '',
    species: '',
    age: '',
    acquiredDate: '',
    origin: 'Compra',
    style: 'Moyogi',
    waterLevel: 'Media',
    location: 'Exterior',
    substrate: '',
    fertilizer: '',
    description: '',
    imageFile: null,
  };
}

function demoState() {
  const bonsaiId = uid();

  return {
    bonsais: [
      {
        id: bonsaiId,
        name: 'Kumo',
        species: 'Juniperus chinensis',
        age: 18,
        acquiredDate: '2021-03-12',
        origin: 'Compra',
        style: 'Moyogi',
        waterLevel: 'Media',
        location: 'Exterior',
        substrate: 'Akadama, kiryuzuna y pomice',
        fertilizer: 'Orgánico sólido en primavera y otoño',
        mainImage: heroImage,
        description: 'Junípero de movimiento suave, trabajado poco a poco para conservar un frente sereno y ramas ligeras.',
        events: [
          { id: uid(), date: '2026-03-08', type: 'Trasplante', notes: 'Cambio a akadama y kiryu. Raíces finas sanas y buen drenaje.' },
          { id: uid(), date: '2026-04-18', type: 'Pinzado', notes: 'Pinzado ligero para compactar brotes exteriores.' },
        ],
        images: [
          { id: uid(), date: '2021-03-12', image: heroImage, description: 'Foto inicial' },
          { id: uid(), date: '2026-03-08', image: heroImage, description: 'Trasplante' },
        ],
        treatments: [
          { id: uid(), date: '2026-05-02', problem: 'Araña roja inicial', product: 'Jabón potásico', result: 'Controlado', notes: 'Dos aplicaciones separadas por siete días.' },
        ],
      },
    ],
    tasks: [
      { id: uid(), bonsaiId, date: '2026-07-18', title: 'Revisar alambre', description: 'Comprobar marcas en la primera rama.' },
      { id: uid(), bonsaiId: '', date: '2026-07-22', title: 'Preparar abono', description: 'Revisar existencias para final de verano.' },
    ],
  };
}

async function persist(successMessage = 'Guardado') {
  await saveState(JSON.parse(JSON.stringify(state)));
  message.value = successMessage;
  setTimeout(() => {
    if (message.value === successMessage) message.value = '';
  }, 2200);
}

function resetObject(target, source) {
  Object.keys(target).forEach((key) => delete target[key]);
  Object.assign(target, source);
}

function openTree(tree) {
  selectedTreeId.value = tree.id;
  activeView.value = 'detail';
  activeDetailTab.value = 'journal';
}

function openCollectionForm() {
  activeView.value = 'collection';
  showTreeForm.value = true;
}

async function addTree() {
  saving.value = true;
  try {
    const mainImage = treeForm.imageFile ? await fileToDataUrl(treeForm.imageFile) : heroImage;
    const tree = {
      id: uid(),
      name: treeForm.name,
      species: treeForm.species,
      age: treeForm.age,
      acquiredDate: treeForm.acquiredDate,
      origin: treeForm.origin,
      style: treeForm.style,
      waterLevel: treeForm.waterLevel,
      location: treeForm.location,
      substrate: treeForm.substrate,
      fertilizer: treeForm.fertilizer,
      description: treeForm.description,
      mainImage,
      events: [],
      images: treeForm.acquiredDate ? [{ id: uid(), date: treeForm.acquiredDate, image: mainImage, description: 'Foto inicial' }] : [],
      treatments: [],
    };

    state.bonsais.unshift(tree);
    selectedTreeId.value = tree.id;
    resetObject(treeForm, emptyTree());
    await persist('Bonsái añadido');
    showTreeForm.value = false;
    activeView.value = 'detail';
  } finally {
    saving.value = false;
  }
}

async function addEvent() {
  if (!selectedTree.value) return;

  saving.value = true;
  try {
    selectedTree.value.events.unshift({
      id: uid(),
      date: eventForm.date,
      type: eventForm.type,
      notes: eventForm.notes,
    });

    if (eventForm.imageFile) {
      const image = await fileToDataUrl(eventForm.imageFile);
      selectedTree.value.images.push({
        id: uid(),
        date: eventForm.date,
        image,
        description: eventForm.type,
      });
      selectedTree.value.mainImage = image;
    }

    resetObject(eventForm, { date: today(), type: 'Poda', notes: '', imageFile: null });
    await persist('Trabajo registrado');
  } finally {
    saving.value = false;
  }
}

async function addImage() {
  if (!selectedTree.value || !imageForm.imageFile) return;

  saving.value = true;
  try {
    const image = await fileToDataUrl(imageForm.imageFile);
    selectedTree.value.images.push({
      id: uid(),
      date: imageForm.date,
      image,
      description: imageForm.description,
    });
    selectedTree.value.mainImage = image;
    resetObject(imageForm, { date: today(), description: '', imageFile: null });
    await persist('Foto añadida');
  } finally {
    saving.value = false;
  }
}

async function addTask(source = 'calendar') {
  const form = source === 'tree' ? treeTaskForm : taskForm;
  const bonsaiId = source === 'tree' ? selectedTree.value?.id : form.bonsaiId;

  if (!form.title) return;

  state.tasks.push({
    id: uid(),
    bonsaiId: bonsaiId || '',
    date: form.date,
    title: form.title,
    description: form.description,
  });

  if (source === 'tree') {
    resetObject(treeTaskForm, { date: today(), title: '', description: '' });
  } else {
    resetObject(taskForm, { date: selectedCalendarDate.value, bonsaiId: '', title: '', description: '' });
  }

  await persist('Tarea añadida');
}

async function deleteTask(id) {
  state.tasks = state.tasks.filter((task) => task.id !== id);
  await persist('Tarea eliminada');
}

async function addTreatment() {
  if (!selectedTree.value) return;

  selectedTree.value.treatments.unshift({
    id: uid(),
    ...treatmentForm,
  });

  resetObject(treatmentForm, { date: today(), problem: '', product: '', result: '', notes: '' });
  await persist('Tratamiento registrado');
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

function exportBackup() {
  const payload = JSON.stringify(state, null, 2);
  const blob = new Blob([payload], { type: 'application/json' });
  const link = document.createElement('a');
  link.href = URL.createObjectURL(blob);
  link.download = `bonsaigest-copia-${today()}.json`;
  link.click();
  URL.revokeObjectURL(link.href);
}

async function importBackup(file) {
  if (!file) return;

  const text = await file.text();
  const imported = JSON.parse(text);
  state.bonsais = imported.bonsais ?? [];
  state.tasks = imported.tasks ?? [];
  selectedTreeId.value = state.bonsais[0]?.id ?? null;
  await persist('Copia importada');
}
</script>

<template>
  <div v-if="!ready" class="loading">
    <Leaf />
    <span>Abriendo BonsaiGest...</span>
  </div>

  <div v-else class="app-shell">
    <aside class="sidebar">
      <button class="brand" @click="activeView = 'dashboard'">
        <span><Leaf :size="20" /></span>
        BonsaiGest
      </button>

      <nav>
        <button :class="{ active: activeView === 'dashboard' }" @click="activeView = 'dashboard'"><Sprout :size="18" /> Inicio</button>
        <button :class="{ active: activeView === 'collection' }" @click="activeView = 'collection'; showTreeForm = false"><Leaf :size="18" /> Colección</button>
        <button :class="{ active: activeView === 'calendar' }" @click="activeView = 'calendar'"><CalendarDays :size="18" /> Agenda</button>
      </nav>

      <div class="backup-box">
        <button class="ghost-button" @click="exportBackup"><Download :size="16" /> Copia</button>
        <label class="ghost-button file-action"><FileUp :size="16" /> Importar<input type="file" accept="application/json" @change="importBackup($event.target.files[0])" /></label>
      </div>
    </aside>

    <main class="content">
      <div v-if="message" class="toast">{{ message }}</div>

      <section v-if="activeView === 'dashboard'" class="view-stack">
        <div class="hero">
          <div>
            <p class="eyebrow">Colección local</p>
            <h1>Agenda y diario de bonsáis</h1>
            <p>Todo queda guardado en este ordenador. Ideal para consultar qué toca hacer y documentar la evolución de cada árbol.</p>
            <div class="hero-actions">
              <button class="primary-button" @click="openCollectionForm"><Plus :size="18" /> Añadir bonsái</button>
              <button class="secondary-button" @click="activeView = 'calendar'"><CalendarDays :size="18" /> Ver agenda</button>
            </div>
          </div>
          <img :src="heroImage" alt="Bonsái" />
        </div>

        <div class="metrics">
          <article><Leaf /><strong>{{ state.bonsais.length }}</strong><span>Bonsáis</span></article>
          <article><Scissors /><strong>{{ totalEvents }}</strong><span>Trabajos</span></article>
          <article><Camera /><strong>{{ totalPhotos }}</strong><span>Fotos</span></article>
          <article><CalendarDays /><strong>{{ upcomingTasks.length }}</strong><span>Próximas tareas</span></article>
        </div>

        <div class="split-grid">
          <section>
            <div class="section-title">
              <h2>Árboles</h2>
              <button class="secondary-button compact" @click="activeView = 'collection'; showTreeForm = false">Ver todos</button>
            </div>
            <div class="tree-grid">
              <button v-for="tree in state.bonsais.slice(0, 4)" :key="tree.id" class="tree-card" @click="openTree(tree)">
                <img :src="treeImage(tree)" :alt="tree.name" />
                <span>{{ tree.name }}</span>
                <small>{{ tree.species }}</small>
              </button>
            </div>
          </section>
          <section class="panel">
            <h2>Próximos cuidados</h2>
            <article v-for="task in upcomingTasks" :key="task.id" class="task-row">
              <CalendarDays :size="17" />
              <div>
                <strong>{{ task.title }}</strong>
                <span>{{ formatDate(task.date) }} · {{ treeName(task.bonsaiId) }}</span>
              </div>
            </article>
          </section>
        </div>
      </section>

      <section v-else-if="activeView === 'collection'" class="view-stack">
        <div class="section-title">
          <div>
            <p class="eyebrow">Colección</p>
            <h1>Mis bonsáis</h1>
          </div>
          <button v-if="!showTreeForm" class="primary-button" @click="showTreeForm = true"><Plus :size="18" /> Añadir bonsái</button>
          <button v-else class="secondary-button" @click="showTreeForm = false">Ver colección</button>
        </div>

        <form v-if="showTreeForm" class="form-panel" @submit.prevent="addTree">
          <h2>Nuevo bonsái</h2>
          <div class="form-grid">
            <label>Nombre<input v-model="treeForm.name" required /></label>
            <label>Especie<input v-model="treeForm.species" required /></label>
            <label>Edad aproximada<input v-model="treeForm.age" type="number" min="0" /></label>
            <label>Fecha de adquisición<input v-model="treeForm.acquiredDate" type="date" /></label>
            <label>Origen<select v-model="treeForm.origin"><option v-for="item in origins" :key="item">{{ item }}</option></select></label>
            <label>Estilo<select v-model="treeForm.style"><option v-for="item in styles" :key="item">{{ item }}</option></select></label>
            <label>Necesidad de agua<select v-model="treeForm.waterLevel"><option v-for="item in waterLevels" :key="item">{{ item }}</option></select></label>
            <label>Ubicación<select v-model="treeForm.location"><option v-for="item in locations" :key="item">{{ item }}</option></select></label>
            <label>Sustrato<input v-model="treeForm.substrate" /></label>
            <label>Abono<input v-model="treeForm.fertilizer" /></label>
            <label class="wide">Foto principal<input type="file" accept="image/*" @change="treeForm.imageFile = $event.target.files[0]" /></label>
            <label class="wide">Historia<textarea v-model="treeForm.description" rows="4" /></label>
          </div>
          <button class="primary-button" :disabled="saving"><Plus :size="18" /> Guardar árbol</button>
        </form>

        <div v-if="state.bonsais.length" class="tree-grid large">
          <button v-for="tree in state.bonsais" :key="tree.id" class="tree-card" @click="openTree(tree)">
            <img :src="treeImage(tree)" :alt="tree.name" />
            <span>{{ tree.name }}</span>
            <small>{{ tree.species }} · {{ tree.style }}</small>
          </button>
        </div>
        <div v-else class="empty-collection">
          <Leaf :size="34" />
          <h2>Todavía no hay bonsáis</h2>
          <p>Añade el primer árbol para empezar el diario y la agenda.</p>
          <button class="primary-button" @click="showTreeForm = true"><Plus :size="18" /> Añadir bonsái</button>
        </div>
      </section>

      <section v-else-if="activeView === 'calendar'" class="view-stack">
        <div class="section-title">
          <div>
            <p class="eyebrow">Agenda</p>
            <h1>Qué toca hacer</h1>
          </div>
          <div class="calendar-actions">
            <button class="icon-button" @click="moveCalendarMonth(-1)"><ChevronLeft :size="18" /></button>
            <button class="secondary-button compact" @click="showTodayInCalendar">Hoy</button>
            <button class="icon-button" @click="moveCalendarMonth(1)"><ChevronRight :size="18" /></button>
          </div>
        </div>

        <div class="calendar-layout">
          <section class="month-panel">
            <div class="month-header">
              <h2>{{ calendarTitle }}</h2>
              <span>{{ state.tasks.length }} tareas</span>
            </div>
            <div class="weekday-grid"><span v-for="day in weekDays" :key="day">{{ day }}</span></div>
            <div class="month-grid">
              <button v-for="day in calendarDays" :key="day.date" class="day-cell" :class="{ muted: !day.currentMonth, today: day.today, selected: day.selected }" @click="selectCalendarDay(day.date)">
                <span class="day-number">{{ day.day }}</span>
                <span v-for="task in day.tasks.slice(0, 3)" :key="task.id" class="task-pill" :class="{ general: !task.bonsaiId }">{{ task.title }}</span>
                <span v-if="day.tasks.length > 3" class="more">+{{ day.tasks.length - 3 }}</span>
              </button>
            </div>
          </section>

          <aside class="panel agenda-panel">
            <p class="eyebrow">Día seleccionado</p>
            <h2>{{ formatDate(selectedCalendarDate) }}</h2>

            <article v-for="task in selectedDateTasks" :key="task.id" class="task-row removable">
              <CalendarDays :size="17" />
              <div>
                <strong>{{ task.title }}</strong>
                <span>{{ treeName(task.bonsaiId) }}</span>
                <small v-if="task.description">{{ task.description }}</small>
              </div>
              <button class="icon-button" @click="deleteTask(task.id)"><Trash2 :size="16" /></button>
            </article>
            <p v-if="!selectedDateTasks.length" class="empty-note">No hay tareas para este día.</p>

            <form class="mini-form" @submit.prevent="addTask('calendar')">
              <label>Fecha<input v-model="taskForm.date" type="date" required /></label>
              <label>Bonsái<select v-model="taskForm.bonsaiId"><option value="">General</option><option v-for="tree in state.bonsais" :key="tree.id" :value="tree.id">{{ tree.name }}</option></select></label>
              <label>Tarea<input v-model="taskForm.title" required /></label>
              <label>Descripción<textarea v-model="taskForm.description" rows="3" /></label>
              <button class="primary-button"><Plus :size="18" /> Añadir tarea</button>
            </form>
          </aside>
        </div>
      </section>

      <section v-else-if="activeView === 'detail' && selectedTree" class="view-stack">
        <button class="secondary-button compact" @click="activeView = 'collection'">Volver</button>

        <div class="detail-hero">
          <img :src="treeImage(selectedTree)" :alt="selectedTree.name" />
          <div>
            <p class="eyebrow">{{ selectedTree.species }}</p>
            <h1>{{ selectedTree.name }}</h1>
            <p>{{ selectedTree.description }}</p>
            <div class="facts">
              <article><span>Edad</span><strong>{{ selectedTree.age || 'Sin dato' }} años</strong></article>
              <article><span>Estilo</span><strong>{{ selectedTree.style }}</strong></article>
              <article><span>Origen</span><strong>{{ selectedTree.origin }}</strong></article>
            </div>
          </div>
        </div>

        <div class="tabs">
          <button :class="{ active: activeDetailTab === 'journal' }" @click="activeDetailTab = 'journal'"><Scissors :size="17" /> Diario</button>
          <button :class="{ active: activeDetailTab === 'gallery' }" @click="activeDetailTab = 'gallery'"><Camera :size="17" /> Evolución</button>
          <button :class="{ active: activeDetailTab === 'agenda' }" @click="activeDetailTab = 'agenda'"><CalendarDays :size="17" /> Agenda</button>
          <button :class="{ active: activeDetailTab === 'cultivation' }" @click="activeDetailTab = 'cultivation'"><Droplets :size="17" /> Cultivo</button>
          <button :class="{ active: activeDetailTab === 'treatments' }" @click="activeDetailTab = 'treatments'"><ShieldCheck :size="17" /> Plagas</button>
        </div>

        <div v-if="activeDetailTab === 'journal'" class="split-grid">
          <form class="form-panel" @submit.prevent="addEvent">
            <h2>Registrar trabajo</h2>
            <label>Fecha<input v-model="eventForm.date" type="date" required /></label>
            <label>Tipo<select v-model="eventForm.type"><option v-for="item in workTypes" :key="item">{{ item }}</option></select></label>
            <label>Notas<textarea v-model="eventForm.notes" rows="5" /></label>
            <label>Foto<input type="file" accept="image/*" @change="eventForm.imageFile = $event.target.files[0]" /></label>
            <button class="primary-button"><Plus :size="18" /> Añadir entrada</button>
          </form>
          <section class="timeline">
            <div v-for="group in timelineGroups" :key="group.label">
              <p class="month-label">{{ group.label }}</p>
              <article v-for="event in group.events" :key="event.id" class="timeline-entry">
                <img v-if="event.images[0]" :src="event.images[0].image" :alt="event.type" />
                <div>
                  <span>{{ formatDate(event.date) }}</span>
                  <h3>{{ event.type }}</h3>
                  <p>{{ event.notes }}</p>
                </div>
              </article>
            </div>
          </section>
        </div>

        <div v-else-if="activeDetailTab === 'gallery'" class="view-stack">
          <form class="form-panel inline-form" @submit.prevent="addImage">
            <label>Fecha<input v-model="imageForm.date" type="date" required /></label>
            <label>Descripción<input v-model="imageForm.description" /></label>
            <label>Foto<input type="file" accept="image/*" required @change="imageForm.imageFile = $event.target.files[0]" /></label>
            <button class="primary-button"><Camera :size="18" /> Subir foto</button>
          </form>
          <div class="gallery-grid">
            <figure v-for="image in selectedTree.images" :key="image.id">
              <img :src="image.image" :alt="image.description" />
              <figcaption>{{ formatDate(image.date) }} · {{ image.description || 'Evolución' }}</figcaption>
            </figure>
          </div>
        </div>

        <div v-else-if="activeDetailTab === 'agenda'" class="split-grid">
          <form class="form-panel" @submit.prevent="addTask('tree')">
            <h2>Tarea para {{ selectedTree.name }}</h2>
            <label>Fecha<input v-model="treeTaskForm.date" type="date" required /></label>
            <label>Tarea<input v-model="treeTaskForm.title" required /></label>
            <label>Descripción<textarea v-model="treeTaskForm.description" rows="4" /></label>
            <button class="primary-button"><CalendarDays :size="18" /> Añadir a agenda</button>
          </form>
          <section class="panel">
            <h2>Tareas previstas</h2>
            <article v-for="task in treeTasks" :key="task.id" class="task-row removable">
              <CalendarDays :size="17" />
              <div>
                <strong>{{ task.title }}</strong>
                <span>{{ formatDate(task.date) }}</span>
                <small v-if="task.description">{{ task.description }}</small>
              </div>
              <button class="icon-button" @click="deleteTask(task.id)"><Trash2 :size="16" /></button>
            </article>
            <p v-if="!treeTasks.length" class="empty-note">No hay tareas para este árbol.</p>
          </section>
        </div>

        <div v-else-if="activeDetailTab === 'cultivation'" class="facts large">
          <article><Droplets /><span>Agua</span><strong>{{ selectedTree.waterLevel }}</strong></article>
          <article><Leaf /><span>Ubicación</span><strong>{{ selectedTree.location }}</strong></article>
          <article><Sprout /><span>Sustrato</span><strong>{{ selectedTree.substrate || 'Sin dato' }}</strong></article>
          <article><Leaf /><span>Abono</span><strong>{{ selectedTree.fertilizer || 'Sin dato' }}</strong></article>
        </div>

        <div v-else class="split-grid">
          <form class="form-panel" @submit.prevent="addTreatment">
            <h2>Registrar tratamiento</h2>
            <label>Fecha<input v-model="treatmentForm.date" type="date" required /></label>
            <label>Problema<input v-model="treatmentForm.problem" required /></label>
            <label>Producto<input v-model="treatmentForm.product" /></label>
            <label>Resultado<input v-model="treatmentForm.result" /></label>
            <label>Notas<textarea v-model="treatmentForm.notes" rows="4" /></label>
            <button class="primary-button"><ShieldCheck :size="18" /> Guardar tratamiento</button>
          </form>
          <section class="panel">
            <article v-for="item in selectedTree.treatments" :key="item.id" class="treatment-row">
              <span>{{ formatDate(item.date) }}</span>
              <h3>{{ item.problem }}</h3>
              <p>{{ item.product }} · {{ item.result }}</p>
              <p>{{ item.notes }}</p>
            </article>
          </section>
        </div>
      </section>
    </main>
  </div>
</template>
