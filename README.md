# Vite-et-Gourmand

Courte description du projet  
(contexte, objectif, périmètre fonctionnel).

---

## Prérequis

- PHP >= 8.4.6
- Composer version 2.8.8
- Symfony CLI
- Base de données PostgreSQL

- Git
- Symfony LTS 7.4

---

## Choix technique

PostgreSQL :
 - Intégration simple et adaptée avec Doctrine ORM
 - Correspond à des projets complexes avec gestion des droits
 - Gere en natif les uuid (+ de securité)
 
Docker :
 - Premiere occasion d'utiliser Docker "grandeur nature"
 - Pour la portabilité totale
 - reset DB instantanément
 - recréer un environnement clean
 
---

## Installation

```bash
git clone git@github.com:PhilHika/Vite-Gourmand.git
cd Vite-et-Gourmand
composer install
```

## Lancer le serveur
symfony serve
puis
url : http://localhost:8080


