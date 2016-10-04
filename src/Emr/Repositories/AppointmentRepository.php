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
                }
                $appointmentInterface->setLocation(json_encode($location, true));
            }
        }





        $appointmentInterface->save();

        return $appointmentInterface;
    }

    public function getSlots($data)
    {
        $busySlots = DB::connection($this->connection)->table('libreehr_postcalendar_events')
            ->where($this->provideSlotConditions($data))
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
            $busyInterval = [];
            foreach ($busy_slots as $slot) {
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

                foreach ($minute as $m ) {
                    array_push($allSlots, array("hour"=>$blockNum, "minute"=>$m, "status" => "free"));
                }
            }

            foreach ($allSlots as $key => $slot) {

                foreach ($busyInterval as $busy) {
                    $arStart = explode(':', $busy['startTime']);
                    if ($arStart[0] < $scheduleStart) {
                        $arStart[0] = (int)$arStart[0] + 12;
                    }
                    if ($slot['hour'] != $arStart[0]) {
                        continue;
                    }
                    if ($slot['minute'] != $arStart[1]) {
                        continue;
                    }
                    $arEnd = explode(':', $busy['endTime']);
                    if ($arEnd[0] < $scheduleStart) {
                        $arEnd[0] = (int)$arEnd[0] + 12;
                    }

                    $busyKey = $this->busySlotKey($allSlots, $key, $arEnd);

                    foreach ($busyKey as $key) {
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
        return $model->where($conditions)->get();
    }

    public function getUnavailableSlots()
    {

    }

    public function delete( $id )
    {

    }

    private function provideSlotConditions($data)
    {
        $conditions = [];
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

}
