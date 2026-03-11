# 🍽️ Vite-et-Gourmand

Application de gestion de commandes de menus traiteur, développée avec Symfony 7.4 et une stack Docker complète.

---

## 🚀 Stack Technique

- **Framework** : Symfony 7.4 LTS (PHP 8.4)
- **Serveur Web** : Nginx (Alpine)
- **Base de données relationnelle** : PostgreSQL 16 (Doctrine ORM)
- **Base de données NoSQL** : MongoDB 7 (Doctrine ODM)
- **Outils** : Mailpit (Capture d'emails), Mongo Express (Admin MongoDB), Composer 2.8

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

Les emails sont gérés par des **services dédiés** dans `src/Service/` :

```
src/Service/
├── CommandeMailerService.php          ← Emails liés aux commandes
├── ContactMailerService.php           ← Emails liés au formulaire de contact
└── PasswordResetMailerService.php     ← Emails liés au reset de mot de passe
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

### 🛒 Système de Commande

#### Parcours utilisateur

1. **Connecté** : Clic sur "Commander" (menu ciblé) → Formulaire pré-rempli → Récapitulatif
2. **Non connecté** : Clic sur "Commander" → Modale d'invitation → Redirection automatique après login
3. **Accès direct** (`/commande/new`) : Sélection du menu dans le listing intégré → Formulaire

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
| `/admin/commande/` | Listing de toutes les commandes (tous clients) |
| `/admin/commande/{id}/edit` | Édition complète (statut, prix, dates, matériel) |

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

**Mode dev hybride (recommandé pour le développement) :**

Ce mode utilise Docker uniquement pour les bases de données et le serveur PHP natif de Symfony pour l'application. Il offre de meilleures performances et un rechargement plus rapide.

```bash
# Lancer les bases de données + Mongo Express
docker compose up -d db mongodb mongo-express

# Lancer le serveur Symfony
symfony serve -d
```

| Service | URL |
| :--- | :--- |
| Application (mode hybride) | http://localhost:8000 |
| Application (mode Docker) | http://localhost:8080 |
| Mongo Express (admin MongoDB) | http://localhost:8081 |
| Mailpit (capture d'emails) | http://localhost:8025 |

### 4. Initialiser les bases de données

```bash
# PostgreSQL : migrations + données initiales
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:fixtures:load --no-interaction

# MongoDB : créer le schéma
php bin/console doctrine:mongodb:schema:create
```

> Les fixtures créent les **3 rôles** (`ROLE_USER`, `ROLE_SALARIE`, `ROLE_ADMIN`) en base. Les comptes utilisateurs sont à créer via l'interface `/register`.

### 5. Commandes utiles

| Action | Commande |
| :--- | :--- |
| **PostgreSQL (ORM)** | |
| Créer une migration | `php bin/console make:migration` |
| Appliquer les migrations | `php bin/console doctrine:migrations:migrate` |
| Charger les fixtures | `php bin/console doctrine:fixtures:load` |
| Valider le schéma | `php bin/console doctrine:schema:validate` |
| **MongoDB (ODM)** | |
| Créer le schéma MongoDB | `php bin/console doctrine:mongodb:schema:create` |
| **Tests** | |
| Lancer les tests PHPUnit | `php bin/phpunit` |
| **Qualité & Debug** | |
| Vider le cache | `php bin/console cache:clear` |
| Voir les routes | `php bin/console debug:router` |
| Voir les logs Docker | `docker compose logs -f` |

> En mode Docker complet, préfixez les commandes par `docker compose exec php`.

---

## 🔒 Sécurité & Gestion des secrets

- Le fichier `.env` commité ne contient **aucun secret** (uniquement des placeholders `!ChangeMe!`).
- Les vraies valeurs sont dans `.env.local` qui est **exclu de Git** via `.gitignore`.
- Les credentials dans `compose.yaml` sont des **valeurs de développement local** uniquement. En production, utilisez des variables d'environnement système ou `composer dump-env prod`.
- `APP_SECRET` est vide dans `.env` et doit être défini dans `.env.local` (dev) ou via une variable d'environnement (prod).

---

## 📝 Licence

Projet réalisé dans le cadre d'un ECF (Evaluation de Compétences en Formation) — Studi.
