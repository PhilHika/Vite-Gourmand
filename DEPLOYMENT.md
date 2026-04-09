# Guide de déploiement - Vite & Gourmand

## 📋 Prérequis

- PHP 8.4+
- PostgreSQL 16+
- MongoDB (optional, pour les horaires)
- Git
- Composer

---

## 🚀 Déploiement en production

### 1. Cloner le dépôt

```bash
git clone https://github.com/your-repo/vite-et-gourmand.git
cd vite-et-gourmand
```

### 2. Créer `.env.prod.local` avec les credentials réels

**⚠️ CRITIQUE** : Ce fichier **NE DOIT PAS** être commité (voir `.gitignore`)

```bash
# Sur le serveur
cp .env.prod.local.example .env.prod.local
nano .env.prod.local  # Éditer avec vos vraies credentials
```

Fichier `.env.prod.local` doit contenir :

```env
###> symfony/framework-bundle ###
APP_SECRET=your-super-secret-key-min-32-chars
###< symfony/framework-bundle ###

###> doctrine/doctrine-bundle ###
DATABASE_URL="postgresql://user:password@hostname:5432/vite_gourmand_prod?serverVersion=16&charset=utf8"
###< doctrine/doctrine-bundle ###

###> symfony/mailer ###
# Choisir selon votre serveur SMTP
MAILER_DSN="smtp://email:password@smtp-server:587?encryption=tls"
###< symfony/mailer ###

###> doctrine/mongodb-odm-bundle ###
MONGO_USER=mongo_user
MONGO_PASSWORD=mongo_password
MONGODB_URI=mongodb://mongo_user:mongo_password@mongo-server:27017
MONGODB_DBNAME=vite_gourmand
###< doctrine/mongodb-odm-bundle ###
```

### 3. Installer les dépendances

```bash
composer install --no-dev --optimize-autoloader
```

### 4. Configurer la base de données

```bash
# Créer les tables
php bin/console doctrine:database:create --env=prod
php bin/console doctrine:migrations:migrate --env=prod --no-interaction

# Charger les données de référence
php bin/console doctrine:fixtures:load --env=prod --no-interaction
```

### 5. Générer les assets

```bash
php bin/console asset-map:compile --env=prod
```

### 6. Vider le cache

```bash
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod
```

### 7. Configurer le serveur web

#### Nginx

```nginx
server {
    listen 80;
    server_name vite-et-gourmand.fr;

    root /var/www/vite-et-gourmand/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass php:9000;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        fastcgi_param APP_ENV prod;
    }

    # Sécurité
    location ~ /\. {
        deny all;
    }
}
```

#### Apache

```apache
<VirtualHost *:80>
    ServerName vite-et-gourmand.fr
    DocumentRoot /var/www/vite-et-gourmand/public

    <Directory /var/www/vite-et-gourmand/public>
        AllowOverride All
        Require all granted
        <IfModule mod_rewrite.c>
            RewriteEngine On
            RewriteCond %{REQUEST_FILENAME} !-f
            RewriteCond %{REQUEST_FILENAME} !-d
            RewriteRule ^(.*)$ index.php [QSA,L]
        </IfModule>
    </Directory>

    SetEnv APP_ENV prod
</VirtualHost>
```

---

## 📧 Configuration Email en production

### Mailer (Symfony Mailer)

Le DSN SMTP est défini dans `.env.prod.local` via `MAILER_DSN`.

**Serveurs recommandés :**

#### OVH

```env
MAILER_DSN="smtp://email@domain.fr:password@smtp.ovh.net:465?encryption=ssl"
```

#### Gmail (avec App Password)

```env
MAILER_DSN="smtp://your-email%40gmail.com:your-app-password@smtp.gmail.com:587?encryption=tls"
```

#### SendGrid

```env
MAILER_DSN="smtp://apikey:SG.xxxxxxxxxxxxxxxxxxxxx@smtp.sendgrid.net:587?encryption=tls"
```

#### AWS SES

```env
MAILER_DSN="smtp://AKIA2XXXXXXXXXX:XXXXXXXXXXXXXX@email.eu-west-1.amazonaws.com:587?encryption=tls"
```

**Test d'envoi :**

```bash
php bin/console mailer:test to@example.com --env=prod
```

---

## 🔒 Sécurité - Checklist

- [ ] `.env.prod.local` **NE DOIT JAMAIS être commité**
- [ ] Vérifier que `.gitignore` contient `.env.prod.local`
- [ ] `APP_SECRET` est une chaîne aléatoire (min 32 caractères)
- [ ] Credentials SMTP stockés uniquement dans `.env.prod.local`
- [ ] HTTPS configuré (certificat SSL/TLS)
- [ ] Permissions fichiers correctes :
  ```bash
  chmod 755 public/
  chmod 755 var/
  chmod 644 .env.prod
  ```

---

## 📊 Structure des fichiers .env

```
.env                 ← Valeurs par défaut (MailHog dev)
  ↓
.env.prod            ← Config prod (placeholders, COMMITÉ)
  ↓
.env.prod.local      ← Credentials réels (NON COMMITÉ, sur serveur)
  ↑
Variables système    ← Peuvent surcharger tout
```

---

## 🔄 Mise à jour en production

```bash
git pull origin main
composer install --no-dev --optimize-autoloader
php bin/console doctrine:migrations:migrate --env=prod --no-interaction
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod
```

---

## 📝 Logs et monitoring

Les logs de production sont dans `var/log/prod.log` :

```bash
tail -f var/log/prod.log
```

Vérifier les emails envoyés :

```bash
grep "MailerInterface" var/log/prod.log
```

---

## 🆘 Troubleshooting

### Emails ne sont pas envoyés

1. Vérifier `MAILER_DSN` dans `.env.prod.local`
2. Tester la connexion SMTP :
   ```bash
   php bin/console mailer:test admin@vite-et-gourmand.fr --env=prod
   ```
3. Consulter `var/log/prod.log`

### Page blanche en prod

```bash
php bin/console cache:clear --env=prod --no-warmup
php bin/console cache:warmup --env=prod
```

### Base de données inaccessible

Vérifier `DATABASE_URL` dans `.env.prod.local`

```bash
php bin/console doctrine:database:create --env=prod
```

---

## 📞 Support

Pour toute question, consulter la [documentation Symfony](https://symfony.com/doc/current/deployment.html).
