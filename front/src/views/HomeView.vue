<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import ProductCard from '../components/ProductCard.vue';
import StoreHeader from '../components/StoreHeader.vue';
import { apiRequest } from '../services/api';
import { useCart } from '../services/cart';

const mangas = ref([]);
const categories = ref([]);
const searchInput = ref('');
const search = ref('');
const selectedCategory = ref('');
const sort = ref('newest');
const page = ref(1);
const pagination = ref({ current_page: 1, last_page: 1, total: 0 });
const loading = ref(true);
const errorMessage = ref('');
const notice = ref('');
const addingMangaId = ref(null);
const releaseTrack = ref(null);
const hasResults = computed(() => mangas.value.length > 0);
const newReleases = computed(() => mangas.value.slice(0, 10));
const { cartCount, refreshCart, addItem } = useCart();

let searchTimer;
let catalogRequestId = 0;
let noticeTimer;

watch(searchInput, (value) => {
  window.clearTimeout(searchTimer);
  searchTimer = window.setTimeout(() => {
    search.value = value.trim();
  }, 300);
});

watch([search, selectedCategory, sort], () => {
  if (page.value !== 1) {
    page.value = 1;
  } else {
    loadCatalog();
  }
});
watch(page, loadCatalog);

onMounted(async () => {
  await Promise.all([loadCategories(), loadCatalog(), refreshCart().catch(() => {})]);
});

async function loadCatalog() {
  const requestId = ++catalogRequestId;
  loading.value = true;
  errorMessage.value = '';

  const params = new URLSearchParams({ per_page: '12', sort: sort.value, page: String(page.value) });

  if (search.value) params.set('search', search.value);
  if (selectedCategory.value) params.set('category', selectedCategory.value);

  try {
    const response = await apiRequest(`/api/mangas?${params}`);

    if (requestId === catalogRequestId) {
      mangas.value = response.data;
      pagination.value = response.meta;
    }
  } catch (error) {
    if (requestId === catalogRequestId) {
      errorMessage.value = error.message;
    }
  } finally {
    if (requestId === catalogRequestId) {
      loading.value = false;
    }
  }
}

async function loadCategories() {
  try {
    const response = await apiRequest('/api/categories');
    categories.value = response.data;
  } catch (error) {
    showNotice(`Não foi possível carregar as categorias: ${error.message}`);
  }
}

async function addToCart(manga) {
  addingMangaId.value = manga.id;

  try {
    await addItem(manga.id);
    showNotice(`${manga.title} foi adicionado ao carrinho.`);
  } catch (error) {
    showNotice(error.message);
  } finally {
    addingMangaId.value = null;
  }
}

function showNotice(message) {
  notice.value = message;
  window.clearTimeout(noticeTimer);
  noticeTimer = window.setTimeout(() => {
    notice.value = '';
  }, 3000);
}

function scrollReleases(direction) {
  releaseTrack.value?.scrollBy({ left: direction * 520, behavior: 'smooth' });
}
</script>

<template>
  <div class="site-shell" id="inicio">
    <StoreHeader v-model:search="searchInput" :cart-count="cartCount" />

    <main>
      <section class="hero">
        <div class="container hero-inner">
          <div class="hero-copy">
            <span class="hero-badge">Seu próximo universo começa aqui</span>
            <h1>Histórias que ficam com você.</h1>
            <p>Explore mangás, encontre novas séries e complete sua coleção.</p>
            <a href="#catalogo" class="hero-link">
              Explorar catálogo
              <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 12h15m-6-6 6 6-6 6" /></svg>
            </a>
          </div>
          <div class="hero-art" aria-hidden="true">
            <div class="art-orbit orbit-one"></div>
            <div class="art-orbit orbit-two"></div>
            <div class="art-book book-back"></div>
            <div class="art-book book-front"><span>M</span></div>
            <span class="art-star star-one">✦</span>
            <span class="art-star star-two">✦</span>
          </div>
        </div>
      </section>

      <section v-if="!search && !selectedCategory && sort === 'newest' && page === 1 && newReleases.length" class="catalog-section releases-section" id="novidades">
        <div class="container">
          <div class="section-heading">
            <div>
              <span>Acabaram de chegar</span>
              <h2>Novidades Mangáverse</h2>
            </div>
            <div class="release-actions">
              <a href="#catalogo" class="section-link">Ver catálogo</a>
              <button aria-label="Ver mangás anteriores" @click="scrollReleases(-1)">‹</button>
              <button aria-label="Ver próximos mangás" @click="scrollReleases(1)">›</button>
            </div>
          </div>
          <div ref="releaseTrack" class="product-carousel">
            <ProductCard
              v-for="manga in newReleases"
              :key="manga.id"
              :manga="manga"
              :adding="addingMangaId === manga.id"
              @add-to-cart="addToCart"
            />
          </div>
        </div>
      </section>

      <section class="catalog-section" id="catalogo">
        <div class="container">
          <div class="section-heading">
            <div>
              <span>Encontre sua próxima leitura</span>
              <h2>{{ search ? `Resultados para “${search}”` : 'Explore o catálogo' }}</h2>
            </div>
          </div>

          <div class="category-pills" id="categorias" aria-label="Filtrar por categoria">
            <button :class="{ active: !selectedCategory }" @click="selectedCategory = ''">Todas</button>
            <button
              v-for="category in categories"
              :key="category.id"
              :class="{ active: selectedCategory === category.slug }"
              @click="selectedCategory = category.slug"
            >
              {{ category.name }}
            </button>
          </div>

          <div class="catalog-controls">
            <p>{{ pagination.total }} resultado(s)</p>
            <label>Ordenar por
              <select v-model="sort">
                <option value="newest">Mais recentes</option>
                <option value="price_asc">Menor preço</option>
                <option value="price_desc">Maior preço</option>
              </select>
            </label>
          </div>

          <p v-if="loading" class="state-message" role="status">Carregando catálogo…</p>
          <div v-else-if="errorMessage" class="empty-state" role="alert">
            <h3>Não foi possível carregar o catálogo</h3>
            <p>{{ errorMessage }}</p>
            <button class="retry-button" @click="loadCatalog">Tentar novamente</button>
          </div>
          <div v-else-if="hasResults" class="product-grid">
            <ProductCard
              v-for="manga in mangas"
              :key="manga.id"
              :manga="manga"
              :adding="addingMangaId === manga.id"
              @add-to-cart="addToCart"
            />
          </div>
          <div v-else class="empty-state">
            <h3>Nenhum mangá encontrado</h3>
            <p>Tente outro termo ou escolha uma categoria diferente.</p>
            <button class="retry-button" @click="searchInput = ''; selectedCategory = ''">Limpar filtros</button>
          </div>

          <nav v-if="pagination.last_page > 1" class="catalog-pagination" aria-label="Paginação do catálogo">
            <button :disabled="page <= 1 || loading" @click="page--">Anterior</button>
            <span>Página {{ pagination.current_page }} de {{ pagination.last_page }}</span>
            <button :disabled="page >= pagination.last_page || loading" @click="page++">Próxima</button>
          </nav>
        </div>
      </section>
    </main>

    <footer class="footer" id="sobre">
      <div class="container footer-main">
        <div>
          <a class="logo footer-logo" href="#inicio">Mangáverse</a>
          <p>Um lugar para descobrir histórias e encontrar seu próximo mangá favorito.</p>
        </div>
        <div>
          <h3>Navegação</h3>
          <a href="#catalogo">Catálogo</a>
          <a href="#categorias">Categorias</a>
        </div>
        <div>
          <h3>Sobre</h3>
          <p>Projeto acadêmico de e-commerce de mangás.</p>
          <p>Feito com Vue e Laravel.</p>
        </div>
      </div>
      <div class="container footer-bottom">
        <span>© {{ new Date().getFullYear() }} Mangáverse</span>
        <span>Projeto acadêmico</span>
      </div>
    </footer>

    <div v-if="notice" class="toast" role="status">{{ notice }}</div>
  </div>
</template>
