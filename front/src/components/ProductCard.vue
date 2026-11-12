<script setup>
import { RouterLink } from 'vue-router';

defineProps({
  manga: { type: Object, required: true },
  adding: { type: Boolean, default: false },
});

defineEmits(['add-to-cart']);

function formatPrice(price) {
  return new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
  }).format(Number(price));
}
</script>

<template>
  <article class="product-card">
    <div class="cover-area">
      <RouterLink class="cover-link" :to="`/mangas/${manga.id}`" :aria-label="`Ver detalhes de ${manga.title}`">
        <img v-if="manga.cover_image_url" class="book-cover-image" :src="manga.cover_image_url" :alt="`Capa de ${manga.title}`" />
        <div v-else class="book-cover" :style="{ '--cover-hue': (manga.id * 47) % 360 }">
          <span class="cover-series">{{ manga.series.title }}</span>
          <strong class="cover-name">{{ manga.title }}</strong>
          <span class="cover-vol">Volume {{ manga.volume_number }}</span>
          <span class="cover-mark">M</span>
        </div>
      </RouterLink>
      <span v-if="!manga.in_stock" class="stock-label unavailable">
        Indisponível
      </span>
    </div>

    <div class="product-content">
      <h3>
        <RouterLink class="product-title-link" :to="`/mangas/${manga.id}`">
          {{ manga.title }} <span>· Vol. {{ manga.volume_number }}</span>
        </RouterLink>
      </h3>
      <p class="product-author">{{ manga.series.authors.join(', ') || 'Mangá' }}</p>
      <strong class="price">{{ formatPrice(manga.price) }}</strong>
      <button
        class="buy-button"
        :disabled="!manga.in_stock || adding"
        @click="$emit('add-to-cart', manga)"
      >
        {{ adding ? 'Adicionando…' : manga.in_stock ? 'Adicionar ao carrinho' : 'Sem estoque' }}
      </button>
    </div>
  </article>
</template>
