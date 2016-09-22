<?php

namespace LibreEHR\Core\Contracts;

use LibreEHR\Core\Emr\Criteria\AbstractCriteria;

interface PharmacyRepositoryInterface extends RepositoryInterface
{
    public function create(PharmacyInterface $pharmacyInterface);
}
