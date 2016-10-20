<?php

namespace LibreEHR\Core\Emr\Eloquent;

use LibreEHR\Core\Emr\Eloquent\AbstractModel as Model;
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

    public function getProviderId()
    {
        return $this->providerID;
    }

    public function setProviderId( $providerId )
    {
        $this->providerID = $providerId;
        return $this;
    }

    public function getPharmacyId()
    {
        return $this->pharmacy_id;
    }

    public function setPharmacyId( $pharmacyId )
    {
        $this->pharmacy_id = $pharmacyId;
        return $this;
    }

    public function getStatus()
    {
        return $this->reg_status;
    }

    public function setStatus( $status )
    {
        $this->reg_status= $status;
        return $this;
    }

    public function getGroupId()
    {
        return $this->group_id;
    }
    public function setGroupId( $groupId )
    {
        $this->group_id = $groupId;
        return $this;
    }

    public function getStreet()
    {
        $streets = explode('|', $this->street);
        foreach ($streets as $k => $street) {
            $streets[$k] = trim($street);
        }
        return $streets;
    }
    public function setStreet( $street )
    {
        $this->street = $this->streetsToLine($street);
        return $this;
    }

    public function getCity()
    {
        return $this->city;
    }
    public function setCity( $city )
    {
        $this->city = $city;
        return $this;
    }

    public function getCounty()
    {
        return $this->county;
    }
    public function setCounty( $county )
    {
        $this->county = $county;
        return $this;
    }
    
    public function getCustomerID()
    {
        return $this->usertext8;
    }
    public function setCustomerID($customerID)
    {
        $this->usertext7 = $customerID;
        return $this;
    }

    public function getContactRelationship()
    {
        return $this->guardianrelationship;
    }
    public function setContactRelationship($contactRelationship)
    {
        $this->guardianrelationship = $contactRelationship;
        return $this;
    }
    

    private function streetsToLine($addressLines)
    {
        $address = '';
        $arrayLength = count($addressLines) - 1;
        foreach ($addressLines as $k => $addressLine) {
            if ($k !== $arrayLength) {
                $address .= $addressLine . ' | ';
            } else {
                $address .= $addressLine;
            }
        }
        return $address;
    }
}
