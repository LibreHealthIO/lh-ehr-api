<?php
/**
 * Created by PhpStorm.
 * User: kchapple
 * Date: 1/11/17
 * Time: 12:07 PM
 */

namespace LibreEHR\Core\Emr\Eloquent;

use LibreEHR\Core\Emr\Eloquent\AbstractModel as Model;

class PatientTrackerElement extends Model
{
    protected $table = 'patient_tracker_element';
    protected $primaryKey = null;

    public $timestamps = false;
    public $incrementing = false;

    public function patientTracker()
    {
        return $this->belongsTo( 'LibreEHR\Core\Emr\Eloquent\PatientTracker', 'pt_tracker_id' );
    }
}
