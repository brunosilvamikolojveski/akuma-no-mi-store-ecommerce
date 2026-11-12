<script setup>
import { RouterLink } from 'vue-router';
import StoreHeader from '../components/StoreHeader.vue';
import { useCart } from '../services/cart';

const { cartCount } = useCart();
</script>

<template>
  <div class="site-shell">
    <StoreHeader :cart-count="cartCount" :show-search="false" />
    <main class="container not-found-page">
      <span>Erro 404</span>
      <h1>Esta página não foi encontrada</h1>
      <p>O endereço pode estar incorreto ou a página não existe mais.</p>
      <RouterLink class="admin-primary-button" to="/">Voltar ao início</RouterLink>
    </main>
    <footer class="footer cart-footer">
      <div class="container footer-bottom"><span>© {{ new Date().getFullYear() }} Mangáverse</span><RouterLink to="/">Voltar à loja</RouterLink></div>
    </footer>
  </div>
</template>
