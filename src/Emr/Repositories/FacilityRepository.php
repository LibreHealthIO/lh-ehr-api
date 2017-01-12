<?php
/**
 * Created by PhpStorm.
 * User: kchapple
 * Date: 1/5/17
 * Time: 10:55 AM
 */
namespace LibreEHR\Core\Emr\Repositories;

class FacilityRepository extends AbstractRepository
{
    public function model()
    {
        return '\LibreEHR\Core\Emr\Eloquent\Facility';
    }
}

