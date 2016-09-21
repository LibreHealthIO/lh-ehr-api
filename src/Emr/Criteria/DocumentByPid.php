<?php
/**
 * Created by PhpStorm.
 * User: kchapple
 * Date: 6/7/16
 * Time: 1:30 PM
 */
namespace LibreEHR\Core\Emr\Criteria;

use LibreEHR\Core\Contracts\CriteriaInterface;
use LibreEHR\Core\Contracts\ModelInterface;

class DocumentByPid extends AbstractCriteria implements CriteriaInterface
{
    public function __construct($pid, $categoryId)
    {
        parent::__construct(array('pid' => $pid));
    }

    public function apply(ModelInterface $model)
    {
        $model->where('foreign_id', $this->pid);
        return $model;
    }
}
