<?php

namespace LibreEHR\Core\Contracts;
use PHPFHIRGenerated\FHIRDomainResource\FHIRAuditEvent;

/**
 * Interface AuditEventAdapterInterface
 * @package LibreEHR\Core\Contracts
 *
 * Take AuditEventAdapterInterface and output something
 *
 */
interface AuditEventAdapterInterface extends BaseAdapterInterface
{
    public function modelToInterface(FHIRAuditEvent $patient);
    public function jsonToInterface($data);
    public function storeInterface(AuditEventInterface $interface);
    public function interfaceToModel(AuditEventInterface $interface);
}