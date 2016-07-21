<?php

namespace LibreEHR\Core\Emr\Eloquent;

use Illuminate\Database\Eloquent\Model;
use LibreEHR\Core\Contracts\AuditEventInterface;

class AuditEvent extends Model implements AuditEventInterface
{
    protected $table = 'audit_events';

    public function getId()
    {
        return $this->id;
    }

    public function setId( $id )
    {
        $this->id = $id;
        return $this;
    }

    public function getEventJson()
    {
        return $this->event_json;
    }

    public function setEventJson( $eventJson )
    {
        $this->event_json = $eventJson;
        return $this;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function setUsername( $username )
    {
        $this->username = $username;
    }
}
