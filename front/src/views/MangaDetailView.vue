<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import StoreHeader from '../components/StoreHeader.vue';
import { apiRequest } from '../services/api';
import { useCart } from '../services/cart';

const route = useRoute();
const manga = ref(null);
const loading = ref(true);
const errorMessage = ref('');
const adding = ref(false);
const cartReady = ref(false);
const notice = ref('');
const quantity = ref(1);
const { cartCount, items: cartItems, refreshCart, addItem } = useCart();
const availableToAdd = computed(() => {
  if (!manga.value) return 0;

  const cartItem = cartItems.value.find((item) => item.manga.id === manga.value.id);
  return Math.max(0, manga.value.available_quantity - (cartItem?.quantity || 0));
});

watch(availableToAdd, (available) => {
  if (available > 0 && quantity.value > available) quantity.value = available;
});

onMounted(async () => {
  await Promise.all([
    loadManga(),
    refreshCart().catch(() => {}).finally(() => { cartReady.value = true; }),
  ]);
});

async function loadManga() {
  try {
    const response = await apiRequest(`/api/mangas/${route.params.id}`);
    manga.value = response.data;
  } catch (error) {
    errorMessage.value = error.message;
  } finally {
    loading.value = false;
  }
}

async function addToCart() {
  adding.value = true;
  notice.value = '';

  try {
    const amount = quantity.value;
    await addItem(manga.value.id, quantity.value);
    notice.value = `${amount} ${amount === 1 ? 'unidade' : 'unidades'} de ${manga.value.title} adicionada${amount === 1 ? '' : 's'} ao carrinho.`;
  } catch (error) {
    notice.value = error.message;
  } finally {
    adding.value = false;
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
    <StoreHeader :cart-count="cartCount" :show-search="false" />

    <main class="container detail-page">
      <nav class="breadcrumbs" aria-label="Você está em">
        <RouterLink to="/">Início</RouterLink>
        <span>/</span>
        <RouterLink to="/#catalogo">Catálogo</RouterLink>
        <span v-if="manga">/</span>
        <span v-if="manga">{{ manga.title }}</span>
      </nav>

      <p v-if="loading" class="state-message" role="status">Carregando mangá…</p>
      <div v-else-if="errorMessage" class="empty-state" role="alert">
        <h2>Não foi possível carregar este mangá</h2>
        <p>{{ errorMessage }}</p>
        <RouterLink class="retry-button" to="/">Voltar ao catálogo</RouterLink>
      </div>
      <section v-else-if="manga" class="manga-detail">
        <div class="detail-cover-area">
          <img v-if="manga.cover_image_url" class="detail-cover-image" :src="manga.cover_image_url" :alt="`Capa de ${manga.title}`" />
          <div v-else class="detail-cover-placeholder" :style="{ '--cover-hue': (manga.id * 47) % 360 }">
            <span>{{ manga.series.title }}</span>
            <strong>{{ manga.title }}</strong>
            <small>Volume {{ manga.volume_number }}</small>
          </div>
        </div>

        <div class="detail-info">
          <span class="eyebrow">{{ manga.series.title }} · Volume {{ manga.volume_number }}</span>
          <h1>{{ manga.title }}</h1>
          <p class="detail-authors">{{ manga.series.authors.join(', ') || 'Autor não informado' }}</p>

          <div class="detail-price">{{ formatPrice(manga.price) }}</div>
          <p class="detail-stock" :class="{ unavailable: !manga.in_stock }">
            {{ manga.in_stock ? 'Disponível em estoque' : 'Indisponível no momento' }}
          </p>
          <div v-if="manga.in_stock && cartReady && availableToAdd > 0" class="detail-quantity">
            <span>Quantidade</span>
            <div class="detail-quantity-control" :aria-label="`Quantidade, pode adicionar até ${availableToAdd} unidades`">
              <button type="button" aria-label="Diminuir quantidade" :disabled="quantity <= 1 || adding" @click="quantity--">−</button>
              <span aria-live="polite">{{ quantity }}</span>
              <button type="button" aria-label="Aumentar quantidade" :disabled="quantity >= availableToAdd || adding" @click="quantity++">+</button>
            </div>
            <small v-if="availableToAdd < manga.available_quantity">
              Você já tem {{ manga.available_quantity - availableToAdd }} no carrinho; pode adicionar mais {{ availableToAdd }}.
            </small>
          </div>
          <p v-else-if="manga.in_stock && cartReady" class="detail-stock unavailable">Você já tem todo o estoque disponível no carrinho.</p>
          <p v-else-if="manga.in_stock" class="detail-stock">Conferindo seu carrinho…</p>
          <button class="buy-button detail-buy-button" :disabled="!manga.in_stock || !cartReady || availableToAdd === 0 || quantity > availableToAdd || adding" @click="addToCart">
            {{ adding ? 'Adicionando…' : 'Adicionar ao carrinho' }}
          </button>
          <p v-if="notice" class="detail-notice" role="status">{{ notice }}</p>

          <section v-if="manga.description" class="detail-description">
            <h2>Sobre este volume</h2>
            <p>{{ manga.description }}</p>
          </section>

          <dl class="detail-metadata">
            <div v-if="manga.series.publisher">
              <dt>Editora</dt>
              <dd>{{ manga.series.publisher }}</dd>
            </div>
            <div v-if="manga.isbn">
              <dt>ISBN</dt>
              <dd>{{ manga.isbn }}</dd>
            </div>
            <div v-if="manga.series.categories.length">
              <dt>Categorias</dt>
              <dd>{{ manga.series.categories.map((category) => category.name).join(', ') }}</dd>
            </div>
          </dl>
        </div>
      </section>
    </main>

    <footer class="footer cart-footer">
      <div class="container footer-bottom">
        <span>© {{ new Date().getFullYear() }} Mangáverse</span>
        <RouterLink to="/">Voltar ao catálogo</RouterLink>
      </div>
    </footer>
  </div>
</template>
