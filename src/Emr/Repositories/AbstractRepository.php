<?php

namespace LibreEHR\Core\Repository;

use LibreEHR\Core\Contracts\FinderInterface;
use LibreEHR\Core\Contracts\ModelInterface;

abstract class AbstractRepository implements RepositoryInterface
{
    /**
     * @var FinderInterface
     */
    protected $finder;

    /**
     * @param FinderInterface $finder
     */
    public function __construct( FinderInterface $finder )
    {
        $this->finder = $finder;
    }

    public function finder()
    {
        return $this->finder;
    }

    /**
     * Specify Model class name
     *
     * @return mixed
     */
    public abstract function model();

    /**
     * Get instance of model
     *
     * @return mixed
     */
    public abstract function makeModel();

    /**
     * @param ModelInterface $model
     */
    public function execute( ModelInterface $model )
    {
        try {
            // TODO this is leaky abstraction, depends on Eloquent, should be pushed into child class
            $result = $model->all();
        } catch ( ErrorException $e ) {
            // TODO Do stuff if it doesn't exist.
        }

        return $result;
    }

    /**
     * @return ModelInterface
     *
     * TODO implement find with result filter (to only return a partial model)
     *
     */
    public function find()
    {
        $model = $this->makeModel();
        foreach ( $this->finder->getCriteria() as $criteria ) {
            $model = $criteria->apply( $model );
        }
        $entity = $this->execute( $model );
        $entity = $this->onAfterFind( $entity );
        return $entity;
    }

    public function onAfterFind( $entity )
    {
        return $entity;
    }
}