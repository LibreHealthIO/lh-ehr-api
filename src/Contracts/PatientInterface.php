<?php

namespace LibreEHR\Core\Contracts;

interface PatientInterface extends ModelInterface
{
    public function getPid();
    public function setPid( $pid );
    public function getFirstName();
    public function setFirstName( $firstName );
    public function getLastName();
    public function setLastName( $lastName );
    public function getDOB();
    public function setDOB( $DOB );
    public function getGender();
    public function setGender( $gender );
    public function getPrimaryPhone();
    public function setPrimaryPhone( $phone );
    public function getAllowSms();
    public function setAllowSms( $allowSms );
    public function getEmailAddress();
    public function setEmailAddress( $emailAddress );

    public function getPhoto();
    public function setPhoto( $photo );

    public function getProviderId();
    public function setProviderId( $providerId );
    public function getPharmacyId();
    public function setPharmacyId( $pharmacyId );
    public function getStatus();
    public function setStatus( $status );
    public function getGroupId();
    public function setGroupId( $groupId );

    public function getStreet();
    public function setStreet( $street );
    public function getCity();
    public function setCity( $city );
    public function getCounty();
    public function setCounty( $country);

    public function getCustomerID();
    public function setCustomerID($customerID);
    public function getContactRelationship();
    public function setContactRelationship($contactRelationship);
}
