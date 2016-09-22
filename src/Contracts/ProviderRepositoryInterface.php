<?php

namespace LibreEHR\Core\Contracts;

use LibreEHR\Core\Emr\Criteria\AbstractCriteria;

interface ProviderRepositoryInterface extends RepositoryInterface
{
    public function create(ProviderInterface $providerInterface);
}
