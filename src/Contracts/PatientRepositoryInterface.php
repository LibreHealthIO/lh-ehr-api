<?php

namespace LibreEHR\Core\Contracts;

use LibreEHR\Core\Emr\Criteria\AbstractCriteria;

interface PatientRepositoryInterface extends RepositoryInterface
{
    public function find( AbstractCriteria $criteria );
    public function create( PatientInterface $patientInterface );
}