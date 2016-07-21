<?php

namespace LibreEHR\Core\Contracts;

interface PatientFinderInterface extends FinderInterface
{
    public function byLastName( $lastName );
    public function byPid( $pid );
}