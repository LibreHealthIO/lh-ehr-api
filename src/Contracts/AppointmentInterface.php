<?php

namespace LibreEHR\Core\Contracts;

interface AppointmentInterface extends ModelInterface
{
    public function getStartTime();
    public function setStartTime($startTime);
    public function getEndTime();
    public function setEndTime($endTime);
}
