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

    public function getPractice()
    {
        return $this->practice;
    }

    public function setPractice($practice)
    {
        $this->practice = $practice;
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

    public function getName()
    {
        // TODO @leo: Implement getName() method.
    }

    public function getCode()
    {
        // TODO @leo : Implement getCode() method.

    }
}
