# 🍽️ Vite-et-Gourmand

Un projet Symfony 7.4 pour la gestion des commande de menus, avec l'appui d'une stack Docker optimisée.

---

## 🚀 Stack Technique

- **Framework** : Symfony 7.4 LTS (PHP 8.4)
- **Serveur Web** : Nginx (Alpine)
- **Base de données** : PostgreSQL 18
- **Outils** : Mailpit (Capture d'emails), Composer 2.8

---

## 🛠️ Installation avec Docker

### 1. Cloner le projet
```bash
git clone git@github.com:PhilHika/Vite-Gourmand.git
cd Vite-et-Gourmand
```

### 2. Configuration de l'environnement
Créez votre fichier local de secrets (non versionné) :
```bash
cp .env .env.local
```
> [!IMPORTANT]
> Modifiez le fichier `.env.local` pour y définir vos identifiants personnalisés (Database, App Secret, etc.).

### 3. Lancer la Stack
```bash
docker compose up -d --build
```

### 4. Installer les dépendances
```bash
docker compose exec php composer install
```

---

## 💡 Pourquoi ce choix technique ?

- **PostgreSQL** : Pour sa gestion native des UUID, son intégration parfaite avec Doctrine ORM et sa robustesse sur des projets complexes.
- **Docker** :
  - **Portabilité** : Environnement identique pour tous les développeurs.
  - **Performance** : PHP optimisé avec Opcache et Nginx configuré pour Symfony.
  - **Flexibilité** : Reset de la base de données et des services en une commande.

---

## 📖 Commandes Utiles

| Action | Commande |
| :--- | :--- |
| Voir les logs | `docker compose logs -f` |
| Créer une migration | `docker compose exec php php bin/console make:migration` |
| Appliquer les migrations | `docker compose exec php php bin/console doctrine:migrations:migrate` |
| Accéder au conteneur PHP | `docker compose exec php bash` |

---

## 🌐 Accès
- **Application** : [http://localhost:8080](http://localhost:8080)
- **Mailpit (Emails)** : [http://localhost:8025](http://localhost:8025)

---

## 📝 Licence
Projet réalisé dans le cadre d'un ECF.
