<template>
  <div class="container mt-5">
    <h1 class="mb-4">Chi Siamo</h1>
    <div class="row">
      <div class="col-md-8">
        <p class="lead">
          GymFinder è la tua piattaforma per trovare la palestra perfetta nella tua città.
        </p>
        <p>
          La nostra missione è connettere gli appassionati di fitness con le migliori strutture sportive,
          offrendo un'esperienza semplice e trasparente nella ricerca e prenotazione di palestre.
        </p>
        <h2 class="mt-4">I Nostri Valori</h2>
        <ul class="list-unstyled">
          <li class="mb-3">✓ Trasparenza nelle informazioni</li>
          <li class="mb-3">✓ Facilità di prenotazione</li>
          <li class="mb-3">✓ Qualità del servizio</li>
          <li class="mb-3">✓ Community di appassionati</li>
        </ul>
      </div>
      <div class="col-md-4">
        <div class="card">
          <div class="card-body">
            <h3 class="card-title">Contattaci</h3>
            <p class="card-text">
              Hai domande o suggerimenti? Non esitare a contattarci!
            </p>
            <a href="mailto:info@gymfinder.it" class="btn btn-primary">Scrivici</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ChiSiamo'
}
</script>

<style scoped>
.lead {
  font-size: 1.25rem;
  font-weight: 300;
}

.list-unstyled li {
  padding-left: 1.5rem;
  position: relative;
}

.list-unstyled li:before {
  content: "✓";
  position: absolute;
  left: 0;
  color: var(--primary-color);
}
</style> 