<?php

namespace LibreEHR\Core\Emr\Eloquent;

use Illuminate\Database\Eloquent\Model;
use LibreEHR\Core\Contracts\AppointmentInterface;

class AppointmentData extends Model implements AppointmentInterface
{
    protected $connection = 'mysql';
    
    protected $table = 'libreehr_postcalendar_events';

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
        return $this->getPcEventDate() . ' ' . $this->pc_startTime;
    }

    public function setStartTime( $startTime )
    {
        $this->pc_startTime = $startTime;
        return $this;
    }

    public function getEndTime()
    {
        return $this->getPcEventDate() . ' ' . $this->pc_endTime;
    }

    public function setEndTime( $endTime )
    {
        $this->pc_endTime = $endTime;
        return $this;
    }

    public function getPcEventDate()
    {
        return $this->pc_eventDate;
    }
    public function setPcEventDate($pcEventDate)
    {
        $this->pc_eventDate = $pcEventDate;
        return $this;
    }

    public function getPcApptStatus()
    {
        return $this->decodeStatus($this->pc_apptstatus);
    }
    public function setPcApptStatus($pcApptstatus)
    {
        $this->pc_apptstatus = $pcApptstatus;
        return $this;
    }

    public function getPcDuration()
    {
        return $this->pc_duration;
    }
    public function setPcDuration($pcDuration)
    {
        $this->pc_duration = $pcDuration;
        return $this;
    }

    public function getDescription()
    {
        return $this->pc_hometext;
    }
    public function setDescription($description)
    {
        $this->pc_hometext = $description;
        return $this;
    }

    public function getPcTime()
    {
        return $this->pc_time;
    }
    public function setPcTime($pcTime)
    {
        $this->pc_time = $pcTime;
        return $this;
    }

    public function getServiceType()
    {
        return $this->pc_title;
    }
    public function setServiceType($serviceType)
    {
        $this->pc_title = $serviceType;
        return $this;
    }

    public function getLocation()
    {
        return $this->pc_location;
    }
    public function setLocation($location)
    {
        $this->pc_location = $location;
        return $this;
    }

    private function decodeStatus($status)
    {
        switch($status) {
            case '+': $decodeStatus = 'Chart pulled';
                break;
            case 'x': $decodeStatus = 'Canceled';
                break;
            case '?': $decodeStatus = 'No show';
                break;
            case '@': $decodeStatus = 'Arrived';
                break;
            case '~': $decodeStatus = 'Arrived late';
                break;
            case '!': $decodeStatus = 'Left w/o visit';
                break;
            case '#': $decodeStatus = 'Ins/fin issue';
                break;
            case '<': $decodeStatus = 'In exam room';
                break;
            case '>': $decodeStatus = 'Checked out';
                break;
            case '$': $decodeStatus = 'Coding done';
                break;
            case '%': $decodeStatus = 'Canceled &lt; 24h';
                break;
            default: $decodeStatus = $status;
        }

        return $decodeStatus;
    }
}
