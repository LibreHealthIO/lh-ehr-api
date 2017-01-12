<?php
/**
 * Created by PhpStorm.
 * User: kchapple
 * Date: 1/5/17
 * Time: 10:55 AM
 */
namespace LibreEHR\Core\Emr\Repositories;

class UserRepository extends AbstractRepository
{
    public function model()
    {
        return '\LibreEHR\Core\Emr\Eloquent\User';
    }

    public function fetchProviders()
    {
        $model = $this->makeModel();
        $result = $model->where( [
            [ 'id', '>', '1' ],
            [ 'active', '=', '1' ],
            [ 'authorized', '=', '1' ],
            [ 'calendar', '=', '1' ]
        ])->get();
        return $result;
    }
}
