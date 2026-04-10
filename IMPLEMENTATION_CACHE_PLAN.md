# 📋 Plan d'implémentation du Cache SQL — Résumé Exécutif

**Date** : 2026-04-06  
**Status** : À implémenter  
**Durée estimée** : 3-4h (incluant tests)

---

## 📍 Architecture décidée

```
QueryCacheService (cœur)
    ↓
Repository::methodCached() (délégation)
    ↓
Controller::action (appel simple)
    + Invalidation auto (Doctrine EventListener)
```

**Choix backend** : Redis (distribué, persistent, compatible MongoDB)  
**Choix invalidation** : Doctrine PostPersist/PostUpdate/PostRemove events

---

## 🎯 Cibles de cache (validées)

### ✅ À cacher (7 cibles)
- [ ] **HomeController::index** → `findBy(['statut' => PUBLIE])` — TTL 1h
- [ ] **MenuController::index** (sans filtres) → `findAll()` — TTL 30min
- [ ] **MenuController::show** → `find($id)` — TTL 1h (via ParamConverter)
- [ ] **AdminReferentielController::list*** → `findAll()` GET — TTL 24h
- [ ] **Twig Extension Horaires** → `findAll()` — TTL 1h (appelée chaque page)
- [ ] **Twig Extension Contenu** → `findByCle()` — TTL 1h
- [ ] **Images plats** → Déjà filesystem ✓ (pas de cache BDD)

### ❌ PAS de cache
- MenuController::index avec filtres (clé cache changerait)
- CommandeController (données en temps réel)
- Tous CRUD admin (modifications fréquentes)
- ProfileController (données utilisateur)
- Security/Registration (authentification)

---

## 📁 Fichiers à créer

### 1️⃣ `src/Service/QueryCacheService.php` (⭐ CŒUR)

```php
<?php
namespace App\Service;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;

class QueryCacheService
{
    public function __construct(
        private readonly CacheItemPoolInterface $cache,
        private readonly LoggerInterface $logger,
        private readonly string $environment = 'prod'
    ) {}

    /**
     * Récupère un résultat en cache ou l'exécute
     * 
     * Exemple :
     *   $menus = $this->cache->getOrFetch(
     *       'menus_all',
     *       fn() => $this->findAll(),
     *       1800  // 30 min
     *   );
     */
    public function getOrFetch(
        string $cacheKey,
        \Closure $fetchCallback,
        int $ttl = 3600
    ): mixed {
        // Pas de cache en dev
        if ($this->environment === 'dev') {
            return $fetchCallback();
        }

        $item = $this->cache->getItem($cacheKey);

        if ($item->isHit()) {
            $this->logger->debug('Cache HIT', ['key' => $cacheKey]);
            return $item->get();
        }

        // Cache MISS : exécute le callback (requête SQL)
        $this->logger->debug('Cache MISS', ['key' => $cacheKey]);
        $result = $fetchCallback();

        // Sauvegarde en cache
        $item->set($result);
        $item->expiresAfter($ttl);
        $this->cache->save($item);

        return $result;
    }

    /**
     * Invalide une clé de cache
     */
    public function invalidate(string $cacheKey): void
    {
        $this->cache->deleteItem($cacheKey);
        $this->logger->info('Cache invalidated', ['key' => $cacheKey]);
    }

    /**
     * Invalide plusieurs clés
     */
    public function invalidateMultiple(array $cacheKeys): void
    {
        $this->cache->deleteItems($cacheKeys);
        $this->logger->info('Cache invalidated (bulk)', ['keys' => $cacheKeys]);
    }

    /**
     * Vide tout le cache (⚠️ À utiliser avec prudence)
     */
    public function clear(): void
    {
        $this->cache->clear();
        $this->logger->warning('Cache cleared entirely');
    }
}
```

**Service.yaml** (si pas auto-configuré) :
```yaml
services:
    App\Service\QueryCacheService:
        arguments:
            $cache: '@cache.app'
            $environment: '%kernel.environment%'
```

---

### 2️⃣ `src/EventListener/CacheInvalidationListener.php`

```php
<?php
namespace App\EventListener;

use App\Entity\Menu;
use App\Entity\Plat;
use App\Entity\Avis;
use App\Entity\Allergene;
use App\Entity\Regime;
use App\Entity\Theme;
use App\Document\Horaire;
use App\Document\ContenuSite;
use App\Service\QueryCacheService;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ODM\MongoDB\Event\PostPersistEventArgs as ODMPostPersistEventArgs;
use Doctrine\ODM\MongoDB\Event\PostUpdateEventArgs as ODMPostUpdateEventArgs;
use Doctrine\ODM\MongoDB\Event\PostRemoveEventArgs as ODMPostRemoveEventArgs;

class CacheInvalidationListener implements EventSubscriberInterface
{
    public function __construct(private readonly QueryCacheService $cache) {}

    public function getSubscribedEvents(): array
    {
        return [
            'postPersist',
            'postUpdate',
            'postRemove',
            'postPersist.odm', // MongoDB
            'postUpdate.odm',
            'postRemove.odm',
        ];
    }

    // --- ORM Events (MySQL) ---

    public function postPersist(PostPersistEventArgs $args): void
    {
        $this->invalidateByEntity($args->getObject());
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $this->invalidateByEntity($args->getObject());
    }

    public function postRemove(PostRemoveEventArgs $args): void
    {
        $this->invalidateByEntity($args->getObject());
    }

    // --- ODM Events (MongoDB) ---

    public function postPersistOdm(ODMPostPersistEventArgs $args): void
    {
        $this->invalidateByEntity($args->getDocument());
    }

    public function postUpdateOdm(ODMPostUpdateEventArgs $args): void
    {
        $this->invalidateByEntity($args->getDocument());
    }

    public function postRemoveOdm(ODMPostRemoveEventArgs $args): void
    {
        $this->invalidateByEntity($args->getDocument());
    }

    private function invalidateByEntity(object $entity): void
    {
        $keys = match ($entity::class) {
            Menu::class => ['menus_all', 'menus_with_availability'],
            Plat::class => ['plats_all'],
            Avis::class => ['avis_publis'],
            Allergene::class => ['allergenes_all'],
            Regime::class => ['regimes_all'],
            Theme::class => ['themes_all'],
            Horaire::class => ['horaires_all'],
            ContenuSite::class => ['contenu_description', 'contenu_conditions_vente'],
            default => [],
        };

        if (!empty($keys)) {
            $this->cache->invalidateMultiple($keys);
        }
    }
}
```

**services.yaml** (auto-configuré en Symfony 7.4) :
```yaml
services:
    App\EventListener\CacheInvalidationListener:
        tags:
            - { name: doctrine.event_subscriber }
            - { name: doctrine.odm.event_subscriber }
```

---

## 📝 Fichiers à modifier (méthodes à ajouter)

### 3️⃣ `src/Repository/MenuRepository.php`

```php
// Ajouter en classe :
private QueryCacheService $queryCacheService;

public function __construct(
    ManagerRegistry $registry,
    QueryCacheService $queryCacheService
) {
    parent::__construct($registry, Menu::class);
    $this->queryCacheService = $queryCacheService;
}

/**
 * Retourne TOUS les menus (en cache)
 * Utilisé par : MenuController::index (sans filtres)
 */
public function findAllCached(int $ttl = 1800): array
{
    return $this->queryCacheService->getOrFetch(
        'menus_all',
        fn() => $this->findAll(),
        $ttl
    );
}

/**
 * Retourne un menu par ID (en cache)
 * Utilisé par : MenuController::show
 */
public function findByIdCached(int $id, int $ttl = 3600): ?Menu
{
    return $this->queryCacheService->getOrFetch(
        "menu_id_{$id}",
        fn() => $this->find($id),
        $ttl
    );
}
```

### 4️⃣ `src/Repository/AvisRepository.php`

```php
private QueryCacheService $queryCacheService;

public function __construct(
    ManagerRegistry $registry,
    QueryCacheService $queryCacheService
) {
    parent::__construct($registry, Avis::class);
    $this->queryCacheService = $queryCacheService;
}

/**
 * Retourne les avis PUBLIÉS (en cache)
 * Utilisé par : HomeController::index
 */
public function findPubliesCached(int $ttl = 3600): array
{
    return $this->queryCacheService->getOrFetch(
        'avis_publis',
        fn() => $this->findBy(['statut' => Avis::STATUT_PUBLIE], ['id' => 'DESC']),
        $ttl
    );
}
```

### 5️⃣ `src/Repository/AllergeneRepository.php`

```php
private QueryCacheService $queryCacheService;

public function __construct(
    ManagerRegistry $registry,
    QueryCacheService $queryCacheService
) {
    parent::__construct($registry, Allergene::class);
    $this->queryCacheService = $queryCacheService;
}

/**
 * Retourne TOUS les allergènes (en cache 24h)
 * Utilisé par : AdminReferentielController::listAllergenes (GET)
 */
public function findAllCached(int $ttl = 86400): array
{
    return $this->queryCacheService->getOrFetch(
        'allergenes_all',
        fn() => $this->findAll(),
        $ttl
    );
}
```

### 6️⃣ `src/Repository/RegimeRepository.php`

```php
private QueryCacheService $queryCacheService;

public function __construct(
    ManagerRegistry $registry,
    QueryCacheService $queryCacheService
) {
    parent::__construct($registry, Regime::class);
    $this->queryCacheService = $queryCacheService;
}

/**
 * Retourne TOUS les régimes (en cache 24h)
 * Utilisé par : AdminReferentielController::listRegimes (GET)
 */
public function findAllCached(int $ttl = 86400): array
{
    return $this->queryCacheService->getOrFetch(
        'regimes_all',
        fn() => $this->findAll(),
        $ttl
    );
}
```

### 7️⃣ `src/Repository/ThemeRepository.php`

```php
private QueryCacheService $queryCacheService;

public function __construct(
    ManagerRegistry $registry,
    QueryCacheService $queryCacheService
) {
    parent::__construct($registry, Theme::class);
    $this->queryCacheService = $queryCacheService;
}

/**
 * Retourne TOUS les thèmes (en cache 24h)
 * Utilisé par : AdminReferentielController::listThemes (GET)
 */
public function findAllCached(int $ttl = 86400): array
{
    return $this->queryCacheService->getOrFetch(
        'themes_all',
        fn() => $this->findAll(),
        $ttl
    );
}
```

### 8️⃣ MongoDB Repositories (Horaire, ContenuSite)

**`src/Repository/HoraireRepository.php`** :
```php
private QueryCacheService $queryCacheService;

public function __construct(
    DocumentManager $dm,
    QueryCacheService $queryCacheService
) {
    parent::__construct($dm, Horaire::class);
    $this->queryCacheService = $queryCacheService;
}

public function findAllCached(int $ttl = 3600): array
{
    return $this->queryCacheService->getOrFetch(
        'horaires_all',
        fn() => $this->findAll(),
        $ttl
    );
}
```

**`src/Repository/ContenuSiteRepository.php`** :
```php
private QueryCacheService $queryCacheService;

public function __construct(
    DocumentManager $dm,
    QueryCacheService $queryCacheService
) {
    parent::__construct($dm, ContenuSite::class);
    $this->queryCacheService = $queryCacheService;
}

public function findByCleCached(string $cle, int $ttl = 3600): ?ContenuSite
{
    return $this->queryCacheService->getOrFetch(
        "contenu_{$cle}",
        fn() => $this->findByCle($cle),
        $ttl
    );
}
```

---

## 🔧 Controllers à modifier

### 9️⃣ `src/Controller/HomeController.php`

```php
// Changer :
$tousLesAvisPublies = $avisRepository->findBy(
    ['statut' => \App\Entity\Avis::STATUT_PUBLIE],
    ['id' => 'DESC']
);

// En :
$tousLesAvisPublies = $avisRepository->findPubliesCached(ttl: 3600);
```

### 🔟 `src/Controller/MenuController.php`

```php
// index() : adapter le filtrage
if ($filterForm->isSubmitted() && $filterForm->isValid()) {
    $menus = $menuRepository->findByFilters($filterForm->getData());
} else {
    $menus = $menuRepository->findAllCached(ttl: 1800); // Cache 30min
}

// show() : laisser ParamConverter Doctrine (OK)
// Doctrine identity map gère déjà le cache en session
```

### 1️⃣1️⃣ `src/Controller/AdminReferentielController.php`

```php
// listAllergenes()
public function listAllergenes(AllergeneRepository $repository): JsonResponse
{
    $list = $repository->findAllCached(ttl: 86400); // Cache 24h
    // ... reste inchangé
}

// listRegimes()
public function listRegimes(RegimeRepository $repository): JsonResponse
{
    $list = $repository->findAllCached(ttl: 86400);
    // ... reste inchangé
}

// listThemes()
public function listThemes(ThemeRepository $repository): JsonResponse
{
    $list = $repository->findAllCached(ttl: 86400);
    // ... reste inchangé
}
```

### 1️⃣2️⃣ `src/Twig/HorairesExtension.php` (à créer ou adapter)

```php
<?php
namespace App\Twig;

use App\Repository\HoraireRepository;
use App\Service\QueryCacheService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class HorairesExtension extends AbstractExtension
{
    public function __construct(
        private readonly HoraireRepository $horaireRepository,
        private readonly QueryCacheService $cache
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('horaires', [$this, 'getHoraires']),
        ];
    }

    public function getHoraires(): array
    {
        return $this->horaireRepository->findAllCached(ttl: 3600);
    }
}
```

### 1️⃣3️⃣ `src/Twig/ContenuExtension.php` (à créer ou adapter)

```php
<?php
namespace App\Twig;

use App\Repository\ContenuSiteRepository;
use App\Service\QueryCacheService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ContenuExtension extends AbstractExtension
{
    public function __construct(
        private readonly ContenuSiteRepository $contenuRepository,
        private readonly QueryCacheService $cache
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('contenu', [$this, 'getContenu']),
        ];
    }

    public function getContenu(string $cle): ?string
    {
        $document = $this->contenuRepository->findByCleCached($cle, ttl: 3600);
        return $document?->getContenu();
    }
}
```

---

## ⚙️ Configuration (config/services.yaml / config/packages/cache.yaml)

**Rien à faire si redis est déjà configuré** !  
Vérifier juste que vous avez :

```yaml
# config/packages/cache.yaml
framework:
    cache:
        app: cache.adapter.redis
        default_redis_provider: 'redis://localhost:6379'
```

ou pour APCu (simple, mono-serveur) :

```yaml
framework:
    cache:
        app: cache.adapter.apcu
```

---

## ✅ Checklist d'implémentation

- [ ] Créer `QueryCacheService.php`
- [ ] Créer `CacheInvalidationListener.php`
- [ ] Ajouter `findAllCached()` à MenuRepository
- [ ] Ajouter `findPubliesCached()` à AvisRepository
- [ ] Ajouter `findAllCached()` à AllergeneRepository
- [ ] Ajouter `findAllCached()` à RegimeRepository
- [ ] Ajouter `findAllCached()` à ThemeRepository
- [ ] Ajouter `findAllCached()` à HoraireRepository
- [ ] Ajouter `findByCleCached()` à ContenuSiteRepository
- [ ] Modifier HomeController::index
- [ ] Modifier MenuController::index
- [ ] Modifier AdminReferentielController (3 méthodes)
- [ ] Créer/adapter HorairesExtension
- [ ] Créer/adapter ContenuExtension
- [ ] Tester invalidation auto (créer/modifier/supprimer une entité)
- [ ] Tester en dev (cache désactivé)
- [ ] Tester en prod (cache activé)

---

## 🧪 Tests à effectuer

```php
// Test 1: Vérifier que le cache marche
// Charger MenuController::index 2x, vérifier 1 requête SQL seulement en prod

// Test 2: Vérifier invalidation auto
// Créer un nouveau Menu en admin → vérifier que cache 'menus_all' est vidé

// Test 3: Vérifier TTL
// Ajouter sleep(30) en dev, relancer → doit recharger

// Test 4: Vérifier filtres non cachés
// Appliquer un filtre → doit faire la requête (pas de cache)
```

---

## 💡 Points critiques à attention

1. **Constructeur des repositories** : Ajouter `QueryCacheService` en DI
2. **Ne PAS cacher les résultats filtrés** (clé cache changerait)
3. **Horaires/Contenu** : Via extensions Twig, PAS directement en controller
4. **Invalidation** : Laisser le listener s'en charger (pas de appels manuels)
5. **Dev vs Prod** : `QueryCacheService` désactive cache en dev
6. **TTL** : Adapter par type (courtes : menus/horaires, longues : référentiels)

---

## 📊 Gain estimé

| Cible | Avant | Après | Gain |
|-------|-------|-------|------|
| Page d'accueil (avis) | 1 query | 0 query (cache) | ⚡⚡⚡ |
| Listing menus | 1 query | 0 query | ⚡⚡⚡ |
| Fiche menu | 1 query | 0 query | ⚡⚡ |
| Extensions (par page) | +3-4 queries | 0 query | ⚡⚡⚡⚡ |
| **Total** | ↓ requêtes | ✅ 50-70% réduction |

