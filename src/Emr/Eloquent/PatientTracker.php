<?php
/**
 * Created by PhpStorm.
 * User: kchapple
 * Date: 1/11/17
 * Time: 12:07 PM
 */

namespace LibreEHR\Core\Emr\Eloquent;

use LibreEHR\Core\Emr\Eloquent\AbstractModel as Model;

class PatientTracker extends Model
{
    protected $table = 'patient_tracker';
    protected $primaryKey = 'id';

    public $timestamps = false;

    public function appointment()
    {
        return $this->belongsTo( 'LibreEHR\Core\Emr\Eloquent\AppointmentData', 'pc_eid', 'id' );
    }

    public function elements()
    {
        return $this->hasMany( 'LibreEHR\Core\Emr\Eloquent\PatientTrackerElement', 'pt_tracker_id' );
    }
}
