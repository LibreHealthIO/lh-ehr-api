<?php

namespace LibreEHR\Core\Contracts;

interface AuditEventRepositoryInterface extends RepositoryInterface
{
    public function create(AuditEventInterface $auditEventInterface);
}