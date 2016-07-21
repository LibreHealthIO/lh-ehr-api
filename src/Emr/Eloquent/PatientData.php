<?php

namespace LibreEHR\Core\Emr\Eloquent;

use Illuminate\Database\Eloquent\Model;
use LibreEHR\Core\Contracts\PatientInterface;

class PatientData extends Model implements PatientInterface
{
    protected $table = 'patient_data';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected  $photo = null;

    public function getPid()
    {
        return $this->pid;
    }

    public function setPid( $pid )
    {
        $this->pid = $pid;
        return $this;
    }


    public function getId()
    {
        return $this->id;
    }

    public function setId( $id )
    {
        $this->id = $id;
        return $this;
    }

    public function getFirstName()
    {
        return $this->fname;
    }

    public function setFirstName( $firstName )
    {
        $this->fname = $firstName;
        return $this;
    }

    public function getLastName()
    {
        return $this->lname;
    }

    public function setLastName( $lastName )
    {
        $this->lname = $lastName;
        return $this;
    }

    public function getDOB()
    {
        return $this->DOB;
    }

    public function setDOB( $DOB )
    {
        $this->DOB = $DOB;
        return $this;
    }

    public function getGender()
    {
        return $this->sex;
    }

    public function setGender( $gender )
    {
        $this->sex = $gender;
        return $this;
    }

    public function getPrimaryPhone()
    {
        return $this->phone_home;
    }

    public function setPrimaryPhone( $phone )
    {
        $this->phone_home = $phone;
        return $this;
    }

    public function getAllowSms()
    {
        return $this->hippa_allowsms;
    }

    public function setAllowSms( $allowSms )
    {
        $this->hipaa_allowsms = $allowSms;
        return $this;
    }

    public function getEmailAddress()
    {
        return $this->email;
    }

    public function setEmailAddress( $emailAddress )
    {
        $this->email = $emailAddress;
        return $this;
    }

    public function getPhoto()
    {
        return $this->photo;
    }

    public function setPhoto( $photo )
    {
        $this->photo = $photo;
        return $this;
    }
}
