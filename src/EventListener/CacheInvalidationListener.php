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
    public function __construct(private readonly QueryCacheService $cache)
    {
    }

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
