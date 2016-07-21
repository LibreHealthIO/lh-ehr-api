<?php

namespace LibreEHR\Core\Emr\Finders;

use LibreEHR\Core\Contracts\PatientFinderInterface;
use LibreEHR\Core\Emr\Eloquent\Document;
use LibreEHR\Core\Emr\Eloquent\PatientData as Patient;

class DocumentFinder extends AbstractFinder implements DocumentFinderInterface
{

    public function byPid( $pid )
    {
        try {
            $documents = Document::where('foreign_id', $pid)->all();
            return $documents;
        } catch ( ErrorException $e ) {
            //Do stuff if it doesn't exist.
        }
    }
}