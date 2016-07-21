<?php

namespace LibreEHR\Core\Contracts;

interface DocumentFinderInterface extends FinderInterface
{
    public function byPid( $pid );
}