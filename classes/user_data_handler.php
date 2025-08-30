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
 * User data handler for user course reports.
 *
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
     * Returns the list of courses where the user is enrolled.
     *
     * @param stdClass $user User object.
     * @return array List of enrolled courses with progress and roles.
     */
    public static function user_enrolled_courses($user) {
        $enrolledcourses = [];
        if ($mycourses = enrol_get_users_courses($user->id, false, '*', 'visible DESC, fullname ASC, sortorder ASC')) {
            foreach ($mycourses as $mycourse) {
                if ($mycourse->category) {
                    $coursecontext = \context_course::instance($mycourse->id);
                    $percentage = self::get_user_course_progress($mycourse, $user->id);

                    $enrolledcourse = [
                        "id" => $mycourse->id,
                        "fullname" => format_string($mycourse->fullname, true, ['context' => $coursecontext]),
                        "shortname" => format_string($mycourse->shortname, true, ['context' => $coursecontext]),
                        'course_link' => (new \moodle_url('/course/view.php', ['id' => $mycourse->id]))->out(),
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

                    $courseuserroles = [];
                    $roles = get_user_roles($coursecontext, $user->id);
                    if ($roles && is_array($roles)) {
                        $count = 0;
                        foreach ($roles as $key => $role) {
                            $courseuserroles[$count]['id'] = $role->id;
                            $courseuserroles[$count]['shortname'] = $role->shortname;
                            $courseuserroles[$count]['name'] = ($role->name) ?: $role->shortname;
                            $count++;
                        }
                    }
                    $enrolledcourse['course_user_roles'] = $courseuserroles;
                    $enrolledcourses[] = $enrolledcourse;
                }
            }
        }
        return $enrolledcourses;
    }

    /**
     * Returns a human-readable date/time string.
     *
     * @param int $timestamp Unix timestamp.
     * @param string $format Date format string (default: %b %d, %Y).
     * @return string Formatted date/time string.
     */
    public static function get_user_date_time($timestamp, $format = '%b %d, %Y') {
        if (!$timestamp) {
            return '';
        }
        $date = new \DateTime();
        $date->setTimestamp(intval($timestamp));
        return userdate($date->getTimestamp(), $format);
    }

    /**
     * Returns user progress percentage in a course.
     *
     * @param stdClass $course Course object.
     * @param int $enrolleduserid User ID of enrolled user.
     * @return int User course progress percentage (0–100).
     */
    public static function get_user_course_progress($course, $enrolleduserid) {
        global $CFG;

        require_once("$CFG->libdir/completionlib.php");

        $completioninfo = new \completion_info($course);
        $percentage = 0;
        if ($completioninfo->is_enabled()) {
            $percentage = progress::get_course_progress_percentage($course, $enrolleduserid);
            if (!is_null($percentage)) {
                $percentage = (int)($percentage);
                return $percentage;
            }
        }
        return 0;
    }

    /**
     * Returns user course enrolment information.
     *
     * @param int $courseid Course ID.
     * @param int $enrolleduserid User ID of enrolled user.
     * @return stdClass|null User enrolment record.
     */
    public static function course_user_enrolments($courseid, $enrolleduserid) {
        global $DB;
        $query = 'SELECT user_enrolments.status, user_enrolments.timecreated ,enrol.enrol
            FROM {user_enrolments} user_enrolments
            LEFT JOIN {enrol} enrol ON user_enrolments.enrolid = enrol.id
            WHERE enrol.courseid = :courseid AND user_enrolments.userid = :userid
            ';
        $params = [
            'courseid' => $courseid,
            'userid' => $enrolleduserid,
        ];
        $userenrolments = $DB->get_record_sql($query, $params);
        return $userenrolments;
    }

    /**
     * Returns user profile image URL.
     *
     * @param stdClass $user User object.
     * @return string URL of user profile image.
     */
    public static function get_user_profile_image_url($user) {
        global $PAGE;
        $userpicture = new \user_picture($user);
        $userpicture->size = 1;
        $profileimageurl = $userpicture->get_url($PAGE)->out(false);
        return $profileimageurl;
    }

    /**
     * Returns user description with formatted text and file URLs.
     *
     * @param stdClass $user User object.
     * @return string User description (HTML).
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
     * Returns user custom profile fields with values.
     *
     * @param stdClass $user User object.
     * @return array List of custom fields with details.
     */
    public static function get_user_customofields($user) {
        global $CFG;
        require_once($CFG->dirroot . "/user/profile/lib.php"); // Custom field library.
        $categories = profile_get_user_fields_with_data_by_category($user->id);
        $usercustomfields = [];
        foreach ($categories as $categoryid => $fields) {
            foreach ($fields as $formfield) {
                if (!empty($formfield->data)) {
                    $usercustomfields[] = [
                        'name' => $formfield->field->name, // ... Human-readable name
                        'value' => $formfield->data,       // ... Raw value
                        'displayvalue' => $formfield->display_data(), // ... Formatted value
                        'type' => $formfield->field->datatype,
                        'shortname' => $formfield->field->shortname,
                    ];
                }
            }
        }
    }

    /**
     * Returns detailed user information.
     *
     * @param int $userid User ID.
     * @param bool $timestamp Whether to return raw timestamps (true) or formatted dates (false).
     * @return array|bool User information array or false if not found.
     */
    public static function get_user_info($userid, $timestamp = true) {
        global $DB;
        $userinfo = [];

        if ($DB->record_exists('user', ['id' => $userid])) {
            $user = $DB->get_record('user', ['id' => $userid]);
            // ... default time zone
            $defaulttimezone = get_config('moodle', 'timezone');
            // ... users interests tags
            $intereststags = '';
            $interests = core_tag_tag::get_item_tags_array(
                'core',
                'user',
                $user->id,
                core_tag_tag::BOTH_STANDARD_AND_NOT,
                0,
                false
            );
            if ($interests) {
                $intereststags = join(', ', $interests);
            }

            // User preferences.
            $preferences = [];
            $userpreferences = get_user_preferences();
            foreach ($userpreferences as $prefname => $prefvalue) {
                $preferences[] = ['name' => $prefname, 'value' => $prefvalue];
            }

            // ... data arrange to return
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
            $userinfo['timezone'] = ($user->timezone == '99') ? $defaulttimezone : $user->timezone;
            $userinfo['timecreated'] = ($timestamp) ? $user->timecreated : self::get_user_date_time($user->timecreated);
            $userinfo['timemodified'] = ($timestamp) ? $user->timemodified : self::get_user_date_time($user->timemodified);
            $userinfo['firstaccess'] = ($timestamp) ? $user->firstaccess : self::get_user_date_time($user->firstaccess, '');
            $userinfo['lastaccess'] = ($timestamp) ? $user->lastaccess : self::get_user_date_time($user->lastaccess, '');
            $userinfo['lastlogin'] = ($timestamp) ? $user->lastlogin : self::get_user_date_time($user->lastlogin, '');
            $userinfo['profile_link'] = (new moodle_url('/user/profile.php', ['id' => $user->id]))->out();
            $userinfo['preferences'] = $preferences;
            $userinfo['interests'] = $intereststags;
            $userinfo['customofields'] = self::get_user_customofields($user);
            $userinfo['enrolled_courses'] = self::user_enrolled_courses($user);
            $userinfo['roles'] = self::get_all_roles($user->id);

            return $userinfo;
        }
        return false;
    }


    /**
     * Returns all user information based on filters and pagination.
     *
     * @param array $parameters {
     *  Optional filter and pagination options.
     *
     *  int    $page        Page number (default 0).
     *  int    $perpage     Records per page (default 50).
     *  int    $id          Filter by user ID (default 0).
     *  string $search      Search keyword (default '').
     *  array  $courseids   Filter by course IDs (default []).
     *  array  $roleids     Filter by role IDs (default []).
     *  int    $createdfrom Timestamp filter for created date from (default 0).
     *  int    $createdto   Timestamp filter for created date to (default 0).
     * }
     * @return array List of user information records with metadata.
     */
    public static function get_all_user_info($parameters) {

        global $CFG, $DB;
        // ... get parameter
        $pagenumber = $parameters['page'] ?? 0;
        $perpage    = $parameters['perpage'] ?? 0;
        $userid     = $parameters['id'] ?? 0;
        $searchuser = $parameters['search'] ?? '';
        $roleids    = $parameters['roleids'] ?? [];
        $courseids  = $parameters['courseids'] ?? [];
        $sortby     = $parameters['sortby'] ?? 'timemodified';
        $sortdir    = $parameters['sortdir'] ?? SORT_DESC;

        //... pagination
        $limitnum   = ($perpage > 0) ? $perpage : 50;
        $limitfrom  = ($pagenumber > 0) ? $limitnum * $pagenumber : 0;

        // ... SQL fragments
        $jointable = [];
        $sqlparams = [
            'guest_user_id' => 1,
            'user_deleted' => 1,
            'user_suspended' => 1,
        ];
        $wherecondition = [
            "u.id <> :guest_user_id",
            "u.deleted <> :user_deleted",
            "u.suspended <> :user_suspended",
        ];
        // ... search by text
        if ($searchuser) {
            $sqlparams['search_username'] = "%" . $DB->sql_like_escape($searchuser) . "%";
            $sqlparams['search_firstname'] = "%" . $DB->sql_like_escape($searchuser) . "%";
            $sqlparams['search_lastname'] = "%" . $DB->sql_like_escape($searchuser) . "%";
            $sqlparams['search_email'] = "%" . $DB->sql_like_escape($searchuser) . "%";
            $wherecondition[] = '( ' . $DB->sql_like('u.username', ':search_username') . ' OR ' .
                $DB->sql_like('u.firstname', ':search_firstname') . ' OR ' .
                $DB->sql_like('u.lastname', ':search_lastname') . ' OR ' .
                $DB->sql_like('u.email', ':search_email') . ' )';
        }
        // ... search by id
        if ($userid) {
            $sqlparams['user_id'] = $userid;
            $wherecondition[] = 'u.id = :user_id';
        }
        // ... search by role ids
        if (is_array($roleids) && count($roleids) > 0) {
            $rolewherecondition = [];
            // ... check if admin is present in roleids
            if (in_array(-1, $roleids)) {
                $adminids = explode(',', $CFG->siteadmins);
                if (count($adminids) > 0) {
                    list($insql, $inparams) = $DB->get_in_or_equal($adminids, SQL_PARAMS_NAMED, 'adminids');
                    $sqlparams = array_merge($sqlparams, $inparams);
                    $rolewherecondition[] = "u.id $insql";
                }
            }
            // ... remove dummy role ids: 0 and -1 values.
            $roleids = array_filter($roleids, function ($value) {
                return $value !== -1 && $value !== 0;
            });
            // ... now again if there are real roles user roles
            if (count($roleids) > 0) {
                $jointable['role_assignments'] = "INNER JOIN {role_assignments} ra ON u.id = ra.userid";
                list($insql, $inparams) = $DB->get_in_or_equal($roleids, SQL_PARAMS_NAMED, 'roleids');
                $sqlparams = array_merge($sqlparams, $inparams);
                $rolewherecondition[] = "ra.roleid $insql";
            }
            // ... join the role condition with OR
            if (count($rolewherecondition) > 0) {
                $wherecondition[] = "(" . implode(" OR ", $rolewherecondition) . ")";
            }
        }
        // ... search by course ids
        if (is_array($courseids) && count($courseids) > 0) {

            $jointable['role_assignments'] = "INNER JOIN {role_assignments} ra ON u.id = ra.userid";
            $jointable['context'] = "INNER JOIN {context} ctx ON ra.contextid = ctx.id";

            $sqlparams['contextlevel'] = CONTEXT_COURSE;
            $wherecondition[] = 'ctx.contextlevel = :contextlevel';

            list($insql, $inparams) = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'courseids');
            $sqlparams = array_merge($sqlparams, $inparams);
            $wherecondition[] = "ctx.instanceid $insql";
        }

        // ... apply where conditions with AND
        $whereapply = '';
        if (count($wherecondition) > 0) {
            $whereapply = "WHERE " . implode(" AND ", $wherecondition);
        }

        // ... apply table join
        $joinapply = '';
        if (count($jointable) > 0) {
            $joinapply = implode(" ", $jointable);
        }

        // ... order by sorting
        $usersortfields = ['firstname', 'lastname', 'email',];
        if (in_array($sortby, $usersortfields)) {
            $sortby = 'u.' . $sortby;
        } else {
            $sortby = 'u.timemodified';
        }
        $sortdir = ($sortdir == SORT_ASC) ? 'ASC' : 'DESC';
        $orderby = "ORDER BY " . $sortby . " " . $sortdir;

        // ... final sql query and execute
        $sqlquery = "SELECT u.id FROM {user} u " .
            $joinapply . " " . $whereapply . " " . $orderby;
        $records = $DB->get_records_sql($sqlquery, $sqlparams, $limitfrom, $limitnum);

        // ... count total records
        $sqlquery = 'SELECT COUNT(u.id) FROM {user} u ' .
            $joinapply . " " . $whereapply;
        $totalrecords = $DB->count_records_sql($sqlquery, $sqlparams);

        // ... create return value
        $alluserinfo = [];
        $datadisplaycount = $limitfrom;
        foreach ($records as $record) {
            $datadisplaycount++;
            $recordinfo = self::get_user_info($record->id, false);
            $recordinfo['sn'] = $datadisplaycount;
            $alluserinfo['data'][] = $recordinfo;
        }

        // ... meta information
        $alluserinfo['meta'] = [
            'totalrecords' => $totalrecords,
            'totalpage' => ceil($totalrecords / $limitnum),
            'pagenumber' => $pagenumber,
            'perpage' => $limitnum,
            'datadisplaycount' => $datadisplaycount,
            'datafrom' => ($datadisplaycount) ? $limitfrom + 1 : $limitfrom,
            'datato' => $datadisplaycount,
        ];

        return $alluserinfo;
    }

    /**
     * Returns all roles for a user or all available roles.
     *
     * @param int $userid User ID (default 0 = return all roles).
     * @param array $excluderoleids Role IDs to exclude.
     * @return array List of roles (id, shortname, name).
     */
    public static function get_all_roles($userid = 0, $excluderoleids = []) {
        global $DB;
        $rolesdata = [];

        if ($userid) {
            $sql = "SELECT DISTINCT r.*
            FROM {role_assignments} ra
            JOIN {role} r ON ra.roleid = r.id
            WHERE ra.userid = ?";

            $params = [$userid];
            $roles = $DB->get_records_sql($sql, $params);
            foreach ($roles as $key => $role) {
                $rolesdata[] = [
                    'id' => $role->id,
                    'shortname' => $role->shortname,
                    'name' => $role->name ?: role_get_name($role),
                ];
            }
            if (is_siteadmin($userid)) {
                $rolesdata[] = [
                    'id' => '-1',
                    'shortname' => 'admin',
                    'name' => get_string('admin'),
                ];
            }
            return $rolesdata;
        }
        // Get all roles.
        $rolesdata = [
            '-1' => get_string('admin'),
        ];

        $allrole = $DB->get_records('role');
        foreach ($allrole as $key => $role) {
            if (in_array($role->id, $excluderoleids)) {
                continue;
            }
            $rolesdata[$role->id] = role_get_name($role);
        }
        return $rolesdata;
    }
}
