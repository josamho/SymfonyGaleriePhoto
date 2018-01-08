<?php

namespace AppBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * DestinationRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class PhotoRepository extends \Doctrine\ORM\EntityRepository
{
	/** récuperer les photos dans l'ordre de publication **/
	public function findPhotoByUser($page, $nbPerPage, $userid)
    {
        $query = $this->createQueryBuilder('p')
            ->join('p.user', 'u')
            ->where('u.id = :user')
            ->setParameter('user', $userid)
            ->orderBy('p.position')
            ->getQuery()
        ;

    $query->setFirstResult(($page-1) * $nbPerPage)
          ->setMaxResults($nbPerPage)
    ;

        return new Paginator($query, true);
        // return $qb->getQuery()->getResult();
    }
}