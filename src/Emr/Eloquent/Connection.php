<?php

namespace LibreEHR\Core\Emr\Eloquent;

use LibreEHR\Core\Emr\Eloquent\AbstractModel as Model;

class Connection extends Model
{
    protected $connection = 'auth';

    protected $table = 'connections';

    public function providers()
    {
        return $this->belongsToMany( 'LibreEHR\Core\Emr\Eloquent\ProviderData', 'provider_connection', 'connection_id', 'provider_id' );
    }

    public function pharmacies()
    {
        return $this->belongsToMany( 'LibreEHR\Core\Emr\Eloquent\PharmacyData', 'pharmacy_connection', 'connection_id', 'pharmacy_id' );
    }
}
