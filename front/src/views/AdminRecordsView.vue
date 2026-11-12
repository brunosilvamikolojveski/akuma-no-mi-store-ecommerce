<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import AdminHeader from '../components/AdminHeader.vue';
import { apiRequest } from '../services/api';
import { useAuth } from '../services/auth';

const { user, loadCurrentUser } = useAuth();
const authors = ref([]);
const categories = ref([]);
const series = ref([]);
const activeTab = ref('series');
const seriesStatuses = { ongoing: 'Em andamento', completed: 'Concluída', hiatus: 'Hiato' };
const loading = ref(true);
const saving = ref(false);
const pageError = ref('');
const formError = ref('');
const fieldErrors = ref({});
const showForm = ref(false);
const editingId = ref(null);
const slugEdited = ref(false);

const form = reactive({});
const isAdmin = computed(() => user.value?.role === 'admin');
const tabInfo = computed(() => ({
  authors: { label: 'Autores', singular: 'autor', plural: 'autores', path: '/api/admin/authors' },
  categories: { label: 'Categorias', singular: 'categoria', plural: 'categorias', path: '/api/admin/categories' },
  series: { label: 'Séries', singular: 'série', plural: 'séries', path: '/api/admin/series' },
}[activeTab.value]));
const activeRecords = computed(() => ({ authors: authors.value, categories: categories.value, series: series.value }[activeTab.value]));

onMounted(initialize);

async function initialize() {
  await loadCurrentUser();
  loading.value = false;

  if (isAdmin.value) await loadRecords();
}

async function loadRecords() {
  loading.value = true;
  pageError.value = '';

  try {
    const [authorResponse, categoryResponse, seriesResponse] = await Promise.all([
      apiRequest('/api/admin/authors'),
      apiRequest('/api/admin/categories'),
      apiRequest('/api/admin/series'),
    ]);

    authors.value = authorResponse.data;
    categories.value = categoryResponse.data;
    series.value = seriesResponse.data;
  } catch (error) {
    pageError.value = error.message;
  } finally {
    loading.value = false;
  }
}

function emptyForm(type) {
  if (type === 'authors') return { name: '', biography: '' };
  if (type === 'categories') return { name: '', slug: '' };
  return { title: '', slug: '', description: '', status: 'ongoing', author_ids: [], category_ids: [] };
}

function resetForm(values) {
  Object.keys(form).forEach((key) => delete form[key]);
  Object.assign(form, values);
}

function openCreate() {
  resetForm(emptyForm(activeTab.value));
  editingId.value = null;
  slugEdited.value = false;
  fieldErrors.value = {};
  formError.value = '';
  showForm.value = true;
}

function openEdit(record) {
  if (activeTab.value === 'authors') {
    resetForm({ name: record.name, biography: record.biography || '' });
  } else if (activeTab.value === 'categories') {
    resetForm({ name: record.name, slug: record.slug });
  } else {
    resetForm({
      title: record.title,
      slug: record.slug,
      description: record.description || '',
      status: record.status || 'ongoing',
      author_ids: record.authors.map((author) => author.id),
      category_ids: record.categories.map((category) => category.id),
    });
  }

  editingId.value = record.id;
  slugEdited.value = true;
  fieldErrors.value = {};
  formError.value = '';
  showForm.value = true;
}

function updateName(field, value) {
  form[field] = value;
  if (!slugEdited.value && ['name', 'title'].includes(field)) form.slug = makeSlug(value);
}

function makeSlug(value) {
  return value
    .toLocaleLowerCase('pt-BR')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/(^-|-$)/g, '');
}

async function saveRecord() {
  saving.value = true;
  formError.value = '';
  fieldErrors.value = {};

  const payload = { ...form };
  if (activeTab.value === 'series') {
    payload.author_ids = form.author_ids.map(Number);
    payload.category_ids = form.category_ids.map(Number);
    payload.status = form.status || 'ongoing';
  }

  try {
    const path = `${tabInfo.value.path}${editingId.value ? `/${editingId.value}` : ''}`;
    const response = await apiRequest(path, {
      method: editingId.value ? 'PATCH' : 'POST',
      body: payload,
    });
    const records = activeTab.value === 'authors' ? authors : activeTab.value === 'categories' ? categories : series;

    if (editingId.value) {
      records.value = records.value.map((record) => record.id === response.data.id ? response.data : record);
    } else {
      records.value = [...records.value, response.data].sort((first, second) => {
        const firstName = first.title || first.name;
        const secondName = second.title || second.name;
        return firstName.localeCompare(secondName, 'pt-BR');
      });
    }

    showForm.value = false;
  } catch (error) {
    formError.value = error.status === 422 ? '' : error.message;
    fieldErrors.value = error.errors || {};
  } finally {
    saving.value = false;
  }
}

async function deleteRecord(record) {
  const name = record.title || record.name;
  if (!window.confirm(`Excluir ${tabInfo.value.singular} “${name}”?`)) return;

  try {
    await apiRequest(`${tabInfo.value.path}/${record.id}`, { method: 'DELETE' });
    const records = activeTab.value === 'authors' ? authors : activeTab.value === 'categories' ? categories : series;
    records.value = records.value.filter((item) => item.id !== record.id);
    pageError.value = '';
  } catch (error) {
    pageError.value = error.message;
  }
}

function recordTitle(record) {
  return record.title || record.name;
}
</script>

<template>
  <div class="site-shell">
    <AdminHeader />

    <main class="admin-records-background">
      <div class="container admin-records-page">
        <header class="admin-records-heading">
          <div>
            <span class="eyebrow">Painel administrativo</span>
            <h1>Séries, autores e categorias</h1>
            <p>Organize as informações usadas para montar o catálogo.</p>
          </div>
          <button class="admin-primary-button" :disabled="!isAdmin" @click="openCreate">Novo {{ tabInfo.singular }}</button>
        </header>

        <section v-if="loading && !isAdmin" class="state-message" role="status">Verificando acesso…</section>
        <section v-else-if="!isAdmin" class="checkout-message">
          <h2>Acesso restrito</h2>
          <p>Entre com uma conta administrativa para gerenciar os cadastros.</p>
          <RouterLink class="retry-button" :to="{ name: 'auth', query: { redirect: '/admin/cadastros' } }">Entrar como administrador</RouterLink>
        </section>
        <template v-else>
          <nav class="admin-record-tabs" aria-label="Tipo de cadastro">
            <button :class="{ active: activeTab === 'series' }" @click="activeTab = 'series'">Séries <span>{{ series.length }}</span></button>
            <button :class="{ active: activeTab === 'authors' }" @click="activeTab = 'authors'">Autores <span>{{ authors.length }}</span></button>
            <button :class="{ active: activeTab === 'categories' }" @click="activeTab = 'categories'">Categorias <span>{{ categories.length }}</span></button>
          </nav>

          <p v-if="pageError" class="checkout-error" role="alert">{{ pageError }}</p>
          <p v-if="loading" class="state-message" role="status">Carregando cadastros…</p>
          <section v-else-if="!activeRecords.length" class="empty-state admin-empty-state">
            <h2>Nenhum cadastro de {{ tabInfo.plural }} ainda</h2>
            <p>Crie o primeiro cadastro para organizar os mangás.</p>
            <button class="retry-button" @click="openCreate">Novo {{ tabInfo.singular }}</button>
          </section>
          <section v-else class="admin-record-list" :aria-label="tabInfo.plural">
            <article v-for="record in activeRecords" :key="record.id" class="admin-record-row">
              <div class="admin-record-icon">{{ activeTab === 'series' ? '冊' : activeTab === 'authors' ? '✎' : '◈' }}</div>
              <div class="admin-record-info">
                <span>{{ activeTab === 'series' ? (seriesStatuses[record.status] || record.status) : activeTab === 'categories' ? record.slug : `${record.series_count || 0} série(s)` }}</span>
                <h2>{{ recordTitle(record) }}</h2>
                <p v-if="activeTab === 'series'">
                  {{ record.authors.map((author) => author.name).join(', ') || 'Sem autores associados' }}
                  <span>·</span>
                  {{ record.categories.map((category) => category.name).join(', ') || 'Sem categorias' }}
                </p>
                <p v-else-if="activeTab === 'authors'">{{ record.biography || 'Sem biografia cadastrada' }}</p>
                <p v-else>{{ record.slug }}</p>
              </div>
              <span v-if="activeTab === 'series'" class="admin-record-count">{{ record.mangas_count || 0 }} volume(s)</span>
              <div class="admin-record-actions">
                <button @click="openEdit(record)">Editar</button>
                <button class="deactivate-action" @click="deleteRecord(record)">Excluir</button>
              </div>
            </article>
          </section>
        </template>
      </div>
    </main>

    <div v-if="showForm" class="admin-modal-overlay" @click.self="showForm = false">
      <section class="admin-record-modal" role="dialog" aria-modal="true" aria-labelledby="record-form-title">
        <header class="admin-modal-header">
          <div>
            <span class="eyebrow">{{ editingId ? 'Editar cadastro' : 'Novo cadastro' }}</span>
            <h2 id="record-form-title">{{ editingId ? 'Editar' : 'Novo' }} {{ tabInfo.singular }}</h2>
          </div>
          <button class="admin-modal-close" aria-label="Fechar formulário" @click="showForm = false">×</button>
        </header>

        <form class="admin-record-form" @submit.prevent="saveRecord">
          <template v-if="activeTab === 'authors'">
            <label>Nome<input :value="form.name" maxlength="120" required @input="updateName('name', $event.target.value)" /><small class="field-error" v-if="fieldErrors.name">{{ fieldErrors.name[0] }}</small></label>
            <label>Biografia (opcional)<textarea v-model="form.biography" rows="4"></textarea><small class="field-error" v-if="fieldErrors.biography">{{ fieldErrors.biography[0] }}</small></label>
          </template>

          <template v-else-if="activeTab === 'categories'">
            <label>Nome<input :value="form.name" maxlength="60" required @input="updateName('name', $event.target.value)" /><small class="field-error" v-if="fieldErrors.name">{{ fieldErrors.name[0] }}</small></label>
            <label>Slug<input v-model="form.slug" maxlength="80" required @input="slugEdited = true" /><small>Usado na URL e nos filtros do catálogo.</small><small class="field-error" v-if="fieldErrors.slug">{{ fieldErrors.slug[0] }}</small></label>
          </template>

          <template v-else>
            <label>Título da série<input :value="form.title" maxlength="180" required @input="updateName('title', $event.target.value)" /><small class="field-error" v-if="fieldErrors.title">{{ fieldErrors.title[0] }}</small></label>
            <label>Slug<input v-model="form.slug" maxlength="200" required @input="slugEdited = true" /><small class="field-error" v-if="fieldErrors.slug">{{ fieldErrors.slug[0] }}</small></label>
            <label>Status<select v-model="form.status"><option value="ongoing">Em andamento</option><option value="completed">Concluída</option><option value="hiatus">Hiato</option></select><small class="field-error" v-if="fieldErrors.status">{{ fieldErrors.status[0] }}</small></label>
            <label>Descrição (opcional)<textarea v-model="form.description" rows="3"></textarea><small class="field-error" v-if="fieldErrors.description">{{ fieldErrors.description[0] }}</small></label>
            <fieldset class="admin-record-checkboxes">
              <legend>Autores</legend>
              <label v-for="author in authors" :key="author.id"><input v-model="form.author_ids" type="checkbox" :value="author.id" /> {{ author.name }}</label>
              <p v-if="!authors.length">Cadastre autores antes de associá-los à série.</p>
            </fieldset>
            <fieldset class="admin-record-checkboxes">
              <legend>Categorias</legend>
              <label v-for="category in categories" :key="category.id"><input v-model="form.category_ids" type="checkbox" :value="category.id" /> {{ category.name }}</label>
              <p v-if="!categories.length">Cadastre categorias para organizá-la no catálogo.</p>
            </fieldset>
          </template>

          <p v-if="formError" class="checkout-error" role="alert">{{ formError }}</p>
          <div class="admin-form-actions">
            <button class="admin-secondary-button" type="button" @click="showForm = false">Cancelar</button>
            <button class="admin-primary-button" type="submit" :disabled="saving">{{ saving ? 'Salvando…' : 'Salvar' }}</button>
          </div>
        </form>
      </section>
    </div>

    <footer class="footer cart-footer">
      <div class="container footer-bottom">
        <span>© {{ new Date().getFullYear() }} Mangáverse</span>
        <span>Painel administrativo</span>
      </div>
    </footer>
  </div>
</template>
