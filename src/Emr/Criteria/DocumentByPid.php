<?php
/**
 * Created by PhpStorm.
 * User: kchapple
 * Date: 6/7/16
 * Time: 1:30 PM
 */
namespace LibreEHR\Core\Emr\Criteria;

use LibreEHR\Core\Emr\Eloquent\Document;

class DocumentByPid extends AbstractCriteria
{
    public function execute()
    {
        $documents = null;
        try {
            $documents = Document::where( 'foreign_id', $this->pid )->get();
            return $documents;
        } catch ( ErrorException $e ) {
            //Do stuff if it doesn't exist.
        }

        return $documents;
    }
}