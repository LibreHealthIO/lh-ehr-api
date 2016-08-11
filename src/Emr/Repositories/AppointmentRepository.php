<?php
/**
 * Created by PhpStorm.
 * User: kchapple
 * Date: 2/5/16
 * Time: 9:44 AM
 */

namespace LibreEHR\Core\Emr\Repositories;

use LibreEHR\Core\Contracts\DocumentRepositoryInterface;
use Illuminate\Support\Facades\DB;
use LibreEHR\Core\Contracts\AppointmentInterface;
use LibreEHR\Core\Contracts\AppointmentRepositoryInterface;
use Illuminate\Support\Facades\App;
use LibreEHR\Core\Emr\Criteria\DocumentByPid;
use LibreEHR\Core\Emr\Eloquent\AppointmentData as Appointment;
use LibreEHR\Core\Emr\Finders\Finder;

class AppointmentRepository extends AbstractRepository implements AppointmentRepositoryInterface
{
    public function model()
    {
        return '\LibreEHR\Core\Contracts\AppointmentInterface';
    }

    public function find()
    {
        return parent::find();
    }

    public function create( AppointmentInterface $appointmentInterface )
    {
        echo '<pre>'; var_dump('zzz'); echo '</pre>'; exit;
        if ( is_a( $appointmentInterface, $this->model() ) ) {


            $appointmentInterface->save();
            $appointmentInterface = $this->get( $appointmentInterface->id );

        }

        return $appointmentInterface;

    }


    public function update( $id, array $data )
    {

    }

    public function delete( $id )
    {

    }

    public function fetchAll()
    {
        return Appointment::all();
    }

    public function get( $id )
    {
        return Appointment::find( $id );
    }

}