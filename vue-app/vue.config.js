const { defineConfig } = require('@vue/cli-service');
const { WebpackManifestPlugin } = require('webpack-manifest-plugin');
const path = require('node:path');

module.exports = defineConfig({
  // Conservé du scaffold Vue CLI : transpile les dépendances pour les anciens navigateurs
  transpileDependencies: true,

  // 1️⃣ Où Vue va écrire ses fichiers buildés
  //    → Dans le dossier public/ de Symfony, sous-dossier build/
  outputDir: path.resolve(__dirname, '../public/build'),

  // 2️⃣ Préfixe URL pour charger les assets
  //    → Symfony sert public/ à la racine, donc /build/ = public/build/
  publicPath: '/build/',

  // 3️⃣ Entrées de build (une par page Vue)
  //   IMPORTANT : `filename: 'index.html'` permet d'accéder à la SPA via
  //   `localhost:8082/` en dev. Sinon, il faudrait taper l'URL exacte du fichier.
  //   Les clés (`menu-index.js`, `menu-index.css`) dans manifest.json restent inchangées.
  pages: {
    'menu-index': {
      entry: 'src/main.js',
      filename: 'index.html'
    }
    // Plus tard, pour ajouter une seconde page :
    // 'commande-new': { entry: 'src/main-commande.js', filename: 'commande-new.html' }
    // → accessible en dev via http://localhost:8082/commande-new.html
  },

  // 4️⃣ Hash dans les noms de fichiers pour invalider le cache navigateur
  filenameHashing: true,

  // 5️⃣ Sourcemaps désactivées en prod pour alléger
  productionSourceMap: false,

  // 6️⃣ Dev server :
  //    Symfony Nginx (Docker) occupe :8080, Mongo Express occupe :8081
  //    → Vue prend :8082.
  //    Les appels /api/* sont proxifiés vers Symfony localhost:8080.
  devServer: {
    port: 8082,
    proxy: {
      '/api': {
        target: 'http://localhost:8080',
        changeOrigin: true,
      },
    },
  },

  // 7️⃣ Plugin manifest : Vue CLI ne le génère pas par défaut.
  //    On l'ajoute pour que Symfony puisse résoudre les noms de fichiers hashés.
  configureWebpack: {
    plugins: [
      new WebpackManifestPlugin({
        fileName: 'manifest.json',
        publicPath: '/build/',
      }),
    ],
  },
});
