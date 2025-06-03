<template>
  <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
    <div class="container">
      <router-link class="navbar-brand" to="/">
        <i class="bi bi-dumbbell me-2"></i>
        GymFinder
      </router-link>
      
      <button 
        class="navbar-toggler" 
        type="button" 
        data-bs-toggle="collapse" 
        data-bs-target="#navbarNav"
      >
        <span class="navbar-toggler-icon"></span>
      </button>
      
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav me-auto">
          <li class="nav-item">
            <router-link class="nav-link" to="/palestre">Palestre</router-link>
          </li>
        </ul>
        
        <div class="d-flex align-items-center">
          <template v-if="userStore.isLoggedIn">
            <div class="dropdown">
              <button 
                class="btn btn-outline-primary dropdown-toggle" 
                type="button" 
                id="userMenu" 
                data-bs-toggle="dropdown"
              >
                <i class="bi bi-person-circle me-1"></i>
                {{ userStore.user?.nome }} {{ userStore.user?.cognome }}
              </button>
              <ul class="dropdown-menu dropdown-menu-end">
                <li>
                  <div class="dropdown-item-text">
                    <small class="d-block text-muted">Tipo account</small>
                    <strong class="text-capitalize">{{ userStore.user?.tipo_utente }}</strong>
                  </div>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li v-if="userStore.isGymOwner">
                  <router-link class="dropdown-item" to="/gestione-palestra">
                    <i class="bi bi-gear me-2"></i>Gestione Palestra
                  </router-link>
                </li>
                <li>
                  <router-link class="dropdown-item" to="/profilo">
                    <i class="bi bi-person me-2"></i>Profilo
                  </router-link>
                </li>
                <li v-if="userStore.user?.tipo_utente === 'cliente'">
                  <router-link class="dropdown-item" to="/prenotazioni">
                    <i class="bi bi-calendar-check me-2"></i>Le mie prenotazioni
                  </router-link>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                  <button class="dropdown-item text-danger" @click="handleLogout">
                    <i class="bi bi-box-arrow-right me-2"></i>Logout
                  </button>
                </li>
              </ul>
            </div>
          </template>
          <template v-else>
            <router-link to="/login" class="btn btn-outline-primary me-2">
              <i class="bi bi-box-arrow-in-right me-1"></i>Accedi
            </router-link>
            <router-link to="/registrazione" class="btn btn-primary">
              <i class="bi bi-person-plus me-1"></i>Registrati
            </router-link>
          </template>
        </div>
      </div>
    </div>
  </nav>
</template>

<script>
import { useUserStore } from '../store/user';
import { useRouter } from 'vue-router';
import { onMounted, nextTick } from 'vue';

export default {
  name: 'Navbar',
  setup() {
    const userStore = useUserStore();
    const router = useRouter();
    
    const handleLogout = () => {
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
      handleLogout
    };
  }
}
</script>

<style scoped>
.navbar {
  padding: 1rem 0;
}

.navbar-brand {
  font-weight: 600;
  font-size: 1.25rem;
}

.dropdown-item {
  cursor: pointer;
}

.dropdown-item-text {
  padding: 0.5rem 1rem;
}

.text-capitalize {
  text-transform: capitalize;
}
</style> 