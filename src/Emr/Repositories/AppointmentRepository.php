<?php
/**
 * Created by PhpStorm.
 * User: kchapple
 * Date: 2/5/16
 * Time: 9:44 AM
 */

namespace LibreEHR\Core\Emr\Repositories;

use Carbon\Carbon;
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

    public function create(AppointmentInterface $appointmentInterface)
    {
        if (is_a($appointmentInterface, $this->model())) {
            $appointmentInterface->setConnection($this->connection);
            $appointmentInterface->save();
            $appointmentInterface = $this->get($appointmentInterface->pc_eid);
        }

        return $appointmentInterface;
    }

    public function update($id, $data)
    {

        $appointment = new Appointment();
        $appointment->setConnectionName($this->connection);
        $appointmentInterface = $appointment->find($id);
        foreach ($data as $k => $ln) {
            if ($k == 'status') {
                $appointmentInterface->setPcApptStatus($ln);
            }
            if ($k == 'description') {
                $appointmentInterface->setDescription($ln);
            }
            if ($k == 'start') {
                $appointmentInterface->setStartTime($ln);
            }
            if ($k == 'end') {
                $appointmentInterface->setEndTime($ln);
            }
            if ($k == 'extension') {
                $extensions = $ln[0]['extension'];
                $location = [];
                foreach ($extensions as $extension) {
                    $url = $extension['url'];
                    if ($url =="#portal-uri") {
                        $location['portalUri'] = $extension['valueString'];
                    }
                    if ($url =="#room-key") {
                        $location['roomKey'] = $extension['valueString'];
                    }
                    if ($url =="#pin") {
                        $location['pin'] = $extension['valueString'];
                    }
                    if ($url =="#provider-id") {
                        $location['providerId'] = $extension['valueString'];
                    }
                    if ($url =="#patient-id") {
                        $appointmentInterface->setPatientId($extension['valueString']);
                    }
                }
                $appointmentInterface->setLocation(json_encode($location, true));
            }
        }

        $appointmentInterface->save();
        return $appointmentInterface;
    }

    public function getSlots($data)
    {
        $appointments = DB::connection($this->connection)->table('libreehr_postcalendar_events')
            ->where($this->provideSlotConditions($data))
            ->get();

        $availableSlots = [];

//            pcCategories:
//
//            1 => 'No Show',
//            2 => 'In Office',
//            3 => 'Out Of Office',
//            4 => 'Vacation',
//            5 => 'Office Visit',
//            8 => 'Lunch',
//            9 => 'Established Patient',
//            10 => 'New Patient',
//            11 => 'Reserved',
//            12 => 'Health and Behavioral Assessment',
//            13 => 'Preventive Care Services',
//            14 => 'Ophthalmological Services'

        $availableSlotCategoties = [2, 5];

        foreach ($appointments as $slot) {
            if (in_array($slot->pc_catid, $availableSlotCategoties)) {
                $availableSlots[] = [
                    'timestamp' => strtotime($slot->pc_eventDate . ' ' .$slot->pc_startTime),
                    'status'    => 'available',
                    'duration'  =>  $slot->pc_duration
                ];
            }
        }
        return $availableSlots;
    }
    
    public function getAppointmentsByParam($data)
    {
        $conditions = [];
        $whereInPid = [];
        if ( isset($data['groupId']) ) {

            // If we are given a group Id, get all the members of group
            $groupMembers = DB::connection($this->connection)->table('patient_data')
                ->where( 'group_id', '=', $data['groupId'] )
                ->get();

            // Build a where IN array
            foreach ( $groupMembers as $groupMember ) {
                $whereInPid[] = $groupMember->pid;
            }

        } else {
            $conditions[] = ['pc_pid', '=', $data['patient']];
        }

        foreach($data as $k => $ln) {
            if (strpos($ln, 'le') !== false) {
                $conditions[] = ['pc_eventDate', '<=', $this->getDate($ln, "lt")];
            }
            if (strpos($ln, 'ge') !== false) {
                $conditions[] = ['pc_eventDate', '>=', $this->getDate($ln, "gt")];
            }
            if (strpos($ln, 'eq') !== false) {
                $conditions[] = ['pc_eventDate', '=', $this->getDate($ln, "eq")];
            }
            if (strpos($ln, 'ne') !== false) {
                $conditions[] = ['pc_eventDate', '!=', $this->getDate($ln, "ne")];
            }
            if (strpos($ln, 'gt') !== false) {
                $conditions[] = ['pc_eventDate', '>=', $this->getDate($ln, "gt")];
                if ($this->getTime($ln)) {
                    $conditions[] = ['pc_startTime', '>', $this->getTime($ln)];
                }
            }
            if (strpos($ln, 'lt') !== false) {
                $conditions[] = ['pc_eventDate', '<=', $this->getDate($ln, "lt")];
                if ($this->getTime($ln)) {
                    $conditions[] = ['pc_startTime', '<', $this->getTime($ln)];
                }
            }
        }

        $model = $this->makeModel();
        $where =  $model->where($conditions);
        if ( count($whereInPid) ) {
            $where->whereIn( 'pc_pid', $whereInPid );
        }
        return $where->get();
    }

    public function getUnavailableSlots()
    {

    }

    public function delete( $id )
    {
        $appointment = new Appointment();
        $appointment->setConnectionName($this->connection);
        $appointmentInterface = $appointment->find($id);
        return $appointmentInterface->delete();
    }

    private function provideSlotConditions($data)
    {
        $conditions = [];
        $conditions[] = ['pc_aid', '=', $data['provider']];
        foreach($data as $k => $ln) {
            if (strpos($ln, 'le') !== false) {
                $conditions[] = ['pc_eventDate', '<=', $this->getDate($ln, "lt")];
            }
            if (strpos($ln, 'ge') !== false) {
                $conditions[] = ['pc_eventDate', '>=', $this->getDate($ln, "gt")];
            }
            if (strpos($ln, 'eq') !== false) {
                $conditions[] = ['pc_eventDate', '=', $this->getDate($ln, "eq")];
            }
            if (strpos($ln, 'ne') !== false) {
                $conditions[] = ['pc_eventDate', '!=', $this->getDate($ln, "ne")];
            }
            if (strpos($ln, 'gt') !== false) {
                $conditions[] = ['pc_eventDate', '>=', $this->getDate($ln, "gt")];
                if ($this->getTime($ln)) {
                    $conditions[] = ['pc_startTime', '>', $this->getTime($ln)];
                }
            }
            if (strpos($ln, 'lt') !== false) {
                $conditions[] = ['pc_eventDate', '<=', $this->getDate($ln, "lt")];
                if ($this->getTime($ln)) {
                    $conditions[] = ['pc_startTime', '<', $this->getTime($ln)];
                }
            }
            if (strpos($k, 'startDate') !== false) {
                $conditions[] = ['pc_eventDate', '=', $ln];
            }
        }
        return $conditions;
    }

    private function getDate($ln, $param)
    {
        if(strpos($ln, 'T') !== false){
            $ln = substr($ln, 0, strpos($ln, 'T'));
        }
        return substr($ln, strpos($ln, $param) + 2);
    }

    private function getTime($string)
    {
        if ((strpos($string, "T")) !== false){
            return substr($string, strpos($string, "T") + 1);
        }
    }

    private function getDayInterval($data)
    {
        $dates = [];
        foreach ($data as $ln) {
            if (strlen($ln) > 3) {
                $dates [] = strtotime(substr($ln, 2));
            }
            if (strpos("T", $ln) !== false) {
                $dates [] = strtotime(str_replace("T"," ", $ln));
            }
        }
        $from_date = date('Y-m-d', min($dates));
        $to_date = date('Y-m-d', max($dates));
        $datePeriod = [
            'from_datetime' => strtotime( $from_date ." 00:00:00" ),
            'to_datetime'   => strtotime( $to_date ." 23:59:59" )
        ];

        return $datePeriod;
    }

    private function getGlobalSettings()
    {
        $globals = DB::connection($this->connection)->table('globals')
            ->where('gl_name', 'like', 'calendar_interval')
            ->orWhere('gl_name', 'like', 'schedule_end')
            ->orWhere('gl_name', 'like', 'schedule_start')
            ->get();

        $emrGlobals = [];
        foreach ($globals as $global) {
            $emrGlobals[$global->gl_name] =$global->gl_value;
        }
        return $emrGlobals;
    }
}
