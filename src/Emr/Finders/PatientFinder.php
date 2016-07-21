<?php

namespace LibreEHR\Core\Emr\Finders;

use LibreEHR\Core\Contracts\PatientFinderInterface;
use LibreEHR\Core\Emr\Eloquent\Document;
use LibreEHR\Core\Emr\Eloquent\PatientData as Patient;

class PatientFinder extends AbstractFinder implements PatientFinderInterface
{
    public function byLastName( $lastName )
    {
        $patients = Patient::where( 'lname', $lastName )->get();
        return $patients;
    }

    public function byPid( $pid )
    {
        try {
            $patient = Patient::where('pid', $pid)->firstOrFail();
            return $patient;
        } catch ( ErrorException $e ) {
            //Do stuff if it doesn't exist.
        }
    }

    public function byId( $id )
    {
        try {
            return Patient::find($id);
        } catch ( ErrorException $e ) {
            //Do stuff if it doesn't exist.
        }
    }
}