<?php

namespace LibreEHR\Core\Contracts;

interface ProviderInterface extends ModelInterface
{
    public function getId();
    public function setId($id);
    public function getFirstName();
    public function setFirstName($firstName);
    public function getLastName();
    public function setLastName($lastName);
    public function getEmailAddress();
    public function setEmailAddress($emailAddress);
    public function getAddress();
    public function setAddress($address);
    public function getTown();
    public function setTown($town);
    public function getState();
    public function setState($state);
    public function getCountry();
    public function setCountry($country);
    public function setName($practiceName);
}
