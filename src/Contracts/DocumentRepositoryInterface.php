<?php

namespace LibreEHR\Core\Contracts;

use LibreEHR\Core\Emr\Criteria\AbstractCriteria;

interface DocumentRepositoryInterface extends RepositoryInterface
{
    public function find( AbstractCriteria $criteria );
    public function create( DocumentInterface $documentInterface );
}