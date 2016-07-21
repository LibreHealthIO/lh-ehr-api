<?php

namespace LibreEHR\Core\Contracts;
use PHPFHIRGenerated\FHIRDomainResource\FHIRPatient;

/**
 * Interface PatientAdapterInterface
 * @package LibreEHR\Core\Contracts
 *
 * Take PatientInterface and output something
 *
 */
interface PatientAdapterInterface extends BaseAdapterInterface
{
    public function modelToInterface( FHIRPatient $patient );
    public function jsonToInterface( $data );
    public function storeInterface( PatientInterface $interface );
    public function interfaceToModel( PatientInterface $interface );
}