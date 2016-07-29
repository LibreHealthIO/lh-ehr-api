<?php

namespace LibreEHR\Core\Contracts;

interface AuditEventInterface extends ModelInterface
{
    public function getEventJson();
    public function setEventJson( $eventJson );

    public function getUsername();
    public function setUsername( $username );
}
