<?php

namespace LibreEHR\Core\Emr\Eloquent;

use Illuminate\Database\Eloquent\Model;
use LibreEHR\Core\Contracts\PharmacyInterface;
use LibreEHR\Core\Contracts\ValueSetInterface;

class PharmacyData extends Model implements PharmacyInterface, ValueSetInterface
{
    protected $connection = 'auth';
    
    protected $table = 'pharmacy';

    protected $primaryKey = 'id';

    public $timestamps = false;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    public function getAddress()
    {
        return $this->address;
    }

    public function setAddress($address)
    {
        $this->address = $address;
        return $this;
    }

    public function getregisteredStatus()
    {
        return $this->registered_status;
    }

    public function setregisteredStatus($registeredStatus)
    {
        $this->registered_status = $registeredStatus;
        return $this;
    }

    public function getCode()
    {
        return $this->getId();
    }
}
