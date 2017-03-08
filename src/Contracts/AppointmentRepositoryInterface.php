<?php

namespace LibreEHR\Core\Contracts;

use LibreEHR\Core\Emr\Criteria\AbstractCriteria;

interface AppointmentRepositoryInterface extends RepositoryInterface
{
    public function create( AppointmentInterface $appointmentInterface );
    public function decodeStatus($status);
    public function encodeStatus($status);
}