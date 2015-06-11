<?php
require_once __DIR__ . '/item_base.php';
require_once __DIR__ . '/criterion.php';
require_once __DIR__ . '/course.php';
require_once __DIR__ . '/../badge.php';

/**
 * Class representing a single activity criterion.
 */
class obf_criterion_activity extends obf_criterion_course {
    /**
     * @var int The completed by -field of the activity criterion as a unix timestamp
     */
    protected $completedby = -1;

    /**
     * @var string For caching the name of the activity
     */
    protected $activityname = '';

    /**
     * @var obf_criterion The criterion this activity belongs to.
     */
    protected $criterion = null;
    protected $id = -1;
    protected $criterionid = -1;

    protected $criteriatype = obf_criterion_item::CRITERIA_TYPE_ACTIVITY;
    protected $required_param = 'module';
    protected $optional_params = array('completedby');
    /**
     * Get the instance of this class by id.
     *
     * @global moodle_database $DB
     * @param int $id The id of the activity criterion
     * @return obf_criterion_activity
     */
    public static function get_instance($id, $method = null) {
        global $DB;

        $record = $DB->get_record('obf_criterion_courses', array('id' => $id));
        $obj = new self();

        return $obj->populate_from_record($record);
    }

    /**
     * Initializes this object with values from $record
     *
     * @param \stdClass $record The record from Moodle's database
     * @return \obf_criterion_activity
     */
    public function populate_from_record(\stdClass $record) {
        $this->set_id($record->id)
                ->set_criterionid($record->obf_criterion_id)
                ->set_courseid($record->courseid)
                ->set_completedby($record->completed_by)
                ->set_criteriatype($record->criteria_type);
        // TODO:  params
        return $this;
    }

    /**
     * Saves this activity criterion to database. If it exists already, the
     * existing record will be updated.
     *
     * @global moodle_database $DB
     * @return mixed Returns this object if everything went ok, false otherwise.
     */
    public function save() {
        global $DB;

        $obj = new stdClass();
        $obj->obf_criterion_id = $this->criterionid;
        $obj->courseid = $this->courseid;
        $obj->completed_by = $this->has_completion_date() ? $this->completedby : null;
        $obj->criteria_type = $this->get_criteriatype();

        // Updating existing record
        if ($this->id > 0) {
            $obj->id = $this->id;
            $DB->update_record('obf_criterion_courses', $obj);
        }

        // Inserting a new record
        else {
            $id = $DB->insert_record('obf_criterion_courses', $obj);

            if (!$id) {
                return false;
            }

            $this->set_id($id);
        }

        return $this;
    }

    /**
     * Returns the name of the activity this criterion is related to.
     *
     * @global moodle_database $DB
     * @return string The full name of the activity.
     */
    public function get_activityname($cminstance) {
        global $DB;

        $activityname = '';
        $cmrecord = $DB->get_record('course_modules', array('id' => $cminstance));
        if ($cmrecord) {
            $modulename = $DB->get_field('modules', 'name',
                    array('id' => $cmrecord->module));
            $activityname = $DB->get_field($modulename, 'name', array('id' => $cmrecord->instance));
        }
        return $activityname;
    }
    public function get_name() {
        $params = $this->get_params();
        $name = '';
        foreach ($params as $key => $param) {
            if (array_key_exists('module',$param)) {
                $cminstance = $param['module'];
                $name .= (empty($name) ? '' : ', ') . $this->get_activityname($cminstance);
            }
        }
        return $name;
    }
    /**
     * @param type $params
     * @return array ids of activity instances
     */
    private static function get_module_instanceids_from_params($params) {
        $ids = array();
        foreach ($params as $key => $param) {
            if (array_key_exists('module',$param)) {
                $ids[] = $param['module'];
            }
        }
        return $ids;
    }

    /**
     * Get course activities
     */
    public static function get_course_activities($courseid) {
        global $DB;

        $activities = array();
        $cmrecords = $DB->get_records('course_modules', array('course' => $courseid));
        foreach ($cmrecords as $cmrecord) {
            $modulename = $DB->get_field('modules', 'name', array('id' => $cmrecord->module));
            $activities[$cmrecord->id] = $DB->get_field($modulename, 'name', array('id' => $cmrecord->instance));
        }
        return $activities;
    }

    /**
     * Returns this criterion as text, including the name of the activity.
     *
     * @return string
     */
    public function get_text() {
        $html = html_writer::tag('strong', $this->get_name());

        if ($this->has_completion_date()) {
            $html .= ' ' . get_string('completedbycriterion', 'local_obf',
                            userdate($this->completedby,
                                    get_string('dateformatdate', 'local_obf')));
        }

        return $html;
    }
    /**
     * @return array html encoded activity descriptions.
     */
    public function get_text_array() {
        $params = $this->get_params();
        $modids = self::get_module_instanceids_from_params($params);
        $texts = array();
        foreach ($modids as $modid) {
            $html = html_writer::tag('strong', $this->get_activityname($modid));
            if (array_key_exists('completedby', $params[$modid])) {
                $html .= ' ' . get_string('completedbycriterion', 'local_obf',
                                userdate($params[$modid]['completedby'],
                                        get_string('dateformatdate', 'local_obf')));
            }
            $texts[] = $html;
        }
        return $texts;
    }

    /**
     * Returns this criterion as text without the activity name.
     *
     * @return string
     */
    public function get_text_for_single_activity() {
        $html = get_string('toearnthisbadge', 'local_obf');

        if ($this->has_completion_date()) {
            $html .= ' ' . get_string('completedbycriterion', 'local_obf',
                            userdate($this->completedby,
                                    get_string('dateformatdate', 'local_obf')));
        }

        $html .= '.';

        return $html;
    }

    /**
     * Print activities to form.
     * @param moodle_form $mform
     * @param type $modules modules so the database is not accessed too much
     * @param type $params
     */
    private function get_form_activities($mform, $modules, $params) {
        $mform->addElement('html',html_writer::tag('p', get_string('selectactivity', 'local_obf')));

        $existing = array();
        $completedby = array_map(function($a) {
                    if (array_key_exists('completedby', $a)) {
                        return $a['completedby'];
                    }
                    return false;
                }, $params);
        foreach ($params as $key => $param) {
            if (array_key_exists('module', $param)) {
                $existing[] = $param['module'];
            }
        }

        foreach ($modules as $key => $mod) {
            $mform->addElement('advcheckbox', 'module_' . $key,
                    $mod,null, array('group' => 1), array(0, $key));
            $mform->addElement('date_selector', 'completedby_' . $key,
                    get_string('activitycompletedby', 'local_obf'),
                    array('optional' => true, 'startyear' => date('Y')));
        }
        foreach ($existing as $modid) {
            $mform->setDefault('module_'.$modid, $modid);
        }
        foreach ($completedby as $key => $value) {
            $mform->setDefault('completedby_'.$key, $value);
        }
    }
    /**
     * Prints criteria activity settings for criteria forms.
     * @param moodle_form $mform
     */
    public function get_options($mform) {
        global $OUTPUT;

        $modules = obf_criterion_activity::get_course_activities($this->get_courseid());
        $params = $this->get_params();

        $this->get_form_activities($mform, $modules, $params);
    }
    /**
     * Prints required config fields for criteria forms.
     * @param moodle_form $mform
     */
    public function get_form_config($mform) {
        global $OUTPUT;
        $mform->addElement('hidden','criteriatype', obf_criterion_item::CRITERIA_TYPE_ACTIVITY);
        $mform->setType('criteriatype', PARAM_INT);

        $mform->createElement('hidden','picktype', 'no');
        $mform->setType('picktype', PARAM_TEXT);
    }
    /**
     * Save params. (activity selections and completedby dates)
     * @param type $data
     */
    public function save_params($data) {
        global $DB;
        $this->save();

        $params = (array)$data;
        // Filter out empty params
        $params = array_filter($params);
        // Get params matching required params
        $match = array_merge($this->optional_params, array($this->required_param));
        $regex = implode('|', array_map(function($a) { return $a .'_';}, $match));
        $requiredkeys = preg_grep('/^('.$regex.').*$/', array_keys($params));

        $paramtable = 'obf_criterion_params';


        $existing = $DB->get_fieldset_select($paramtable, 'name', 'obf_criterion_id = ?', array($this->get_criterionid()));
        $todelete = array_diff($existing, $requiredkeys);
        $todelete = array_unique($todelete);
        if (!empty($todelete)) {
            list($insql,$inparams) = $DB->get_in_or_equal($todelete, SQL_PARAMS_NAMED, 'cname', true);
            $inparams = array_merge($inparams, array('critid' => $this->get_criterionid()));
            $DB->delete_records_select($paramtable, 'obf_criterion_id = :critid AND name '.$insql, $inparams );
        }
        foreach ($requiredkeys as $key) {
            if (in_array($key, $existing)) {
                $toupdate = $DB->get_record($paramtable,
                        array('obf_criterion_id' => $this->get_criterionid(),
                                'name' => $key) );
                $toupdate->value = $params[$key];
                $DB->update_record($paramtable, $toupdate, true);
            } else {
                $obj = new stdClass();
                $obj->obf_criterion_id = $this->get_criterionid();
                $obj->name = $key;
                $obj->value = $params[$key];
                $DB->insert_record($paramtable, $obj);
            }
        }
    }
    /**
     * Activities do not support multiple courses.
     * @return boolean false
     */
    public function criteria_supports_multiple_courses() {
        return false;
    }
}
