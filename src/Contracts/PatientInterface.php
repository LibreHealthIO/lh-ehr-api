<?php

namespace LibreEHR\Core\Contracts;

interface PatientInterface extends ModelInterface
{
    public function getPid();
    public function setPid($pid);
    public function getFirstName();
    public function setFirstName($firstName);
    public function getLastName();
    public function setLastName($lastName);
    public function getDOB();
    public function setDOB($DOB);
    public function getGender();
    public function setGender($gender);
    public function getPrimaryPhone();
    public function setPrimaryPhone($phone);
    public function getAllowSms();
    public function setAllowSms($allowSms);
    public function getEmailAddress();
    public function setEmailAddress($emailAddress);

    public function getPhoto();
    public function setPhoto($photo);
}
