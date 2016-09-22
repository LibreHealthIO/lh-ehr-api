<?php

namespace LibreEHR\Core\Contracts;

use PHPFHIRGenerated\FHIRDomainResource\FHIROrganization;

/**
 * Interface PatientAdapterInterface
 * @package LibreEHR\Core\Contracts
 *
 * Take PatientInterface and output something
 *
 */
interface PharmacyAdapterInterface
{
    public function modelToInterface(FHIROrganization $pharmacy);
    public function jsonToInterface($data);
    public function storeInterface(PharmacyInterface $interface);
    public function interfaceToModel(PharmacyInterface $interface);
}
