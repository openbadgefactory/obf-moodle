<?php

/* 
 * Copyright (c) 2017 Discendum Oy

 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:

 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.

 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.

 */

global $CFG;
require_once(__DIR__ . '/item_base.php');

require_once($CFG->dirroot . '/user/lib.php');


/**
 * Totara program and certificate completion criterion -class.
 *
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class obf_criterion_profile extends obf_criterion_course {
    protected $criteriatype = obf_criterion_item::CRITERIA_TYPE_PROFILE;
    
    /**
     * @var string $requiredparam
     * @see obf_criterion_course::save_params
     */
    protected $requiredparam = 'field';
    /**
     * @var string[] $optionalparams Optional params to be saved.
     * @see obf_criterion_course::save_params
     */
    protected $optionalparams = array();
    
    
    /**
     * Add appropriate parameter elements to the criteria form
     *
     */
    public function config_options(&$mform, $param) {
        global $OUTPUT;
        $prefix = $this->requiredparam . '_';

        if ($param['error']) {
            $parameter[] =& $mform->createElement('advcheckbox', $prefix . $param['id'], '',
                    $OUTPUT->error_text($param['name']), null, array(0, $param['id']));
            $mform->addGroup($parameter, 'param_' . $prefix . $param['id'], '', array(' '), false);
        } else {
            $parameter[] =& $mform->createElement('advcheckbox', $prefix . $param['id'], '', $param['name'], null, array(0, $param['id']));
            $parameter[] =& $mform->createElement('static', 'break_start_' . $param['id'], null, '<div style="margin-left: 3em;">');

            if (in_array('grade', $this->optionalparams)) {
                $parameter[] =& $mform->createElement('static', 'mgrade_' . $param['id'], null, get_string('mingrade', 'badges'));
                $parameter[] =& $mform->createElement('text', 'grade_' . $param['id'], '', array('size' => '5'));
                $mform->setType('grade_' . $param['id'], PARAM_INT);
            }

            if (in_array('bydate', $this->optionalparams)) {
                $parameter[] =& $mform->createElement('static', 'complby_' . $param['id'], null, get_string('bydate', 'badges'));
                $parameter[] =& $mform->createElement('date_selector', 'bydate_' . $param['id'], "", array('optional' => true));
            }

            $parameter[] =& $mform->createElement('static', 'break_end_' . $param['id'], null, '</div>');
            $mform->addGroup($parameter, 'param_' . $prefix . $param['id'], '', array(' '), false);
            if (in_array('grade', $this->optionalparams)) {
                $mform->addGroupRule('param_' . $prefix . $param['id'], array(
                    'grade_' . $param['id'] => array(array(get_string('err_numeric', 'form'), 'numeric', '', 'client'))));
            }
            $mform->disabledIf('bydate_' . $param['id'] . '[day]', 'bydate_' . $param['id'] . '[enabled]', 'notchecked');
            $mform->disabledIf('bydate_' . $param['id'] . '[month]', 'bydate_' . $param['id'] . '[enabled]', 'notchecked');
            $mform->disabledIf('bydate_' . $param['id'] . '[year]', 'bydate_' . $param['id'] . '[enabled]', 'notchecked');
            $mform->disabledIf('param_' . $prefix . $param['id'], $prefix . $param['id'], 'notchecked');
        }

        // Set default values.
        $mform->setDefault($prefix . $param['id'], $param['checked']);
        if (isset($param['bydate'])) {
            $mform->setDefault('bydate_' . $param['id'], $param['bydate']);
        }
        if (isset($param['grade'])) {
            $mform->setDefault('grade_' . $param['id'], $param['grade']);
        }
    }
    
    public function get_affected_users() {
        $context = context_system::instance();
        $selectfields = 'u.id, u.email';
        $fields = $this->get_profile_field_ids();
        foreach($fields as $field) {
            if (!is_number($field) && $field != 'email') {
                $selectfields .= ', u.'.$field;
            }
        }
        $users = get_users_by_capability($context, 'local/obf:earnbadge', $selectfields);
        return $users;
    }
    
    public function get_name() {
        return 'Profile';
    }
    /**
     * Returns this criterion as text, including the name of the course.
     *
     * @return string
     */
    public function get_text() {
        $html = html_writer::tag('strong', $this->get_name());
        return $html;
    }
    
    public function get_profile_field_ids() {
        $params = $this->get_params();
        return array_keys(array_filter($params, function ($v) {
            return array_key_exists('field', $v) ? true : false;
        }));
    }
    
    /**
     * Returns the fields by id
     *
     * @param int[] $ids List if ids
     * @return stdClass[] Programs matching ids.
     */
    public static function get_profile_fields_by_ids($ids) {
        global $DB, $OUTPUT;
        $output = array();
        
        foreach ($ids as $id) {
            if (is_numeric($id)) {
                $str = $DB->get_field('user_info_field', 'name', array('id' => $id));
            } else {
                $str = get_user_field_name($id);
            }
            $obj = new stdClass();
            $obj->id = $id;
            
            if (!$str) {
                $obj->fullname = $OUTPUT->error_text(get_string('error:nosuchfield', 'badges'));
            } else {
                $obj->fullname = $str;
            }
            $output[] = $obj;
        }
        return $output;
    }
    
    public function get_profile_fields() {
        $ids = $this->get_profile_field_ids();
        return self::get_profile_fields_by_ids($ids);
    }
    /**
     * Get text array to be printed on badge awarding rules -page.
     * @return array html encoded activity descriptions.
     */
    public function get_text_array() {
        $texts = array();
        $fields = $this->get_profile_fields();
        if (count($fields) == 0) {
            return $texts;
        }
        foreach ($fields as $field) {
            $html = html_writer::tag('strong', $field->fullname);
            $texts[] = $html;
        }
        return $texts;
    }
    
    /**
     * Prints required config fields for criteria forms.
     *
     * @param MoodleQuickForm& $mform
     * @param mixed& $obj Form object
     */
    public function get_form_config(&$mform, &$obj) {
        global $OUTPUT;
        $mform->addElement('hidden', 'criteriatype', obf_criterion_item::CRITERIA_TYPE_PROFILE);
        $mform->setType('criteriatype', PARAM_INT);

        if (!$this->exists() && $this->get_criteriatype() == obf_criterion_item::CRITERIA_TYPE_PROFILE) {
            $mform->addElement('hidden', 'createitem', 1);
            $mform->setType('createitem', PARAM_INT);

        }

        $mform->createElement('hidden', 'picktype', 'no');
        $mform->setType('picktype', PARAM_TEXT);
    }
    
    /**
     * Prints completion options to form.
     *
     * @param MoodleQuickForm& $mform
     * @param mixed& $obj Form object
     * @param mixed[] $items
     */
    public function get_options(&$mform, &$obj = null) {
      global $DB;

      $none = true;
      $existing = array();
      $missing = array();

      // Note: cannot use user_get_default_fields() here because it is not possible to decide which fields user can modify.
      $dfields = array('firstname', 'lastname', 'email', 'address', 'phone1', 'phone2', 'icq', 'skype', 'yahoo',
                       'aim', 'msn', 'department', 'institution', 'description', 'city', 'url', 'country');

      $sql = "SELECT uf.id as fieldid, uf.name as name, ic.id as categoryid, ic.name as categoryname, uf.datatype
              FROM {user_info_field} uf
              JOIN {user_info_category} ic
              ON uf.categoryid = ic.id AND uf.visible <> 0
              ORDER BY ic.sortorder ASC, uf.sortorder ASC";

      // Get custom fields.
      $cfields = $DB->get_records_sql($sql);
      $cfids = array_map(create_function('$o', 'return $o->fieldid;'), $cfields);

      if ($this->id !== 0) {
          $existing = array_keys($this->get_params());
          $missing = array_diff($existing, array_merge($dfields, $cfids));
      }

      if (!empty($missing)) {
          $mform->addElement('header', 'category_profile', get_string('criteriaprofileheader', 'local_obf'));
          $mform->addHelpButton('category_profile', 'criteriaprofileheader', 'local_obf');
          foreach ($missing as $m) {
              $this->config_options($mform, array('id' => $m, 'checked' => true, 'name' => get_string('error:nosuchfield', 'local_obf'), 'error' => true));
              $none = false;
          }
      }

      if (!empty($dfields)) {
          $mform->addElement('header', 'first_header', $this->get_name());
          $mform->addHelpButton('first_header', 'criteria_' . $this->criteriatype, 'local_obf');
          foreach ($dfields as $field) {
              $checked = false;
              if (in_array($field, $existing)) {
                  $checked = true;
              }
              $this->config_options($mform, array('id' => $field, 'checked' => $checked, 'name' => get_user_field_name($field), 'error' => false));
              $none = false;
          }
      }

      if (!empty($cfields)) {
          foreach ($cfields as $field) {
              if (!isset($currentcat) || $currentcat != $field->categoryid) {
                  $currentcat = $field->categoryid;
                  $mform->addElement('header', 'category_' . $currentcat, format_string($field->categoryname));
              }
              $checked = false;
              if (in_array($field->fieldid, $existing)) {
                  $checked = true;
              }
              $this->config_options($mform, array('id' => $field->fieldid, 'checked' => $checked, 'name' => $field->name, 'error' => false));
              $none = false;
          }
      }
      $obj->setExpanded($mform, 'first_header', true);


      return array($none, get_string('noparamstoadd', 'badges'));
    }
    
    /**
     * Prints completion options to form.
     *
     * @param MoodleQuickForm& $mform
     * @param mixed& $obj Form object
     * @param mixed[] $items
     */
    public function get_form_completion_options(&$mform, $obj = null, $items = null) {
      // Radiobuttons to select whether this criterion is completed
      // when any of the fields are completed or all of them.
      $radiobuttons = array();
      $radiobuttons[] = $mform->createElement('radio', 'completion_method', '',
              get_string('criteriacompletionmethodprofileall', 'local_obf'),
              obf_criterion::CRITERIA_COMPLETION_ALL);
      $radiobuttons[] = $mform->createElement('radio', 'completion_method', '',
              get_string('criteriacompletionmethodprofileany', 'local_obf'),
              obf_criterion::CRITERIA_COMPLETION_ANY);

      $mform->addElement('header', 'header_completion_method',
              get_string('criteriacompletedwhen', 'local_obf'));
      $obj->setExpanded($mform, 'header_completion_method');
      $mform->addGroup($radiobuttons, 'radioar', '', '<br />', false);
      $criterion = $this->get_criterion();
      if ($criterion) {
        $mform->setDefault('completion_method', $criterion->get_completion_method());
      }
      
    }
    /**
     * Check if criteria is reviewable.
     * @return bool True if reviewable.
     */
    public function is_reviewable() {
        return $this->criterionid != -1 && count($this->get_profile_field_ids()) > 0 &&
                $this->criteriatype != obf_criterion_item::CRITERIA_TYPE_UNKNOWN;
    }
    /**
     * If criterion required a field.
     * Child class may override this function if required fields differ.
     * @param string $field Fielnd name.
     * @return bool True if field is required.
     */
    public function requires_field($field) {
        return in_array($field, array('criterionid'));
    }
    /**
     * Reviews criteria for single user.
     *
     * @param stdClass $user
     * @param obf_criterion $criterion The main criterion.
     * @param obf_criterion_item[] $otheritems Other items related to main criterion.
     * @param array& $extra Extra options passed to review method.
     * @return boolean If the course criterion is completed by the user.
     */
    public function review_for_user($user, $criterion = null, $otheritems = null, &$extra = null) {
        global $CFG, $DB;
        $requireall = $criterion->get_completion_method() == obf_criterion::CRITERIA_COMPLETION_ALL;

        $criterioncompleted = false;
        $userid = $user->id;
        
        $datepassed = false;
        
        $fields = $this->get_profile_field_ids();
        $found = 0;
        $requiredcustomfieldids = array();
        $requiredcustomfields = array();
        $customfields = array();
        $normalfields = array();
        foreach($fields as $field) {
            if (is_number($field)) {
                $requiredcustomfieldids[] = $field;
            } else {
                $normalfields[] = $field;
            }
        }
        if (!empty($requiredcustomfieldids)) {
            $customfields = profile_get_custom_fields();
            foreach($customfields as $field) {
                if (in_array($field->id, $requiredcustomfieldids)) {
                    $requiredcustomfields[] = $field->shortname;
                }
            }
            profile_load_custom_fields($user);
        }
        foreach ($normalfields as $field) {
            if (!empty($user->{$field})) {
                $found++;
            }
        }
        foreach ($requiredcustomfields as $field) {
            if (!empty($user->profile->{$field})) {
                $found++;
            }
        }
        if (
                false == $requireall && $found > 0 || 
                (count($requiredcustomfieldids) + count($normalfields)) == $found
            ) {
            $criterioncompleted = true;
        }
        
        
        return $criterioncompleted;
    }
    /**
     * This does not support multiple courses.
     * @return boolean false
     */
    public function criteria_supports_multiple_courses() {
        return false;
    }
}