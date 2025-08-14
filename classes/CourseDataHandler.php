<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * course information.
 * @package   report_usercoursereports   
 * @copyright 2025 https://santoshmagar.com.np/
 * @author    santoshtmp7
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_usercoursereports;

use completion_info;
use core_course_category;
use core_course_list_element;
use course_modinfo;
use moodle_url;
use stdClass;

defined('MOODLE_INTERNAL') || die;

/**
 * class handler to get course data
 *
 * @package    report_usercoursereports
 * @copyright  2025 santoshtmp <https://santoshmagar.com.np/>
 * @author     santoshtmp
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class CourseDataHandler {

    /**
     * return course mod_customcert infomation
     * @param int $course_id course id     
     * @return string mod-custom certificate url
     */
    public static function course_mod_customcert($course_id, $user_id = '') {
        global $DB, $CFG;
        $customcert_data = [
            'mod_id' => '',
            'customcert_id' => '',
            'certificate_url' => '',
            'certificate_url_download' => '',
            'certificate_issues' => false,
            'certificate_issues_date' => 0,
            'certificate_issues_code' => ''
        ];
        $query = 'SELECT course_modules.id AS id, course_modules.instance AS instance
            FROM {course_modules} course_modules 
            JOIN {modules} modules ON modules.id = course_modules.module
            WHERE course_modules.course = :courseid AND modules.name = :modules_name AND course_modules.visible = :course_modules_visible AND  course_modules.deletioninprogress = :deletioninprogress
            Order By course_modules.id DESC
            LIMIT 1
            ';
        $params = [
            'courseid' => $course_id,
            'modules_name' => 'customcert',
            'course_modules_visible' => 1,
            'deletioninprogress' => 0
        ];
        $mod_customcert = $DB->get_record_sql($query, $params);
        if ($mod_customcert) {
            $customcert_data['mod_id'] = $mod_customcert->id;
            $customcert_data['customcert_id'] = $mod_customcert->instance;
            $customcert_data['certificate_url'] = $CFG->wwwroot . '/mod/customcert/view.php?id=' . $mod_customcert->id;
            $customcert_data['certificate_url_download']  = $CFG->wwwroot . '/mod/customcert/view.php?id=' . $mod_customcert->id . '&downloadown=1';
            if ($user_id && $mod_customcert->instance) {
                // $DB->record_exists('customcert_issues', ['userid' => $user_id, 'customcertid' => $customcert_id])
                $customcert_issues = $DB->get_record('customcert_issues', ['userid' => $user_id, 'customcertid' => $mod_customcert->instance]);
                if ($customcert_issues) {
                    $customcert_data['certificate_issues'] = true;
                    $customcert_data['certificate_issues_date'] = $customcert_issues->timecreated;
                    $customcert_data['certificate_issues_code'] = $customcert_issues->code;
                }
            }
        }
        return $customcert_data;
    }

    /**
     * Returns given course's summary with proper embedded files urls and formatted
     *
     * @param \stdClass $course
     * @return string
     */
    public static function get_course_formatted_summary($course) {
        global $CFG;
        if (!$course->summary) {
            return '';
        }
        require_once($CFG->libdir . '/filelib.php');
        $options = null;
        $context = \context_course::instance($course->id);
        $summary = file_rewrite_pluginfile_urls($course->summary, 'pluginfile.php', $context->id, 'course', 'summary', null);
        $summary = format_text($summary, $course->summaryformat);

        return $summary;
    }

    /**
     *  Returns the first course's summary image url
     * @param \stdClass $course
     * @param boolen check to return default image or null if there is no course image
     * @return string course image url or null
     */
    public static function get_course_image($course, $default_image_on_null = false) {
        global $CFG, $OUTPUT;
        $course = new core_course_list_element($course);

        foreach ($course->get_course_overviewfiles() as $file) {
            if ($file->is_valid_image()) {
                $url = moodle_url::make_file_url(
                    "$CFG->wwwroot/pluginfile.php",
                    '/' . $file->get_contextid() . '/' . $file->get_component() . '/' .
                        $file->get_filearea() . $file->get_filepath() . $file->get_filename(),
                    !$file->is_valid_image()
                );

                return $url->out();
            }
        }
        if ($default_image_on_null) {
            return $OUTPUT->get_generated_image_for_id($course->id);
        }
        return '';
    }

    /**
     *
     * @param core_course_list_element $course
     *
     */
    protected function check_course($course_ref = '') {
        global $COURSE, $DB;
        if (gettype($course_ref) == 'object') {
            $course = $course_ref;
        } else {
            $course_ref = (int)$course_ref;
            if (is_int($course_ref)) {
                if ($DB->record_exists('course', array('id' => $course_ref))) {
                    $course = get_course($course_ref);
                } else {
                    $course = $COURSE;
                }
            } else {
                $course = $COURSE;
            }
        }
        return  $course;
    }

    public function get_section_progress($course_ref, $section) {

        global $OUTPUT, $USER, $COURSE;
        $course = $this->check_course($course_ref);
        $context = \context_course::instance($COURSE->id);
        $roles = get_user_roles($context, $USER->id);
        $user_student = false;
        foreach ($roles as $key => $value) {
            if ($value->roleid == '5') {
                $user_student = true;
            }
        }
        if (!$user_student  || isguestuser() || empty($course)) {
            return;
        }

        $modinfo = get_fast_modinfo($course);
        if (empty($modinfo->sections[$section->section])) {
            return '';
        }

        // Generate array with count of activities in this section.
        $sectionmods = array();
        $total = 0;
        $complete = 0;
        $cancomplete = isloggedin() && !isguestuser();
        $completioninfo = new completion_info($course);
        foreach ($modinfo->sections[$section->section] as $cmid) {
            $thismod = $modinfo->cms[$cmid];

            if ($thismod->modname == 'label') {
                // Labels are special (not interesting for students)!
                continue;
            }

            if ($thismod->uservisible) {
                if (isset($sectionmods[$thismod->modname])) {
                    $sectionmods[$thismod->modname]['name'] = $thismod->modplural;
                    $sectionmods[$thismod->modname]['count']++;
                } else {
                    $sectionmods[$thismod->modname]['name'] = $thismod->modfullname;
                    $sectionmods[$thismod->modname]['count'] = 1;
                }
                if ($cancomplete && $completioninfo->is_enabled($thismod) != COMPLETION_TRACKING_NONE) {
                    $total++;
                    $completiondata = $completioninfo->get_data($thismod, true);
                    if (
                        $completiondata->completionstate == COMPLETION_COMPLETE ||
                        $completiondata->completionstate == COMPLETION_COMPLETE_PASS
                    ) {
                        $complete++;
                    }
                }
            }
        }

        if (empty($sectionmods)) {
            // No sections.
            return '';
        }
        // Output section completion data.
        $templatecontext = [];
        if ($total > 0) {
            $completion = new stdClass;
            $completion->complete = $complete;
            $completion->total = $total;

            $percent = 0;
            if ($complete > 0) {
                $percent = (int) (($complete / $total) * 100);
            }

            $templatecontext['percent'] =  $percent;
        }

        return $templatecontext;
    }


    /**
     * Function to fetch the customfield data.
     * @param  int $courseid  Course ID
     * @return Custom field data.
     */
    public static function get_course_metadata($courseid) {
        $handler = \core_customfield\handler::get_handler('core_course', 'course');
        $datas = $handler->get_instance_data($courseid);
        $metadata = [];
        foreach ($datas as $data) {
            if (empty($data->get_value())) {
                continue;
            }
            $metadata[$data->get_field()->get('shortname')] = $data->get_value();
        }
        return $metadata;
    }

    /**
     * Returns custom fields data for this course
     * @param int $course_id course id     
     * @param string $return_format ""/"raw" or "key_value" or "key_array"    
     * @return \core_customfield\data_controller[]
     *  if (!isset($COURSE->customfields)) {
     *    $COURSE->customfields = \core_course\customfield\course_handler::create()->get_instance_data($COURSE->id);
     * }
     */
    public static function get_custom_field_metadata($course_id, $return_format = 'raw') {
        $handler = \core_course\customfield\course_handler::create();
        // $customfields = $handler->get_instance_data($course_id);
        $customfields = $handler->export_instance_data($course_id);
        $metadata = [];

        foreach ($customfields as $data) {
            if ($return_format == 'key_value') {
                $metadata[$data->get_shortname()] = $data->get_value();
            } else if ($return_format == 'key_array') {
                $metadata[$data->get_shortname()] = [
                    'type' => $data->get_type(),
                    'value' => $data->get_value(),
                    'valueraw' => $data->get_data_controller()->get_value(),
                    'name' => $data->get_name(),
                    'shortname' => $data->get_shortname()
                ];
            } else {
                $metadata[] = [
                    'type' => $data->get_type(),
                    'value' => $data->get_value(),
                    'valueraw' => $data->get_data_controller()->get_value(),
                    'name' => $data->get_name(),
                    'shortname' => $data->get_shortname()
                ];
            }
        }
        return $metadata;
    }

    /**
     * 
     */
    public static function course_card_info($course_id, $default_values = false) {
        global $DB, $CFG, $OUTPUT;
        $courseinfo = [];

        if ($DB->record_exists('course', array('id' => $course_id))) {
            $course = $DB->get_record('course', ['id' => $course_id]);
            $course_categories = $DB->get_record('course_categories', ['id' => $course->category]);

            // get course enrolment plugin instance.
            $enrollment_methods = [];
            $index = 0;
            $enrolinstances = enrol_get_instances((int)$course->id, true);
            foreach ($enrolinstances as $key => $courseenrolinstance) {
                $enrollment_methods[$index]['enrol'] = $courseenrolinstance->enrol;
                $enrollment_methods[$index]['name'] = ($courseenrolinstance->name) ?: $courseenrolinstance->enrol;
                $enrollment_methods[$index]['cost'] = $courseenrolinstance->cost;
                $enrollment_methods[$index]['currency'] = $courseenrolinstance->currency;
                $enrollment_methods[$index]['roleid'] = $courseenrolinstance->roleid;
                $enrollment_methods[$index]['role_name'] = '';
                $index++;
            }

            // 
            $rep = array("</p>", "<br>", "</div>");
            $summary = str_replace($rep, " ", $course->summary);
            $summary = format_string($summary);
            if (strlen($summary) > 200) {
                $summary = substr($summary, 0, 200);
                $summary .= '...';
            }

            // 
            $courseinfo['id'] = $course->id;
            $courseinfo['categoryid'] = $course->category;
            $courseinfo['datatype'] = $course->category;
            $courseinfo['shortname'] =  format_string($course->shortname);
            $courseinfo['fullname'] =  format_string($course->fullname);
            $courseinfo['category_name'] = format_string($course_categories->name);
            $courseinfo['course_link'] = $CFG->wwwroot . '/course/view.php?id=' . $course->id;
            $courseinfo['course_category_link'] = (new \moodle_url('/course/index.php', array('id' => $course->category)))->out();
            $courseinfo['enrollment_link'] = (new \moodle_url('/enrol/index.php', array('id' => $course->id)))->out();
            $courseinfo['thumbnail_image_link'] = self::get_course_image($course, $default_values);
            $courseinfo['summary'] = self::get_course_formatted_summary($course);
            $courseinfo['short_summary'] = $summary;
            $courseinfo['arrow-right'] = $OUTPUT->image_url('icons/arrow-right', 'theme_yipl');
            $courseinfo['enrollment_methods'] = $enrollment_methods;

            // 
            return $courseinfo;
        }
        return false;
    }

    /**
     * @param int $course course id
     * @param boolen $default_values
     * @param boolen $timestamp
     * @return array|boolen 
     */
    public static function get_course_info($course_id, $default_values = false, $timestamp = true) {
        global $DB, $CFG, $USER;
        $courseinfo = [];

        if ($DB->record_exists('course', array('id' => $course_id))) {
            $course = $DB->get_record('course', ['id' => $course_id]);
            $context = \context_course::instance($course->id, IGNORE_MISSING);
            $course_categories = $DB->get_record('course_categories', ['id' => $course->category]);
            // $courseCategory = core_course_category::get($course->category);

            // course custom field data
            try {
                $numsections = (int)$DB->get_field_sql('SELECT max(section) from {course_sections} WHERE course = ?', [$course->id]);
            } catch (\Throwable $th) {
                $numsections = get_config('moodlecourse ')->numsections;
            }

            // get course enrolment plugin instance.
            $enrollment_methods = [];
            $index = 0;
            $enrolinstances = enrol_get_instances((int)$course->id, true);
            foreach ($enrolinstances as $key => $courseenrolinstance) {
                $enrol_plugin = enrol_get_plugin($courseenrolinstance->enrol);
                $enrollment_methods[$index]['enrol'] = $courseenrolinstance->enrol;
                $enrollment_methods[$index]['name'] = $enrol_plugin->get_instance_name($courseenrolinstance);
                $enrollment_methods[$index]['cost'] = $courseenrolinstance->cost;
                $enrollment_methods[$index]['currency'] = $courseenrolinstance->currency;
                $enrollment_methods[$index]['roleid'] = $courseenrolinstance->roleid;
                $index++;
            }

            // get all enrolled users
            $enrolledlearners = get_enrolled_users($context, 'moodle/course:isincompletionreports');
            $count_active_users = $count_course_completion = 0;
            foreach ($enrolledlearners as $enrolled_user) {
                $user_lastaccess_course = $DB->get_record('user_lastaccess', array('userid' => $enrolled_user->id, 'courseid' => $course->id));
                if ($user_lastaccess_course) {
                    $count_active_users++;
                }
                $percentage = UserDataHandler::get_user_course_progress($course, $enrolled_user->id);
                if ($percentage == 100) {
                    $count_course_completion++;
                }
            }


            // data arrange to return
            $courseinfo['id'] = $course->id;
            $courseinfo['categoryid'] = $course->category;
            $courseinfo['shortname'] =  format_string($course->shortname);
            $courseinfo['fullname'] =  format_string($course->fullname);
            $courseinfo['category_name'] = format_string(($course_categories->name));
            $courseinfo['course_link'] = (new \moodle_url('/course/view.php', array('id' => $course->id)))->out();
            $courseinfo['course_category_link'] = (new \moodle_url('/course/index.php', array('id' => $course->category)))->out();
            $courseinfo['enrollment_link'] = (new \moodle_url('/enrol/index.php', array('id' => $course->id)))->out();
            $courseinfo['participant_link'] = (new moodle_url('/user/index.php', array('id' => $course->id)))->out();
            $courseinfo['thumbnail_image_link'] = self::get_course_image($course, $default_values);
            $courseinfo['summary'] = self::get_course_formatted_summary($course);
            $courseinfo['course_sortorder'] = $course->sortorder;
            $courseinfo['course_total_sections'] = $numsections + 1;
            $courseinfo['course_newsitems'] = $course->newsitems;
            $courseinfo['course_format'] = $course->format;
            $courseinfo['course_visible'] = $course->visible;
            $courseinfo['course_startdate'] = ($timestamp) ? $course->startdate : UserDataHandler::get_user_date_time($course->startdate);
            $courseinfo['course_enddate'] = ($timestamp) ? $course->enddate : UserDataHandler::get_user_date_time($course->enddate);
            $courseinfo['course_timecreated'] = ($timestamp) ? $course->timecreated : UserDataHandler::get_user_date_time($course->timecreated);
            $courseinfo['course_timemodified'] = ($timestamp) ? $course->timemodified : UserDataHandler::get_user_date_time($course->timemodified);
            $courseinfo['enrollment_methods'] = $enrollment_methods;
            $courseinfo['enroll_total_student'] = count_enrolled_users($context, 'moodle/course:isincompletionreports');
            $courseinfo['count_active_users'] = $count_active_users;
            $courseinfo['count_course_completion'] = $count_course_completion;
            $courseinfo['count_activities'] = count(course_modinfo::get_array_of_activities($course, true));
            $courseinfo['course_customfields'] = self::get_custom_field_metadata($course_id);

            $extra_metadata = self::get_custom_field_metadata($course_id, 'key_value');
            $courseinfo = [...$courseinfo, ...$extra_metadata]; //array_merge($courseinfo, $extra_metadata);

            return $courseinfo;
        }
        return false;
    }

    /**
     * @param int $per_page 
     * @param int $page_number 
     * @param string $search_course 
     * @param int $category_id 
     * @return array
     */
    public static function get_all_course_info($per_page = 20, $page_number = 1, $search_course = '', $category_id = 0) {
        global $DB;
        $all_courses_info = [];
        // 
        $limitfrom = 0;
        $limitnum = ($per_page > 0) ? $per_page : 0;
        if ($page_number > 0) {
            $limitfrom = $limitnum * $page_number;
        }
        // 
        $course_id = '';
        $sql_params = [
            'frontpagecourse_id' => 1,
            'visible_val' => 1
        ];
        $where_condition = [];
        $where_condition_apply = "WHERE course.id <> :frontpagecourse_id AND course.visible = :visible_val ";
        if ($search_course) {
            $sql_params['search_fullname'] = "%" . $search_course . "%";
            $sql_params['search_shortname'] = "%" . $search_course . "%";
            $where_condition[] = '( course.fullname LIKE :search_fullname || course.shortname LIKE :search_shortname )';
        }
        if ($course_id) {
            $sql_params['course_id'] = $course_id;
            $where_condition[] = 'course.id = :course_id';
        }
        if ($category_id) {
            $sql_params['category_id'] = $category_id;
            $where_condition[] = 'course.category = :category_id';
        }
        if (count($where_condition) > 0) {
            $where_condition_apply .= " AND " . implode(" AND ", $where_condition);
        }
        // 
        $sql_query = 'SELECT * FROM {course} course ' . $where_condition_apply . ' ORDER BY course.id DESC ';
        // 
        $records = $DB->get_records_sql($sql_query, $sql_params, $limitfrom, $limitnum);
        $total_records = $DB->get_records_sql($sql_query, $sql_params);

        //create return value
        $page_data_count = $limitfrom;
        foreach ($records as $record) {
            $page_data_count++;
            $record_info = CourseDataHandler::get_course_info($record->id, true, false);
            $record_info['sn'] = $page_data_count;
            $all_courses_info['data'][] = $record_info;
        }
        // meta information
        $all_courses_info['meta'] = [
            'total_record' => count($total_records),
            'total_page' => ceil(count($total_records) / $per_page),
            'current_page' => $page_number,
            'per_page' => $per_page,
            'page_data_count' => $page_data_count,
            'data_from' => $limitfrom + 1,
            'data_to' => $page_data_count,
        ];
        // return data
        return $all_courses_info;
    }

    /**
     * ===================  END  ===================
     */
}
