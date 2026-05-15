<template>
  <div class="card mb-4">
    <div class="card-body">
      <div class="row g-3 align-items-end">

        <!-- Prix min -->
        <div class="col-md-2">
          <label class="form-label">Prix min (€/pers)</label>
          <input type="number" class="form-control" min="0" step="0.5"
                 :value="filtres.prixMin"
                 @input="mettreAJour('prixMin', $event.target.value)"
                 @keyup.enter="$emit('apply-now')">
        </div>

        <!-- Prix max -->
        <div class="col-md-2">
          <label class="form-label">Prix max (€/pers)</label>
          <input type="number" class="form-control" min="0" step="0.5"
                 :value="filtres.prixMax"
                 @input="mettreAJour('prixMax', $event.target.value)"
                 @keyup.enter="$emit('apply-now')">
        </div>

        <!-- Thème -->
        <div class="col-md-2">
          <label class="form-label">Thème</label>
          <select class="form-select"
                  :value="filtres.theme"
                  @change="mettreAJour('theme', $event.target.value)">
            <option value="">Tous les thèmes</option>
            <option v-for="theme in themes" :key="theme.id" :value="theme.id">
              {{ theme.libelle }}
            </option>
          </select>
        </div>

        <!-- Régime -->
        <div class="col-md-2">
          <label class="form-label">Régime</label>
          <select class="form-select"
                  :value="filtres.regime"
                  @change="mettreAJour('regime', $event.target.value)">
            <option value="">Tous les régimes</option>
            <option v-for="regime in regimes" :key="regime.id" :value="regime.id">
              {{ regime.libelle }}
            </option>
          </select>
        </div>

        <!-- Nombre de personnes -->
        <div class="col-md-2">
          <label class="form-label">Nombre de personnes</label>
          <input type="number" class="form-control" min="1"
                 :value="filtres.nombrePersonne"
                 @input="mettreAJour('nombrePersonne', $event.target.value)"
                 @keyup.enter="$emit('apply-now')">
        </div>

        <!-- Bouton réinitialiser -->
        <div class="col-md-2">
          <button type="button" class="btn btn-outline-secondary" @click="$emit('reset')">
            <i class="bi bi-x-circle"></i> Réinitialiser
          </button>
        </div>

      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'MenuFilters',

  // Props : données reçues du parent (MenuIndex)
  props: {
    filtres: { type: Object, required: true },
    themes:  { type: Array, default: () => [] },
    regimes: { type: Array, default: () => [] }
  },

  // Events que ce composant peut émettre vers son parent
  emits: ['update:filtres', 'reset', 'apply-now'],

  methods: {
    // Pattern v-model:filtres : on émet l'objet COMPLET au parent à chaque modif
    mettreAJour(cleFiltre, nouvelleValeur) {
      const filtresMisAJour = { ...this.filtres, [cleFiltre]: nouvelleValeur };
      this.$emit('update:filtres', filtresMisAJour);
    }
  }
};
</script>
