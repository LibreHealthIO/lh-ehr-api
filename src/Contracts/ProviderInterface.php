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
    public function getName();
    public function setName($practiceName);
    public function getCode();
}
