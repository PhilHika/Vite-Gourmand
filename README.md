# 🍽️ Vite-et-Gourmand

Un projet Symfony 7.4 pour la gestion des commande de menus, avec l'appui d'une stack Docker optimisée.

---

## 🚀 Stack Technique

- **Framework** : Symfony 7.4 LTS (PHP 8.4)
- **Serveur Web** : Nginx (Alpine)
- **Base de données** : PostgreSQL 18
- **Outils** : Mailpit (Capture d'emails), Composer 2.8

---

## 🏗️ Architecture & Modèle de Données

Le projet suit une architecture MVC classique avec Symfony, en mettant l'accent sur un domaine métier robuste.

### Schéma des Entités (Aperçu)

```mermaid
classDiagram
    class Utilisateur {
        +int id
        +string email
        +string password
        +array roles
        +string prenom
        +string telephone
    }
    class Commande {
        +string numero_commande (UUID)
        +datetime date_commande
        +datetime date_prestation
        +int nombre_personne
        +float prix_livraison
        +float prix_menu
        +string statut
    }
    class Menu {
        +int id
        +string titre
        +float prix_par_personne
        +int nombre_personne_minimum
    }
    class Plat {
        +int id
        +string titre_plat
        +blob photo
    }
    class Avis {
        +int id
        +string commentaire
        +int note
    }

```

---

## 💡 Philosophie de Développement

### 🛡️ Fail-Fast & Validation
Nous utilisons une approche "Fail-Fast" pour garantir l'intégrité des données dès le niveau des entités :
- **Validation Doctrine/Symfony** : Utilisation intensive des attributs `#[Assert]` pour la validation automatique (ex: email, longueurs, types).
- **Validation Métier** : Les setters des entités incluent des vérifications manuelles (ex: date de prestation > date de commande) et lèvent des `\InvalidArgumentException` immédiatement en cas d'erreur.

### Commande 🆔 Identifiants Uniques
- Utilisation de **UUID v4** + Date Reference : 
  Pour les numéros de commande (`Commande::numero_commande`), offrant une sécurité accrue et une meilleure portabilité des données.
  
### 🔑 Gestion des mots de passe (Entité Utilisateur)
- **Contrainte du cahier des charges** : Le schéma impose un `VARCHAR(50)` pour le mot de passe.
- **Problématique** : Les algorithmes de hachage par défaut de Symfony (BCrypt/Argon2) génèrent des empreintes de 60 caractères ou plus, ce qui provoquerait une troncature et rendrait l'authentification impossible.
- **Solution retenue** : Utilisation de l'algorithme `PBKDF2` avec `SHA-256` (encodage Base64). Cette configuration génère des empreintes de **44 caractères**, permettant de respecter strictement la contrainte de 50 caractères tout en conservant un système d'authentification fonctionnel et sécurisé.

---

## 🛠️ Installation & Workflow

### 1. Installation Rapide
```bash
git clone git@github.com:PhilHika/Vite-Gourmand.git
cd Vite-et-Gourmand
cp .env .env.local # Configurez vos variables
docker compose up -d --build
docker compose exec php composer install
```

### 2. Commandes Utiles

| Action | Commande |
| :--- | :--- |
| **Bases de données** | |
| Créer une migration | `docker compose exec php php bin/console make:migration` |
| Appliquer les migrations | `docker compose exec php php bin/console doctrine:migrations:migrate` |
| **Qualité & Debug** | |
| Vider le cache | `docker compose exec php php bin/console cache:clear` |
| Voir les routes | `docker compose exec php php bin/console debug:router` |
| Accéder au conteneur PHP | `docker compose exec php bash` |
| **Logs** | `docker compose logs -f` |

---

## 🌐 Accès aux Services
- **Application** : [http://localhost:8080](http://localhost:8080)
- **Mailpit (Emails)** : [http://localhost:8025](http://localhost:8025)

---

## 📝 Licence
Projet réalisé dans le cadre d'un ECF.
