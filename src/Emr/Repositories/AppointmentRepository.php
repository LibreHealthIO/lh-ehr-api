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
        $busySlots = DB::connection($this->connection)->table('libreehr_postcalendar_events')
            ->where($this->provideSlotConditions($data))
            ->get();

        $emrGlobals = $this->getGlobalSettings();

        $param = array(
            'scheduleStart' => $emrGlobals['schedule_start'],
            'scheduleEnd' => $emrGlobals['schedule_end'],
            'calendarInterval' => $emrGlobals['calendar_interval'] * 60,    // minutes to seconds
            'dayInterval'  => $this->getDayInterval($data)
        );

        $freeSlots = $this->addFreeSlots($param);
        $allSlots = $this->addBusySlots($freeSlots, $busySlots, $param);

        return $allSlots;
    }

    public function addFreeSlots($param)
    {
        $freeSlots = [];

        $startDate = $param['dayInterval']['min'];
        $timeStart = $param['scheduleStart'];
        $timeEnd = $param['scheduleEnd'];
        $calendarInterval = $param['calendarInterval'];
        $dayInterval = floor(($param['dayInterval']['max'] - $param['dayInterval']['min']) / (60 * 60 * 24));

        $day = 86400;    //  $day = 60*60*24;  1 day in seconds

        for ($d = 0; $d <= $dayInterval; $d ++) {
            $currentDay = date('Y/m/d', ($startDate + ($d * $day)));
            $scheduleStart = strtotime($timeStart . ':00 ' .$currentDay);
            $scheduleEnd = strtotime($timeEnd . ':00 ' . $currentDay);
            for ($t = $scheduleStart; $t <= $scheduleEnd; $t += $calendarInterval) {

//                    foreach ($busySlots as $k => $slot) {
//                        $slotTimestamp = strtotime($slot->pc_startTime.' '.$slot->pc_eventDate);
//                        $slotDay = date('Y/m/d', strtotime($slot->pc_eventDate));

//                        if ($slotTimestamp >= $scheduleStart && $slotTimestamp <= $scheduleEnd && $slotDay == $currentDay) {
//                            $allSlots[$currentDay][] = [
//                                'timestamp' => date('Y/m/d H:i:s', $slotTimestamp),
//                                'status'    => 'busy',
//                                'duration'  =>  $slot->pc_duration/60 . ' minutes'
//                            ];
//                        } else {
                $freeSlots[$currentDay][] = [
                    'timestamp' => $t,
                    'status'    => 'free',
                    'duration'  =>  $calendarInterval
                ];
//                        }

//                    }


            }
        }

        return $freeSlots;

    }


    public function addBusySlots($freeSlots, $busySlots, $param)
    {
        foreach ($freeSlots as $ln) {
            $foo = false;
            foreach ($ln as $freeSlot) {
                $foo = false;
                // date('Y/m/d H:i:s', $t);
                $freeSlotStart = $freeSlot['timestamp'];
                $freeSlotEnd = $freeSlot['timestamp'] + $freeSlot['duration'];

                foreach ($busySlots as $busySlot) {
                    $foo = false;

                    $slotTimestamp = strtotime($busySlot->pc_startTime.' '.$busySlot->pc_eventDate);
                    $slotDay = date('Y/m/d', strtotime($busySlot->pc_eventDate));
                    
                    if ($slotTimestamp >= $freeSlotStart && $slotTimestamp <= $freeSlotEnd)
                    {
                           
                    }
                }
            }
        }
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

    private function getDayInterval($data)
    {
        $dates = [];
        foreach ($data as $ln) {
            if (strlen($ln) > 3) {
                $dates [] = strtotime(substr($ln, 2));
            }
        }
        $dayInterval = [
            'min' => min($dates),
            'max' => max($dates),
        ];
        return $dayInterval;
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
