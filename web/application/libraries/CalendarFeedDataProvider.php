<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Data provider for the iCal export feature in the user dashboard and the calendar feed API.
 *
 * Architecturally, this class provides a layer of business logic/data processing that sits between controller and model.
 *
 * @todo Calendar events returned from the Model layer should be accessed via some sort of DTO/unified interface,
 * @todo regardless whether they are are backed by offerings or ILMs. Juggling arrays is getting cumbersome.
 * @todo [ST 2014/01/07]
 */
class CalendarFeedDataProvider
{
    /**
     * @var CI_Controller
     */
    protected $_ci;

    /**
     * Constructor.
     */
    public function __construct ()
    {
        // hold on to the CI instance
        $this->_ci =& get_instance();

        // load the models that we need.
        // chances are that they are already loaded, but better safe than sorry.
        $this->_ci->load->model('Canned_Queries', 'queries', true);
        $this->_ci->load->model('Session', 'iliosSession', true);
    }

    /**
     * Retrieves calendar events comprised of offerings and independent learning sessions
     * for a given user in the context of a given school and given user role(s).
     *
     * The output of this method can be fed into <code>ICalExporter::toICal()</code> to produce an iCalendar feed.
     *
     * @param int $userId The user id.
     * @param int|null $schoolId An "owning" school id. If NULL is given then offerings across all schools are queried.
     * @param array $userRoles An list of user role ids providing.
     * @return array An array of assoc. arrays. Each item represents a calendar event, containing values keyed off by:
     *     'event_id'   ... A unique event id.
     *     'text'       ... Event description/summary.
     *     'start_date' ... Event start date.
     *     'end_date'   ... Event end date.
     *     'event_pid'  ... The parent event id of this event (in a series of event).
     *     'rec_type'   ... Recurrence type.
     *     'rec_length' ... The event duration in seconds.
     *     'location'   ... Event location.
     *
     * @see ICalExporter::toICal()
     */
    public function getData ($userId, $schoolId = null, array $userRoles = array())
    {
        // Specify the period of events to be included in the export.
        $timestart = strtotime("-3 months");     // last 3 months
        $timeend = strtotime("+3 months");   // next 3 months (~90 days)

        $offerings = $this->_ci->queries->getOfferingsDetailsForCalendarFeed($userId, $schoolId, $userRoles, $timestart,
            $timeend);
        $ilm_sessions = $this->_ci->queries->getSILMsDetailsForCalendarFeed($userId, $schoolId, $userRoles, $timestart,
            $timeend);

        $hostaddress = str_replace('http://', '', base_url());
        $hostaddress = str_replace('https://', '', $hostaddress);

        // strip the slash at the end of the string.
        $hostaddress = rtrim($hostaddress, '/');

        // Create an array of events for iCalendar from offerings array;
        // also combine offerings with same offering_ids.
        $events = array();
        foreach ($offerings as $offering) {
            $event = array();
            $id = $offering['offering_id'];

            $event['event_id'] = $id . '@' . $hostaddress; // UID
            $event['text'] = $offering['session_title']; // SUMMARY
            $event['utc_time'] = true;
            $event['event_pid'] = null;
            $event['rec_type'] = null;
            $event['event_length'] = null;
            $event['start_date'] = $offering['start_date'];
            $event['end_date'] = $offering['end_date'];
            if ($offering['published_as_tbd'] || $offering['course_published_as_tbd']) {
                $event['location'] = $this->_ci->languagemap->t('general.acronyms.to_be_decided');
                $event['event_details'] = $this->_ci->languagemap->t('general.acronyms.to_be_decided');
            } else {
                $event['location'] = array_key_exists('room', $offering) ? $offering['room'] : null;  // LOCATION
                $event['event_details'] = $this->_eventDetailsToText($offering['description'], $offering['session_type'],
                    $offering['supplemental'], $offering['attire_required'], $offering['equipment_required'],
                    $offering['instructors'], $offering['session_objectives'], $offering['session_materials'],
                    $offering['course_objectives'], $offering['course_materials']
                );
            }
            $events[$id] = $event;
        }

        foreach ($ilm_sessions as $session) {

            $event = array();
            // Add a 'ilm-' prefex to the ilm_session id in case it conflicts with offering id.
            $id = 'ilm-' . $session['ilm_session_facet_id'];

            $event['event_id'] = $id . '@' . $hostaddress; // UID

            $event['text'] = $this->_ci->languagemap->t('course_management.session.independent_learning_short') . ': ';
            $event['text'] .= $session['hours'] . ' ';
            $event['text'] .= strtolower($this->_ci->languagemap->t('general.terms.hours')) . ' ';
            $event['text'] .= strtolower($this->_ci->languagemap->t('general.phrases.due_by')) . ' ';
            $event['text'] .= strftime('%a, %b %d', strtotime($session['due_date'])) . ' - ';
            $event['text'] .= $session['course_title'].' - ' . $session['session_title']; // SUMMARY

            // gotta convert start/end date to UTC
            // @see http://stackoverflow.com/a/5841145
            $startDate  = new DateTime($session['due_date'] . ' 17:00:00');
            $startDate->setTimezone(new DateTimeZone('UTC'));
            $event['start_date'] = $startDate->format('Y-m-d H:i:s');

            $endDate = new DateTime($session['due_date'] . ' 17:30:00');
            $endDate->setTimezone(new DateTimeZone('UTC'));
            $event['end_date'] = $endDate->format('Y-m-d H:i:s');
            $event['event_pid'] = null;
            $event['utc_time'] = true;
            $event['rec_type'] = null;
            $event['event_length'] = null;
            $event['location'] = null;
            if ($session['published_as_tbd'] || $session['course_published_as_tbd']) {
                $event['event_details'] = $this->_ci->languagemap->t('general.acronyms.to_be_decided');
            } else {
                $event['event_details'] = $event['event_details'] = $this->_eventDetailsToText($session['description'],
                    $session['session_type'], $session['supplemental'], $session['attire_required'],
                    $session['equipment_required'], $session['instructors'], $session['session_objectives'],
                    $session['session_materials'], $session['course_objectives'], $session['course_materials']
                );
            }
            $events[$id] = $event;

        }
        return array_values($events);
    }

    /**
     * Helper method that transform a given HTML formatted string into plain text.
     *
     * @param string $s The markup.
     * @return string The plain text.
     */
    protected function _unHTML ($s)
    {
        return str_replace("\n", ' ', trim(strip_tags($s)));
    }

    /** Flattens out given event details to a string.
     * Any markup is removed from the content in the process.
     *
     * @param string $description The event description.
     * @param string $sessionType The type of the event-owning session.
     * @param bool $isSupplemental Flag indicating whether the owning session is supplemental.
     * @param bool $requiresSpecialAttire Flag indicating whether the owning session requires special attire.
     * @param bool $requiresSpecialEquipment Flag indicating whether the owning session requires special equipment.
     * @param array $instructors A list of instructors teaching the event.
     * @param array $sessionObjectives A list of session objectives for this event.
     * @param array $sessionMaterials A list of session learning materials for this event.
     * @param array $courseObjectives A list of course objectives for this event.
     * @param array $courseMaterials A list of course learning materials for this event.
     * @return string The aggregated given event details as text.
     */
    protected function _eventDetailsToText ($description = '', $sessionType = '', $isSupplemental = false,
                                                   $requiresSpecialAttire = false, $requiresSpecialEquipment = false,
                                                   array $instructors = array(), array $sessionObjectives = array(),
                                                   array $sessionMaterials = array(), array $courseObjectives = array(),
                                                   array $courseMaterials = array())
    {
        $rhett = '';

        if ($description) {
            $rhett = $this->_unHTML($description) . "\n";
        }

        // Taught by
        if (! empty($instructors)) {
            $rhett .= $this->_ci->languagemap->t('general.phrases.taught_by') . ' ' . implode(', ', $instructors) . "\n";
        }

        // This offering is a(n)
        $rhett .= $this->_ci->languagemap->t('dashboard.offering_description.offering_type') . ' ' . $sessionType;

        // is this event supplemental?
        if ($isSupplemental) {
            $rhett .= $this->_ci->languagemap->t('dashboard.offering_description.offering_supplemental_suffix') . "\n";
        } else {
            $rhett .= "\n";
        }
        // does this event require special attire?
        if ($requiresSpecialAttire) {
            $rhett .= $this->_ci->languagemap->t('dashboard.offering_description.special_attire'). "\n";
        }


        // does this event require special equipment?
        if ($requiresSpecialEquipment) {
            $rhett .= $this->_ci->languagemap->t('dashboard.offering_description.special_equipment'). "\n";
        }

        // flatten out session objectives
        if (! empty($sessionObjectives)) {
            $rhett .= "\n";
            $rhett .= $this->_ci->languagemap->t('general.terms.session') . ' ';
            $rhett .= $this->_ci->languagemap->t('general.terms.objectives') . "\n";
            foreach ($sessionObjectives as $objective)
                $rhett .= $this->_unHTML($objective) . "\n";
        }

        // flatten out session LMs
        if (! empty($sessionMaterials)) {
            $rhett .= "\n";
            $rhett .= $this->_ci->languagemap->t('general.terms.session') . ' ';
            $rhett .= $this->_ci->languagemap->t('general.phrases.learning_materials') . "\n";
            $rhett .= $this->_learningMaterialsToText($sessionMaterials);
            $rhett .= "\n";
        }

        // flatten out course objectives
        if (! empty($courseObjectives)) {
            $rhett .= "\n";
            $rhett .= $this->_ci->languagemap->t('general.terms.course') . ' ';
            $rhett .= $this->_ci->languagemap->t('general.terms.objectives') . "\n";
            foreach ($courseObjectives as $objective) {
                $rhett .= $this->_unHTML($objective) . "\n";
            }
        }

        // flatten out course LMs
        if (! empty($courseMaterials)) {
            $rhett .= "\n";
            $rhett .= $this->_ci->languagemap->t('general.terms.course') . ' ';
            $rhett .= $this->_ci->languagemap->t('general.phrases.learning_materials') . "\n";
            $rhett .= $this->_learningMaterialsToText($courseMaterials);
        }
        return $rhett;
    }

    /**
     * Converts a given list of learning materials (LMs) to flat text.
     *
     * @param array $learningMaterials A list of LMs.
     * @return string The LMs as text.
     *
     * @see Canned_Queries::getCourseMaterials()
     * @see Canned_Queries::geSessionMaterials()
     * @see CalendarFeedDataProvider::_eventDetailsToText()
     */
    private function _learningMaterialsToText (array $learningMaterials)
    {
        $rhett = '';
        foreach ($learningMaterials as $material) {

            // append title
            $rhett .= $this->_unHTML($material['title']);

            // check the LM type by attribute sniffing
            if (isset($material['citation'])) { // it's a citation!
                $rhett .= ' "' . $this->_unHTML($material['citation']) . '"';
            } elseif (isset($material['web_link'])) { // oh look, it's a web link. append the target url.
                $rhett .= ' (' . $material['web_link'] . ')';
            } else { // ... guess its a file then. link to it.
                if ($material['token']) { // no token? no link.
                    $rhett .= ' (' . base_url() . 'ilios.php/api/dl/' . $material['token'] . ')';
                } else {
                    $rhett .= ' (' . $this->_ci->languagemap->t('general.phrases.no_public_link') . ')';
                }
            }

            // if the LM is required then say so.
            if ($material['required']) {
                $rhett .= ' (' . $this->_ci->languagemap->t('general.terms.required') . ')';
            }
            // description
            $rhett .= ': ' . $this->_unHTML($material['description']) . "\n";

            // if notes are public and present then append them.
            if ($material['notes'] && $material['notes_are_public']) {
                $rhett .= $this->_ci->languagemap->t('general.terms.notes') . ':'
                    . $this->_unHTML($material['notes']) . "\n";
            }
        }
        return $rhett;
    }
}
