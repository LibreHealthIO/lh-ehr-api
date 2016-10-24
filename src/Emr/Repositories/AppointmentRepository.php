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
use LibreEHR\Core\Lib\Date\DateCalc;

class AppointmentRepository extends AbstractRepository implements AppointmentRepositoryInterface
{

    const REPEAT_EVERY_DAY      = 0;
    const REPEAT_EVERY_WEEK     = 1;
    const REPEAT_EVERY_MONTH    = 2;
    const REPEAT_EVERY_YEAR     = 3;
    const REPEAT_EVERY_WORK_DAY = 4;

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
            ->get()
            ->toArray();

        $busySlots = [];

        $datePeriod = $this->getDayInterval($data);
        $to_date = date('Y-m-d', $datePeriod['to_datetime']);
        $from_date = date('Y-m-d', $datePeriod['from_datetime']);

        $events2 = [];

        foreach ($appointments as $slot) {
            $nextX = false;
            if($nextX) {
                $stopDate = $slot->pc_endDate;
            } else $stopDate = ($slot->pc_endDate <= $to_date) ? $slot->pc_endDate : $to_date;
            ///////
            $incX = 0;

            switch ($slot->pc_recurrtype) {
                case '0':
                    $events2[] = $slot;
                    break;

                case '1':
                    $event_recurrspec = @unserialize($slot->pc_recurrspec);
                    $rfreq = $event_recurrspec['event_repeat_freq'];
                    $rtype = $event_recurrspec['event_repeat_freq_type'];
                    $exdate = $event_recurrspec['exdate'];

                    list($ny,$nm,$nd) = explode('-', $slot->pc_eventDate);
                    $occurance = $slot->pc_eventDate;

                    while ($occurance < $from_date) {
                        $occurance =$this->increment($nd, $nm, $ny, $rfreq, $rtype);
                        list($ny, $nm, $nd) = explode('-', $occurance);
                    }

                    while ($occurance <= $stopDate) {
                        $excluded = false;
                        if (!empty($exdate)) {
                            foreach (explode(",", $exdate) as $exception) {
                                // occurrance format == yyyy-mm-dd
                                // exception format == yyyymmdd
                                if (preg_replace("/-/", "", $occurance) == $exception) {
                                    $excluded = true;
                                }
                            }
                        }

                        if ($excluded == false) {
                            $slot->pc_eventDate = $occurance;
                            $slot->pc_endDate = '0000-00-00';
                            $events2[] = clone $slot;
                            //////
                            if ($nextX) {
                                ++$incX;
                                if ($incX == $nextX) {
                                    break;
                                }
                            }
                            //////
                        }

                        $occurance = $this->increment($nd, $nm, $ny, $rfreq, $rtype);
                        list($ny,$nm,$nd) = explode('-', $occurance);

                    }
                    break;

                case '2':
                    $event_recurrspec = @unserialize($slot->pc_recurrspec);
                    $rfreq = $event_recurrspec['event_repeat_on_freq'];
                    $rnum  = $event_recurrspec['event_repeat_on_num'];
                    $rday  = $event_recurrspec['event_repeat_on_day'];
                    $exdate = $event_recurrspec['exdate'];

                    list($ny,$nm,$nd) = explode('-', $slot->pc_eventDate);

                    $occuranceYm = "$ny-$nm"; // YYYY-mm
                    $from_dateYm = substr($from_date, 0, 7); // YYYY-mm
                    $stopDateYm = substr($stopDate, 0, 7); // YYYY-mm

                    // $nd will sometimes be 29, 30 or 31, and if used in mktime below, a problem
                    // with overflow will occur ('01' should be plugged in to avoid this). We need
                    // to mirror the calendar code which has this problem, so $nd has been used.
                    while ($occuranceYm < $from_dateYm) {
                        $occuranceYmX = date('Y-m-d', mktime(0, 0, 0, $nm+$rfreq, $nd, $ny));
                        list($ny,$nm,$nd) = explode('-', $occuranceYmX);
                        $occuranceYm = "$ny-$nm";
                    }

                    while ($occuranceYm <= $stopDateYm) {
                        // (YYYY-mm)-dd
                        $dnum = $rnum;
                        $occurance = $slot->pc_eventDate;
                        if ($occurance >= $from_date && $occurance <= $stopDate) {
                            $excluded = false;
                            if (isset($exdate)) {
                                foreach (explode(",", $exdate) as $exception) {
                                    // occurrance format == yyyy-mm-dd
                                    // exception format == yyyymmdd
                                    if (preg_replace("/-/", "", $occurance) == $exception) {
                                        $excluded = true;
                                    }
                                }
                            }
                            if ($excluded == false) {
                                $slot->pc_eventDate = $occurance;
                                $slot->pc_endDate = '0000-00-00';
                                $events2[] = clone $slot;

                                if ($nextX) {
                                    ++$incX;
                                    if ($incX == $nextX) {
                                        break;
                                    }
                                }
                            }

                        }

                        $occuranceYmX = date('Y-m-d', mktime(0, 0, 0, $nm+$rfreq, $nd, $ny));
                        list($ny, $nm, $nd) = explode('-', $occuranceYmX);
                        $occuranceYm = "$ny-$nm";

                    }

                    break;
            }
        }

        $freeSlots = array();
        foreach ($events2 as $event) {
            if ( $event->pc_catid == 2 ) {
                for ( $i = 0; $i < $event->pc_duration; $i += 900  ) { // TODO get global slot value
                    $freeSlots[] = [
                        'timestamp' => strtotime($event->pc_eventDate . ' ' . $event->pc_startTime ) + $i,
                        'status' => 'available',
                        'duration' => 900 // $event->pc_duration
                    ];
                }
            }
        }

//        $freeSlots = $this->getAvailableSlots($events2);
//
//        foreach ($freeSlots as $k => $freeSlot) {
//            foreach ($busySlots as $busySlot) {
//                if ($freeSlot['timestamp'] == $busySlot['timestamp']) {
//                    $freeSlots[$k]['status'] = 'busy';
//                }
//            }
//        }

        return $freeSlots;
    }


    private function getAvailableSlots($appointments)
    {
        usort($appointments, function ($a, $b) {
            return strtotime($a->pc_eventDate) - strtotime($b->pc_eventDate);
        });

        $availableSlots = array();
        $start_time = 0;
        $date = 0;
        for ($i = 0; $i < count($appointments); ++$i)
        {
            if ($appointments[$i]->pc_catid == 2) { // 2 == In Office
                $start_time = $appointments[$i]->pc_startTime;
                $date = $appointments[$i]->pc_eventDate;
                $provider_id = $appointments[$i]->pc_aid;
            } else if ($appointments[$i]->pc_catid == 3) { // 3 == Out Of Office
                continue;
            } else {
                $start_time = $appointments[$i]->pc_endTime;
                $date = $appointments[$i]->pc_eventDate;
                $provider_id = $appointments[$i]->pc_aid;
            }

            // find next appointment with the same provider
            $next_appointment_date = 0;
            $next_appointment_time = 0;
            for ($j = $i+1; $j < count($appointments); ++$j) {
                if ($appointments[$j]->pc_aid == $provider_id) {
                    $next_appointment_date = $appointments[$j]->pc_eventDate;
                    $next_appointment_time = $appointments[$j]->pc_startTime;
                    break;
                }
            }

            $same_day = (strtotime($next_appointment_date) == strtotime($date)) ? true : false;

            if ($next_appointment_time && $same_day) {
                // check the start time of the next appointment
                $start_datetime = strtotime($date." ".$start_time);
                $next_appointment_datetime = strtotime($next_appointment_date." ".$next_appointment_time);
                $curr_time = $start_datetime;
                while ($curr_time < $next_appointment_datetime - ($this->getGlobalSettings()['calendar_interval'] / 2)) {
                    //create a new appointment ever 15 minutes
                    $availableSlots []= [
                        'timestamp' => $curr_time,
                        'status'    => 'available',
                        'duration'  =>  $this->getGlobalSettings()['calendar_interval']

                    ];
                    $curr_time += $this->getGlobalSettings()['calendar_interval'] * 60; // add a 15-minute slot
                }
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
            $status = DB::connection($this->connection)->table('patient_data')
                ->where('pid', '=', $data['patient'])->value('reg_status');
            if ($status == 'deleted') {
                $conditions[] = ['pc_pid', '!=', $data['patient']];
            } else {
                $conditions[] = ['pc_pid', '=', $data['patient']];
            }
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
        $providerRepo = new ProviderRepository();
        $provider = $providerRepo->get($data['provider']);
        $conditions[] = ['pc_aid', '=', $provider->getEmrId()];
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

    public function getGlobalCalendarInterval() {
        return $this->getGlobalSettings()['calendar_interval'];
    }

    private function increment($d,$m,$y,$f,$t)
    {
        if($t == self::REPEAT_EVERY_DAY) {
            return date('Y-m-d',mktime(0,0,0,$m,($d+$f),$y));
        } elseif($t == self::REPEAT_EVERY_WORK_DAY) {
            // a workday is defined as Mon,Tue,Wed,Thu,Fri
            // repeating on every or Nth work day means to not include
            // weekends (Sat/Sun) in the increment... tricky

            // ugh, a day-by-day loop seems necessary here, something where
            // we can check to see if the day is a Sat/Sun and increment
            // the frequency count so as to ignore the weekend. hmmmm....
            $orig_freq = $f;
            for ($daycount=1; $daycount<=$orig_freq; $daycount++) {
                $nextWorkDOW = date('w',mktime(0,0,0,$m,($d+$daycount),$y));
                if ($this->isWeekendDay($nextWorkDOW)) { $f++; }
            }

            // and finally make sure we haven't landed on a end week days
            // adjust as necessary
            $nextWorkDOW = date('w',mktime(0,0,0,$m,($d+$f),$y));
            if (count($GLOBALS['weekend_days']) === 2){
                if ($nextWorkDOW == $GLOBALS['weekend_days'][0]) {
                    $f+=2;
                }elseif($nextWorkDOW == $GLOBALS['weekend_days'][1]){
                    $f++;
                }
            } elseif(count($GLOBALS['weekend_days']) === 1 && $nextWorkDOW === $GLOBALS['weekend_days'][0]) {
                $f++;
            }

            return date('Y-m-d',mktime(0,0,0,$m,($d+$f),$y));

        } elseif($t == self::REPEAT_EVERY_WEEK) {
            return date('Y-m-d',mktime(0,0,0,$m,($d+(7*$f)),$y));
        } elseif($t == self::REPEAT_EVERY_MONTH) {
            return date('Y-m-d',mktime(0,0,0,($m+$f),$d,$y));
        } elseif($t == self::REPEAT_EVERY_YEAR) {
            return date('Y-m-d',mktime(0,0,0,$m,$d,($y+$f)));
        }
    }

    private function isWeekendDay($day){

        if (in_array($day, $GLOBALS['weekend_days'])) {
            return true;
        } else {
            return false;
        }
    }
}
