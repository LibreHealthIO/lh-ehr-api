<?php

namespace LibreEHR\Core\Emr\Repositories;

use LibreEHR\FHIR\Models\Option;

class OptionsRepository extends AbstractRepository
{
    public function model()
    {
        return '\LibreEHR\Core\Emr\Eloquent\Options';
    }

    public function find()
    {
        return parent::find();
    }

    public function fetchOptionsByKey( $key )
    {
        $model = $this->makeModel();
        $result = $model->where( 'key', '=', $key )
            ->first();
        return $this->processResult( $result );
    }

    public function processResult( $result )
    {
        $optionValueJson = $result->value;
        $rawOptions = json_decode( $optionValueJson );
        $options = array();
        foreach ( $rawOptions as $r ) {

            $option = new Option( $r, $r );
            $options[]= $option;
        }

        return $options;
    }
}
