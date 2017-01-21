<?php

namespace LibreEHR\Core\Emr\Repositories;

use LibreEHR\FHIR\Models\Option;

class ListOptionsRepository extends AbstractRepository
{
    public function model()
    {
        return '\LibreEHR\Core\Emr\Eloquent\ListOptions';
    }

    public function find()
    {
        return parent::find();
    }

    public function fetchOptionsByListId( $listId )
    {
        $model = $this->makeModel();
        $results = $model->where( 'list_id', '=', $listId )
            ->orderBy( 'seq', 'asc' )
            ->orderBy( 'title', 'asc' )
            ->get();
        return $this->processResult( $results );
    }

    public function processResult( $results )
    {
        $options = array();
        foreach ( $results as $r ) {

            $option = new Option( $r['title'], $r['option_id'] );
            $options[]= $option;
        }

        return $options;
    }
}
