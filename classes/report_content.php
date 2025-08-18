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
 * @package   report_usercoursereports   
 * @copyright 2025 https://santoshmagar.com.np/
 * @author    santoshtmp7
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_usercoursereports;

use html_writer;
use moodle_url;
use stdClass;

defined('MOODLE_INTERNAL') || die;

/**
 * class handler to get report table
 *
 * @package    report_usercoursereports
 * @copyright  2025 santoshtmp <https://santoshmagar.com.np/>
 * @author     santoshtmp
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_content {

    /**
     * Get Report List 
     */
    public static function get_report_list($page_path) {
        $type = optional_param('type', '', PARAM_TEXT);
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
            new moodle_url($page_path, ['type' => 'course']),
            get_string('coursereports', 'report_usercoursereports'),
            ['class' => ($type == 'course') ? 'active btn btn-primary' : 'btn btn-secondary']
        );
        $contents .= html_writer::link(
            new moodle_url($page_path, ['type' => 'user']),
            get_string('usereports', 'report_usercoursereports'),
            ['class' => ($type == 'user') ? 'active btn btn-primary' : 'btn btn-secondary']
        );
        $contents .= html_writer::end_tag('div');
        $contents .= html_writer::end_tag('div');

        return $contents;
    }

    /**
     * Get course.
     *
     * @param string $page_path
     * @return string
     */
    public static function get_course_info_table($page_url, $per_page, $page, $search, $category_ids) {
        global $PAGE, $OUTPUT, $DB;

        $all_course_info = course_data_handler::get_all_course_info($per_page, $page, $search, $category_ids);
        $tring_data = new stdClass();
        $tring_data->data_from = $all_course_info['meta']['data_from'];
        $tring_data->data_to = $all_course_info['meta']['data_to'];
        $tring_data->data_total = $all_course_info['meta']['total_record'];
        // 
        $contents = '';
        // Display the form.

        $contents .= html_writer::tag('p', get_string('showingreportdatanumber', 'report_usercoursereports', $tring_data));
        $contents .= html_writer::start_tag('table', ['id' => 'course-report-table', 'class' => 'generaltable generalbox']);
        $contents .= html_writer::start_tag('thead');
        $contents .= html_writer::tag(
            'tr',
            html_writer::tag('th', get_string('sn', 'report_usercoursereports')) .
                html_writer::tag('th', get_string('fullname')) .
                html_writer::tag('th', get_string('category')) .
                html_writer::tag('th', get_string('participants')) .
                html_writer::tag('th', get_string('courseformat', 'report_usercoursereports')) .
                html_writer::tag('th', get_string('coursevisibility', 'report_usercoursereports')) .
                html_writer::tag('th', get_string('enrolmentmethods', 'report_usercoursereports')) .
                html_writer::tag('th', get_string('createddate', 'report_usercoursereports'))
        );
        $contents .= html_writer::end_tag('thead');

        $contents .= html_writer::start_tag('tbody', ['data-type' => 'course-report']);

        foreach ($all_course_info['data'] as $course) {
            // output item row
            $contents .= html_writer::start_tag(
                'tr',
                [
                    'data-id' => $course['id'],
                    'data-categoryid' => $course['categoryid'],
                    'data-shortname' => $course['shortname'],
                ]
            );
            $contents .= html_writer::tag('td', $course['sn']);
            $contents .= html_writer::tag(
                'td',
                html_writer::link(
                    $course['course_link'],
                    html_writer::img(
                        $course['thumbnail_image_link'],
                        $course['fullname'],
                        ['class' => 'course-thumbnail']
                    ) .
                        html_writer::tag('div', $course['fullname'], ['class' => 'course-name pl-3 ']),
                    ['class' => 'course-link d-flex justify-content-start']
                ),
                ['class' => 'course-name-wrapper']
            );
            $contents .= html_writer::tag(
                'td',
                html_writer::link(
                    $course['course_category_link'],
                    $course['category_name']
                )
            );
            $contents .= html_writer::tag('td', $course['enroll_total_student']);
            $contents .= html_writer::tag('td', get_string('pluginname', 'format_' . $course['course_format']));
            $contents .= html_writer::tag('td', $course['course_visible'] ? get_string('show') : get_string('hide'));
            $contents .= html_writer::tag(
                'td',
                html_writer::alist(
                    array_column($course['enrollment_methods'], 'name'),
                    ['style' => 'list-style: none; padding-left: 0; margin: 0;'],
                )
            );
            $contents .= html_writer::tag('td', $course['course_timecreated']);
            $contents .= html_writer::end_tag('tr');
        }
        $contents .= html_writer::end_tag('tbody');
        $contents .= html_writer::end_tag('table');
        $contents .= $OUTPUT->paging_bar(
            $all_course_info['meta']['total_record'],
            $page,
            $per_page,
            $page_url
        );

        return $contents;
    }
    /**
     * Get users.
     *
     * @param string $page_path
     * @return string
     */
    public static function get_user_info_table($page_url, $per_page, $page, $search) {
        global $PAGE, $OUTPUT;
        // 
        $all_user_info = user_data_handler::get_all_user_info($per_page, $page, $search);
        $tring_data = new stdClass();
        $tring_data->data_from = $all_user_info['meta']['data_from'];
        $tring_data->data_to = $all_user_info['meta']['data_to'];
        $tring_data->data_total = $all_user_info['meta']['total_record'];
        // 
        $contents = '';
        $contents .= html_writer::tag('p', get_string('showingreportdatanumber', 'report_usercoursereports', $tring_data));
        $contents .= html_writer::start_tag('table', ['id' => 'user-report-table', 'class' => 'generaltable generalbox']);
        $contents .= html_writer::start_tag('thead');
        $contents .= html_writer::tag(
            'tr',
            html_writer::tag('th', get_string('sn', 'report_usercoursereports')) .
                html_writer::tag('th', get_string('fullname')) .
                html_writer::tag('th', get_string('email')) .
                html_writer::tag('th', get_string('location')) .
                html_writer::tag('th', get_string('roles')) .
                html_writer::tag('th', get_string('courses')) .
                html_writer::tag('th', get_string('lastaccess', 'report_usercoursereports'))
        );
        $contents .= html_writer::end_tag('thead');

        $contents .= html_writer::start_tag('tbody', ['data-type' => 'user-report']);

        foreach ($all_user_info['data'] as $user) {
            // output item row
            $contents .= html_writer::start_tag(
                'tr',
                [
                    'data-id' => $user['id'],
                    'data-username' => $user['username'],
                ]
            );
            $contents .= html_writer::tag('td', $user['sn']);
            $contents .= html_writer::tag(
                'td',
                html_writer::link(
                    $user['profile_link'],
                    html_writer::img(
                        $user['profileimage_link'],
                        $user['fullname'],
                        ['class' => 'user-thumbnail',]
                    ) .
                        html_writer::tag(
                            'div',
                            html_writer::tag('span', $user['firstname'] . ' ' . $user['lastname']) .
                                html_writer::tag('span',  '(' . $user['username'] . ')'),
                            ['class' => 'pl-3 d-flex flex-column justify-content-start']
                        ),
                    ['class' => 'd-flex justify-content-start']
                ),
                ['class' => 'd-flex']
            );
            $contents .= html_writer::tag('td', $user['email']);
            $contents .= html_writer::tag(
                'td',

                (($user['address']) ?  html_writer::tag('div', get_string('address') . ': ' . $user['address']) : '') .
                    (($user['city']) ? html_writer::tag('div', get_string('city') . ': ' . $user['city']) : '') .
                    (($user['country_name']) ? html_writer::tag('div', get_string('country') . ': ' . $user['country_name']) : '')
            );
            $contents .= html_writer::tag(
                'td',
                html_writer::alist(
                    array_column($user['roles'], 'name'),
                    ['style' => 'list-style: none; padding-left: 0; margin: 0;'],
                )
            );
            $contents .= html_writer::tag('td', count($user['enrolled_courses']));
            $contents .= html_writer::tag('td', $user['lastaccess']);
            $contents .= html_writer::end_tag('tr');
        }
        $contents .= html_writer::end_tag('tbody');
        $contents .= html_writer::end_tag('table');
        $contents .= $OUTPUT->paging_bar(
            $all_user_info['meta']['total_record'],
            $page,
            $per_page,
            $page_url
        );

        return $contents;
    }
}
