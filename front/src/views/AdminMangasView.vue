<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { RouterLink } from 'vue-router';
import AdminHeader from '../components/AdminHeader.vue';
import { apiRequest } from '../services/api';
import { useAuth } from '../services/auth';

const { user, loadCurrentUser } = useAuth();
const mangas = ref([]);
const series = ref([]);
const search = ref('');
const activeFilter = ref('all');
const loading = ref(true);
const saving = ref(false);
const errorMessage = ref('');
const fieldErrors = ref({});
const showForm = ref(false);
const editingMangaId = ref(null);

const emptyForm = () => ({
  series_id: '',
  volume_number: 1,
  title: '',
  isbn: '',
  cover_image: null,
  description: '',
  price: '',
  stock: 0,
  published_at: '',
  is_active: true,
});

const form = reactive(emptyForm());
const isAdmin = computed(() => user.value?.role === 'admin');
const visibleMangas = computed(() => {
  const query = search.value.trim().toLocaleLowerCase('pt-BR');

  return mangas.value.filter((manga) => {
    const matchesStatus = activeFilter.value === 'all'
      || (activeFilter.value === 'active' && manga.is_active)
      || (activeFilter.value === 'inactive' && !manga.is_active);
    const matchesSearch = !query
      || `${manga.title} ${manga.series.title} ${manga.series.authors.join(' ')}`
        .toLocaleLowerCase('pt-BR')
        .includes(query);

    return matchesStatus && matchesSearch;
  });
});
const activeCount = computed(() => mangas.value.filter((manga) => manga.is_active).length);
const inactiveCount = computed(() => mangas.value.length - activeCount.value);

onMounted(initialize);

async function initialize() {
  await loadCurrentUser();
  loading.value = false;

  if (isAdmin.value) {
    await Promise.all([loadMangas(), loadSeries()]);
  }
}

async function loadMangas() {
  loading.value = true;
  errorMessage.value = '';

  try {
    const response = await apiRequest('/api/admin/mangas?per_page=50');
    mangas.value = response.data;
  } catch (error) {
    errorMessage.value = error.message;
  } finally {
    loading.value = false;
  }
}

async function loadSeries() {
  try {
    const response = await apiRequest('/api/series');
    series.value = response.data;
  } catch (error) {
    errorMessage.value = error.message;
  }
}

function openCreateForm() {
  Object.assign(form, emptyForm());
  editingMangaId.value = null;
  fieldErrors.value = {};
  errorMessage.value = '';
  showForm.value = true;
}

function openEditForm(manga) {
  Object.assign(form, {
    series_id: manga.series.id,
    volume_number: manga.volume_number,
    title: manga.title,
    isbn: manga.isbn || '',
    cover_image: null,
    description: manga.description || '',
    price: manga.price,
    stock: manga.stock ?? 0,
    published_at: manga.published_at || '',
    is_active: manga.is_active,
  });
  editingMangaId.value = manga.id;
  fieldErrors.value = {};
  errorMessage.value = '';
  showForm.value = true;
}

function closeForm() {
  showForm.value = false;
  fieldErrors.value = {};
}

async function saveManga() {
  saving.value = true;
  errorMessage.value = '';
  fieldErrors.value = {};

  const payload = {
    series_id: Number(form.series_id),
    volume_number: Number(form.volume_number),
    title: form.title || null,
    isbn: form.isbn || null,
    description: form.description || null,
    price: Number(form.price),
    stock: Number(form.stock),
    published_at: form.published_at || null,
    is_active: form.is_active,
  };

  let body = payload;
  let method = editingMangaId.value ? 'PATCH' : 'POST';

  if (form.cover_image) {
    body = new FormData();
    Object.entries(payload).forEach(([key, value]) => {
      if (value !== null) body.append(key, typeof value === 'boolean' ? (value ? '1' : '0') : String(value));
    });
    body.append('cover_image', form.cover_image);

    if (editingMangaId.value) {
      body.append('_method', 'PATCH');
      method = 'POST';
    }
  }

  try {
    const response = await apiRequest(
      editingMangaId.value ? `/api/admin/mangas/${editingMangaId.value}` : '/api/admin/mangas',
      { method, body },
    );

    if (editingMangaId.value) {
      mangas.value = mangas.value.map((manga) => manga.id === response.data.id ? response.data : manga);
    } else {
      mangas.value = [response.data, ...mangas.value];
    }

    closeForm();
  } catch (error) {
    errorMessage.value = error.status === 422 ? '' : error.message;
    fieldErrors.value = error.errors || {};
  } finally {
    saving.value = false;
  }
}

async function deactivateManga(manga) {
  if (!window.confirm(`Desativar “${manga.title}”? Ele deixará de aparecer no catálogo.`)) return;

  try {
    await apiRequest(`/api/admin/mangas/${manga.id}`, { method: 'DELETE' });
    mangas.value = mangas.value.map((item) => item.id === manga.id ? { ...item, is_active: false } : item);
  } catch (error) {
    errorMessage.value = error.message;
  }
}

function formatPrice(price) {
  return new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
  }).format(Number(price));
}
</script>

<template>
  <div class="site-shell">
    <AdminHeader />

    <main class="admin-catalog-background">
      <div class="container admin-catalog-page">
        <div class="admin-catalog-heading">
          <div>
            <span class="eyebrow">Painel administrativo</span>
            <h1>Catálogo de mangás</h1>
            <p>Cadastre volumes e mantenha estoque e preços atualizados.</p>
          </div>
          <div class="admin-catalog-heading-actions">
            <RouterLink class="admin-secondary-link" to="/admin/pedidos">Ver pedidos</RouterLink>
            <button class="admin-primary-button" :disabled="!isAdmin || !series.length" @click="openCreateForm">Novo mangá</button>
          </div>
        </div>

        <section v-if="loading && !isAdmin" class="state-message" role="status">Verificando acesso…</section>
        <section v-else-if="!isAdmin" class="checkout-message">
          <h2>Acesso restrito</h2>
          <p>Entre com uma conta administrativa para gerenciar o catálogo.</p>
          <RouterLink class="retry-button" :to="{ name: 'auth', query: { redirect: '/admin/mangas' } }">Entrar como administrador</RouterLink>
        </section>
        <template v-else>
          <div class="admin-catalog-stats">
            <article><span>Volumes cadastrados</span><strong>{{ mangas.length }}</strong></article>
            <article><span>Visíveis no catálogo</span><strong>{{ activeCount }}</strong></article>
            <article><span>Desativados</span><strong>{{ inactiveCount }}</strong></article>
          </div>

          <div class="admin-catalog-toolbar">
            <label class="admin-search">
              <span class="sr-only">Buscar no catálogo administrativo</span>
              <input v-model="search" type="search" placeholder="Buscar por mangá, série ou autor" />
            </label>
            <div class="admin-catalog-filters" aria-label="Filtrar volumes">
              <button :class="{ active: activeFilter === 'all' }" @click="activeFilter = 'all'">Todos</button>
              <button :class="{ active: activeFilter === 'active' }" @click="activeFilter = 'active'">Ativos</button>
              <button :class="{ active: activeFilter === 'inactive' }" @click="activeFilter = 'inactive'">Desativados</button>
            </div>
          </div>

          <p v-if="errorMessage && !showForm" class="checkout-error" role="alert">{{ errorMessage }}</p>
          <p v-if="loading" class="state-message" role="status">Carregando catálogo…</p>
          <section v-else-if="!visibleMangas.length" class="empty-state admin-empty-state">
            <h2>Nenhum mangá encontrado</h2>
            <p>Altere a busca ou o filtro, ou cadastre um novo volume.</p>
          </section>
          <section v-else class="admin-manga-list" aria-label="Volumes do catálogo">
            <article v-for="manga in visibleMangas" :key="manga.id" class="admin-manga-row">
              <div class="admin-manga-cover" :style="{ '--cover-hue': (manga.id * 47) % 360 }">
                <img v-if="manga.cover_image_url" :src="manga.cover_image_url" :alt="`Capa de ${manga.title}`" />
                <span v-else>{{ manga.title }}</span>
              </div>
              <div class="admin-manga-info">
                <span>{{ manga.series.title }} · Vol. {{ manga.volume_number }}</span>
                <h2>{{ manga.title }}</h2>
                <p>{{ manga.series.authors.join(', ') || 'Autor não informado' }}</p>
              </div>
              <div class="admin-manga-stock">
                <span>Preço</span><strong>{{ formatPrice(manga.price) }}</strong>
                <small>{{ manga.stock }} em estoque</small>
              </div>
              <span class="manga-active-badge" :class="{ inactive: !manga.is_active }">
                {{ manga.is_active ? 'Ativo' : 'Desativado' }}
              </span>
              <div class="admin-manga-actions">
                <button @click="openEditForm(manga)">Editar</button>
                <button v-if="manga.is_active" class="deactivate-action" @click="deactivateManga(manga)">Desativar</button>
                <button v-else class="activate-action" @click="openEditForm(manga)">Reativar</button>
              </div>
            </article>
          </section>
        </template>
      </div>
    </main>

    <div v-if="showForm" class="admin-modal-overlay" @click.self="closeForm">
      <section class="admin-manga-form-modal" role="dialog" aria-modal="true" aria-labelledby="manga-form-title">
        <header class="admin-modal-header">
          <div>
            <span class="eyebrow">{{ editingMangaId ? 'Editar catálogo' : 'Novo cadastro' }}</span>
            <h2 id="manga-form-title">{{ editingMangaId ? 'Editar mangá' : 'Cadastrar mangá' }}</h2>
          </div>
          <button class="admin-modal-close" aria-label="Fechar formulário" @click="closeForm">×</button>
        </header>

        <form class="admin-manga-form" @submit.prevent="saveManga">
          <label>Série<select v-model="form.series_id" required><option value="" disabled>Selecione uma série</option><option v-for="item in series" :key="item.id" :value="String(item.id)">{{ item.title }}</option></select><small class="field-error" v-if="fieldErrors.series_id">{{ fieldErrors.series_id[0] }}</small></label>
          <div class="admin-form-row">
            <label>Volume<input v-model.number="form.volume_number" type="number" min="1" required /><small class="field-error" v-if="fieldErrors.volume_number">{{ fieldErrors.volume_number[0] }}</small></label>
            <label>Título do volume<input v-model="form.title" maxlength="180" placeholder="Opcional; usa o nome da série" /><small class="field-error" v-if="fieldErrors.title">{{ fieldErrors.title[0] }}</small></label>
          </div>
          <div class="admin-form-row">
            <label>Preço (R$)<input v-model="form.price" type="number" min="0.01" step="0.01" required /><small class="field-error" v-if="fieldErrors.price">{{ fieldErrors.price[0] }}</small></label>
            <label>Estoque<input v-model.number="form.stock" type="number" min="0" required /><small class="field-error" v-if="fieldErrors.stock">{{ fieldErrors.stock[0] }}</small></label>
          </div>
          <div class="admin-form-row">
            <label>ISBN da edição (opcional)<input v-model="form.isbn" maxlength="20" placeholder="Código identificador impresso no livro" /><small>Código internacional que identifica esta edição; pode deixar em branco.</small><small class="field-error" v-if="fieldErrors.isbn">{{ fieldErrors.isbn[0] }}</small></label>
            <label>Data de publicação<input v-model="form.published_at" type="date" /><small class="field-error" v-if="fieldErrors.published_at">{{ fieldErrors.published_at[0] }}</small></label>
          </div>
          <label>Imagem da capa (opcional)<input type="file" accept="image/jpeg,image/png,image/webp" @change="form.cover_image = $event.target.files[0] || null" /><small>JPG, PNG ou WebP; até 2 MB. Ao editar, selecione uma nova imagem para substituir a atual.</small><small class="field-error" v-if="fieldErrors.cover_image">{{ fieldErrors.cover_image[0] }}</small></label>
          <label>Descrição<textarea v-model="form.description" rows="4"></textarea><small class="field-error" v-if="fieldErrors.description">{{ fieldErrors.description[0] }}</small></label>
          <label class="admin-active-checkbox"><input v-model="form.is_active" type="checkbox" /> Visível no catálogo</label>
          <p v-if="errorMessage" class="checkout-error" role="alert">{{ errorMessage }}</p>
          <div class="admin-form-actions">
            <button class="admin-secondary-button" type="button" @click="closeForm">Cancelar</button>
            <button class="admin-primary-button" type="submit" :disabled="saving || !series.length">
              {{ saving ? 'Salvando…' : editingMangaId ? 'Salvar alterações' : 'Cadastrar mangá' }}
            </button>
          </div>
        </form>
      </section>
    </div>

    <footer class="footer cart-footer">
      <div class="container footer-bottom">
        <span>© {{ new Date().getFullYear() }} Mangáverse</span>
        <RouterLink to="/">Voltar à loja</RouterLink>
      </div>
    </footer>
  </div>
</template>
