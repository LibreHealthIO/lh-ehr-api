<?php

namespace LibreEHR\Core\Contracts;

interface ModelInterface extends BaseInterface
{
    public function setConnection( $databaseKey );
}