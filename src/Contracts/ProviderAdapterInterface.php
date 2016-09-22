<?php

namespace LibreEHR\Core\Contracts;

use PHPFHIRGenerated\FHIRDomainResource\FHIRPractitioner;

/**
 * Interface PatientAdapterInterface
 * @package LibreEHR\Core\Contracts
 *
 * Take PatientInterface and output something
 *
 */
interface ProviderAdapterInterface
{
    public function modelToInterface(FHIRPractitioner $provider);
    public function jsonToInterface($data);
    public function storeInterface(ProviderInterface $interface);
    public function interfaceToModel(ProviderInterface $interface);
}
