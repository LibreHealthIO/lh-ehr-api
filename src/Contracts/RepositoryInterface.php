<?php

namespace LibreEHR\Core\Contracts;

use LibreEHR\Core\Emr\Criteria\AbstractCriteria;

interface RepositoryInterface
{
    public function find( AbstractCriteria $criteria );
}