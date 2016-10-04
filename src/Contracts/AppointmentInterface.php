<?php

namespace LibreEHR\Core\Contracts;

interface AppointmentInterface extends ModelInterface
{
    public function getStartTime();
    public function setStartTime( $startTime );
    public function getEndTime();
    public function setEndTime( $endTime );

    public function getPatientId();
    public function setPatientId( $patientId );
    public function getProviderId();
    public function setProviderId( $providerId );


}
