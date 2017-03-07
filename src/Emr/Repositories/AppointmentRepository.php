<?php
/**
 * Created by PhpStorm.
 * User: kchapple
 * Date: 2/5/16
 * Time: 9:44 AM
 */

namespace LibreEHR\Core\Emr\Repositories;

use Illuminate\Support\Facades\DB;
use LibreEHR\Core\Contracts\AppointmentInterface;
use LibreEHR\Core\Contracts\AppointmentRepositoryInterface;
use LibreEHR\Core\Emr\Eloquent\AppointmentData as Appointment;
use LibreEHR\Core\Emr\Eloquent\PatientTracker;
use LibreEHR\Core\Emr\Eloquent\PatientTrackerElement;

class AppointmentRepository extends AbstractRepository implements AppointmentRepositoryInterface
{

    const REPEAT_EVERY_DAY      = 0;
    const REPEAT_EVERY_WEEK     = 1;
    const REPEAT_EVERY_MONTH    = 2;
    const REPEAT_EVERY_YEAR     = 3;
    const REPEAT_EVERY_WORK_DAY = 4;

    protected $weekend_days = [ 'Sat', 'Sun' ];

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

    private function encodeStatus($status)
    {
        $conditions= [
            0 => ['mapping', 'like', $status],
            1 => ['list_id', 'like', 'apptstat']
        ];
        return DB::connection($this->connection)->table('list_options')->where($conditions)->value('option_id');
    }

    public function update($id, $data)
    {

        $appointment = new Appointment();
        $appointment->setConnectionName($this->connection);
        $appointmentInterface = $appointment->find($id);
        foreach ($data as $k => $ln) {
            if ($k == 'status') {
                $appointmentInterface->setPcApptStatus($ln);
                $patientTracker = new PatientTracker();
                $patientTracker->setConnectionName( $this->connection );
                $patientTracker = $patientTracker->where( 'eid', $appointmentInterface->getId() )->first();

                $ptid = null;
                if ( $patientTracker ) {
                    $ptid = $patientTracker->id;
                } else {
                    // Doens't exist need to create
                    $patientTracker = new PatientTracker();
                    $patientTracker->setConnectionName( $this->connection );
                    $patientTracker->date = date( 'Y-m-d H:i:s' );
                    $patientTracker->apptdate = $appointmentInterface->getPcEventDate();
                    $patientTracker->appttime = $appointmentInterface->pc_startTime;
                    $patientTracker->eid = $appointmentInterface->getId();
                    $patientTracker->pid = $appointmentInterface->getPatientId();
                    $patientTracker->original_user = 'admin';
                    $patientTracker->encounter = 0;
                    $patientTracker->lastseq = 1;
                    $ptid = $patientTracker->save();
                    $ptid = $patientTracker->id;
                }

                $trackerElement = new PatientTrackerElement();
                $trackerElement->setConnectionName( $this->connection );
                $elements = $trackerElement->where( 'pt_tracker_id', $ptid )->get();
                $maxseq = 0;
                foreach ( $elements as $element ) {
                    $maxseq = max( $maxseq, $element->seq );
                }
                $trackerElement->pt_tracker_id = $ptid;
                $trackerElement->start_datetime = date( 'Y-m-d H:i' );
                $trackerElement->room = '';
                $trackerElement->status = $this->encodeStatus( $ln );
                $trackerElement->seq = $maxseq + 1;
                $trackerElement->user = 'admin';
                $trackerElement->save();

                $patientTracker->lastseq = $trackerElement->seq;
                $patientTracker->save();

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

    /**
     * @param $slotStartTime
     * @param $slotEndTime
     * @param $constraints
     * @return bool
     *
     * Check the start time and end time against the constraints and make sure
     * that the start time and end break any constraints
     *
     */
    protected function checkConstraints( $slotStartTime, $slotEndTime, $constraints )
    {
        $pass = true;
        foreach ( $constraints as $constraint ) {

            if ( $constraint[0] != 'DATE' &&
                $constraint[0] != 'DATETIME' ) {
                continue;
            }

            $constraintOperator = $constraint[1];

            // Check to see if the constraint has a time, if it doesn't can be all day
            if ( strlen( $constraint[2] ) <= 10 &&
                strpos( $constraintOperator, '<' ) !== false ) {
                $constraintTimestamp = strtotime($constraint[2]." 23:59" );
            } else if ( strlen( $constraint[2] ) <= 10 &&
                strpos( $constraintOperator, '>' ) !== false ) {
                $constraintTimestamp = strtotime($constraint[2]." 00:00" );
            } else {
                $constraintTimestamp = strtotime($constraint[2]);
            }

            $startFormatted = date( 'Y-m-d h:i', $slotStartTime );
            $endFormatted = date( 'Y-m-d h:i', $slotEndTime );


            // slot must start before constraint timestamp
            if ($constraintOperator == '<=' &&
                $slotEndTime > $constraintTimestamp ) {
                $pass = false;
                break;
            } else if ($constraintOperator == '<' &&
                $slotEndTime >= $constraintTimestamp ) {
                $pass = false;
                break;
            } else if ($constraintOperator == '>=' &&
                $slotStartTime < $constraintTimestamp ) {
                $pass = false;
                break;
            } else if ($constraintOperator == '>' &&
                // $slotStartTime <= $constraintTimestamp ) { This should eb <= but the gponline app expects first slot on the gt barrier
                $slotStartTime < $constraintTimestamp ) {
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
                if ( strlen( $constraint[2] ) <= 10 ) {
                    $min = substr( $constraint[2], 0, 10 ) . " 00:00";
                } else {
                    $min = $constraint[2];
                }
                break;
            }
        }

        return $min;
    }

    public function findBiggest( $constraints )
    {
        $max = null;
        $le = false;
        foreach ( $constraints as $key => $constraint ) {
            if ( $key == 'lt' || $key == 'le' ) {
                if ( strlen( $constraint[2] ) <= 10 ) {
                    $max = substr( $constraint[2], 0, 10 )." 23:59";
                } else {
                    $max = $constraint[2];
                }
                if ( $key == 'le' ) {
                    $le = true;
                }
                break;
            }
        }

        if ( $le ) {
            $maxplusone = strtotime( $max ) + (24 * 60 * 60);
            $max = date( 'Y-m-d H:i', $maxplusone );
        }

        return $max;
    }

    protected $slots, $slotsecs, $slotstime, $slotbase, $slotcount, $input_catid;

    // Record an event into the slots array for a specified day.
    public function doOneDay( $catid, $udate, $starttime, $duration, $prefcatid, $apptstatus )
    {
        $udate = strtotime( $starttime, $udate );
        if ( $udate < $this->slotstime ) return;
        $i = (int)($udate / $this->slotsecs) - $this->slotbase;
        $iend = (int)(($duration + $this->slotsecs - 1) / $this->slotsecs) + $i;
        if ( $iend > $this->slotcount ) $iend = $this->slotcount;
        if ( $iend <= $i ) $iend = $i + 1;
        for ( ; $i < $iend; ++$i ) {
            if ( $iend >= $this->slotcount ) continue;

            if ( $catid == 2 ) {        // in office
                // If a category ID was specified when this popup was invoked, then select
                // only IN events with a matching preferred category or with no preferred
                // category; other IN events are to be treated as OUT events.
                if ( $this->input_catid ) {
                    if ( $prefcatid == $this->input_catid || !$prefcatid )
                        $this->slots[ $i ] |= 1;
                    else
                        $this->slots[ $i ] |= 2;
                } else {
                    $this->slots[ $i ] |= 1;
                }
                break; // ignore any positive duration for IN
            } else if ( $catid == 3 ) { // out of office
                $this->slots[ $i ] |= 2;
                break; // ignore any positive duration for OUT
            //} else if ( ( $catid == 5 || $catid == 9 ) &&
             //       ( $apptstatus == '^' || $apptstatus == 'x' || $apptstatus == '-' ) ) {
              //  $this->slots[ $i ] |= 1; // can still book
             //   break;
            } else { // all others reserve time
                $this->slots[ $i ] |= 4;
            }
        }
    }

    public function getSlots( $data )
    {
        $this->slots = $this->slotsecs = $this->slotstime = $this->slotbase = $this->slotcount = $this->input_catid = null;
        $constraints = $this->provideSlotConditions( $data );
        $from_date = $this->findSmallest( $constraints );
        $to_date = $this->findBiggest( $constraints );
        $allEvents = DB::connection($this->connection)->table( 'libreehr_postcalendar_events' );
//        if ( $constraints['pc_aid'] ) {
//            $allEvents->where(   );
//        }
        $providerId = $data['provider'];
        $allEvents->where( function ( $query ) use ( $providerId ) {
            // match provider ID
            $query->where( [
                [ 'pc_aid', '=', $providerId ] ] );
        })->where( function ( $query ) use ( $from_date, $to_date ) {
            // match Dates
            $query->where( [
                [ 'pc_endDate', '>=', substr( $from_date, 0, 10 ) ],
                [ 'pc_eventDate', '<=', substr( $to_date, 0, 10 ) ] ] )
             ->orWhere( [
                 [ 'pc_endDate', '=', '0000-00-00' ],
                 [ 'pc_eventDate', '>=', substr( $from_date, 0, 10 ) ],
                 [ 'pc_eventDate', '<=', substr( $to_date, 0, 10 ) ] ] );
        });

        $allEvents = $allEvents->get()->toArray();

        $events2 = [];

        // seconds per time slot
        $this->slotsecs = $this->getGlobalCalendarInterval()*60;

        $catslots = 1;
//        if ($input_catid) {
//            $srow = sqlQuery("SELECT pc_duration FROM libreehr_postcalendar_categories WHERE pc_catid = ?", array($input_catid) );
//            if ($srow['pc_duration']) $catslots = ceil($srow['pc_duration'] / $slotsecs);
//        }

        // compute starting time slot number and number of slots.
        $this->slotstime = strtotime( substr( $from_date, 0, 10 ) );
        $this->slotetime = strtotime( substr( $to_date, 0, 10 ) );
        $this->slotbase  = (int) ($this->slotstime / $this->slotsecs);
        $this->slotcount = (int) ($this->slotetime / $this->slotsecs) - $this->slotbase;

        if ($this->slotcount <= 0 || $this->slotcount > 100000) die(xlt("Invalid date range"));

        $slotsperday = (int) (60 * 60 * 24 / $this->slotsecs);

        // Compute the number of time slots for the given event duration, or if
        // none is given then assume the default category duration.
        $evslots = $catslots;
//        if (isset($_REQUEST['evdur'])) {
//            $evslots = 60 * $_REQUEST['evdur'];
//            $evslots = (int) (($evslots + $slotsecs - 1) / $slotsecs);
//        }


        // Create and initialize the slot array. Values are bit-mapped:
        //   bit 0 = in-office occurs here
        //   bit 1 = out-of-office occurs here
        //   bit 2 = reserved
        // So, values may range from 0 to 7.
        //
        $this->slots = array_pad(array(), $this->slotcount, 0);

        foreach ( $allEvents as $row ) {
            $thistime = strtotime($row->pc_eventDate . " 00:00:00");
            if ($row->pc_recurrtype) {

                preg_match('/"event_repeat_freq_type";s:1:"(\d)"/', $row->pc_recurrspec, $matches);
                $repeattype = $matches[1];

                preg_match('/"event_repeat_freq";s:1:"(\d)"/', $row->pc_recurrspec, $matches);
                $repeatfreq = $matches[1];
                if ($row->pc_recurrtype == 2) {
                    // Repeat type is 2 so frequency comes from event_repeat_on_freq.
                    preg_match('/"event_repeat_on_freq";s:1:"(\d)"/', $row->pc_recurrspec, $matches);
                    $repeatfreq = $matches[1];
                }
                if (! $repeatfreq) $repeatfreq = 1;

                preg_match('/"event_repeat_on_num";s:1:"(\d)"/', $row->pc_recurrspec, $matches);
                $my_repeat_on_num = $matches[1];

                preg_match('/"event_repeat_on_day";s:1:"(\d)"/', $row->pc_recurrspec, $matches);
                $my_repeat_on_day = $matches[1];

                // This gets an array of exception dates for the event.
                $exdates = array();
                if (preg_match('/"exdate";s:\d+:"([0-9,]*)"/', $row->pc_recurrspec, $matches)) {
                    $exdates = explode(",", $matches[1]);
                }

                $endtime = strtotime($row->pc_endDate . " 00:00:00") + (24 * 60 * 60);
                if ($endtime > $this->slotetime) $endtime = $this->slotetime;

                $repeatix = 0;
                while ($thistime < $endtime) {
                    $adate = getdate($thistime);
                    $thisymd = sprintf('%04d%02d%02d', $adate['year'], $adate['mon'], $adate['mday']);

                    // Skip the event if a repeat frequency > 1 was specified and this is
                    // not the desired occurrence, or if this date is in the exception array.
                    if (!$repeatix && !in_array($thisymd, $exdates)) {
                        $this->doOneDay($row->pc_catid, $thistime, $row->pc_startTime,
                            $row->pc_duration, $row->pc_prefcatid, $row->pc_apptstatus);
                    }
                    if (++$repeatix >= $repeatfreq) $repeatix = 0;

                    if ($row->pc_recurrtype == 2) {
                        // Need to skip to nth or last weekday of the next month.
                        $adate['mon'] += 1;
                        if ($adate['mon'] > 12) {
                            $adate['year'] += 1;
                            $adate['mon'] -= 12;
                        }
                        if ($my_repeat_on_num < 5) { // not last
                            $adate['mday'] = 1;
                            $dow = jddayofweek(cal_to_jd(CAL_GREGORIAN, $adate['mon'], $adate['mday'], $adate['year']));
                            if ($dow > $my_repeat_on_day) $dow -= 7;
                            $adate['mday'] += ($my_repeat_on_num - 1) * 7 + $my_repeat_on_day - $dow;
                        }
                        else { // last weekday of month
                            $adate['mday'] = cal_days_in_month(CAL_GREGORIAN, $adate['mon'], $adate['year']);
                            $dow = jddayofweek(cal_to_jd(CAL_GREGORIAN, $adate['mon'], $adate['mday'], $adate['year']));
                            if ($dow < $my_repeat_on_day) $dow += 7;
                            $adate['mday'] += $my_repeat_on_day - $dow;
                        }
                    } // end recurrtype 2

                    else { // recurrtype 1
                        if ($repeattype == 0)        { // daily
                            $adate['mday'] += 1;
                        } else if ($repeattype == 1) { // weekly
                            $adate['mday'] += 7;
                        } else if ($repeattype == 2) { // monthly
                            $adate['mon'] += 1;
                        } else if ($repeattype == 3) { // yearly
                            $adate['year'] += 1;
                        } else if ($repeattype == 4) { // work days
                            if ($adate['wday'] == 5)      // if friday, skip to monday
                                $adate['mday'] += 3;
                            else if ($adate['wday'] == 6) // saturday should not happen
                                $adate['mday'] += 2;
                            else
                                $adate['mday'] += 1;
                        } else {
                            die("Invalid repeat type '" . text($repeattype) ."'");
                        }
                    } // end recurrtype 1

                    $thistime = mktime(0, 0, 0, $adate['mon'], $adate['mday'], $adate['year']);
                }
            } else {
                $this->doOneDay($row->pc_catid, $thistime, $row->pc_startTime,
                    $row->pc_duration, $row->pc_prefcatid, $row->pc_apptstatus);
            }
        }

        // Mark all slots reserved where the provider is not in-office.
        // Actually we could do this in the display loop instead.
        $inoffice = false;
        for ($i = 0; $i < $this->slotcount; ++$i) {
            if (($i % $slotsperday) == 0) $inoffice = false;
            if ($this->slots[$i] & 1) $inoffice = true;
            if ($this->slots[$i] & 2) $inoffice = false;
            if (! $inoffice) { $this->slots[$i] |= 4; $prov[$i] = $i; }
        }

        $availableSlots = array();
        for ($i = 0; $i < $this->slotcount; ++$i) {

            $available = true;
            for ($j = $i; $j < $i + $evslots; ++$j) {
                if ($this->slots[$j] >= 4) $available = false;
            }
            if (!$available) continue; // skip reserved slots

            $utime = ($this->slotbase + $i) * $this->slotsecs;

            if ( !$this->checkConstraints( $utime, $utime + $this->slotsecs, $constraints ) ) {
                continue;
            }

            $availableSlots[] = [
                'startTimestamp' => $utime,
                'endTimestamp' => $utime + $this->slotsecs,
                'status' => 'available',
            ];

            // If the duration is more than 1 slot, increment $i appropriately.
            // This is to avoid reporting available times on undesirable boundaries.
            $i += $evslots - 1;
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
        $conditions['pc_aid'] = ['pc_aid', '=', $data['provider']];
        $conditionTemplate = 'DATE';

        foreach($data as $k => $ln) {

            if ( strpos( $ln, 'T' ) !== false ) {
                $conditionTemplate = 'DATETIME';
            }

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
            if (count($this->weekend_days) === 2){
                if ($nextWorkDOW == $this->weekend_days[0]) {
                    $f+=2;
                }elseif($nextWorkDOW == $this->weekend_days[1]){
                    $f++;
                }
            } elseif(count($this->weekend_days) === 1 && $nextWorkDOW === $this->weekend_days[0]) {
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

        if (in_array($day, $this->weekend_days)) {
            return true;
        } else {
            return false;
        }
    }
}
