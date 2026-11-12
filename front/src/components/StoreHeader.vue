<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuth } from '../services/auth';
import CartMergeNotice from './CartMergeNotice.vue';

const props = defineProps({
  search: { type: String, default: '' },
  cartCount: { type: Number, default: 0 },
  showSearch: { type: Boolean, default: true },
});

defineEmits(['update:search']);

const router = useRouter();
const route = useRoute();
const { user, loadCurrentUser, signOut } = useAuth();
const accountMenu = ref(null);
const accountMenuOpen = ref(false);
const cartBadge = computed(() => props.cartCount > 99 ? '99+' : props.cartCount);

onMounted(() => {
  loadCurrentUser();
  document.addEventListener('click', closeAccountMenuOutside);
});

onBeforeUnmount(() => document.removeEventListener('click', closeAccountMenuOutside));
watch(() => route.fullPath, () => { accountMenuOpen.value = false; });

function closeAccountMenuOutside(event) {
  if (!accountMenu.value?.contains(event.target)) accountMenuOpen.value = false;
}

async function logout() {
  try {
    accountMenuOpen.value = false;
    await signOut();
    await router.push('/');
  } catch {
    window.alert('Não foi possível encerrar a sessão. Tente novamente.');
  }
}
</script>

<template>
  <header class="header">
    <div class="utility-bar">
      <div class="container utility-content">
        <span>Um universo de histórias em cada volume</span>
        <span>Encontre seu próximo mangá</span>
      </div>
    </div>

    <div class="container header-main" :class="{ 'no-search': !showSearch }">
      <RouterLink class="logo" to="/" aria-label="Mangáverse — início">Mangáverse</RouterLink>

      <label v-if="showSearch" class="search-box">
        <span class="sr-only">Buscar mangás</span>
        <input
          :value="search"
          type="search"
          placeholder="Busque por título, série ou descrição"
          @input="$emit('update:search', $event.target.value)"
        />
        <svg viewBox="0 0 24 24" aria-hidden="true">
          <circle cx="10.8" cy="10.8" r="6.3" />
          <path d="m16 16 4.2 4.2" />
        </svg>
      </label>

      <div class="header-actions">
        <RouterLink v-if="!user" class="account-link" to="/entrar" aria-label="Entrar na conta">
          <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3.5" /><path d="M4.5 20c.7-3.2 3.2-5 7.5-5s6.8 1.8 7.5 5" /></svg>
          <span>Entrar</span>
        </RouterLink>
        <div v-else ref="accountMenu" class="account-menu">
          <button class="account-trigger" :aria-expanded="accountMenuOpen" @click="accountMenuOpen = !accountMenuOpen">
            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3.5" /><path d="M4.5 20c.7-3.2 3.2-5 7.5-5s6.8 1.8 7.5 5" /></svg>
            <span>{{ user.name }}</span>
            <span class="account-chevron" aria-hidden="true">⌄</span>
          </button>
          <div v-if="accountMenuOpen" class="account-dropdown">
            <RouterLink v-if="user.role === 'admin'" to="/admin">Painel administrativo</RouterLink>
            <RouterLink to="/perfil">Meu perfil</RouterLink>
            <RouterLink to="/pedidos">Meus pedidos</RouterLink>
            <button @click="logout">Sair</button>
          </div>
        </div>
        <RouterLink class="cart-link" to="/carrinho" :aria-label="`Carrinho com ${cartCount} itens`">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M3 4h2l2.1 11.1a2 2 0 0 0 2 1.6h8.5a2 2 0 0 0 1.9-1.4L21 8H6" />
            <circle cx="9.5" cy="20" r="1" />
            <circle cx="18" cy="20" r="1" />
          </svg>
          <span class="cart-badge">{{ cartBadge }}</span>
        </RouterLink>
      </div>
    </div>

    <nav class="category-nav" aria-label="Navegação da loja">
      <div class="container nav-content">
        <RouterLink to="/#novidades" :class="{ 'nav-active': route.path === '/' && route.hash === '#novidades' }">Novidades</RouterLink>
        <RouterLink to="/#catalogo" :class="{ 'nav-active': route.path === '/' && route.hash === '#catalogo' }">Catálogo</RouterLink>
        <RouterLink to="/#categorias" :class="{ 'nav-active': route.path === '/' && route.hash === '#categorias' }">Categorias</RouterLink>
        <RouterLink to="/#sobre" :class="{ 'nav-active': route.path === '/' && route.hash === '#sobre' }">Sobre a Mangáverse</RouterLink>
      </div>
    </nav>
    <CartMergeNotice />
  </header>
</template>
