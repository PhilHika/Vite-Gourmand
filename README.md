# 🍽️ Vite-et-Gourmand

Application de gestion de commandes de menus traiteur, développée avec Symfony 7.4 et une stack Docker complète.

---

## 🚀 Stack Technique

- **Framework** : Symfony 7.4 LTS (PHP 8.4)
- **Serveur Web** : Nginx (Alpine)
- **Base de données relationnelle** : PostgreSQL 16 (Doctrine ORM)
- **Base de données NoSQL** : MongoDB 7 (Doctrine ODM)
- **Frontend dynamique** : Vue.js 3 (Vue CLI 5) — utilisé pour les pages à interactivité riche (filtres sans rechargement, etc.)
- **Outils** : Mailpit (Capture d'emails), Mongo Express (Admin MongoDB), Composer 2.8, npm 10+

---

## ✨ Fonctionnalités

- **Catalogue de menus** : consultation, filtrage, gestion du stock par menu
- **Commande en ligne** : parcours guidé, réduction automatique, confirmation par email
- **Espace client** : profil, historique des commandes, avis
- **Espace administration** (`/admin`) : gestion des menus, plats, commandes, utilisateurs, référentiels
- **Formulaire de contact** : pattern DTO, notification email à l'équipe
- **Réinitialisation de mot de passe** : implémentation custom (sans bundle externe)
- **Horaires d'ouverture** : stockés en MongoDB (Document `Horaire`)

---

## 🏗️ Architecture & Modèle de Données

Le projet suit une architecture MVC classique avec Symfony.

### Double base de données

- **PostgreSQL** (Doctrine ORM) : données relationnelles (Utilisateurs, Commandes, Menus, Plats, Avis, etc.) dans `src/Entity/`
- **MongoDB** (Doctrine ODM) : données documentaires (Horaires d'ouverture, Contenu du site) dans `src/Document/`

### Schéma des Entités

```mermaid
classDiagram
    class Utilisateur {
        +int id
        +string email
        +string password
        +string prenom
        +string nom
        +string telephone
        +string ville
        +string pays
        +string adressePostale
    }
    class Role {
        +int id
        +string libelle
    }
    class Commande {
        +string numeroCommande
        +datetime dateCommande
        +datetime datePrestation
        +time heureLivraison
        +float prixMenu
        +int nombrePersonne
        +float prixLivraison
        +string statut
        +bool pretMateriel
        +bool restitutionMateriel
    }
    class Menu {
        +int id
        +string titre
        +float prixParPersonne
        +int nombrePersonneMinimum
        +int quantiteRestante
        +string description
    }
    class Plat {
        +int id
        +string titrePlat
        +blob photo
    }
    class Avis {
        +int id
        +string commentaire
        +int note
    }
    class Allergene {
        +int id
        +string libelle
    }
    class Regime {
        +int id
        +string libelle
    }
    class Theme {
        +int id
        +string libelle
    }

    Utilisateur "many" --> "1" Role : a
    Utilisateur "1" --> "many" Commande : passe
    Utilisateur "1" --> "many" Avis : dépose
    Commande "many" --> "1" Menu : concerne
    Menu "many" <--> "many" Plat : contient
    Plat "many" <--> "many" Allergene : contient
    Menu "many" --> "1" Regime : suit
    Menu "many" --> "1" Theme : a
```

---

## 💡 Philosophie de Développement

### 🛡️ Validation
- Attributs `#[Assert]` Symfony sur les entités et DTO (email, longueurs, types, contraintes métier).

### 🔑 Gestion des mots de passe
- `UserPasswordHasherInterface` avec l'algorithme `auto` (bcrypt/argon2id selon la config).
- Stockage sur `VARCHAR(255)` en base.
- `UtilisateurRepository` implémente `PasswordUpgraderInterface` : re-hachage automatique silencieux à chaque login si l'algorithme configuré a évolué.

### 🆔 Identifiants de commande
- Format `XXXXXXXX-YYYYMMDD` (UUID v4 tronqué + date), généré automatiquement via `#[ORM\PrePersist]`.

### 🔐 Réinitialisation de mot de passe

Implémentation manuelle (sans bundle externe) avec une table dédiée `reset_password_request` :
- Le schéma de la table `utilisateur` n'est **pas modifié**
- Tokens UUID v4, expiration 1 heure
- Réponse identique que l'email existe ou non (pas de divulgation d'emails)
- Session invalidée après reset

| Route | Description |
| :--- | :--- |
| `/reset-password` | Formulaire de demande (saisie email) |
| `/reset-password/{token}` | Formulaire de saisie du nouveau mot de passe |

### 📧 Système d'emails (Symfony Mailer + Pattern Service)

#### Architecture Service

Les emails sont gérés par des **services dédiés** dans `src/Service/`, chacun **derrière un contrat** dans `src/Contract/` (principe SOLID — Dependency Inversion) :

```
src/Contract/
├── CommandeMailerServiceInterface.php   ← Contrat : envoyerConfirmation(), envoyerChangementStatut()
├── ContactMailerServiceInterface.php    ← Contrat : envoyerMessageContact()
└── PasswordResetMailerServiceInterface.php ← Contrat : envoyerLienReset()

src/Service/
├── CommandeMailerService.php          ← Implémentation (commandes)
├── ContactMailerService.php           ← Implémentation (formulaire de contact)
└── PasswordResetMailerService.php     ← Implémentation (reset de mot de passe)
```

Les vues des emails sont dans `templates/emails/` (layout + un template par type). Les services se chargent uniquement de l'envoi.

#### Emails automatiques

| Trigger | Destinataire | Service |
| :--- | :--- | :--- |
| Nouvelle commande confirmée | Client + tous gestionnaires `[STAFF]` | `CommandeMailerService` |
| Changement de statut (admin) | Client + tous gestionnaires `[STAFF]` | `CommandeMailerService` |
| Formulaire de contact | Équipe admin | `ContactMailerService` |
| Réinitialisation mot de passe | Utilisateur demandeur | `PasswordResetMailerService` |

Les gestionnaires (`ROLE_SALARIE` + `ROLE_ADMIN`) sont récupérés dynamiquement via `UtilisateurRepository::findGestionnaires()`.

#### Mode synchrone

Configuré via `SendEmailMessage: sync` dans `config/packages/messenger.yaml` (pas de worker Messenger requis). À réévaluer si le volume d'emails augmente significativement.

#### Configuration SMTP

- **Dev** : Mailpit sur `smtp://localhost:1025` (interface : http://localhost:8025)
- **Prod** : SMTP réel (Gmail, SendGrid, Brevo, etc.) via `MAILER_DSN` dans `.env.local`

### 🖼️ Gestion des médias (Images)

Afin de garantir des performances optimales et une mise en cache efficace, les images des plats ne sont pas stockées en base de données, mais téléversées dans le système de fichiers (`public/uploads/plats/`). L'entité ne conserve qu'une chaîne de caractères (`VARCHAR`) correspondant au nom du fichier.

> **📍 Justification architecturale (Écart par rapport au schéma initial ECF)**
> Le schéma de base de données (UML) fourni prévoyait un attribut `photo` de type `BLOB` sur l'entité `Plat`.
> Pour plusieurs raisons structurelles je me suis ecarté de ce choix :
> 1. **Performances et volume BDD** : Les BLOBs alourdissent considérablement le poids de la base de données, ce qui ralentit drastiquement les requêtes SQL, la consommation de RAM, ainsi que les opérations de sauvegarde (dump) et de restauration.
> 2. **Mise en cache serveur et CDN** : Servir une image depuis un BLOB nécessite systématiquement d'invoquer le processeur PHP (via un Contrôleur) pour extraire et retourner les flux binaires. En stockant le fichier sur le disque (`/public/`), on délègue cette tâche au serveur web (Nginx) qui sert ces fichiers statiques instantanément tout en tirant parti du cache navigateur client et d'un éventuel CDN. 
> 3. **Scalabilité** : Conserver uniquement les chemins (URL) en base de données permet d'isoler le stockage des fichiers. Si l'application évolue, il sera aisé de déporter le dossier d'upload vers un stockage objet externe de type Amazon S3 sans aucune modification du schéma de données.
> **Ce changement vers un `VARCHAR(255)` est donc une décision d'optimisation.**

### 🛒 Système de Commande

#### Parcours de Commande & Checkout Interactif

Afin d'offrir une expérience utilisateur (UX) moderne et fluide, le tunnel de validation de commande a été repensé en deux approches :
1. **Accès direct sans menu initial** : L'utilisateur se rend sur la page de commande `/commande/new`. Il renseigne **d'abord** ses informations de livraison (date, heure, adresse, nombre de personnes). Ensuite, un bouton ouvre une **fenêtre modale (popup) interactive** affichant le catalogue. La sélection soumet dynamiquement le formulaire (via JavaScript) sans perte de contexte, l'amenant directement au récapitulatif final.
2. **Accès ciblé depuis une fiche Menu** : L'utilisateur arrive sur le formulaire avec le menu déjà pré-sélectionné en URL (`?menu=ID`). Le formulaire de livraison et le récapitulatif tarifaire sont affichés simultanément.
3. *Note : Si l'utilisateur n'est pas connecté, une modale d'invitation le redirige vers le login.*

> **📍 Justification architecturale (Écart par rapport au schéma initial ECF)**
> Le schéma de base de données (UML) fourni en annexe de l'évaluation ne prévoyait pas d'informations d'adresse sur l'entité `Commande` (les coordonnées n'étaient rattachées qu'à `Utilisateur`).
> Toutefois, la logique métier inhérente à une application Traiteur/E-commerce exige la possibilité de spécifier une adresse de livraison ponctuelle (entreprise, lieu de réception) différente de l'adresse de facturation/profil, et requiert de conserver une trace *immuable* de cette adresse dans l'historique de la commande même en cas de déménagement du client.
> **J'ai donc fait le choix conceptuel fort d'ajouter les attributs `adresseLivraison`, `villeLivraison` et `paysLivraison` directement sur l'entité `Commande`**. Cet écart assumé par rapport au schéma de départ permet de répondre aux véritables exigences de l'évaluation avec un livrable réaliste, fonctionnel et professionnel.

#### Réduction tarifaire

> 10% appliquée automatiquement si `nombrePersonne >= nombrePersonneMinimum + 5`
>
> `prixMenu = prixParPersonne × nombrePersonne × 0.90`

#### Gestion du stock

- Bouton "Commander" remplacé par **"Épuisé"** si `quantiteRestante <= 0`
- Vérification serveur à la soumission (protection contre les commandes concurrentes)
- `quantiteRestante` décrémenté automatiquement à chaque commande validée

#### Gestion admin des commandes (`ROLE_SALARIE` / `ROLE_ADMIN`)

| Route | Description |
| :--- | :--- |
| `/admin/commande/` | Listing de toutes les commandes avec filtres (statut, client) |
| `/admin/commande/{id}/edit` | Édition complète (statut, prix, dates, matériel) |

**Filtres disponibles sur le listing :**
- **Statut** : dropdown sur tous les statuts possibles
- **Client** : champ de recherche autocomplété — liste uniquement les clients ayant au moins une commande, filtre par nom/prénom/email à la saisie

#### Annulation par le client (`ROLE_USER`)

Le client peut annuler sa commande depuis `/commande/{numeroCommande}` **uniquement si le statut est `en_attente`**. La route POST `/commande/{numeroCommande}/annuler` vérifie ownership + statut + CSRF, puis restitue le stock du menu.

### ⭐ Système d'Avis et Modération

#### Côté Client
- Possibilité de laisser un avis (note de 1 à 5 + commentaire) sur une commande **livrée** depuis l'espace profil.
- Affichage dynamique des 4 derniers avis publiés sur la page d'accueil.
- Bouton ouvrant une modale à défilement pour consulter l'intégralité de l'historique des avis publiés.

#### Côté Administration (`ROLE_SALARIE` / `ROLE_ADMIN`)
- Tableau de bord recensant en priorité les avis **en attente** de modération.
- Accès à l'historique complet des avis via une fenêtre modale.
- Possibilité de changer le statut d'un avis (`En attente`, `Publié`, `Refusé`). 
- **Sécurité et visibilité** : Seuls les avis avec le statut `Publié` sont affichés sur la page d'accueil publique.

> **📍 Justification architecturale (Écart par rapport au schéma initial ECF)**
> Afin de proposer une fonctionnalité d'avis réaliste et robuste, l'entité `Avis` a bénéficié de quelques ajustements pro-actifs par rapport au schéma conceptuel basique :
> 1. **Relation `OneToOne` avec `Commande`** : Au lieu d'une simple relation générique "1 Utilisateur -> N Avis", un avis est strictement lié à **une** commande unique (qui doit avoir le statut livrée). Cela évite le spam d'avis sans achat et garantit que chaque avis représente une prestation réelle.
> 2. **Typage de la `note` en `SMALLINT`** (1 à 5) : Garantit l'intégrité de la note directement au niveau de la base de données PostgreSQL, empêchant d'éventuelles failles de saisie.
> 3. **Ajout d'un système d'état (`statut` en `VARCHAR(255)`)** : Gérer un site sans modération expose le propriétaire à des avis indésirables ou diffamatoires. L'ajout natif des statuts constants (`STATUT_EN_ATTENTE`, `STATUT_PUBLIE`, `STATUT_REFUSE`) était indispensable pour simuler un véritable outil de gestion e-commerce professionnel.

### 🔎 Filtrage dynamique du catalogue (Vue CLI + API Symfony)

Listing `/menu` rendu par une **SPA Vue 3** — filtres appliqués **sans rechargement**.

**Côté Vue** — [vue-app/src/views/MenuIndex.vue](vue-app/src/views/MenuIndex.vue) :
- Fetch `/api/referentiels` une seule fois (thèmes/régimes)
- `watch` deep sur `filtresMenu` + **debounce 500 ms** → fetch `/api/menus`
- Event `@apply-now` (touche Enter) → bypass du debounce
- `window.history.replaceState` → URL partageable, back/forward OK

**Côté Symfony** — [src/Form/MenusFilterType.php](src/Form/MenusFilterType.php) :
- Format query : `menus_filter[clé]=valeur`
- `method: GET`, `csrf_protection: false`
- Sert de **parseur/validateur** des query params (pas de rendu HTML)
- Validation Symfony conservée (`EntityType` pour `theme`/`regime`), UX déléguée à `MenuFilters.vue`

---

## 🛠️ Installation & Workflow

### 1. Cloner le projet

```bash
git clone git@github.com:PhilHika/Vite-Gourmand.git
cd Vite-et-Gourmand
composer install
```

### 2. Configuration des variables d'environnement

Le projet utilise **3 niveaux de configuration** :

| Fichier | Rôle | Commité sur Git |
| :--- | :--- | :--- |
| `.env` | Placeholders par défaut (aucun secret) | Oui |
| `.env.local` | Vraies valeurs de votre environnement | Non |
| `compose.yaml` | Credentials Docker de développement (en dur) | Oui |

> **Note sur le `compose.yaml`** : les credentials des bases de données de développement sont inscrites en dur dans le fichier `compose.yaml` (et non via des variables `${...}` du `.env`). Ce choix est volontaire : Symfony CLI expose automatiquement certaines variables d'environnement Docker (comme `MONGODB_DB`) qui entrent en conflit avec les valeurs du `.env`, provoquant des erreurs de connexion. En inscrivant les valeurs directement dans `compose.yaml`, on évite ce conflit. Ces credentials sont exclusivement destinées au développement local.

**Créer votre `.env.local` :**

```bash
cp .env .env.local
```

Puis modifier `.env.local` avec vos valeurs :

| Variable | Description | Exemple |
| :--- | :--- | :--- |
| `APP_SECRET` | Clé secrète Symfony | `php -r "echo bin2hex(random_bytes(16));"` |
| `DATABASE_URL` | DSN PostgreSQL | `postgresql://user:pass@127.0.0.1:5433/vite_gourmand?serverVersion=16` |
| `MONGO_USER` | Utilisateur MongoDB | `mongoDB_user` |
| `MONGO_PASSWORD` | Mot de passe MongoDB | `mongoDB_dev_2026` |
| `MONGODB_URI` | URI MongoDB | `mongodb://${MONGO_USER}:${MONGO_PASSWORD}@127.0.0.1:27017` |
| `MONGODB_DBNAME` | Nom de la base MongoDB | `vite_gourmand` |
| `MAILER_DSN` | Transport email | `smtp://localhost:1025` (dev) |

> **Important** : en mode dev hybride (PHP local + Docker pour les DB), le `DATABASE_URL` pointe vers `127.0.0.1:5433` (port exposé par Docker). En mode full Docker, il pointe vers `db:5432` (hostname interne Docker).

### 3. Démarrer l'environnement

**Mode Docker complet :**
```bash
docker compose up -d --build
docker compose exec php composer install
```

**Mode dev hybride (recommandé pour le développement sur Windows) :**

Ce mode utilise Docker uniquement pour les bases de données et le **serveur PHP natif de Symfony CLI** pour l'application. Sous Windows, ce mode est nettement plus rapide (le bind-mount Docker du dossier projet ralentit considérablement PHP-FPM).

```bash
# Lancer les bases de données + Mongo Express
docker compose up -d db mongodb mongo-express

# Lancer le serveur Symfony (lit automatiquement .symfony.local.yaml)
symfony serve -d
```

Le fichier `.symfony.local.yaml` à la racine du projet fixe automatiquement deux paramètres importants :
- `port: 8080` — même port qu'en mode Docker complet, **un seul URL à retenir** quel que soit le mode
- `no_tls: true` — désactive l'auto-redirect HTTPS du serveur Go de Symfony CLI

> ⚠️ Sans `no_tls: true`, Symfony CLI répond 307 sur HTTP et redirige vers `https://localhost:8080`. Le proxy Vue (`localhost:8082` → `localhost:8080`) reçoit ce redirect et le passe au navigateur, qui voit alors un cross-origin → **erreur CORS**. C'est pour ça qu'on désactive TLS en dev.

| Service | URL | Mode |
| :--- | :--- | :--- |
| Application (Symfony) | http://localhost:8080 | Hybride OU Docker (exclusifs) |
| Mongo Express (admin MongoDB) | http://localhost:8081 | Docker |
| Mailpit (capture d'emails) | http://localhost:8025 | Docker |
| Vue.js dev server (HMR) | http://localhost:8082 | `npm run serve` |

> 🔁 **Hybride et Docker complet utilisent tous deux le port :8080** — ils sont donc **mutuellement exclusifs**. Si tu lances `symfony serve -d` puis `docker compose up nginx`, le second échouera à bind sur :8080.

### 4. Initialiser les bases de données

```bash
# PostgreSQL : migrations + données initiales
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:fixtures:load --no-interaction

# MongoDB : créer le schéma
php bin/console doctrine:mongodb:schema:create
```

> Les fixtures créent les **3 rôles** (`ROLE_USER`, `ROLE_SALARIE`, `ROLE_ADMIN`) en base. Les comptes utilisateurs sont à créer via l'interface `/register`.

### 5. Installer et builder le frontend Vue.js

Le projet utilise Vue.js 3 (Vue CLI 5) pour les pages à interactivité riche. Le code source Vue vit dans `vue-app/` et est compilé vers `public/build/` pour être servi par Symfony.

```bash
cd vue-app
npm ci                  # installe les dépendances (équivalent npm install mais plus rapide et déterministe)
npm run build           # compile les .vue vers public/build/
cd ..
```

Vérifie que `public/build/manifest.json` a été généré : c'est ce fichier que Symfony lit pour résoudre les noms de fichiers hashés via `{{ asset('menu-index.js', 'vue_build') }}` en Twig.

> **Pourquoi `npm ci` plutôt que `npm install`** : `npm ci` installe **exactement** les versions du `package-lock.json` (plus rapide, déterministe, idéal pour CI/CD et premier clone). `npm install` peut mettre à jour les versions selon les caret/tilde.

#### Workflow de dev frontend (Hot Module Reload)

Pour développer sur les composants Vue avec rechargement instantané :

```bash
cd vue-app
npm run serve           # → http://localhost:8082
```

Les appels `/api/*` faits depuis `localhost:8082` sont automatiquement proxifiés vers Symfony sur `localhost:8080` (peu importe que tu sois en mode hybride ou Docker) — configuré dans `vue-app/vue.config.js`.

> **Limite du dev server Vue (`:8082`)** : la SPA y tourne en isolation, sans le layout Twig (navbar, footer). Pour tester l'intégration finale, valide toujours sur `http://localhost:8080/menu` après un `npm run build`.

#### Note importante : CORS uniquement en dev

Le proxy Vue + `.symfony.local.yaml` (no_tls) est nécessaire **seulement** en mode dev avec `npm run serve`. En production :

- Vue est **pré-buildé** une seule fois au déploiement (`npm run build` → `public/build/`)
- Symfony Nginx sert à la fois le HTML Twig ET les assets `/build/*`
- Toutes les requêtes (page + API) sont donc en **même origine** → **CORS impossible**
- Le HTTPS est géré par le reverse proxy en amont (Cloudflare, Traefik, etc.), pas par l'app

#### Détail technique : intégration Vue ↔ Symfony

- `vue-app/vue.config.js` : redirige `outputDir` vers `../public/build/` et configure le plugin `webpack-manifest-plugin` (manifest.json non généré nativement par Vue CLI 5)
- `config/packages/framework.yaml` : déclare un asset package `vue_build` qui lit ce manifest
- Templates Twig : `{{ asset('chunk-vendors.js', 'vue_build') }}` et `{{ asset('menu-index.js', 'vue_build') }}` injectent les bons fichiers hashés

### 6. Créer un administrateur (post-déploiement)

Après un déploiement en production (ou sur un environnement vierge sans fixtures), la commande `app:create-admin` permet de créer un compte administrateur en une seule étape :

```bash
php bin/console app:create-admin --email=admin@viteetgourmand.fr --password=MonMotDePasse
```

**Ce que fait la commande automatiquement :**
1. Vérifie si la table `role` contient les 3 rôles standards (`ROLE_USER`, `ROLE_SALARIE`, `ROLE_ADMIN`). Si des rôles manquent, elle les crée.
2. Vérifie si un utilisateur avec cet email existe déjà. Si oui, elle s'arrête sans erreur (idempotent).
3. Crée l'utilisateur avec le rôle `ROLE_ADMIN` et un mot de passe hashé.

**Options disponibles :**

| Option | Obligatoire | Valeur par défaut | Description |
| :--- | :--- | :--- | :--- |
| `--email` | Oui | — | Email de l'admin |
| `--password` | Oui | — | Mot de passe (sera hashé) |
| `--prenom` | Non | `Admin` | Prénom |
| `--nom` | Non | `Admin` | Nom |
| `--telephone` | Non | `0000000000` | Téléphone |
| `--ville` | Non | `Paris` | Ville |
| `--pays` | Non | `France` | Pays |
| `--adresse` | Non | `Adresse admin` | Adresse postale |

**Exemple sur Heroku :**
```bash
heroku run php bin/console app:create-admin --email=admin@viteetgourmand.fr --password=S3cur3P@ss!
```

> **Sécurité** : ces commandes ne sont accessibles que via le terminal serveur (CLI). Elles ne peuvent pas être exécutées depuis le navigateur. Seul un utilisateur authentifié sur la plateforme d'hébergement (Heroku, Upsun, SSH, etc.) peut les exécuter.

### 7. Réinitialiser les mots de passe admin

La commande `app:reset-admin` permet de réinitialiser le mot de passe des comptes admin sans les supprimer (préserve les commandes et avis liés).

**Trois modes d'utilisation :**

```bash
# Mode interactif (recommandé) : demande un mot de passe pour chaque admin
php bin/console app:reset-admin

# Mode batch : même mot de passe pour tous les admins
php bin/console app:reset-admin --password=NouveauMotDePasse

# Mode ciblé : un seul admin spécifique
php bin/console app:reset-admin --email=admin@viteetgourmand.fr --password=NouveauMotDePasse
```

> En mode interactif, le mot de passe est saisi de manière **cachée** (non affiché à l'écran). Si vous appuyez sur Entrée sans rien taper, l'admin est ignoré.

### 8. Commandes utiles

| Action | Commande |
| :--- | :--- |
| **PostgreSQL (ORM)** | |
| Créer une migration | `php bin/console make:migration` |
| Appliquer les migrations | `php bin/console doctrine:migrations:migrate` |
| Charger les fixtures | `php bin/console doctrine:fixtures:load` |
| Valider le schéma | `php bin/console doctrine:schema:validate` |
| **MongoDB (ODM)** | |
| Créer le schéma MongoDB | `php bin/console doctrine:mongodb:schema:create` |
| **Déploiement** | |
| Créer un admin (+ init rôles) | `php bin/console app:create-admin --email=... --password=...` |
| Reset mot de passe admin | `php bin/console app:reset-admin` |
| **Tests** | |
| Lancer tous les tests | `php bin/phpunit --no-coverage` |
| Lancer uniquement les tests unitaires | `php bin/phpunit tests/Unit --no-coverage` |
| Lancer un fichier précis | `php bin/phpunit tests/Unit/Entity/CommandeTest.php --no-coverage` |
| Lancer un test précis | `php bin/phpunit --filter testCalculerPrixMenuAvecRemise --no-coverage` |
| **Frontend (Vue CLI)** — depuis `vue-app/` | |
| Installer les dépendances | `cd vue-app && npm ci` |
| Lancer le dev server (HMR sur :8082) | `cd vue-app && npm run serve` |
| Builder pour la prod (→ `public/build/`) | `cd vue-app && npm run build` |
| Vérifier le manifest généré | `cat public/build/manifest.json` |
| **Qualité & Debug** | |
| Vider le cache | `php bin/console cache:clear` |
| Voir les routes | `php bin/console debug:router` |
| Voir la config asset packages | `php bin/console debug:config framework assets` |
| Voir les logs Docker | `docker compose logs -f` |

> En mode Docker complet, préfixez les commandes par `docker compose exec php`.

---

## 🧪 Tests PHPUnit

### Structure des tests

```
tests/
├── bootstrap.php                              ← Chargement Symfony (APP_ENV=test)
└── Unit/                                      ← Tests sans BDD ni kernel
    ├── Entity/
    │   ├── AvisTest.php
    │   ├── AllergeneTest.php
    │   ├── CommandeTest.php
    │   ├── PlatTest.php
    │   ├── RegimeTest.php
    │   ├── ResetPasswordRequestTest.php
    │   ├── RoleTest.php
    │   ├── ThemeTest.php
    │   └── UtilisateurTest.php
    ├── Document/
    │   └── HoraireTest.php
    ├── Service/
    │   └── CommandeMailerServiceTest.php
    └── Twig/
        ├── HoraireExtensionTest.php
        └── ContenuSiteExtensionTest.php
```

### Fichiers à lire pour apprendre PHPUnit (commentaires tutoriels)

Ces fichiers contiennent des commentaires pédagogiques détaillés. À lire dans cet ordre :

1. `tests/Unit/Entity/AvisTest.php`
2. `tests/Unit/Entity/CommandeTest.php`
3. `tests/Unit/Entity/PlatTest.php`
4. `tests/Unit/Document/HoraireTest.php`
5. `tests/Unit/Entity/UtilisateurTest.php`
6. `tests/Unit/Entity/ResetPasswordRequestTest.php`
7. `tests/Unit/Service/CommandeMailerServiceTest.php`
8. `tests/Unit/Twig/HoraireExtensionTest.php`
9. `tests/Unit/Twig/ContenuSiteExtensionTest.php`

### Fichiers en style production (sans commentaires tutoriels)

- `tests/Unit/Entity/RoleTest.php`
- `tests/Unit/Entity/AllergeneTest.php`
- `tests/Unit/Entity/RegimeTest.php`
- `tests/Unit/Entity/ThemeTest.php`

> **📍 Note de déploiement prod (Opcache)** : passer `validate_timestamps=0` dans `docker/php/opcache.ini` pour désactiver la vérification des fichiers à chaque requête (gain de performance significatif). En contrepartie, vider obligatoirement l'opcache à chaque déploiement via `php bin/console cache:clear`.

---

## 🔒 Sécurité & Gestion des secrets

- Le fichier `.env` commité ne contient **aucun secret** (uniquement des placeholders `!ChangeMe!`).
- Les vraies valeurs sont dans `.env.local` qui est **exclu de Git** via `.gitignore`.
- Les credentials dans `compose.yaml` sont des **valeurs de développement local** uniquement. En production, utilisez des variables d'environnement système ou `composer dump-env prod`.
- `APP_SECRET` est vide dans `.env` et doit être défini dans `.env.local` (dev) ou via une variable d'environnement (prod).

---

## 📝 Licence

Projet réalisé dans le cadre d'un ECF (Evaluation de Compétences en Formation) — Studi.
