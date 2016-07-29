<?php
namespace LibreEHR\Core\Emr\Criteria;

use LibreEHR\Core\Contracts\CriteriaInterface;
use LibreEHR\Core\Contracts\ModelInterface;

class ByPid extends AbstractCriteria implements CriteriaInterface
{
    public function __construct( $pid )
    {
        parent::__construct( array( 'pid' => $pid ) );
    }

    public function apply( ModelInterface $model )
    {
        $model->where('pid', $this->pid);
        return $model;
    }
}
