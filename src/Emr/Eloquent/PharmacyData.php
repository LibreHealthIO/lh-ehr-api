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
        return $this->pharmacy_name;
    }

    public function setName($name)
    {
        $this->pharmacy_name = $name;
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

    public function getTown()
    {
        return $this->town;
    }

    public function setTown($town)
    {
        $this->town = $town;
        return $this;
    }

    public function getState()
    {
        return $this->state;
    }

    public function setState($state)
    {
        $this->state = $state;
        return $this;
    }

    public function getCountry()
    {
        return $this->country;
    }

    public function setCountry($country)
    {
        $this->country = $country;
        return $this;
    }

    public function getRegisteredStatus()
    {
        return $this->registered_status;
    }

    public function setRegisteredStatus($registeredStatus)
    {
        $this->registered_status = $registeredStatus;
        return $this;
    }

    public function getCode()
    {
        $pharmacy['id'] = $this->getId();
        $pharmacy['name'] = $this->getName();
        $pharmacy['address'] = $this->getAddress() . ', '
                             . $this->getTown() . ', '
                             . $this->getState() . ', '
                             . $this->getCountry();
        return $pharmacy;
    }

    public function getEmrId()
    {
        return $this->emr_id;
    }
    public function setEmrId($emrId)
    {
        $this->emr_id = $emrId;
        return $this;
    }
}
