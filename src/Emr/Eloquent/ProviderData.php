<?php

namespace LibreEHR\Core\Emr\Eloquent;

use Illuminate\Database\Eloquent\Model;
use LibreEHR\Core\Contracts\ProviderInterface;
use LibreEHR\Core\Contracts\ValueSetInterface;

class ProviderData extends Model implements ProviderInterface, ValueSetInterface
{
    protected $connection = 'auth';
    
    protected $table = 'provider';

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

    public function getFirstName()
    {
        return $this->firstname;
    }

    public function setFirstName($firstName)
    {
        $this->firstname = $firstName;
        return $this;
    }

    public function getLastName()
    {
        return $this->lastname;
    }

    public function setLastName($lastName)
    {
        $this->lastname = $lastName;
        return $this;
    }

    public function getEmailAddress()
    {
        return $this->email;
    }

    public function setEmailAddress($emailAddress)
    {
        $this->email = $emailAddress;
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

    public function getName()
    {
        return $this->practice_name;
    }

    public function setName($practiceName)
    {
        $this->practice_name = $practiceName;
        return $this;
    }

    public function getCode()
    {
        $provider['id'] = uniqid($this->getId());
        $provider['name'] = $this->getName();
        $provider['practiceName'] = $this->getName();
        $provider['address'] = $this->getAddress() . ', '
                             . $this->getTown() . ', '
                             . $this->getState() . ', '
                             . $this->getCountry();
        return $provider;
    }
}
