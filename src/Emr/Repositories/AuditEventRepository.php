<?php
/**
 * Created by PhpStorm.
 * User: kchapple
 * Date: 2/5/16
 * Time: 9:44 AM
 */

namespace LibreEHR\Core\Emr\Repositories;

use LibreEHR\Core\Contracts\AuditEventInterface;
use LibreEHR\Core\Contracts\AuditEventRepositoryInterface;

class AuditEventRepository implements AuditEventRepositoryInterface
{
    public function create( AuditEventInterface $auditEventInterface )
    {
        if ( is_a( $auditEventInterface, '\LibreEHR\Core\Emr\Eloquent\AuditEvent' ) ) {
            $auditEventInterface->save();
        }

        return $auditEventInterface;
    }

    public function find()
    {
    }

}