<?php

namespace LibreEHR\Core\Emr\Eloquent;

use Illuminate\Database\Eloquent\Model;

abstract class AbstractModel extends Model
{
    protected $connectionName = 'mysql';

    public function setConnectionName( $name )
    {
        $this->connectionName = $name;
        $this->setConnection( $this->connectionName );
        return $this;
    }

    protected function setExists( $exists )
    {
        $this->exists = $exists;
        return $this;
    }
}