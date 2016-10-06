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
        $busySlots = DB::connection($this->connection)->table('libreehr_postcalendar_events')
            ->where($this->provideSlotConditions($data))
            ->get();

        $param = array(
            'scheduleStart' => 8,
            'scheduleEnd' => 17,
            'calendarInterval' => 15*60,
            'dayInterval'  => $this->getDayInterval($busySlots)
        );

        $allSlots = $this->addFreeSlots($busySlots, $param);
        
        return $allSlots;
    }


    public function addFreeSlots($busySlots, $param)
    {
        if ($busySlots) {
            $allSlots = [];
            $slotDateTimes = [];
            foreach ($busySlots as $slot) {
                $slotDateTimes[$slot->pc_eid] = strtotime($slot->pc_startTime.' '.$slot->pc_eventDate);
            }

            $startDate = $param['dayInterval']['min'];
            
            // TODO Add functionality for getting params from Globals

//            $scheduleStart = $param['scheduleStart'];  // 8 am
//            $scheduleEnd = $param['scheduleEnd'];      // 17 pm


            $timeStart = '8 am';
            $timeEnd = '5 pm';


            $calendarInterval = $param['calendarInterval'];
            $dayInterval = floor(($param['dayInterval']['max'] - $param['dayInterval']['min']) / (60 * 60 * 24));


//            $day = 60*60*24;  1 day
            $day = 86400;


            for ($d = 1; $d <= $dayInterval; $d ++) {
                $currentDay = date('d/m/Y', ($startDate + $d * $day));
                $scheduleStart = strtotime($timeStart . ' ' .$currentDay);
                $scheduleEnd = strtotime($timeEnd . ' ' . $currentDay);
                for ($t = $scheduleStart; $t <= $scheduleEnd; $t += $calendarInterval) {
                    if (in_array($t, $slotDateTimes)) {
                        $allSlots[] = [
                          'timestamp' => $t,
                          'status'    => 'busy',
                          'duration'  =>  $this->getSlotDuration($t, $slotDateTimes, $busySlots)
                        ];
                    } else {
                        $allSlots[] = [
                            'timestamp' => $t,
                            'status'    => 'free',
                            'duration'  =>  $calendarInterval/60 . ' minutes'
                        ];
                    }
                }
            }

            return $allSlots;
        }
    }


    private function getSlotDuration($timestamp, $slotDateTimes, $busySlots)
    {
        $id = array_search($timestamp, $slotDateTimes);
        $busySlots->filter(function ($data) use ($id) {
            if ($data->pc_eid == $id) {
                return $data->pc_startTime - $data->pc_endTime;
            }
        });
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
        $appointment = new Appointment();
        $appointment->setConnectionName($this->connection);
        $appointmentInterface = $appointment->find($id);
        return $appointmentInterface->delete();
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

    private function getDayInterval($busySlots)
    {
        $dates = [];
        foreach ($busySlots as $slot) {
            $dates [] = strtotime(substr($slot->pc_eventDate, 2));
        }
        $dayInterval = [
            'min' => min($dates),
            'max' => max($dates),
        ];
        return $dayInterval;
    }
}
