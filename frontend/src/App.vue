<template>
  <div id="app">
    <header>
      <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
          <router-link class="navbar-brand" to="/">
            <i class="bi bi-dumbbell me-2"></i>GymFinder
          </router-link>
          <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
          </button>
          <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
              <li class="nav-item">
                <router-link class="nav-link" to="/">Home</router-link>
              </li>
              <li class="nav-item">
                <router-link class="nav-link" to="/palestre">Palestre</router-link>
              </li>
              <li class="nav-item" v-if="isLoggedIn && userStore.user.tipo_utente === 'palestra'">
                <router-link class="nav-link" to="/gestione-palestra">Gestione Palestra</router-link>
              </li>
            </ul>
            <div class="d-flex" v-if="!isLoggedIn">
              <router-link to="/login" class="btn btn-outline-light me-2">Accedi</router-link>
              <router-link to="/registrazione" class="btn btn-light">Registrati</router-link>
            </div>
            <div class="dropdown" v-else>
              <button class="btn btn-light dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown">
                {{ userStore.user.nome }} {{ userStore.user.cognome }}
              </button>
              <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                <li><router-link class="dropdown-item" to="/profilo">Profilo</router-link></li>
                <li><router-link class="dropdown-item" to="/prenotazioni">Le mie prenotazioni</router-link></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="#" @click.prevent="logout">Logout</a></li>
              </ul>
            </div>
          </div>
        </div>
      </nav>
    </header>

    <main>
      <router-view v-slot="{ Component }">
        <transition name="fade" mode="out-in">
          <component :is="Component" />
        </transition>
      </router-view>
    </main>

    <footer class="bg-dark text-white py-4 mt-5">
      <div class="container">
        <div class="row">
          <div class="col-md-4">
            <h5>GymFinder</h5>
            <p>Trova la palestra perfetta per te nella tua città.</p>
          </div>
          <div class="col-md-4">
            <h5>Link utili</h5>
            <ul class="list-unstyled">
              <li><router-link to="/" class="text-white">Home</router-link></li>
              <li><router-link to="/palestre" class="text-white">Palestre</router-link></li>
              <li><router-link to="/chi-siamo" class="text-white">Chi siamo</router-link></li>
            </ul>
          </div>
          <div class="col-md-4">
            <h5>Contatti</h5>
            <ul class="list-unstyled">
              <li><i class="bi bi-envelope me-2"></i> info@gymfinder.it</li>
              <li><i class="bi bi-telephone me-2"></i> +39 123 456 7890</li>
            </ul>
          </div>
        </div>
        <hr>
        <div class="text-center">
          <p>&copy; {{ new Date().getFullYear() }} GymFinder. Tutti i diritti riservati.</p>
        </div>
      </div>
    </footer>
  </div>
</template>

<script>
import { useUserStore } from './store/user';
import { computed, onMounted, nextTick } from 'vue';
import { useRouter } from 'vue-router';

export default {
  name: 'App',
  setup() {
    const userStore = useUserStore();
    const router = useRouter();
    
    const isLoggedIn = computed(() => userStore.isLoggedIn);
    
    const logout = () => {
      userStore.logout();
      router.push('/login');
    };

    onMounted(async () => {
      await nextTick();
      // Initialize dropdowns using the global bootstrap instance
      const dropdownElementList = document.querySelectorAll('.dropdown-toggle');
      dropdownElementList.forEach(dropdownToggle => {
        new window.bootstrap.Dropdown(dropdownToggle, {
          autoClose: true
        });
      });
    });
    
    return {
      userStore,
      isLoggedIn,
      logout
    };
  }
}
</script>

<style>
/* Stili globali */
body {
  font-family: 'Roboto', sans-serif;
  background-color: #f8f9fa;
}

main {
  min-height: calc(100vh - 300px);
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* Personalizzazione Bootstrap */
.btn-primary {
  background-color: #0d6efd;
}

.card {
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  transition: transform 0.3s;
}

.card:hover {
  transform: translateY(-5px);
}
</style>
