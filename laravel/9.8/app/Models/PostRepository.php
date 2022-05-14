<?php

namespace App\Models;

use Doctrine\ORM\EntityRepository;

/**
 * @method Post find()
 */
class PostRepository extends EntityRepository
{
    /**
     * @param ?int $number
     * @return Post[]
     */
    public function getRecentBugs($number = 30)
    {
        $dql = $this->getEntityManager()->createQuery('');
        $query = $this->getEntityManager()->createQuery($dql);
        $query->setMaxResults($number);
        return $query->getResult();
    }
}
