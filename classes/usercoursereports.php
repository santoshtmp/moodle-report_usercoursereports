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
 * User course reports handler class.
 *
 * @package   report_usercoursereports
 * @copyright 2025 https://santoshmagar.com.np/
 * @author    santoshtmp7
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_usercoursereports;

use flexible_table;
use grade_item;
use html_writer;
use moodle_url;
use report_usercoursereports\form\filter_courseusers;
use report_usercoursereports\form\singlesearch;
use stdClass;

/**
 * Handles generating reports for users and courses.
 *
 * @package    report_usercoursereports
 * @copyright  2025 santoshtmp <https://santoshmagar.com.np/>
 * @author     santoshtmp
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class usercoursereports {

    /**
     * Retrieves and sanitizes request parameters for report generation.
     *
     * @return array Associative array of cleaned parameters.
     */
    public static function get_params() {
        $parameters = [
            'type'              => required_param('type', PARAM_TEXT),
            'id'                => optional_param('id', 0, PARAM_INT),
            'search'            => optional_param('search', '', PARAM_TEXT),
            'spage'             => optional_param('spage', 0, PARAM_INT),
            'perpage'           => optional_param('perpage', 0, PARAM_INT),
            'courseformat'      => optional_param('courseformat', '', PARAM_TEXT),
            'coursevisibility'  => optional_param('coursevisibility', '', PARAM_TEXT),
            'enrolmethod'       => optional_param('enrolmethod', '', PARAM_TEXT),
            'groupid'           => optional_param('groupid', 0, PARAM_INT),
            'createdfrom'       => optional_param('createdfrom', 0, PARAM_INT),
            'createdto'         => optional_param('createdto', 0, PARAM_INT),
            'startdatefrom'     => optional_param('startdatefrom', 0, PARAM_INT),
            'startdateto'       => optional_param('startdateto', 0, PARAM_INT),
            'categoryids'       => optional_param_array('categoryids', 0, PARAM_INT),
            'courseids'         => optional_param_array('courseids', 0, PARAM_INT),
            'roleids'           => optional_param_array('roleids', 0, PARAM_INT),
            'suspended'         => optional_param('suspended', '', PARAM_TEXT),
            'confirmed'         => optional_param('confirmed', '', PARAM_TEXT),
            'download'          => optional_param('download', 0, PARAM_ALPHA),
            'sortby'            => optional_param('sortby', '', PARAM_TEXT),
            'sortdir'           => optional_param('sortdir', '', PARAM_INT),
        ];
        return $parameters;
    }

    /**
     * Process and filter URL parameters for report links.
     *
     * Skips system-related params (sesskey, form states, etc.) and
     * ignores values set to "all" for specific filters.
     *
     * @param array $parameters Key-value array of request parameters.
     * @return array Cleaned parameters to be appended to URLs.
     */
    public static function urlparam($parameters) {
        $urlparam = [];
        $skipparam = [
            'applyfilter',
            'clearfilter',
            'sesskey',
            'mform_isexpanded_id_filterfieldwrapper',
            '_qf__report_usercoursereports_form_filter_form',
            'treset',
        ];
        $skipallparam = ['courseformat', 'coursevisibility', 'enrolmethod', 'suspended', 'confirmed'];
        foreach ($parameters as $key => $value) {
            if (in_array($key, $skipparam) || (in_array($key, $skipallparam) && $value == 'all')) {
                continue;
            }
            if (!empty($value) & !is_array($value)) {
                $urlparam[$key] = $value;
            } else if (is_array($value) && count($value)) {
                foreach ($value as $index => $val) {
                    $urlparam[$key . '[' . $index . ']'] = $val;
                }
            }
        }

        return $urlparam;
    }

    /**
     * Render the report type switcher list.
     *
     * Displays toggle buttons to switch between course and user reports.
     *
     * @param array $parameters
     * @return string HTML output of the report switcher.
     */
    public static function get_report_list($parameters) {
        $type = $parameters['type'];
        $pagepath = $parameters['pagepath'] ?? '/report/usercoursereports/index.php';
        $contents = '';
        $contents .= html_writer::start_tag(
            'div',
            ['class' => 'report-usercoursereports-list mt-3 mb-3']
        );
        $contents .= html_writer::start_tag(
            'div',
            ['class' => 'list-wrapper d-flex gap-3', 'style' => 'gap:10px']
        );
        $contents .= html_writer::link(
            new moodle_url($pagepath, ['type' => 'course']),
            get_string('coursereports', 'report_usercoursereports'),
            ['class' => ($type == 'course') ? 'active btn btn-primary' : 'btn btn-secondary']
        );
        $contents .= html_writer::link(
            new moodle_url($pagepath, ['type' => 'user']),
            get_string('userreports', 'report_usercoursereports'),
            ['class' => ($type == 'user') ? 'active btn btn-primary' : 'btn btn-secondary']
        );

        if ($parameters['id']) {
            $singlesearch = new singlesearch(null, ['type' => $type], 'get', '', ['id' => 'usercoursereports-single-search']);
            $contents .= $singlesearch->render();
        }
        $contents .= html_writer::end_tag('div');
        $contents .= html_writer::end_tag('div');

        return $contents;
    }

    /**
     * Generates a detailed profile view for a single user.
     *
     * @param array $parameters
     * @return string HTML output for the user's profile and enrolled courses.
     */
    public static function get_singleuser_info($parameters) {
        global $OUTPUT;
        $userinfo = user_data_handler::get_user_info($parameters['id'], false);
        $context = [
            'userinfo' => $userinfo,
            'allroles' => implode(', ', array_column($userinfo['roles'], 'name')),
        ];

        // ... output content
        $contents = '';
        $contents .= $OUTPUT->render_from_template("report_usercoursereports/singleuserdetails", $context);

        return $contents;
    }


    /**
     * Generates a detailed view for a single course.
     *
     * @param array $parameters Array containing:
     *   - 'id' => int, The ID of the course to display.
     * @return string HTML output for the course details.
     */
    public static function get_singlecourse_info($parameters) {
        global $OUTPUT;
        $courseid = $parameters['id'];
        $courseinfo = course_data_handler::get_course_info($courseid, true, false);
        $filtercourseusers = new filter_courseusers(
            new moodle_url($parameters['pagereseturl']),
            $parameters,
            'GET',
            '',
            [
                'id' => 'usercoursereports-filter',
                'class' => 'mform report-usercoursereports-filter pt-3 pb-3 me',
                'data-usercoursereports-type' => $parameters['type'],
            ]
        );
        if ($filtercourseusers->is_cancelled()) {
            redirect(new moodle_url($parameters['pagereseturl']));
        }

        // ... output content
        $context = [
            'courseinfo' => $courseinfo,
            'filtercourseusers' => $filtercourseusers->render(),
        ];
        $contents = '';
        $contents .= $OUTPUT->render_from_template("report_usercoursereports/singlecoursedetails", $context);
        return $contents;
    }
}
