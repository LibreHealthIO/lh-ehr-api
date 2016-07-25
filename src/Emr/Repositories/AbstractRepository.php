<?php

namespace LibreEHR\Core\Repository;

use LibreEHR\Core\Emr\Criteria\AbstractCriteria;

abstract class AbstractRepository implements RepositoryInterface
{
    /**
     * @var Collection
     */
    protected $criteria;

    public function find( AbstractCriteria $criteria )
    {
        $entity = $criteria->execute();
        $entity = $this->onAfterFind( $entity );
        return $entity;
    }

    public function onAfterFind( $entity )
    {
        return $entity;
    }
}