# 🍽️ Vite-et-Gourmand

Application de gestion de commandes de menus traiteur, développée avec Symfony 7.4 et une stack Docker complète.

---

## 🚀 Stack Technique

- **Framework** : Symfony 7.4 LTS (PHP 8.4)
- **Serveur Web** : Nginx (Alpine)
- **Base de données relationnelle** : PostgreSQL 18 (Doctrine ORM)
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
- **MongoDB** (Doctrine ODM) : données documentaires (Horaires d'ouverture) dans `src/Document/`

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

Les corps d'emails sont construits en HTML directement dans les services (pas de templates Twig séparés).

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

### 1. Cloner et configurer

```bash
git clone git@github.com:PhilHika/Vite-Gourmand.git
cd Vite-et-Gourmand
cp .env .env.local  # puis éditer .env.local avec vos valeurs
```

### 2. Variables d'environnement (`.env.local`)

| Variable | Description | Exemple |
| :--- | :--- | :--- |
| `APP_SECRET` | Clé secrète Symfony | chaîne aléatoire 32 chars |
| `DATABASE_URL` | DSN PostgreSQL | `postgresql://user:pass@db:5432/vite_gourmand` |
| `MONGODB_URI` | URI MongoDB | `mongodb://root:password@mongodb:27017` |
| `MONGODB_DB` | Nom de la base MongoDB | `vite_gourmand` |
| `MAILER_DSN` | Transport email | `smtp://localhost:1025` (dev) |

### 3. Démarrer l'environnement

**Mode Docker complet (recommandé) :**
```bash
docker compose up -d --build
docker compose exec php composer install
```

**Mode rapide (DB Docker + PHP local) :**
```bash
docker compose up -d db mongodb
symfony serve --port=8080
```

### 4. Initialiser la base de données

```bash
# PostgreSQL : migrations + données initiales
docker compose exec php php bin/console doctrine:migrations:migrate
docker compose exec php php bin/console doctrine:fixtures:load

# MongoDB : créer le schéma
docker compose exec php php bin/console doctrine:mongodb:schema:create
```

> Les fixtures créent uniquement les **3 rôles** (`ROLE_USER`, `ROLE_SALARIE`, `ROLE_ADMIN`) en base. Les comptes utilisateurs sont à créer manuellement via l'interface `/register`.

### 5. Commandes Utiles

| Action | Commande |
| :--- | :--- |
| **PostgreSQL (ORM)** | |
| Créer une migration | `docker compose exec php php bin/console make:migration` |
| Appliquer les migrations | `docker compose exec php php bin/console doctrine:migrations:migrate` |
| Charger les fixtures | `docker compose exec php php bin/console doctrine:fixtures:load` |
| Valider le schéma | `docker compose exec php php bin/console doctrine:schema:validate` |
| **MongoDB (ODM)** | |
| Créer le schéma MongoDB | `docker compose exec php php bin/console doctrine:mongodb:schema:create` |
| **Tests** | |
| Lancer les tests PHPUnit | `docker compose exec php php bin/phpunit` |
| **Qualité & Debug** | |
| Vider le cache | `docker compose exec php php bin/console cache:clear` |
| Voir les routes | `docker compose exec php php bin/console debug:router` |
| Accéder au conteneur PHP | `docker compose exec php bash` |
| Voir les logs | `docker compose logs -f` |

---

## 🌐 Accès aux Services

| Service | URL |
| :--- | :--- |
| **Application** | http://localhost:8080 |
| **Mongo Express** (Admin MongoDB) | http://localhost:8081 |
| **Mailpit** (Capture d'emails) | http://localhost:8025 |

---

## 📝 Licence

Projet réalisé dans le cadre d'un ECF (Evaluation de Compétences en Formation) — Studi.
