<?php

namespace LibreEHR\Core\Emr\Eloquent;

use Illuminate\Database\Eloquent\Model;
use LibreEHR\Core\Contracts\AppointmentInterface;

class AppointmentData extends Model implements AppointmentInterface
{
    protected $table = 'openemr_postcalendar_events';

    protected $primaryKey = 'pc_eid';

    public $timestamps = false;


    public function getId()
    {
        return $this->pc_eid;
    }

    public function setId( $id )
    {
        $this->pc_eid = $id;
        return $this;
    }

    public function getStartTime()
    {
        return $this->pc_startTime;
    }

    public function setStartTime( $startTime )
    {
        $this->pc_startTime = $startTime;
        return $this;
    }

    public function getEndTime()
    {
        return $this->pc_endTime;
    }

    public function setEndTime( $endTime )
    {
        $this->pc_endTime = $endTime;
        return $this;
    }
}
