<?php

namespace LibreEHR\Core\Contracts;

interface PharmacyInterface extends ModelInterface
{
    public function getId();
    public function setId($id);
    public function getName();
    public function setName($name);
    public function getAddress();
    public function setAddress($address);
    public function getregisteredStatus();
    public function setregisteredStatus($registeredStatus);
}
