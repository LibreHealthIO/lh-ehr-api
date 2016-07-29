<?php

namespace LibreEHR\Core\Contracts;

interface FinderInterface
{
    public function pushCriteria( CriteriaInterface $criteria );

    public function getCriteria();
}
