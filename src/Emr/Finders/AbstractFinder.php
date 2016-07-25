<?php
/**
 * Created by PhpStorm.
 * User: kchapple
 * Date: 7/25/16
 * Time: 10:55 AM
 */

class AbstractFinder
{
    /**
     * @param Criteria $criteria
     * @return $this
     */
    public function pushCriteria( AbstractCriteria $criteria )
    {
        // Find existing criteria
        $key = $this->criteria->search(function ($item) use ($criteria) {
            return (is_object($item) AND (get_class($item) == get_class($criteria)));
        });
        // Remove old criteria
        if (is_int($key)) {
            $this->criteria->offsetUnset($key);
        }

        $this->criteria->push($criteria);
        return $this;
    }

}