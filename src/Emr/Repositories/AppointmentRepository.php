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
        if ( is_a( $appointmentInterface, $this->model() ) ) {
            $appointmentInterface->save();
            $appointmentInterface = $this->get( $appointmentInterface->pc_eid );
        }

        return $appointmentInterface;
    }

    public function update($id, $status)
    {
        $appointmentInterface = Appointment::find($id);
        $appointmentInterface->setPcApptStatus($status);
        $appointmentInterface->save();

        return $appointmentInterface;
    }

    public function getSlots($startDate )
    {
        $busySlots = DB::table('openemr_postcalendar_events')
            ->where('pc_eventDate', '=', $startDate)
            ->get();

        $param = array(
            'scheduleStart' => 8,
            'scheduleEnd' => 17,
            'calendarInterval' => 15,
        );

        $allSlots = $this->addFreeSlots($busySlots, $param);
        $slotFormat = $this->slotFormat($allSlots, $param);

        return $slotFormat;
    }

    private function slotFormat($allSlots, $param)
    {

        $slotFormat = array();
        foreach ($allSlots as $key => $slot) {

            $slotFormat[$key]['startTime'] = date("H:i:s", mktime($slot['hour'], $slot['minute'], 0, 0, 0, 0));
            $slotFormat[$key]['endTime'] = date("H:i:s", mktime($slot['hour'], $slot['minute'] + $param['calendarInterval'], 0, 0, 0, 0));
            $slotFormat[$key]['status'] = $slot['status'];
        }
        return $slotFormat;

    }


    public function addFreeSlots($busy_slots, $param)
    {
        if($busy_slots) {
            $i = 0;
            foreach($busy_slots as $slot) {
                $busyInterval[$i]['startTime'] = $slot->pc_startTime;
                $busyInterval[$i]['endTime'] = $slot->pc_endTime;
                $i++;
            }
            $scheduleStart = $param['scheduleStart'];
            $scheduleEnd = $param['scheduleEnd'];
            $calendarInterval = $param['calendarInterval'];

            // $times is an array of associative arrays, where each sub-array
            // has keys 'hour', 'minute' and 'mer'.
            //
            $allSlots = array();

            // For each hour in the schedule...
            //
            for($blockNum = $scheduleStart; $blockNum <= $scheduleEnd; $blockNum++){

                // $minute is an array of time slot strings within this hour.
                $minute = array('00');

                for($minutes = $calendarInterval; $minutes <= 60; $minutes += $calendarInterval) {
                    if($minutes <= '9'){
                        $under_ten = "0" . $minutes;
                        array_push($minute, "$under_ten");
                    }
                    else if($minutes >= '60') {
                        break;
                    }
                    else {
                        array_push($minute, "$minutes");
                    }
                }

                foreach($minute as $m ){
                    array_push($allSlots, array("hour"=>$blockNum, "minute"=>$m, "status" => "free"));
                }
            }

            foreach ($allSlots as $key => $slot) {

                foreach($busyInterval as $busy) {
                    $arStart = explode(':',$busy['startTime']);
                    if($arStart[0] < $scheduleStart){
                        $arStart[0] = (int)$arStart[0] + 12;
                    }
                    if($slot['hour'] != $arStart[0]){
                        continue;
                    }
                    if($slot['minute'] != $arStart[1]){
                        continue;
                    }
                    $arEnd = explode(':',$busy['endTime']);
                    if($arEnd[0] < $scheduleStart){
                        $arEnd[0] = (int)$arEnd[0] + 12;
                    }

                    $busyKey = $this->busySlotKey($allSlots, $key, $arEnd);

                    foreach ($busyKey as $key){
                        $allSlots[$key]['status'] = 'busy';
                    }
                }
            }

            return $allSlots;
        }
    }

    private function busySlotKey(&$allSlots, $keyStart, array $endTime)
    {

        $busyKey = array();
        for ($i = $keyStart; $i <= count($allSlots); $i++) {
            if($allSlots[$i]['hour'] > $endTime[0]) {
                break;
            }
            if($allSlots[$i]['hour'] == $endTime[0] && $allSlots[$i]['minute'] >= $endTime[1]) {
                break;
            }
            $allSlots[$i]['status'] = 'busy';
        }
        return $busyKey;

    }

    public function getAppointmentsByParam($data)
    {
        $conditions = [];
        $conditions[] = ['pc_pid', '=', $data['patient']];

        if(isset($data['date_lt'])) {
            $conditions[] = ['pc_eventDate', '<', $data['date_lt']];
        }
        if(isset($data['date_gt'])) {
            $conditions[] = ['pc_eventDate', '>', $data['date_gt']];
        }
        if(isset($data['date_eq'])) {
            $conditions[] = ['pc_eventDate', '=', $data['date_eq']];
        }
        if(isset($data['date_ne'])) {
            $conditions[] = ['pc_eventDate', '!=', $data['date_ne']];
        }
        if(isset($data['date_ge'])) {
            $conditions[] = ['pc_eventDate', '>=', $data['date_ge']];
            if ($this->getTime($data['date_ge'])){
                $conditions[] = ['pc_startTime', '>=', $this->getTime($data['date_ge'])];
            }
        }
        if(isset($data['date_le'])) {
            $conditions[] = ['pc_eventDate', '<=', $data['date_le']];
            if ($this->getTime($data['date_le'])){
                $conditions[] = ['pc_startTime', '<=', $this->getTime($data['date_le'])];
            }
        }
        $appointments = $this->processingAppointmentStatuses(Appointment::where($conditions)->get());

        return $appointments;
    }

    private function processingAppointmentStatuses($appointments){

        if(!$appointments) {

            return $appointments;
        }
        $appStatuses = DB::table('list_options')
            ->select('option_id', 'mapping')
            ->where('list_id', 'apptstat')
            ->get();
        $statuses = array();
        foreach($appStatuses as $statusesObj) {
            $statuses[$statusesObj->option_id] = $statusesObj->mapping;
        }
        foreach($appointments as &$appointment) {

            $appointment['pc_apptstatus'] = $statuses[$appointment['pc_apptstatus'] ];
        }

        return $appointments;

    }

    public function getUnavailableSlots()
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


    private function getTime($string)
    {
        if ((strpos($string, "T")) !== false){
            return substr($string, strpos($string, "T") + 1);
        }
    }

}