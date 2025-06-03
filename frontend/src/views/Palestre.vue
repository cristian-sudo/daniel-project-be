<template>
  <div class="palestre-page">
    <div class="container py-5">
      <h1 class="mb-4">Cerca Palestre</h1>
      
      <!-- Barra di ricerca -->
      <div class="search-container mb-5">
        <div class="card shadow-sm">
          <div class="card-body">
            <div class="row g-3">
              <div class="col-md-10">
                <input 
                  type="text" 
                  class="form-control" 
                  placeholder="Inserisci la città" 
                  v-model="searchCity"
                  @keyup.enter="searchGyms"
                >
              </div>
              <div class="col-md-2">
                <button 
                  class="btn btn-primary w-100" 
                  @click="searchGyms"
                  :disabled="loading"
                >
                  <span v-if="loading" class="spinner-border spinner-border-sm me-2" role="status"></span>
                  <i v-else class="bi bi-search me-2"></i>
                  Cerca
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Risultati della ricerca -->
      <div v-if="loading" class="text-center py-5">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Caricamento...</span>
        </div>
        <p class="mt-3">Ricerca in corso...</p>
      </div>
      
      <div v-else-if="gyms.length > 0">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h2>Risultati per "{{ displayCity }}"</h2>
          <div class="dropdown">
            <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="sortDropdown" data-bs-toggle="dropdown">
              Ordina per
            </button>
            <ul class="dropdown-menu" aria-labelledby="sortDropdown">
              <li><a class="dropdown-item" href="#" @click.prevent="sortGyms('rating')">Valutazione</a></li>
              <li><a class="dropdown-item" href="#" @click.prevent="sortGyms('price-asc')">Prezzo (crescente)</a></li>
              <li><a class="dropdown-item" href="#" @click.prevent="sortGyms('price-desc')">Prezzo (decrescente)</a></li>
            </ul>
          </div>
        </div>
        
        <div class="row g-4">
          <div v-for="gym in sortedGyms" :key="gym.id" class="col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm">
              <img 
                :src="gym.immagine_principale || '../assets/img/default-gym.jpg'" 
                class="card-img-top" 
                alt="Palestra"
                style="height: 200px; object-fit: cover;"
              >
              <div class="card-body">
                <h3 class="card-title h5">{{ gym.nome }}</h3>
                <p class="card-text text-muted">
                  <i class="bi bi-geo-alt me-1"></i>{{ gym.citta }}
                </p>
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <div class="rating">
                    <i v-for="n in 5" :key="n" class="bi" :class="n <= Math.round(gym.valutazione_media) ? 'bi-star-fill text-warning' : 'bi-star'"></i>
                    <span class="ms-1">{{ gym.valutazione_media }}</span>
                    <span class="text-muted">({{ gym.recensioni_count }})</span>
                  </div>
                  <span class="badge bg-primary">{{ gym.prezzo_mensile }}€/mese</span>
                </div>
                <p class="card-text small" v-if="gym.descrizione">
                  {{ truncateText(gym.descrizione, 100) }}
                </p>
              </div>
              <div class="card-footer bg-white border-0">
                <router-link :to="`/palestra/${gym.id}`" class="btn btn-outline-primary w-100">
                  Visualizza dettagli
                </router-link>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <div v-else-if="hasSearched" class="text-center py-5">
        <div class="alert alert-info">
          <i class="bi bi-info-circle me-2"></i>
          Nessuna palestra trovata per "{{ displayCity }}". Prova con un'altra città.
        </div>
      </div>
      
      <div v-else class="text-center py-5">
        <div class="alert alert-secondary">
          <i class="bi bi-search me-2"></i>
          Inserisci una città per iniziare la ricerca
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useGymStore } from '../store/gym';

export default {
  name: 'Palestre',
  setup() {
    const gymStore = useGymStore();
    const route = useRoute();
    const router = useRouter();
    
    const searchCity = ref('');
    const sortBy = ref('rating');
    const hasSearched = ref(false);
    
    // Initialize gyms as an empty array
    const gyms = computed(() => gymStore.gyms || []);
    const loading = computed(() => gymStore.loading);
    
    // Inizializza la ricerca con il parametro dalla query se presente
    onMounted(() => {
      if (route.query.citta) {
        searchCity.value = route.query.citta;
        searchGyms();
      }
    });
    
    const searchGyms = async () => {
      if (searchCity.value.trim()) {
        hasSearched.value = true;
        const result = await gymStore.searchGyms(searchCity.value.trim());
        console.log('Search result:', result);
        console.log('Gyms after search:', gymStore.gyms);
        
        // Aggiorna l'URL con il parametro di ricerca
        router.replace({ 
          query: { citta: searchCity.value.trim() } 
        });
      }
    };
    
    const sortGyms = (sortType) => {
      sortBy.value = sortType;
    };
    
    const sortedGyms = computed(() => {
      if (!gyms.value || !gyms.value.length) return [];
      
      const gymsList = [...gyms.value];
      
      switch (sortBy.value) {
        case 'rating':
          return gymsList.sort((a, b) => b.valutazione_media - a.valutazione_media);
        case 'price-asc':
          return gymsList.sort((a, b) => a.prezzo_mensile - b.prezzo_mensile);
        case 'price-desc':
          return gymsList.sort((a, b) => b.prezzo_mensile - a.prezzo_mensile);
        default:
          return gymsList;
      }
    });
    
    const displayCity = computed(() => searchCity.value.trim());
    
    const truncateText = (text, maxLength) => {
      if (!text) return '';
      return text.length > maxLength ? text.substring(0, maxLength) + '...' : text;
    };
    
    return {
      searchCity,
      searchGyms,
      sortGyms,
      sortedGyms,
      displayCity,
      hasSearched,
      truncateText,
      gyms,
      loading
    };
  }
}
</script>

<style scoped>
.search-container {
  background-color: #f8f9fa;
  border-radius: 10px;
}
</style>
