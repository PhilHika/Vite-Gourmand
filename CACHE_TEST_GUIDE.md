# 🧪 Guide de Test du Système de Cache SQL

## Architecture implémentée

✅ **QueryCacheService** : Service de base pour get/set/invalidate le cache  
✅ **CacheInvalidationListener** : Auto-invalidation via Doctrine events  
✅ **Repositories cachés** : Menu, Avis, Allergène, Régime, Thème, Horaire, ContenuSite  
✅ **Controllers** : HomeController, MenuController, AdminReferentielController adaptés  
✅ **Twig Extensions** : HoraireExtension, ContenuSiteExtension avec cache  

---

## Tests à effectuer

### Test 1 : Cache des menus (MenuController::index)

**Étapes** :
1. Ouvrir `http://localhost:8000/menu` (1ère visite)
   - Logs attendus : "Cache MISS" pour 'menus_all'
2. Recharger la page (2ème visite)
   - Logs attendus : "Cache HIT" pour 'menus_all'

**Pour voir les logs** :
```bash
tail -f var/log/dev.log | grep -i cache
```

---

### Test 2 : Cache des avis (HomeController::index)

**Étapes** :
1. Ouvrir `http://localhost:8000/acceuil` (1ère visite)
   - Logs attendus : "Cache MISS" pour 'avis_publis'
2. Recharger la page (2ème visite)
   - Logs attendus : "Cache HIT" pour 'avis_publis'

---

### Test 3 : Invalidation auto (CRUD)

**Étapes** :
1. Aller sur `/admin/menu` (créer un nouveau menu)
2. Créer un menu
3. Retour à `/menu`
   - Le cache 'menus_all' doit être vidé automatiquement
   - Logs attendus : "Cache invalidated" suivi de "Cache MISS"

**Vérifier** :
```bash
grep "Cache invalidated" var/log/dev.log
```

---

### Test 4 : Filtres (MenuController::index avec filtres)

**Étapes** :
1. Aller sur `/menu`
2. Appliquer un filtre (ex: prix, thème)
   - **Pas de cache appliqué** (comportement attendu)
   - Doit faire une requête SQL directe

---

### Test 5 : Différence Dev vs Prod

**En dev** (cache=off):
```bash
php bin/console cache:test --env=dev
```
- Logs attendus : "Cache MISS" à chaque fois (pas de cache)

**En prod** (cache=on):
```bash
php bin/console cache:test --env=prod
```
- Logs attendus : "Cache MISS" puis "Cache HIT"

---

### Test 6 : Extensions Twig (Horaires, Contenu)

**Horaires** :
```twig
{{ get_horaires() }}
```
- 1ère visite : "Cache MISS" pour 'horaires_all'
- 2ème visite : "Cache HIT"

**Contenu** :
```twig
{{ get_description_site() }}
```
- 1ère visite : "Cache MISS" pour 'contenu_description'
- 2ème visite : "Cache HIT"

---

## Vérification des clés de cache

Les clés suivantes doivent être créées dans le cache :

- `menus_all` (TTL: 30min)
- `avis_publis` (TTL: 1h)
- `allergenes_all` (TTL: 24h)
- `regimes_all` (TTL: 24h)
- `themes_all` (TTL: 24h)
- `horaires_all` (TTL: 1h)
- `contenu_description` (TTL: 1h)
- `contenu_conditions_vente` (TTL: 1h)

---

## Commande de test rapide

```bash
php bin/console cache:test
```

Output attendu :
```
🧪 Test du système de cache SQL
========================

Test 1 : Cache des menus (findAllCached)
Appel 1 (MISS attendu)...
✓ Résultat : X menus
Appel 2 (HIT attendu)...
✓ Résultat : X menus
✓ Même objet en cache

[...]

✅ Tous les tests passés !
```

---

## Troubleshooting

### "Cache miss at every hit"
→ Cache désactivé en dev, c'est normal ! Testez en prod ou modifiez `QueryCacheService::getOrFetch()`.

### "Class not found: QueryCacheService"
→ Exec `php bin/console cache:clear`

### "LoggerInterface not injectable"
→ Le service `logger` doit être disponible. Vérifier `config/services.yaml`.

### "Impossible to clear cache manually"
→ Exec `php bin/console cache:pool:clear cache.app`

---

## Performance esperée

| Avant | Après | Gain |
|-------|-------|------|
| Homepage : 4 queries | 1 query | ⚡⚡⚡ |
| Menu listing : 2 queries | 1 query | ⚡⚡ |
| Admin referentiel : 3 queries | 1 query (cache 24h) | ⚡⚡⚡⚡ |

---

## Fichiers modifiés

### Créés
- `src/Service/QueryCacheService.php`
- `src/EventListener/CacheInvalidationListener.php`
- `src/Command/TestCacheCommand.php`

### Modifiés
- `src/Repository/MenuRepository.php` → `findAllCached()`, `findByIdCached()`
- `src/Repository/AvisRepository.php` → `findPubliesCached()`
- `src/Repository/AllergeneRepository.php` → `findAllCached()`
- `src/Repository/RegimeRepository.php` → `findAllCached()`
- `src/Repository/ThemeRepository.php` → `findAllCached()`
- `src/Repository/HoraireRepository.php` → `findAllCached()`
- `src/Repository/ContenuSiteRepository.php` → `findByCleCached()`
- `src/Controller/HomeController.php` → utilise `findPubliesCached()`
- `src/Controller/MenuController.php` → utilise `findAllCached()`
- `src/Controller/AdminReferentielController.php` → utilise `findAllCached()` x3
- `src/Twig/HoraireExtension.php` → utilise `findAllCached()`
- `src/Twig/ContenuSiteExtension.php` → utilise `findByCleCached()`
