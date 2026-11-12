<script setup>
import { onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import StoreHeader from '../components/StoreHeader.vue';
import { useCart } from '../services/cart';

const { items, subtotal, cartCount, refreshCart, updateItem, removeItem, clearCart } = useCart();
const loading = ref(true);
const busyItemId = ref(null);
const errorMessage = ref('');

onMounted(async () => {
  try {
    await refreshCart();
  } catch (error) {
    errorMessage.value = error.message;
  } finally {
    loading.value = false;
  }
});

async function changeQuantity(item, quantity) {
  busyItemId.value = item.id;
  errorMessage.value = '';

  try {
    await updateItem(item.id, quantity);
  } catch (error) {
    errorMessage.value = error.message;
  } finally {
    busyItemId.value = null;
  }
}

async function deleteItem(item) {
  busyItemId.value = item.id;
  errorMessage.value = '';

  try {
    await removeItem(item.id);
  } catch (error) {
    errorMessage.value = error.message;
  } finally {
    busyItemId.value = null;
  }
}

async function emptyCart() {
  errorMessage.value = '';

  try {
    await clearCart();
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
    <StoreHeader :cart-count="cartCount" :show-search="false" />

    <main class="cart-page container">
      <div class="cart-heading">
        <div>
          <span class="eyebrow">Sua seleção</span>
          <h1>Meu carrinho</h1>
        </div>
        <RouterLink class="continue-link" to="/">Continuar comprando</RouterLink>
      </div>

      <p v-if="loading" class="state-message" role="status">Carregando carrinho…</p>
      <div v-else-if="errorMessage && !items.length" class="empty-state" role="alert">
        <h2>Não foi possível carregar o carrinho</h2>
        <p>{{ errorMessage }}</p>
        <RouterLink class="retry-button" to="/">Voltar ao catálogo</RouterLink>
      </div>
      <div v-else-if="!items.length" class="empty-state">
        <div class="empty-cart-icon" aria-hidden="true">▱</div>
        <h2>Seu carrinho está vazio</h2>
        <p>Explore o catálogo e adicione os mangás que deseja ler.</p>
        <RouterLink class="retry-button" to="/">Explorar catálogo</RouterLink>
      </div>
      <div v-else class="cart-layout">
        <section class="cart-items" aria-label="Itens do carrinho">
          <div class="cart-items-heading">
            <h2>Itens ({{ cartCount }})</h2>
            <button class="clear-cart-button" @click="emptyCart">Limpar carrinho</button>
          </div>

          <p v-if="errorMessage" class="cart-error" role="alert">{{ errorMessage }}</p>

          <article v-for="item in items" :key="item.id" class="cart-item">
            <div class="cart-cover" :style="{ '--cover-hue': (item.manga.id * 47) % 360 }" aria-hidden="true">
              <span>{{ item.manga.title }}</span>
              <small>Vol. {{ item.manga.volume_number }}</small>
            </div>
            <div class="cart-item-info">
              <span class="cart-series">{{ item.manga.series }}</span>
              <h3>{{ item.manga.title }} <span>· Vol. {{ item.manga.volume_number }}</span></h3>
              <p>{{ formatPrice(item.unit_price) }} por unidade</p>
              <span v-if="!item.manga.is_active || !item.manga.in_stock" class="cart-unavailable">
                Indisponível no momento
              </span>
            </div>
            <div class="cart-item-actions">
              <div class="quantity-control" :aria-label="`Quantidade de ${item.manga.title}`">
                <button
                  :disabled="busyItemId === item.id || item.quantity <= 1"
                  :aria-label="`Diminuir quantidade de ${item.manga.title}`"
                  @click="changeQuantity(item, item.quantity - 1)"
                >−</button>
                <span>{{ item.quantity }}</span>
                <button
                  :disabled="busyItemId === item.id || !item.manga.in_stock"
                  :aria-label="`Aumentar quantidade de ${item.manga.title}`"
                  @click="changeQuantity(item, item.quantity + 1)"
                >+</button>
              </div>
              <strong>{{ formatPrice(item.line_total) }}</strong>
              <button class="remove-item-button" :disabled="busyItemId === item.id" @click="deleteItem(item)">
                Remover
              </button>
            </div>
          </article>
        </section>

        <aside class="cart-summary">
          <h2>Resumo do pedido</h2>
          <div class="summary-row"><span>Itens</span><span>{{ cartCount }}</span></div>
          <div class="summary-row"><span>Subtotal</span><span>{{ formatPrice(subtotal) }}</span></div>
          <div class="summary-total"><span>Total</span><strong>{{ formatPrice(subtotal) }}</strong></div>
          <p>O frete é gratuito nesta versão acadêmica. O pagamento é simulado.</p>
          <RouterLink class="checkout-link" to="/finalizar">Continuar para entrega</RouterLink>
        </aside>
      </div>
    </main>

    <footer class="footer cart-footer">
      <div class="container footer-bottom">
        <span>© {{ new Date().getFullYear() }} Mangáverse</span>
        <RouterLink to="/">Voltar ao catálogo</RouterLink>
      </div>
    </footer>
  </div>
</template>
