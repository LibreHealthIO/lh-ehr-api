<?php

namespace LibreEHR\Core\Contracts;

interface RepositoryInterface
{
    public function find();
    public function setConnection( $connection );
}