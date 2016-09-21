<?php
/**
 * Created by PhpStorm.
 * User: kchapple
 * Date: 7/25/16
 * Time: 10:55 AM
 */

namespace LibreEHR\Core\Emr\Finders;

use LibreEHR\Core\Contracts\CriteriaInterface;

class AbstractFinder
{
    protected $criteria = array();

    /**
     * @param CriteriaInterface $criteria
     * @return $this
     */
    public function pushCriteria(CriteriaInterface $criteria)
    {
        // TODO do we need to do anything with existing criteria?
        $this->criteria[]= $criteria;
        return $this;
    }

    public function getCriteria()
    {
        return $this->criteria;
    }
}
