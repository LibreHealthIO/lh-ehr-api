<?php

namespace LibreEHR\Core\Contracts;

interface PharmacyInterface extends ModelInterface
{
    public function getId();
    public function setId($id);
    public function setName($name);
    public function getAddress();
    public function setAddress($address);
    public function getTown();
    public function setTown($town);
    public function getState();
    public function setState($state);
    public function getCountry();
    public function setCountry($country);
    public function getRegisteredStatus();
    public function setRegisteredStatus($registeredStatus);
    public function getEmrId();
    public function setEmrId($emrId);
}
