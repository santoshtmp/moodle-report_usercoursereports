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
use core_course_list_element;
use course_modinfo;
use moodle_url;
use stdClass;

/**
 * class handler to get course data
 *
 * @package    report_usercoursereports
 * @copyright  2025 santoshtmp <https://santoshmagar.com.np/>
 * @author     santoshtmp
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_data_handler {

    /**
     * Get custom certificate module information for a course.
     *
     * @param int $courseid Course ID.
     * @param int $userid Optional. User ID to check issued certificates.
     * @return array {
     *     mod_id, customcert_id, certificate_url, certificate_url_download,
     *     certificate_issues, certificate_issues_date, certificate_issues_code
     * }
     */
    public static function course_mod_customcert($courseid, $userid = '') {
        global $DB, $CFG;
        $customcertdata = [
            'mod_id' => '',
            'customcert_id' => '',
            'certificate_url' => '',
            'certificate_url_download' => '',
            'certificate_issues' => false,
            'certificate_issues_date' => 0,
            'certificate_issues_code' => '',
        ];
        $query = 'SELECT course_modules.id AS id, course_modules.instance AS instance
            FROM {course_modules} course_modules
            JOIN {modules} modules ON modules.id = course_modules.module
            WHERE course_modules.course = :courseid AND modules.name = :modules_name
                AND course_modules.visible = :course_modules_visible AND course_modules.deletioninprogress = :deletioninprogress
            Order By course_modules.id DESC
            LIMIT 1
            ';
        $params = [
            'courseid' => $courseid,
            'modules_name' => 'customcert',
            'course_modules_visible' => 1,
            'deletioninprogress' => 0,
        ];
        $modcustomcert = $DB->get_record_sql($query, $params);
        if ($modcustomcert) {
            $customcertdata['mod_id'] = $modcustomcert->id;
            $customcertdata['customcert_id'] = $modcustomcert->instance;
            $customcertdata['certificate_url'] = (new \moodle_url('/mod/customcert/view.php', ['id' => $modcustomcert->id]))->out();
            $customcertdata['certificate_url_download'] = (new \moodle_url('/mod/customcert/view.php', [
                'id' => $modcustomcert->id,
                'downloadown' => 1,
            ]))->out();
            if ($userid && $modcustomcert->instance) {
                $customcertissues = $DB->get_record('customcert_issues', [
                    'userid' => $userid,
                    'customcertid' => $modcustomcert->instance,
                ]);
                if ($customcertissues) {
                    $customcertdata['certificate_issues'] = true;
                    $customcertdata['certificate_issues_date'] = $customcertissues->timecreated;
                    $customcertdata['certificate_issues_code'] = $customcertissues->code;
                }
            }
        }
        return $customcertdata;
    }

    /**
     * Returns formatted summary of a course with embedded files resolved.
     *
     * @param stdClass $course Course record.
     * @return string Formatted summary HTML.
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
     * Returns the first summary image of a course.
     *
     * @param stdClass $course Course record.
     * @param bool $defaultimageonnull Return default image if no image found.
     * @return string URL of course image or empty string.
     */
    public static function get_course_image($course, $defaultimageonnull = false) {
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
        if ($defaultimageonnull) {
            return $OUTPUT->get_generated_image_for_id($course->id);
        }
        return '';
    }

    /**
     * Validate and return a course object.
     *
     * @param mixed $courseref Course object or course ID.
     * @return stdClass Moodle course object.
     */
    protected function check_course($courseref = '') {
        global $COURSE, $DB;
        if (gettype($courseref) == 'object') {
            $course = $courseref;
        } else {
            $courseref = (int)$courseref;
            if (is_int($courseref)) {
                if ($DB->record_exists('course', ['id' => $courseref])) {
                    $course = get_course($courseref);
                } else {
                    $course = $COURSE;
                }
            } else {
                $course = $COURSE;
            }
        }
        return  $course;
    }

    /**
     * Get completion progress of a specific section in a course.
     *
     * @param mixed $courseref Course ID or object.
     * @param stdClass $section Section record.
     * @return array|string Progress context (percent) or empty string.
     */
    public function get_section_progress($courseref, $section) {

        global $USER, $COURSE;
        $course = $this->check_course($courseref);
        $context = \context_course::instance($COURSE->id);
        $userstudent = is_enrolled($context, $USER, 'moodle/course:isincompletionreports');
        if (!$userstudent  || isguestuser() || empty($course)) {
            return;
        }

        $modinfo = get_fast_modinfo($course);
        if (empty($modinfo->sections[$section->section])) {
            return '';
        }

        // Generate array with count of activities in this section.
        $sectionmods = [];
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

            $templatecontext['percent'] = $percent;
        }

        return $templatecontext;
    }


    /**
     * Fetch all custom field data (raw values) for a given course.
     *
     * @param int $courseid Course ID.
     * @return array Key-value array of custom field shortnames and values.
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
     * Get course custom fields metadata with flexible formats.
     *
     * @param int $courseid Course ID.
     * @param string $returnformat Format: "raw", "key_value", "key_array".
     * @return array Custom field metadata.
     */
    public static function get_custom_field_metadata($courseid, $returnformat = 'raw') {
        $handler = \core_course\customfield\course_handler::create();
        $customfields = $handler->export_instance_data($courseid);
        $metadata = [];

        foreach ($customfields as $data) {
            if ($returnformat == 'key_value') {
                $metadata[$data->get_shortname()] = $data->get_value();
            } else if ($returnformat == 'key_array') {
                $metadata[$data->get_shortname()] = [
                    'type' => $data->get_type(),
                    'value' => $data->get_value(),
                    'valueraw' => $data->get_data_controller()->get_value(),
                    'name' => $data->get_name(),
                    'shortname' => $data->get_shortname(),
                ];
            } else {
                $metadata[] = [
                    'type' => $data->get_type(),
                    'value' => $data->get_value(),
                    'valueraw' => $data->get_data_controller()->get_value(),
                    'name' => $data->get_name(),
                    'shortname' => $data->get_shortname(),
                ];
            }
        }
        return $metadata;
    }

    /**
     * Get compact card info for a course (summary, image, enrolment).
     *
     * @param int $courseid Course ID.
     * @param bool $defaultvalues Whether to include default images if missing.
     * @return array|false Course card info or false if course does not exist.
     */
    public static function course_card_info($courseid, $defaultvalues = false) {
        global $DB, $OUTPUT;
        $courseinfo = [];

        if ($DB->record_exists('course', ['id' => $courseid])) {
            $course = $DB->get_record('course', ['id' => $courseid]);
            $coursecategories = $DB->get_record('course_categories', ['id' => $course->category]);

            // ... get course enrolment plugin instance.
            $enrollmentmethods = [];
            $index = 0;
            $enrolinstances = enrol_get_instances((int)$course->id, true);
            foreach ($enrolinstances as $key => $courseenrolinstance) {
                $enrollmentmethods[$index]['enrol'] = $courseenrolinstance->enrol;
                $enrollmentmethods[$index]['name'] = ($courseenrolinstance->name) ?: $courseenrolinstance->enrol;
                $enrollmentmethods[$index]['cost'] = $courseenrolinstance->cost;
                $enrollmentmethods[$index]['currency'] = $courseenrolinstance->currency;
                $enrollmentmethods[$index]['roleid'] = $courseenrolinstance->roleid;
                $enrollmentmethods[$index]['role_name'] = '';
                $index++;
            }

            $rep = ["</p>", "<br>", "</div>"];
            $summary = str_replace($rep, " ", $course->summary);
            $summary = format_string($summary);
            if (strlen($summary) > 200) {
                $summary = substr($summary, 0, 200);
                $summary .= '...';
            }

            // ... manage return data
            $courseinfo['id'] = $course->id;
            $courseinfo['categoryid'] = $course->category;
            $courseinfo['datatype'] = $course->category;
            $courseinfo['shortname'] = format_string($course->shortname);
            $courseinfo['fullname'] = format_string($course->fullname);
            $courseinfo['category_name'] = format_string($coursecategories->name);
            $courseinfo['course_link'] = (new \moodle_url('/course/view.php', ['id' => $course->id]))->out();
            $courseinfo['category_link'] = (new \moodle_url('/course/index.php', [
                'categoryid' => $course->category,
            ]))->out();
            $courseinfo['enrollment_link'] = (new \moodle_url('/enrol/index.php', ['id' => $course->id]))->out();
            $courseinfo['thumbnail_link'] = self::get_course_image($course, $defaultvalues);
            $courseinfo['summary'] = self::get_course_formatted_summary($course);
            $courseinfo['short_summary'] = $summary;
            $courseinfo['arrow-right'] = $OUTPUT->image_url('icons/arrow-right', 'theme_yipl');
            $courseinfo['enrollment_methods'] = $enrollmentmethods;

            return $courseinfo;
        }
        return false;
    }

    /**
     * Get course enrolment plugin instance
     * @param int $courseid
     * @return array
     */
    public static function get_course_enrollmentmethods($courseid) {
        global $DB;
        $enrollmentmethods = [];
        $enrolinstances = enrol_get_instances((int)$courseid, true);
        foreach ($enrolinstances as $key => $courseenrolinstance) {
            $enrolplugin = enrol_get_plugin($courseenrolinstance->enrol);
            $instance = [
                'enrol' => $courseenrolinstance->enrol,
                'name' => $enrolplugin->get_instance_name($courseenrolinstance),
                'cost' => $courseenrolinstance->cost,
                'currency' => $courseenrolinstance->currency,
                'roleid' => $courseenrolinstance->roleid,
                'rolename' => role_get_name($DB->get_record('role', ['id' => $courseenrolinstance->roleid])),
            ];
            $enrollmentmethods[] = $instance;
        }
        return $enrollmentmethods;
    }

    /**
     * Get group mode names.
     * @return array Group mode choices.
     */
    public static function get_groupmode_name() {
        $choices = array();
        $choices[NOGROUPS] = get_string('groupsnone', 'group');
        $choices[SEPARATEGROUPS] = get_string('groupsseparate', 'group');
        $choices[VISIBLEGROUPS] = get_string('groupsvisible', 'group');
        return $choices;
    }

    /**
     * Get detailed information for a course.
     *
     * @param int $courseid Course ID.
     * @param bool $defaultvalues Whether to return default values.
     * @param bool $timestamp Whether to return timestamps or formatted dates.
     * @return array Detailed course info array or empty array if not found.
     */
    public static function get_course_info($courseid, $defaultvalues = false, $timestamp = true) {
        global $CFG, $DB;
        $courseinfo = [];

        if ($DB->record_exists('course', ['id' => $courseid])) {
            $course = get_course($courseid);
            $context = \context_course::instance($course->id, IGNORE_MISSING);
            $coursecategories = $DB->get_record('course_categories', ['id' => $course->category]);

            // ... course custom field data
            try {
                $numsections = (int)$DB->get_field_sql(
                    'SELECT max(section) from {course_sections} WHERE course = ?',
                    [$course->id]
                );
            } catch (\Throwable $th) {
                $numsections = get_config('moodlecourse ')->numsections;
            }

            // Collect groups
            $groups = groups_get_all_groups($course->id);
            $groupsinfo = [];
            if (!empty($groups)) {
                foreach ($groups as $group) {
                    $groupsinfo[] = [
                        'id'          => $group->id,
                        'name'        => format_string($group->name),
                        'description' => format_text($group->description, $group->descriptionformat),
                        'idnumber'    => $group->idnumber,
                        'enrolmentkey' => $group->enrolmentkey,
                        'picture'     => $group->picture
                    ];
                }
            }

            // ... data arrange to return
            $courseinfo['id'] = $course->id;
            $courseinfo['categoryid'] = $course->category;
            $courseinfo['shortname'] = format_string($course->shortname);
            $courseinfo['fullname'] = format_string($course->fullname);
            $courseinfo['category_name'] = format_string(($coursecategories->name));
            $courseinfo['course_link'] = (new \moodle_url('/course/view.php', ['id' => $course->id]))->out();
            $courseinfo['category_link'] = (new \moodle_url('/course/index.php', [
                'categoryid' => $course->category,
            ]))->out();
            $courseinfo['enrollment_link'] = (new \moodle_url('/enrol/index.php', ['id' => $course->id]))->out();
            $courseinfo['participant_link'] = (new moodle_url('/user/index.php', ['id' => $course->id]))->out();
            $courseinfo['thumbnail_link'] = self::get_course_image($course, $defaultvalues);
            $courseinfo['summary'] = self::get_course_formatted_summary($course);
            $courseinfo['sortorder'] = $course->sortorder;
            $courseinfo['course_format'] = $course->format;
            $courseinfo['course_formatname'] = get_string('pluginname', 'format_' . $course->format);
            $courseinfo['visible'] = $course->visible;
            $courseinfo['enablecompletion'] = $course->enablecompletion;
            $courseinfo['maxbytes'] = get_max_upload_sizes($CFG->maxbytes, 0, 0, $course->maxbytes)[$course->maxbytes];
            $courseinfo['groupmode'] = self::get_groupmode_name()[$course->groupmode];
            $courseinfo['course_startdate'] = ($timestamp) ?
                $course->startdate : user_data_handler::get_user_date_time($course->startdate);
            $courseinfo['course_enddate'] = ($timestamp) ?
                $course->enddate : user_data_handler::get_user_date_time($course->enddate);
            $courseinfo['course_timecreated'] = ($timestamp) ?
                $course->timecreated : user_data_handler::get_user_date_time($course->timecreated);
            $courseinfo['course_timemodified'] = ($timestamp) ?
                $course->timemodified : user_data_handler::get_user_date_time($course->timemodified);
            $courseinfo['enrollment_methods'] = self::get_course_enrollmentmethods($course->id);
            $courseinfo['count_enrolled_users'] = count_enrolled_users($context);
            $courseinfo['total_sections'] = $numsections;
            $courseinfo['course_newsitems'] = $course->newsitems;
            $courseinfo['count_activities'] = count(course_modinfo::get_array_of_activities($course, true));
            $courseinfo['course_customfields'] = self::get_custom_field_metadata($courseid);

            $extrametadata = self::get_custom_field_metadata($courseid, 'key_value');
            $courseinfo = [...$courseinfo, ...$extrametadata];
            $courseinfo['groups'] = $groupsinfo;
        }
        return $courseinfo;
    }

    /**
     * Get all courses information with filters and pagination.
     *
     * @param array $parameters {
     * Optional parameters:
     *  int    $page             Page number (0-indexed).
     *  int    $perpage          Number of records per page (default 50).
     *  int    $id               Specific course ID filter.
     *  string $search           Search keyword in fullname/shortname.
     *  array  $categoryids      Category IDs to filter by.
     *  string $courseformat     Course format filter.
     *  string $coursevisibility 'show' or 'hide' or 'all'.
     *  string $enrolmethod      Enrolment method filter.
     *  int    $createdfrom      Created from timestamp.
     *  int    $createdto        Created to timestamp.
     *  int    $startdatefrom    Start date from timestamp.
     *  int    $startdateto      Start date to timestamp.
     * }
     * @param bool $alldetail
     * @return array {
     *  array $data List of course info arrays.
     *  array $meta Pagination meta data.
     * }
     */
    public static function get_all_course_info($parameters, $alldetail = false) {
        global $DB;
        // ... get parameter
        $pagenumber         = (int)($parameters['page'] ?? 0);
        $perpage            = (int)($parameters['perpage'] ?? 50);
        $searchcourse       = trim($parameters['search'] ?? '');
        $categoryids        = $parameters['categoryids'] ?? [];
        $courseformat       = $parameters['courseformat'] ?? '';
        $coursevisibility   = $parameters['coursevisibility'] ?? '';
        $enrolmethod        = $parameters['enrolmethod'] ?? '';
        $createdfrom        = (int)($parameters['createdfrom'] ?? 0);
        $createdto          = (int)($parameters['createdto'] ?? 0);
        $startdatefrom      = (int)($parameters['startdatefrom'] ?? 0);
        $startdateto        = (int)($parameters['startdateto'] ?? 0);
        $sortby             = $parameters['sortby'] ?? 'timemodified';
        $sortdir            = $parameters['sortdir'] ?? SORT_DESC;

        // ... pagination
        $limitnum   = ($perpage > 0) ? $perpage : 50;
        $limitfrom  = ($pagenumber > 0) ? $limitnum * $pagenumber : 0;

        // ... SQL fragments
        $jointable = [];
        $sqlparams = ['frontpagecourseid' => 1];
        $wherecondition = ["c.id <> :frontpagecourseid"];

        // ... search by text
        if ($searchcourse) {
            $sqlparams['search_fullname'] = "%" . $DB->sql_like_escape($searchcourse) . "%";
            $sqlparams['search_shortname'] = "%" . $DB->sql_like_escape($searchcourse) . "%";
            $wherecondition[] = '( ' .
                $DB->sql_like('c.fullname', ':search_fullname') .
                ' OR ' .
                $DB->sql_like('c.shortname', ':search_shortname') .
                ' )';
        }
        // ... search by category id
        if (is_array($categoryids) && count($categoryids) > 0) {
            list($insql, $inparams) = $DB->get_in_or_equal($categoryids, SQL_PARAMS_NAMED, 'categoryid');
            $sqlparams = array_merge($sqlparams, $inparams);
            $wherecondition[] = "c.category $insql";
        }
        // ... search by course format
        if ($courseformat && $courseformat != 'all') {
            $sqlparams['courseformat'] = $courseformat;
            $wherecondition[] = 'c.format = :courseformat';
        }
        // ... search by coursevisibility
        if ($coursevisibility && $coursevisibility != 'all') {
            $coursevisibility = ($coursevisibility == 'show') ? 1 : 0;
            $sqlparams['coursevisibility'] = $coursevisibility;
            $wherecondition[] = 'c.visible = :coursevisibility';
        }
        // ... search by enrolmethod
        if ($enrolmethod && $enrolmethod != 'all') {
            $sqlparams['enrolmethod'] = $enrolmethod;
            $wherecondition[] = 'e.enrol = :enrolmethod';
        }
        // ... search by createdfrom
        if ($createdfrom) {
            $sqlparams['createdfrom'] = $createdfrom;
            $wherecondition[] = 'c.timecreated >= :createdfrom';
        }
        // ... search by createdto
        if ($createdto) {
            $sqlparams['createdto'] = $createdto + 24 * 3600;
            $wherecondition[] = 'c.timecreated <= :createdto';
        }
        // ... search by startdatefrom
        if ($startdatefrom) {
            $sqlparams['startdatefrom'] = $startdatefrom;
            $wherecondition[] = 'c.startdate >= :startdatefrom';
        }
        // ... search by startdateto
        if ($startdateto) {
            $sqlparams['startdateto'] = $startdateto + 24 * 3600;
            $wherecondition[] = 'c.startdate <= :startdateto';
        }

        // ... apply table join
        $joinapply = '';
        $jointable['course_categories'] = "JOIN {course_categories} cc ON cc.id = c.category";
        $jointable['enrol'] = "LEFT JOIN {enrol} e ON e.courseid = c.id AND e.status = :enrolstatus";
        $jointable['user_enrolments'] = "LEFT JOIN {user_enrolments} ue ON ue.enrolid = e.id";
        if (count($jointable) > 0) {
            $joinapply = implode(" ", $jointable);
        }
        $sqlparams['enrolstatus'] = ENROL_INSTANCE_ENABLED;

        // ... apply where conditions with AND
        $whereapply = '';
        if (count($wherecondition) > 0) {
            $whereapply = "WHERE " . implode(" AND ", $wherecondition);
        }

        // ... order by sorting
        $coursesortfields = ['fullname', 'shortname', 'startdate', 'timecreated', 'timemodified'];
        if (in_array($sortby, $coursesortfields)) {
            $sortby = 'c.' . $sortby;
        } else if ($sortby == 'category') {
            $sortby = 'cc.name';
        } else if ($sortby == 'participants') {
            $sortby = 'participants';
        } else {
            $sortby = 'c.timemodified';
        }
        $sortdir = ($sortdir == SORT_ASC) ? 'ASC' : 'DESC';
        $orderby = "ORDER BY " . $sortby . " " . $sortdir;

        // ... query select fields if required.
        $selectfields = 'c.id';
        $groupby = "c.id";
        if (!$alldetail) {
            $selectfields = implode(
                ", ",
                [
                    'c.id',
                    'c.category',
                    'c.fullname',
                    'c.shortname',
                    'c.format',
                    'c.visible',
                    'c.startdate',
                    'c.timecreated',
                    'cc.name AS category_name',
                    'COUNT(DISTINCT ue.userid) AS participants',
                ]
            );
            $groupby = "c.id, c.category, c.fullname, c.shortname, c.format, c.visible, c.startdate, c.timecreated, cc.name";
        }

        // ... final sql query and execute
        $sqlquery = "SELECT " . $selectfields .
            " FROM {course} c " .
            $joinapply . " " .
            $whereapply .
            " GROUP BY " . $groupby . " " .
            $orderby;
        $records = $DB->get_records_sql($sqlquery, $sqlparams, $limitfrom, $limitnum);

        // ... count total records
        $sqlcount = 'SELECT COUNT(DISTINCT c.id) FROM {course} c ' .
            $joinapply . " " .
            $whereapply;
        $totalrecords = $DB->count_records_sql($sqlcount, $sqlparams);

        // ... create return value
        $allcoursesinfo = [];
        $datadisplaycount = $limitfrom;
        foreach ($records as $record) {
            $datadisplaycount++;
            if ($alldetail) {
                $recordinfo = self::get_course_info($record->id, true, false);
            } else {
                $recordinfo = [];
                $recordinfo['id'] = $record->id;
                $recordinfo['categoryid'] = $record->category;
                $recordinfo['shortname'] = format_string($record->shortname);
                $recordinfo['fullname'] = format_string($record->fullname);
                $recordinfo['category_name'] = format_string(($record->category_name));
                $recordinfo['course_link'] = (new \moodle_url('/course/view.php', ['id' => $record->id]))->out();
                $recordinfo['category_link'] = (new \moodle_url(
                    '/course/index.php',
                    ['categoryid' => $record->category]
                ))->out();
                $recordinfo['thumbnail_link'] = self::get_course_image($record, true);
                $recordinfo['course_format'] = $record->format;
                $recordinfo['visible'] = $record->visible;
                $recordinfo['count_participants'] = $record->participants;
                $recordinfo['course_startdate'] = user_data_handler::get_user_date_time($record->startdate);
                $recordinfo['course_timecreated'] = user_data_handler::get_user_date_time($record->timecreated);
                $recordinfo['enrollment_methods'] = self::get_course_enrollmentmethods($record->id);
            }
            $recordinfo['sn'] = $datadisplaycount;

            $allcoursesinfo['data'][] = $recordinfo;
        }
        // ... meta information
        $allcoursesinfo['meta'] = [
            'totalrecords' => $totalrecords,
            'totalpage' => ceil($totalrecords / $limitnum),
            'pagenumber' => $pagenumber,
            'perpage' => $limitnum,
            'datadisplaycount' => $datadisplaycount,
            'datafrom' => ($datadisplaycount) ? $limitfrom + 1 : $limitfrom,
            'datato' => $datadisplaycount,
        ];

        return $allcoursesinfo;
    }
}
