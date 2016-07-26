<?php
/**
 * Created by PhpStorm.
 * User: kchapple
 * Date: 2/5/16
 * Time: 9:44 AM
 */

namespace LibreEHR\Core\Emr\Repository;

use LibreEHR\Core\Contracts\AuditEventInterface;
use LibreEHR\Core\Contracts\AuditEventRepositoryInterface;

class AuditEventRepository implements AuditEventRepositoryInterface
{

    public function model()
    {
        return '\LibreEHR\Core\Emr\Eloquent\AuditEvent';
    }

    public function makeModel()
    {
        return App::make( '\LibreEHR\Core\Emr\Contracts\AuditEventInterface' );
    }

    public function find()
    {
        return parent::find();
    }

    public function create( AuditEventInterface $auditEventInterface )
    {
        if ( is_a( $auditEventInterface, '\LibreEHR\Core\Emr\Eloquent\AuditEvent' ) ) {
            $auditEventInterface->save();
        }

        return $auditEventInterface;
    }

}