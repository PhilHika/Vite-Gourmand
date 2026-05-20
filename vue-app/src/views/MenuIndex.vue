<template>
  <section class="py-5">
    <div class="listing-container container px-4 px-lg-5 mt-5">

      <!-- Bouton "Passer commande" sans menu pré-sélectionné -->
      <div class="text-center mb-4">
        <a v-if="isAuthenticated"
           class="btn btn-success btn-lg"
           href="/commande/new">
          <i class="bi bi-cart-plus"></i> Passer commande
        </a>
        <button v-else
                type="button"
                class="btn btn-success btn-lg"
                @click="ouvrirModaleLogin(null, 'personnalisée')">
          <i class="bi bi-cart-plus"></i> Passer commande
        </button>
      </div>

      <!-- Composant filtres : v-model:filtres = props descendantes + event remontant
           apply-now : permet à l'utilisateur d'appliquer immédiatement sans attendre le debounce
           (touche Enter dans un champ numérique) -->
      <MenuFilters
        v-model:filtres="filtresMenu"
        :themes="listeThemes"
        :regimes="listeRegimes"
        @reset="reinitialiserFiltres"
        @apply-now="appliquerMaintenant" />

      <!-- État de chargement (spinner Bootstrap) -->
      <div v-if="chargementEnCours" class="text-center py-5">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Chargement...</span>
        </div>
      </div>

      <!-- Grille des cartes de menus -->
      <div v-else class="row gx-4 gx-lg-5 row-cols-2 row-cols-md-3 row-cols-xl-4 justify-content-center">
        <MenuCard
          v-for="menu in menusFiltres"
          :key="menu.id"
          :menu="menu"
          :is-authenticated="isAuthenticated"
          :is-salarie="isSalarie"
          @require-login="ouvrirModaleLogin($event.menuId, $event.menuTitre)" />

        <p v-if="menusFiltres.length === 0" class="text-center text-muted mt-4">
          Aucun menu disponible pour ces critères.
        </p>
      </div>

    </div>
  </section>
</template>

<script>
import { Modal } from 'bootstrap';
import MenuFilters from '../components/MenuFilters.vue';
import MenuCard from '../components/MenuCard.vue';

// État vide des filtres : utilisé pour l'init ET le reset
const FILTRES_VIDES = {
  prixMin: null,
  prixMax: null,
  theme: '',
  regime: '',
  nombrePersonne: null
};

export default {
  name: 'MenuIndex',

  components: { MenuFilters, MenuCard },

  data() {
    return {
      // Données chargées depuis l'API
      menusFiltres: [],
      listeThemes: [],
      listeRegimes: [],

      // Flags renvoyés par /api/menus (sécurité côté Symfony)
      isAuthenticated: false,
      isSalarie: false,

      // UI state
      chargementEnCours: false,

      // Filtres actuellement appliqués (copie de l'état "vide" au démarrage)
      filtresMenu: { ...FILTRES_VIDES },

      // Timer pour le debounce des changements de filtres
      timerDebounce: null,

      // Flag d'initialisation pour bloquer le watcher pendant mounted()
      initialise: false
    };
  },

  watch: {
    // Réagit à TOUT changement dans l'objet filtresMenu (deep: true)
    filtresMenu: {
      handler() {
        // Ignorer le déclenchement initial (provoqué par lireFiltresDepuisUrl)
        if (!this.initialise) {
          return;
        }
        // Debounce 500ms : laisse le temps de saisir un nombre à plusieurs chiffres
        // ou de basculer entre 2 champs (prixMin → prixMax) sans relancer 2 fetchs.
        clearTimeout(this.timerDebounce);
        this.timerDebounce = setTimeout(this.appliquerFiltres.bind(this), 500);
      },
      deep: true
    }
  },

  mounted() {
    // Ordre d'init :
    //   1. Lire les filtres depuis l'URL (au cas où on arrive via un lien partagé)
    //   2. Charger les référentiels (un seul appel — themes/regimes ne bougent pas)
    //   3. Charger la liste initiale de menus (avec les filtres lus en 1.)
    //   4. Activer le watcher pour les modifications utilisateur ultérieures
    this.lireFiltresDepuisUrl();
    this.chargerReferentiels();
    this.chargerMenus();
    this.initialise = true;
  },

  methods: {
    // Appelé par le watcher (après debounce) : refresh complet
    appliquerFiltres() {
      this.chargerMenus();
      this.synchroniserUrl();
    },

    // Force un refresh immédiat sans attendre le debounce (touche Enter dans un input)
    appliquerMaintenant() {
      clearTimeout(this.timerDebounce);
      this.appliquerFiltres();
    },

    // Fetch /api/referentiels → remplit listeThemes et listeRegimes
    async chargerReferentiels() {
      try {
        const reponse = await fetch('/api/referentiels');
        const dataReferentiels = await reponse.json();
        this.listeThemes = dataReferentiels.themes;
        this.listeRegimes = dataReferentiels.regimes;
      } catch (erreur) {
        console.error('Échec du chargement des référentiels :', erreur);
      }
    },

    // Fetch /api/menus avec les filtres courants → remplit menusFiltres
    async chargerMenus() {
      this.chargementEnCours = true;
      try {
        const queryString = this.construireParamsUrl();
        const urlFetch = '/api/menus' + (queryString ? '?' + queryString : '');
        const reponse = await fetch(urlFetch);
        const dataMenus = await reponse.json();
        this.menusFiltres = dataMenus.menus;
        this.isAuthenticated = dataMenus.isAuthenticated;
        this.isSalarie = dataMenus.isSalarie;
      } catch (erreur) {
        console.error('Échec du chargement des menus :', erreur);
      } finally {
        this.chargementEnCours = false;
      }
    },

    // Construit la query string compatible avec MenusFilterType de Symfony
    // (préfixe "menus_filter[clé]=valeur" attendu par le form Symfony)
    construireParamsUrl() {
      const params = new URLSearchParams();
      for (const cleFiltre of Object.keys(this.filtresMenu)) {
        const valeur = this.filtresMenu[cleFiltre];
        if (valeur !== null && valeur !== '') {
          params.append(`menus_filter[${cleFiltre}]`, valeur);
        }
      }
      return params.toString();
    },

    // Met à jour l'URL du navigateur sans recharger (back/forward fonctionne)
    synchroniserUrl() {
      const queryString = this.construireParamsUrl();
      const nouvelleUrl = '/menu' + (queryString ? '?' + queryString : '');
      window.history.replaceState({}, '', nouvelleUrl);
    },

    // Au chargement, restaure les filtres depuis l'URL si elle en contient
    lireFiltresDepuisUrl() {
      const params = new URLSearchParams(window.location.search);
      for (const cleFiltre of Object.keys(FILTRES_VIDES)) {
        const valeurUrl = params.get(`menus_filter[${cleFiltre}]`);
        if (valeurUrl !== null && valeurUrl !== '') {
          this.filtresMenu[cleFiltre] = valeurUrl;
        }
      }
    },

    // Remet tous les filtres à null/'' (déclenchera le watcher → fetch + url)
    reinitialiserFiltres() {
      this.filtresMenu = { ...FILTRES_VIDES };
    },

    // Ouvre la modale Bootstrap #loginModal (définie dans le Twig shell, Phase E)
    // et personnalise son contenu en fonction du menu demandé
    ouvrirModaleLogin(menuId, menuTitre) {
      const elementModale = document.getElementById('loginModal');

      // Fallback si on est sur le dev server Vue (port 8082) : pas de Twig, pas de modale
      if (!elementModale) {
        window.location.href = '/login';
        return;
      }

      // Personnaliser le message de la modale
      const elementMessage = document.getElementById('modalMessage');
      if (elementMessage) {
        if (menuId !== null) {
          elementMessage.innerHTML = `Pour commander le menu <strong>${menuTitre}</strong>, veuillez vous connecter ou créer un compte.`;
        } else {
          elementMessage.textContent = 'Pour passer une commande personnalisée, veuillez vous connecter ou créer un compte.';
        }
      }

      // Mettre à jour le lien de login avec le bon _target_path
      const elementLien = document.getElementById('modalLoginLink');
      if (elementLien) {
        const cheminCible = menuId !== null ? `/commande/new?menu=${menuId}` : '/commande/new';
        elementLien.href = '/login?_target_path=' + encodeURIComponent(cheminCible);
      }

      // Afficher la modale
      const instanceModale = Modal.getOrCreateInstance(elementModale);
      instanceModale.show();
    }
  }
};
</script>
