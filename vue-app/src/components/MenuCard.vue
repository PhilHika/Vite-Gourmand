<template>
  <div class="col mb-5">
    <div class="card h-100">

      <!-- Titre cliquable (stretched-link couvre toute la carte) -->
      <div class="card-header bg-transparent border-0 p-3 text-center" style="height:80px;">
        <a :href="`/menu/${menu.id}`" class="stretched-link link-dark">
          <h5 class="fw-bolder mb-0">{{ menu.titre }}</h5>
        </a>
      </div>

      <!-- Prix par personne + minimum -->
      <div v-if="menu.prixParPersonne"
           class="text-center pt-2 pb-3"
           style="position: relative; z-index: 2;">
        <span class="text-danger small fw-bold d-block mb-1">
          <i class="bi bi-people"></i>
          Minimum {{ menu.nombrePersonneMinimum }} personne{{ menu.nombrePersonneMinimum > 1 ? 's' : '' }}
        </span>
        <p class="fw-bold mb-0">
          {{ formatPrix(menu.prixParPersonne) }} €
          <small class="text-muted fw-normal">/ pers.</small>
        </p>
      </div>

      <!-- Carousel des plats (si au moins 1 plat) -->
      <div v-if="menu.plats.length > 0"
           :id="`carouselMenu${menu.id}`"
           ref="carouselElement"
           class="carousel slide">

        <!-- Images des plats -->
        <div class="carousel-inner">
          <div v-for="(plat, index) in menu.plats"
               :key="plat.id"
               class="carousel-item"
               :class="{ active: index === 0 }">
            <img class="d-block w-100"
                 :src="`/${plat.photoPath}`"
                 :alt="plat.titrePlat">
          </div>
        </div>

        <!-- Navigation : < [liste plats] > -->
        <div class="d-flex align-items-center justify-content-between px-2 py-2"
             style="position: relative; z-index: 2;">

          <button v-if="menu.plats.length > 1"
                  type="button"
                  class="btn btn-sm btn-outline-dark flex-shrink-0"
                  :data-bs-target="`#carouselMenu${menu.id}`"
                  data-bs-slide="prev">
            <i class="bi bi-chevron-left"></i>
          </button>
          <span v-else></span>

          <ul class="list-unstyled mb-0 text-center flex-grow-1 mx-2">
            <li v-for="plat in menu.plats" :key="plat.id">
              <span class="small text-dark">{{ plat.titrePlat }}</span>
              <i v-if="plat.allergenes.length > 0"
                 class="bi bi-exclamation-triangle-fill text-warning ms-1"
                 data-bs-toggle="tooltip"
                 data-bs-placement="top"
                 :title="`Allergènes : ${plat.allergenes.join(', ')}`"
                 style="cursor:help;"></i>
            </li>
          </ul>

          <button v-if="menu.plats.length > 1"
                  type="button"
                  class="btn btn-sm btn-outline-dark flex-shrink-0"
                  :data-bs-target="`#carouselMenu${menu.id}`"
                  data-bs-slide="next">
            <i class="bi bi-chevron-right"></i>
          </button>
          <span v-else></span>
        </div>
      </div>

      <!-- Pas de plats : image par défaut -->
      <div v-else>
        <img class="card-img-top" src="/images/menus/default.jpg" :alt="menu.titre">
        <p class="text-muted fst-italic small text-center mt-2">Aucun plat renseigné</p>
      </div>

      <!-- Régime + conditions -->
      <div class="card-body p-4 pt-1 pb-1">
        <div class="text-center">
          <span v-if="menu.regime" class="badge bg-secondary mb-2">{{ menu.regime }}</span>

          <div v-if="menu.conditions.length > 0" class="mt-2 text-start">
            <p class="small fw-bold mb-1 text-primary">
              <i class="bi bi-info-circle"></i> Conditions :
            </p>
            <ul class="small text-muted ps-3 mb-0">
              <li v-for="(condition, indexCondition) in menu.conditions" :key="indexCondition">
                {{ condition }}
              </li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Stock + boutons -->
      <div class="card-footer p-4 pt-3 border-top-0 bg-transparent mt-auto">
        <div class="text-center">

          <p v-if="menu.quantiteRestante > 0" class="text-success fw-bold mb-3 small">
            Stock disponible : {{ menu.quantiteRestante }}
          </p>
          <p v-else class="text-danger fw-bold mb-3 small">
            Stock épuisé
          </p>

          <div class="d-flex flex-column gap-2">
            <a class="btn btn-outline-dark" :href="`/menu/${menu.id}`">
              Voir le menu
            </a>

            <!-- Stock OK + connecté : lien direct vers commande -->
            <a v-if="menu.quantiteRestante > 0 && isAuthenticated"
               class="btn btn-success"
               :href="`/commande/new?menu=${menu.id}`"
               style="position: relative; z-index: 2;">
              <i class="bi bi-cart-plus"></i> Commander
            </a>

            <!-- Stock OK + non connecté : remonte event au parent pour ouvrir la modale login -->
            <button v-else-if="menu.quantiteRestante > 0 && !isAuthenticated"
                    type="button"
                    class="btn btn-success"
                    style="position: relative; z-index: 2;"
                    @click="$emit('require-login', { menuId: menu.id, menuTitre: menu.titre })">
              <i class="bi bi-cart-plus"></i> Commander
            </button>

            <!-- Stock épuisé -->
            <button v-else type="button" class="btn btn-secondary" disabled>
              <i class="bi bi-x-circle"></i> Épuisé
            </button>
          </div>

          <!-- Bouton modifier (ROLE_SALARIE) -->
          <div v-if="isSalarie" class="d-flex flex-column gap-2 mt-2">
            <a class="btn btn-primary"
               :href="`/admin/menu/${menu.id}/edit`"
               style="position: relative; z-index: 2;">
              <i class="bi bi-pencil"></i> Modifier
            </a>
          </div>

        </div>
      </div>

    </div>
  </div>
</template>

<script>
import { Carousel, Tooltip } from 'bootstrap';

export default {
  name: 'MenuCard',

  props: {
    menu: { type: Object, required: true },
    isAuthenticated: { type: Boolean, default: false },
    isSalarie: { type: Boolean, default: false }
  },

  emits: ['require-login'],

  data() {
    return {
      instanceCarousel: null,
      instancesTooltips: []
    };
  },

  // Hook appelé APRÈS que Vue ait inséré le composant dans le DOM
  mounted() {
    // 1. Initialiser le carousel Bootstrap si présent
    if (this.$refs.carouselElement) {
      this.instanceCarousel = new Carousel(this.$refs.carouselElement);
    }

    // 2. Initialiser les tooltips d'allergènes (peut y en avoir plusieurs)
    const elementsTooltips = this.$el.querySelectorAll('[data-bs-toggle="tooltip"]');
    this.instancesTooltips = [];
    for (const elementTooltip of elementsTooltips) {
      const instanceTooltip = new Tooltip(elementTooltip);
      this.instancesTooltips.push(instanceTooltip);
    }
  },

  // Hook appelé AVANT que Vue ne retire le composant du DOM
  // Indispensable pour éviter des fuites mémoire avec les instances Bootstrap
  beforeUnmount() {
    if (this.instanceCarousel) {
      this.instanceCarousel.dispose();
    }
    for (const instanceTooltip of this.instancesTooltips) {
      if (instanceTooltip) {
        instanceTooltip.dispose();
      }
    }
  },

  methods: {
    // Formatte un prix en style français : 12,50 (et non 12.50)
    formatPrix(prix) {
      return prix.toFixed(2).replace('.', ',');
    }
  }
};
</script>
