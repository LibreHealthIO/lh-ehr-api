<?php
/**
 * Created by PhpStorm.
 * User: kchapple
 * Date: 7/25/16
 * Time: 10:18 AM
 */

namespace LibreEHR\Core\Emr\Criteria;

use LibreEHR\Core\Emr\Eloquent\PatientData as Patient;

class ByQuery extends AbstractCriteria
{
    protected $sql = null;
    protected $bindings = null;

    public function __construct($sql, array $bindings = null)
    {
        $this->sql = $sql;
        $this->bindings = $bindings;
    }

    public function execute()
    {
        $patient = null;
        try {
            $patient = Patient::where('pid', $this->pid)->firstOrFail();
            return $patient;
        } catch (ErrorException $e) {
            //Do stuff if it doesn't exist.
        }

        return $patient;
    }
}
