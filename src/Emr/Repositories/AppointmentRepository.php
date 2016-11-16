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

    protected function checkConstraints( $slotStartTime, $slotEndTime, $constraints )
    {
        $pass = true;
        foreach ( $constraints as $constraint ) {
            $constraintTimestamp = strtotime($constraint[2]);
            $constraintOperator = $constraint[1];

            // slot must start before constraint timestamp
            if ($constraintOperator == '<=' &&
                $slotStartTime > $constraintTimestamp ) {
                $pass = false;
                break;
            } else if ($constraintOperator == '<' &&
                $slotStartTime >= $constraintTimestamp ) {
                $pass = false;
                break;
            } else if ($constraintOperator == '>=' &&
                $slotStartTime < $constraintTimestamp ) {
                $pass = false;
                break;
            } else if ($constraintOperator == '>' &&
                $slotStartTime <= $constraintTimestamp ) {
                $pass = false;
                break;
            }
        }

        return $pass;
    }

    public function findSmallest( $constraints )
    {
        $min = null;
        foreach ( $constraints as $key => $constraint ) {
            if ( $key == 'gt' || $key == 'ge' ) {
                $min = substr( $constraint[2], 0, 10 );
                break;
            }
        }

        return $min;
    }

    public function findBiggest( $constraints )
    {
        $max = null;
        foreach ( $constraints as $key => $constraint ) {
            if ( $key == 'lt' || $key == 'le' ) {
                $max = substr( $constraint[2], 0, 10 );
                break;
            }
        }

        return $max;
    }

    public function getSlots( $data )
    {
        $constraints = $this->provideSlotConditions( $data );
        $from_date = $this->findSmallest( $constraints );
        $to_date = $this->findBiggest( $constraints );

        $allEvents = DB::connection($this->connection)->table( 'libreehr_postcalendar_events' );
//        if ( $constraints['pc_aid'] ) {
//            $allEvents->where(   );
//        }
        $allEvents->where([
            [ 'pc_aid', '=', $constraints['pc_aid'][2] ],
            [ 'pc_endDate', '>=', $from_date ],
            [ 'pc_eventDate', '<=', $to_date ],
            [ 'pc_recurrtype', '>', 0 ] ]);
        $allEvents->orWhere([
            [ 'pc_eventDate', '>=', $from_date ],
            [ 'pc_eventDate', '<=', $to_date ] ]);
        $allEvents = $allEvents->get()->toArray();

        $events2 = [];

        foreach ( $allEvents as $slot ) {
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

        usort($events2, function ($a, $b) {
            return strtotime($a->pc_eventDate) - strtotime($b->pc_eventDate);
        });

        // Break down all events into slots
        $availableSlots = array();
        $otherEvents = $events2;
        foreach ( $events2 as $event ) {
            if ( $event->pc_catid == 2 ) { // In Office

                // Start the slot counter at the start of the event
                $slotStartTime = strtotime( $event->pc_eventDate . ' ' . $event->pc_startTime );
                $slotEndTime = $slotStartTime + $this->getGlobalCalendarInterval()*60;
                $endDate = $event->pc_endDate == '0000-00-00' ? $event->pc_eventDate : $event->pc_endDate;
                $endTime = strtotime( $endDate . ' ' . $event->pc_endTime );

                // Iterate over this in-office slot in increments of Slot Duration until we reach the Event duration,
                // OR the end of the in-office event
                for ( $i = 0; ( $i < $event->pc_duration && $i < $endTime ); $i += $this->getGlobalCalendarInterval()*60  ) {

                    $isAvailable = true;

                    if ( !$this->checkConstraints( $slotStartTime, $slotEndTime, $constraints ) ) {
                        $isAvailable = false;
                    }

                    if ( $isAvailable ) {
                        // Search for a blocked-out time that would make this slot unavailable
                        foreach ($otherEvents as $otherEvent) {
                            if (($otherEvent->pc_apptstatus == '*' ||
                                $otherEvent->pc_apptstatus == '=')
                            ) {

                                $otherStartTime = strtotime($otherEvent->pc_eventDate . ' ' . $otherEvent->pc_startTime);
                                $endDate = $otherEvent->pc_endDate == '0000-00-00' ? $otherEvent->pc_eventDate : $otherEvent->pc_endDate;
                                $otherEndTime = strtotime($endDate . ' ' . $otherEvent->pc_endTime);
                                if ($otherStartTime < $slotEndTime &&
                                    $otherEndTime > $slotStartTime
                                ) {
                                    $isAvailable = false;
                                    break;
                                }
                            }
                        }
                    }

                    if ( $isAvailable ) {
                        $availableSlots[] = [
                            'startTimestamp' => $slotStartTime,
                            'endTimestamp' => $slotEndTime,
                            'status' => 'available',
                        ];
                    }

                    // Move the slot start and end times
                    $slotStartTime += $this->getGlobalCalendarInterval() * 60;
                    $slotEndTime += $this->getGlobalCalendarInterval() * 60;
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
        $conditions['pc_aid'] = ['pc_aid', '=', $provider->getEmrId()];

        $conditionTemplate = 'CONCAT(pc_eventDate,\' \',pc_startTime)';

        foreach($data as $k => $ln) {
            if (strpos($ln, 'le') !== false) {
                $conditions['le'] = [ $conditionTemplate, '<=', $this->getDate( $ln ) ];
            }
            if (strpos($ln, 'ge') !== false) {
                $conditions['ge'] = [ $conditionTemplate, '>=', $this->getDate( $ln ) ];
            }
            if (strpos($ln, 'eq') !== false) {
                $conditions['eq'] = [ $conditionTemplate, '=', $this->getDate( $ln ) ];
            }
            if (strpos($ln, 'ne') !== false) {
                $conditions['ne'] = [ $conditionTemplate, '!=', $this->getDate( $ln ) ];
            }
            if (strpos($ln, 'gt') !== false) {
                $conditions['gt'] = [ $conditionTemplate, '>', $this->getDate( $ln ) ];
            }
            if (strpos($ln, 'lt') !== false) {
                $conditions['lt'] = [ $conditionTemplate, '<', $this->getDate( $ln ) ];
            }
            if (strpos($k, 'startDate') !== false) {
                $conditions['startDate'] = ['pc_eventDate', '=', $ln];
            }
        }
        return $conditions;
    }

    private function getDate( $ln )
    {
        $datetime = str_replace( '%3A', ':', $ln );
        $datetime = str_replace( 'T', ' ', $datetime );
        return substr( $datetime, 2 );
    }

    private function getTime($string)
    {
        if ((strpos($string, "T")) !== false){
            return substr($string, strpos($string, "T") + 1);
        }
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
