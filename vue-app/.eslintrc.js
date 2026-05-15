module.exports = {
  root: true,
  env: {
    node: true,
    browser: true
  },
  extends: [
    // Garde uniquement les règles essentielles de Vue (détection de bugs réels)
    'plugin:vue/vue3-essential'
    // '@vue/standard' retiré : trop dogmatique sur le style (semicolons, trailing commas, etc.)
  ],
  parserOptions: {
    parser: '@babel/eslint-parser',
    // VS Code lance ESLint depuis la racine du workspace Symfony, pas depuis vue-app/.
    // Sans ce flag, @babel/eslint-parser cherche babel.config.js depuis la mauvaise cwd
    // et rapporte une fausse erreur "No Babel config file detected".
    requireConfigFile: false
  },
  rules: {
    // console.log spamme la prod, mais console.warn / console.error sont légitimes
    // pour la diagnostic d'erreurs runtime → on les autorise.
    'no-console': process.env.NODE_ENV === 'production'
      ? ['warn', { allow: ['warn', 'error'] }]
      : 'off',
    'no-debugger': process.env.NODE_ENV === 'production' ? 'warn' : 'off'
  }
};
