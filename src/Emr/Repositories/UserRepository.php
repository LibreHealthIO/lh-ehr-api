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

    public function fetchProviders( $fetchInactive = false )
    {
        $params = [
            [ 'id', '>', '1' ],
            [ 'authorized', '=', '1' ],
            [ 'calendar', '=', '1' ]
        ];
        if ( $fetchInactive === false ) {
            $params []= [ 'active', '=', '1' ];
        }
        $model = $this->makeModel();
        $result = $model->where($params )->get();
        return $result;
    }
}
