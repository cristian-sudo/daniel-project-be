<template>
  <div class="prenotazioni-page">
    <div class="container py-5">
      <h1 class="mb-4">Le mie prenotazioni</h1>
      
      <div v-if="loading" class="text-center py-5">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Caricamento...</span>
        </div>
        <p class="mt-3">Caricamento prenotazioni...</p>
      </div>
      
      <div v-else-if="bookings.length > 0">
        <div class="card shadow-sm">
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th>Palestra</th>
                    <th>Data inizio</th>
                    <th>Data fine</th>
                    <th>Stato</th>
                    <th>Azioni</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="booking in bookings" :key="booking.id">
                    <td>{{ booking.gym_nome }}</td>
                    <td>{{ formatDate(booking.data_inizio) }}</td>
                    <td>{{ booking.data_fine ? formatDate(booking.data_fine) : '-' }}</td>
                    <td>
                      <span class="badge" :class="getStatusClass(booking.stato)">
                        {{ getStatusLabel(booking.stato) }}
                      </span>
                    </td>
                    <td>
                      <div class="btn-group btn-group-sm">
                        <button 
                          class="btn btn-info" 
                          @click="viewBookingDetails(booking)"
                        >
                          <i class="bi bi-eye"></i>
                        </button>
                        <button 
                          v-if="booking.stato === 'in attesa'" 
                          class="btn btn-danger" 
                          @click="confirmDeleteBooking(booking)"
                          :disabled="actionLoading"
                        >
                          <i class="bi bi-trash"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
      
      <div v-else class="text-center py-5">
        <div class="card shadow-sm">
          <div class="card-body py-5">
            <i class="bi bi-calendar-x" style="font-size: 3rem; color: #0d6efd;"></i>
            <h2 class="h4 mt-3">Nessuna prenotazione</h2>
            <p class="text-muted">Non hai ancora effettuato prenotazioni</p>
            <router-link to="/palestre" class="btn btn-primary mt-3">
              Cerca palestre
            </router-link>
          </div>
        </div>
      </div>
      
      <!-- Modal Dettagli Prenotazione -->
      <div class="modal fade" id="bookingDetailsModal" tabindex="-1" v-if="selectedBooking" ref="bookingDetailsModal">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Dettagli prenotazione</h5>
              <button type="button" class="btn-close" @click="selectedBooking = null"></button>
            </div>
            <div class="modal-body">
              <div class="mb-3">
                <h6>Palestra</h6>
                <p>{{ selectedBooking.gym_nome }}</p>
              </div>
              <div class="mb-3">
                <h6>Data richiesta</h6>
                <p>{{ formatDateTime(selectedBooking.data_richiesta) }}</p>
              </div>
              <div class="mb-3">
                <h6>Periodo</h6>
                <p>Dal {{ formatDate(selectedBooking.data_inizio) }} 
                  {{ selectedBooking.data_fine ? `al ${formatDate(selectedBooking.data_fine)}` : '(senza scadenza)' }}</p>
              </div>
              <div class="mb-3">
                <h6>Stato</h6>
                <p>
                  <span class="badge" :class="getStatusClass(selectedBooking.stato)">
                    {{ getStatusLabel(selectedBooking.stato) }}
                  </span>
                </p>
              </div>
              <div class="mb-3" v-if="selectedBooking.data_risposta">
                <h6>Data risposta</h6>
                <p>{{ formatDateTime(selectedBooking.data_risposta) }}</p>
              </div>
              <div class="mb-3" v-if="selectedBooking.note">
                <h6>Note</h6>
                <p>{{ selectedBooking.note }}</p>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" @click="selectedBooking = null">Chiudi</button>
              <button 
                v-if="selectedBooking.stato === 'in attesa'" 
                type="button" 
                class="btn btn-danger" 
                @click="confirmDeleteBooking(selectedBooking)"
                :disabled="actionLoading"
              >
                Annulla prenotazione
              </button>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Modal Conferma Eliminazione -->
      <div class="modal fade" id="deleteBookingModal" tabindex="-1" v-if="bookingToDelete" ref="deleteBookingModal">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Conferma annullamento</h5>
              <button type="button" class="btn-close" @click="bookingToDelete = null"></button>
            </div>
            <div class="modal-body">
              <p>Sei sicuro di voler annullare la prenotazione per "{{ bookingToDelete.gym_nome }}"?</p>
              <p class="text-danger">Questa azione non può essere annullata.</p>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" @click="bookingToDelete = null">Annulla</button>
              <button 
                type="button" 
                class="btn btn-danger" 
                @click="deleteBooking"
                :disabled="actionLoading"
              >
                <span v-if="actionLoading" class="spinner-border spinner-border-sm me-2" role="status"></span>
                Conferma annullamento
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted, watch } from 'vue';
import { useBookingStore } from '../store/booking';

export default {
  name: 'Prenotazioni',
  setup() {
    const bookingStore = useBookingStore();
    
    const selectedBooking = ref(null);
    const bookingToDelete = ref(null);
    const actionLoading = ref(false);
    
    // Carica le prenotazioni dell'utente
    onMounted(async () => {
      await bookingStore.getMyBookings();
    });
    
    const viewBookingDetails = (booking) => {
      selectedBooking.value = booking;
    };
    
    const confirmDeleteBooking = (booking) => {
      bookingToDelete.value = booking;
    };
    
    const deleteBooking = async () => {
      actionLoading.value = true;
      
      try {
        const result = await bookingStore.deleteBooking(bookingToDelete.value.id);
        
        if (result) {
          bookingToDelete.value = null;
          
          // Se la prenotazione eliminata era quella selezionata, deselezionala
          if (selectedBooking.value && selectedBooking.value.id === bookingToDelete.value.id) {
            selectedBooking.value = null;
          }
        }
      } catch (error) {
        console.error('Errore durante l\'eliminazione della prenotazione:', error);
      } finally {
        actionLoading.value = false;
      }
    };
    
    // Formattazione
    const formatDate = (dateString) => {
      if (!dateString) return '';
      const date = new Date(dateString);
      return date.toLocaleDateString('it-IT');
    };
    
    const formatDateTime = (dateTimeString) => {
      if (!dateTimeString) return '';
      const date = new Date(dateTimeString);
      return date.toLocaleString('it-IT');
    };
    
    const getStatusLabel = (status) => {
      switch (status) {
        case 'in attesa': return 'In attesa';
        case 'confermata': return 'Confermata';
        case 'rifiutata': return 'Rifiutata';
        default: return status;
      }
    };
    
    const getStatusClass = (status) => {
      switch (status) {
        case 'in attesa': return 'bg-warning';
        case 'confermata': return 'bg-success';
        case 'rifiutata': return 'bg-danger';
        default: return 'bg-secondary';
      }
    };
    
    // Gestione modali con Bootstrap
    watch(selectedBooking, (newVal) => {
      if (newVal) {
        // Inizializza il modal di Bootstrap
        const modal = new bootstrap.Modal(document.getElementById('bookingDetailsModal'));
        modal.show();
      }
    });
    
    watch(bookingToDelete, (newVal) => {
      if (newVal) {
        // Inizializza il modal di Bootstrap
        const modal = new bootstrap.Modal(document.getElementById('deleteBookingModal'));
        modal.show();
      }
    });
    
    return {
      bookings: computed(() => bookingStore.bookings),
      loading: computed(() => bookingStore.loading),
      selectedBooking,
      bookingToDelete,
      actionLoading,
      viewBookingDetails,
      confirmDeleteBooking,
      deleteBooking,
      formatDate,
      formatDateTime,
      getStatusLabel,
      getStatusClass
    };
  }
}
</script>

<style scoped>
.table th, .table td {
  vertical-align: middle;
}
</style>
