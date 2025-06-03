<template>
  <div class="home">
    <section class="hero bg-primary text-white py-5">
      <div class="container">
        <div class="row align-items-center">
          <div class="col-md-6">
            <h1 class="display-4 fw-bold mb-4">Trova la palestra perfetta per te</h1>
            <p class="lead mb-4">Cerca tra le migliori palestre nella tua città, leggi le recensioni e prenota subito!</p>
            <div class="search-box">
              <div class="input-group mb-3">
                <input 
                  type="text" 
                  class="form-control form-control-lg" 
                  placeholder="Inserisci la tua città" 
                  v-model="searchCity"
                  @keyup.enter="searchGyms"
                >
                <button 
                  class="btn btn-light" 
                  type="button" 
                  @click="searchGyms"
                >
                  <i class="bi bi-search"></i> Cerca
                </button>
              </div>
            </div>
          </div>
          <div class="col-md-6 d-none d-md-block">
            
          </div>
        </div>
      </div>
    </section>

    <section class="features py-5">
      <div class="container">
        <h2 class="text-center mb-5">Perché scegliere GymFinder?</h2>
        <div class="row g-4">
          <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm">
              <div class="card-body text-center">
                <div class="feature-icon bg-primary text-white rounded-circle mb-3">
                  <i class="bi bi-search"></i>
                </div>
                <h3 class="card-title h5">Ricerca Facile</h3>
                <p class="card-text">Trova rapidamente le palestre nella tua zona con la nostra ricerca intuitiva.</p>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm">
              <div class="card-body text-center">
                <div class="feature-icon bg-primary text-white rounded-circle mb-3">
                  <i class="bi bi-star"></i>
                </div>
                <h3 class="card-title h5">Recensioni Verificate</h3>
                <p class="card-text">Leggi le recensioni di altri utenti per scegliere la palestra più adatta a te.</p>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm">
              <div class="card-body text-center">
                <div class="feature-icon bg-primary text-white rounded-circle mb-3">
                  <i class="bi bi-calendar-check"></i>
                </div>
                <h3 class="card-title h5">Prenotazione Online</h3>
                <p class="card-text">Prenota la tua iscrizione direttamente online, senza chiamate o attese.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="popular-gyms py-5 bg-light">
      <div class="container">
        <h2 class="text-center mb-5">Palestre Popolari</h2>
        <div v-if="loading" class="text-center">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Caricamento...</span>
          </div>
        </div>
        <div v-else-if="gyms.length > 0" class="row g-4">
          <div v-for="gym in gyms" :key="gym.id" class="col-md-4">
            <div class="card h-100 border-0 shadow-sm">
              <img 
                :src="gym.immagine_principale || '../assets/img/default-gym.jpg'" 
                class="card-img-top" 
                alt="Palestra"
                style="height: 200px; object-fit: cover;"
              >
              <div class="card-body">
                <h3 class="card-title h5 mb-2">{{ gym.nome }}</h3>
                <p class="card-text text-muted mb-2">
                  <i class="bi bi-geo-alt me-1"></i>{{ gym.citta }}
                </p>
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <div class="rating">
                    <i v-for="n in 5" :key="n" class="bi" 
                       :class="n <= Math.round(gym.valutazione_media) ? 'bi-star-fill text-warning' : 'bi-star'">
                    </i>
                    <span class="ms-1">{{ gym.valutazione_media }}</span>
                  </div>
                  <span class="badge bg-primary">{{ gym.prezzo_mensile }}€/mese</span>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                  <span class="badge bg-info">
                    <i class="bi bi-people me-1"></i>{{ gym.iscritti_count || 0 }} iscritti
                  </span>
                  <router-link :to="`/palestra/${gym.id}`" class="btn btn-sm btn-outline-primary">
                    Dettagli
                  </router-link>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div v-else class="text-center">
          <p>Nessuna palestra trovata. Prova a cercare nella tua città!</p>
        </div>
        <div class="text-center mt-4">
          <router-link to="/palestre" class="btn btn-primary">Vedi tutte le palestre</router-link>
        </div>
      </div>
    </section>

    <section class="cta py-5 bg-primary text-white">
      <div class="container text-center">
        <h2 class="mb-4">Sei un proprietario di palestra?</h2>
        <p class="lead mb-4">Registrati come gestore e aggiungi la tua palestra su GymFinder per aumentare la tua visibilità!</p>
        <router-link to="/registrazione" class="btn btn-light btn-lg">Registrati ora</router-link>
      </div>
    </section>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useGymStore } from '../store/gym';

export default {
  name: 'Home',
  setup() {
    const gymStore = useGymStore();
    const router = useRouter();
    const searchCity = ref('');
    const loading = ref(true);
    const popularGyms = ref([]);
    
    // Carica le palestre popolari all'avvio
    const loadPopularGyms = async () => {
      loading.value = true;
      try {
        const response = await gymStore.searchGyms('');
        if (response) {
          // Ordina le palestre per numero di iscritti e valutazione
          popularGyms.value = gymStore.gyms
            .sort((a, b) => {
              // Prima ordina per numero di iscritti
              if (b.iscritti_count !== a.iscritti_count) {
                return b.iscritti_count - a.iscritti_count;
              }
              // Se hanno lo stesso numero di iscritti, ordina per valutazione
              return b.valutazione_media - a.valutazione_media;
            })
            .slice(0, 6); // Prendi solo le prime 6 palestre più popolari
        }
      } catch (error) {
        console.error('Errore nel caricamento delle palestre:', error);
      } finally {
        loading.value = false;
      }
    };
    
    onMounted(async () => {
      await loadPopularGyms();
    });
    
    const searchGyms = () => {
      if (searchCity.value.trim()) {
        router.push({ 
          name: 'Palestre', 
          query: { citta: searchCity.value.trim() } 
        });
      }
    };
    
    return {
      searchCity,
      searchGyms,
      loading,
      gyms: popularGyms
    };
  }
}
</script>

<style scoped>
.hero {
  padding: 80px 0;
}

.feature-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 60px;
  height: 60px;
  font-size: 1.5rem;
}

.search-box {
  background-color: rgba(255, 255, 255, 0.2);
  padding: 20px;
  border-radius: 10px;
}
</style>
