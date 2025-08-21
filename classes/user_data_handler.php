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
 * user information.
 * @package   report_usercoursereports   
 * @copyright  2025 https://santoshmagar.com.np/
 * @author    santoshtmp7
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_usercoursereports;

use completion_info;
use core_completion\progress;
use core_tag_tag;
use stdClass;
use moodle_url;


defined('MOODLE_INTERNAL') || die;

/**
 * class handler to get user data
 *
 * @package    report_usercoursereports
 * @copyright  2025 santoshtmp <https://santoshmagar.com.np/>
 * @author     santoshtmp
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_data_handler {

    /**
     * Returns List of courses where the user is enrolled
     *
     * @param \stdClass $user
     *
     * @return array
     */
    public static function user_enrolled_courses($user) {
        $enrolledcourses = [];
        if ($mycourses =  enrol_get_users_courses($user->id, false, '*', 'visible DESC, fullname ASC, sortorder ASC')) {
            foreach ($mycourses as $mycourse) {
                if ($mycourse->category) {
                    $coursecontext = \context_course::instance($mycourse->id);
                    $percentage = self::get_user_course_progress($mycourse, $user->id);

                    $enrolledcourse = [
                        "id" => $mycourse->id,
                        "fullname" => format_string($mycourse->fullname, true, array('context' => $coursecontext)),
                        "shortname" => format_string($mycourse->shortname, true, array('context' => $coursecontext)),
                        'course_link' => (new \moodle_url('/course/view.php', array('id' => $mycourse->id)))->out(),
                        "progress" => $percentage,
                        "completion_date" => ($percentage == 100) ? time() : "",
                        "enroll_date" => self::course_user_enrolments($mycourse->id, $user->id)->timecreated,
                        "cost" => "",
                        "currency" => "",
                        "course_user_roles" => "",
                    ];

                    $enrolinstances = enrol_get_instances((int)$mycourse->id, true);
                    foreach ($enrolinstances as $key => $courseenrolinstance) {
                        if ($courseenrolinstance->enrol == 'fee') {
                            $enrolledcourse['cost'] = $courseenrolinstance->cost;
                            $enrolledcourse['currency'] = $courseenrolinstance->currency;
                        }
                    }

                    $course_user_roles = [];
                    $roles = get_user_roles($coursecontext, $user->id);
                    if ($roles && is_array($roles)) {
                        $count = 0;
                        foreach ($roles as $key => $role) {
                            $course_user_roles[$count]['id'] = $role->id;
                            $course_user_roles[$count]['shortname'] = $role->shortname;
                            $course_user_roles[$count]['name'] = ($role->name) ?: $role->shortname;
                            $count++;
                        }
                    }
                    $enrolledcourse['course_user_roles'] = $course_user_roles;
                    $enrolledcourses[] = $enrolledcourse;
                }
            }
        }
        return $enrolledcourses;
    }

    /**
     * return human readable date time
     */
    public static function get_user_date_time($timestamp, $format = '%b %d, %Y') {
        // '%A, %b %d, %Y, %I:%M %p'
        $date = new \DateTime();
        $date->setTimestamp(intval($timestamp));
        $user_date_time = userdate($date->getTimestamp(), $format);
        return $user_date_time;
    }

    /**
     * return user progress in the course
     * @param stdClass $course course      
     * @param int $enrolled_user_id id of enrolled user in course
     * @return int $percentage user course progress percentage
     */
    public static function get_user_course_progress($course, $enrolled_user_id) {
        global $CFG;

        require_once("$CFG->libdir/completionlib.php");

        $completioninfo = new \completion_info($course);
        $percentage = 0;
        if ($completioninfo->is_enabled()) {
            $percentage = progress::get_course_progress_percentage($course, $enrolled_user_id);
            if (!is_null($percentage)) {
                $percentage =  (int)($percentage);
                return $percentage;
            }
        }
        return 0;
    }

    /**
     * return user course enrollment infomation
     * @param int $course_id course id     
     * @param int $enrolled_user_id id of enrolled user in course
     * @return object $userenrolments user course enrollment
     */
    public static function course_user_enrolments($course_id, $enrolled_user_id) {
        global $DB;
        $query = 'SELECT user_enrolments.status, user_enrolments.timecreated ,enrol.enrol
            FROM {user_enrolments} user_enrolments 
            LEFT JOIN {enrol} enrol ON user_enrolments.enrolid = enrol.id
            WHERE enrol.courseid = :courseid AND user_enrolments.userid = :userid
            ';
        $params = [
            'courseid' => $course_id,
            'userid' => $enrolled_user_id
        ];
        $userenrolments = $DB->get_record_sql($query, $params);
        return $userenrolments;
    }

    /**
     * User Profile image
     * @param \stdClass $user user object
     * @return url
     */
    public static function get_user_profile_image_url($user) {
        global $PAGE;
        $userpicture = new \user_picture($user);
        $userpicture->size = 1;
        $profileimageurl = $userpicture->get_url($PAGE)->out(false);
        return $profileimageurl;
    }

    /**
     * @param \stdClass $user user object
     * @return url
     */
    public static function get_user_description($user) {
        global $CFG;

        $usercontext = \context_user::instance($user->id, MUST_EXIST);
        require_once("$CFG->libdir/filelib.php");
        $description = file_rewrite_pluginfile_urls(
            $user->description,
            'pluginfile.php',
            $usercontext->id,
            'user',
            'profile',
            null
        );
        $description = format_text($description, $user->descriptionformat);
        return $description;
    }

    /**
     * User custom fields
     * @param \stdClass $user user object
     * @return url
     */
    public static function get_user_customofields($user) {
        global $CFG;
        require_once($CFG->dirroot . "/user/profile/lib.php"); // Custom field library.
        $categories = profile_get_user_fields_with_data_by_category($user->id);
        $user_customfields = [];
        foreach ($categories as $categoryid => $fields) {
            foreach ($fields as $formfield) {
                if (!empty($formfield->data)) {
                    $user_customfields[] = [
                        'name' => $formfield->field->name, // Human-readable name
                        'value' => $formfield->data,       // Raw value
                        'displayvalue' => $formfield->display_data(), // Formatted value
                        'type' => $formfield->field->datatype,
                        'shortname' => $formfield->field->shortname
                    ];
                }
            }
        }
    }

    /**
     * 
     * get_user_info == user_get_user_details from /user/lib.php
     * @param int $user user id
     * @return array or boolen 
     */
    public static function get_user_info($user_id, $timestamp = true) {
        global $DB;
        $userinfo = [];

        if ($DB->record_exists('user', array('id' => $user_id))) {
            $user = $DB->get_record('user', ['id' => $user_id]);
            // default time zone
            $default_timezone = get_config('moodle', 'timezone');
            // users interests tags
            $interests_tags = '';
            $interests = core_tag_tag::get_item_tags_array('core', 'user', $user->id, core_tag_tag::BOTH_STANDARD_AND_NOT, 0, false);
            if ($interests) {
                $interests_tags = join(', ', $interests);
            }

            // User preferences.
            $preferences = array();
            $userpreferences = get_user_preferences();
            foreach ($userpreferences as $prefname => $prefvalue) {
                $preferences[] = array('name' => $prefname, 'value' => $prefvalue);
            }


            // data arrange to return
            $userinfo['id'] = $user->id;
            $userinfo['username'] = $user->username;
            $userinfo['email'] = $user->email;
            $userinfo['firstname'] = $user->firstname;
            $userinfo['lastname'] = $user->lastname;
            $userinfo['auth'] = $user->auth;
            $userinfo['phone1'] = $user->phone1;
            $userinfo['phone2'] = $user->phone2;
            $userinfo['institution'] = $user->institution;
            $userinfo['department'] = $user->department;
            $userinfo['address'] = $user->address;
            $userinfo['city'] = $user->city;
            $userinfo['country'] = $user->country;
            $userinfo['country_name'] = ($user->country) ? get_string_manager()->get_list_of_countries()[$user->country] : '';
            $userinfo['lang'] = $user->lang;
            $userinfo['profileimage_link'] = self::get_user_profile_image_url($user);
            $userinfo['description'] = self::get_user_description($user);
            $userinfo['timezone'] = ($user->timezone == '99') ? $default_timezone : $user->timezone;
            $userinfo['timecreated'] = ($timestamp) ? $user->timecreated : self::get_user_date_time($user->timecreated);
            $userinfo['timemodified'] = ($timestamp) ? $user->timemodified : self::get_user_date_time($user->timemodified);
            $userinfo['firstaccess'] = ($timestamp) ? $user->firstaccess : self::get_user_date_time($user->firstaccess, '');
            $userinfo['lastaccess'] = ($timestamp) ? $user->lastaccess : self::get_user_date_time($user->lastaccess, '');
            $userinfo['lastlogin'] = ($timestamp) ? $user->lastlogin : self::get_user_date_time($user->lastlogin, '');
            $userinfo['profile_link'] = (new moodle_url('/user/profile.php', ['id' => $user->id]))->out();
            $userinfo['preferences'] = $preferences;
            $userinfo['interests'] = $interests_tags;
            $userinfo['customofields'] = self::get_user_customofields($user);
            $userinfo['enrolled_courses'] = self::user_enrolled_courses($user);
            $userinfo['roles'] = self::get_all_roles($user->id);

            // 
            return $userinfo;
        }
        return false;
    }


    /**
     * Get all user information based on filters and pagination.
     *
     * @param array $parameters {
     *     Optional. An array of filter and pagination options.
     *
     *     @type int    $page        Page number for pagination (default 0).
     *     @type int    $perpage     Number of records per page (default 20).
     *     @type int    $id          User ID filter (default 0).
     *     @type string $search      Search keyword for users (default '').
     *     @type array  $courseids   Course IDs filter (default []).
     *     @type array  $roleids     Role IDs filter (default []).
     *     @type int    $createdfrom Timestamp filter for created date from (default 0).
     *     @type int    $createdto   Timestamp filter for created date to (default 0).
     * }
     *
     * @return array List of user information records.
     */
    public static function get_all_user_info($parameters) {

        global $DB;
        $alluserinfo = [];
        // ... get parameter

        $pagenumber = $parameters['page'] ?? 0;
        $perpage = $parameters['perpage'] ?? 20;
        $user_id = $parameters['id'] ?? 0;
        $search_user = $parameters['search'] ?? '';
        $courseids = $parameters['courseids'] ?? [];
        $roleids = $parameters['roleids'] ?? [];
        $createdfrom = (int)$parameters['createdfrom'] ?? 0;
        $createdto = (int)$parameters['createdto'] ?? 0;

        // 
        $limitfrom = 0;
        $perpage = ($perpage) ?: 20;
        $limitnum = ($perpage > 0) ? $perpage : 0;
        if ($pagenumber > 0) {
            $limitfrom = $limitnum * $pagenumber;
        }
        // 
        $query_join_apply = '';
        $query_join = [];
        $sql_params = [
            'guest_user_id' => 1,
            'user_deleted' => 1,
            'user_suspended' => 1,
        ];
        $where_condition = [];
        $where_condition_apply = " WHERE u.id <> :guest_user_id AND u.deleted <> :user_deleted AND u.suspended <> :user_suspended";
        // 
        if ($search_user) {
            $sql_params['search_username'] = "%" . $search_user . "%";
            $sql_params['search_firstname'] = "%" . $search_user . "%";
            $sql_params['search_lastname'] = "%" . $search_user . "%";
            $sql_params['search_email'] = "%" . $search_user . "%";
            $where_condition[] = '( u.username LIKE :search_username || u.firstname LIKE :search_firstname || u.lastname LIKE :search_lastname || u.email LIKE :search_email )';
        }
        if ($user_id) {
            $sql_params['user_id'] = $user_id;
            $where_condition[] = 'u.id = :user_id';
        }
        if (is_array($roleids) && count($roleids) > 0) {
            if (in_array(-1, $roleids)) {
                $admin_role = true;
            }
            // Remove 0 and -1 values.
            $roleids = array_filter($roleids, function ($value) {
                return $value !== -1 && $value !== 0;
            });
            // now if there are more roles then process further
            if (count($roleids) > 0) {
                $sql_params['roleids'] = implode(',', $roleids);
                $where_condition[] = 'ra.roleid IN (:roleids)';
                $query_join['role_assignments'] = "INNER JOIN {role_assignments} AS ra ON u.id = ra.userid";
            }
            // var_dump($sql_params['roleids']);
            // var_dump($roleids);
        }
        if (is_array($courseids) && count($courseids) > 0) {
            $sql_params['courseids'] = implode(',', $courseids);
            $sql_params['contextlevel'] = CONTEXT_COURSE;
            $where_condition[] = 'ctx.instanceid IN (:courseids)';
            $where_condition[] = 'ctx.contextlevel = :contextlevel';
            $query_join['role_assignments'] = "INNER JOIN {role_assignments} AS ra ON u.id = ra.userid";
            $query_join['context'] = "INNER JOIN {context} AS ctx ON ra.contextid = ctx.id";
        }
        // 
        if (count($where_condition) > 0) {
            $where_condition_apply .= " AND " . implode(" AND ", $where_condition);
        }
        if (count($query_join) > 0) {
            $query_join_apply .= " " . implode(" ", $query_join);
        }
        // 
        $sql_query = 'SELECT DISTINCT u.id FROM {user} AS u' . $query_join_apply . $where_condition_apply . ' ORDER BY u.id DESC ';
        // 
        $records = $DB->get_records_sql($sql_query, $sql_params, $limitfrom, $limitnum);
        $total_records = $DB->get_records_sql($sql_query, $sql_params);
        // count_records_sql

        //create return value
        $datadisplaycount = $limitfrom;
        foreach ($records as $record) {
            $datadisplaycount++;
            $record_info = self::get_user_info($record->id, false);
            $record_info['sn'] = $datadisplaycount;
            $alluserinfo['data'][] = $record_info;
        }
        // meta information
        $alluserinfo['meta'] = [
            'totalrecords' => count($total_records),
            'totalpage' => ceil(count($total_records) / $perpage),
            'pagenumber' => $pagenumber,
            'perpage' => $perpage,
            'datadisplaycount' => $datadisplaycount,
            'datafrom' => ($datadisplaycount) ? $limitfrom + 1 : $limitfrom,
            'datato' => $datadisplaycount,
        ];
        // return data
        return $alluserinfo;
    }

    /**
     * User roles
     */
    public static function get_all_roles($user_id = 0) {
        global $DB;
        $roles_data = [];

        if ($user_id) {
            $sql = "SELECT r.*
            FROM {role_assignments} ra
            JOIN {role} r ON ra.roleid = r.id
            WHERE ra.userid = ?";

            $params = [$user_id];
            $roles = $DB->get_records_sql($sql, $params);
            foreach ($roles as $key => $role) {
                $roles_data[] = [
                    'id' => $role->id,
                    'shortname' => $role->shortname,
                    'name' => $role->name ?: role_get_name($role)
                ];
            }
            if (is_siteadmin($user_id)) {
                $roles_data[] = ['id' => '-1', 'shortname' => 'admin', 'name' => get_string('admin')];
            }
            return $roles_data;
        }
        // Get all roles.
        $roles_data = [
            '0' => get_string('allroles', 'report_usercoursereports'),
            '-1' => get_string('admin')
        ];

        $allrole = $DB->get_records('role');
        foreach ($allrole as $key => $role) {
            $roles_data[$role->id] = role_get_name($role);
        }
        return $roles_data;
    }
    // END.
}
