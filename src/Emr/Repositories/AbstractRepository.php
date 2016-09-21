<?php

namespace LibreEHR\Core\Emr\Repositories;

use Illuminate\Support\Facades\App;
use LibreEHR\Core\Contracts\FinderInterface;
use LibreEHR\Core\Contracts\ModelInterface;
use LibreEHR\Core\Contracts\RepositoryInterface;

abstract class AbstractRepository implements RepositoryInterface
{
    /**
     * @var FinderInterface
     */
    protected $finder;

    /**
     * @param FinderInterface $finder
     */
    public function __construct(FinderInterface $finder)
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
    abstract public function model();

    /**
     * Get instance of model
     *
     * @return mixed
     */
    public function makeModel()
    {
        return App::make($this->model());
    }

    /**
     * @param ModelInterface $model
     */
    public function execute(ModelInterface $model)
    {
        try {
            // TODO this is leaky abstraction, depends on Eloquent, should be pushed into child class
            $result = $model->firstOrFail();
        } catch (ErrorException $e) {
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
        foreach ($this->finder->getCriteria() as $criteria) {
            $model = $criteria->apply($model);
        }
        return $this->onAfterFind($this->execute($model));
    }

    public function onAfterFind($entity)
    {
        return $entity;
    }
}
