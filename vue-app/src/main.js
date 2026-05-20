import { createApp } from 'vue';
import App from './App.vue';
// Import du JS Bootstrap : nécessaire pour utiliser bootstrap.Modal, bootstrap.Carousel,
// bootstrap.Tooltip depuis nos composants .vue. Le CSS Bootstrap reste chargé via le CDN
// dans templates/base.html.twig (pas besoin de l'importer ici).
import 'bootstrap';

createApp(App).mount('#app');
