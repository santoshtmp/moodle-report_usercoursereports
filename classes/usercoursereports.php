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

use html_writer;
use moodle_url;
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
     * @param string $type Current report type ('course' or 'user').
     * @param string $pagepath The base page path for navigation.
     * @return string HTML output of the report switcher.
     */
    public static function get_report_list($type, $pagepath) {
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
        $contents .= html_writer::end_tag('div');
        $contents .= html_writer::end_tag('div');

        return $contents;
    }

    /**
     * Generate a table of courses with metadata and filters applied.
     *
     * Fetches course data and renders a paginated table.
     *
     * @param moodle_url $pageurl Current page URL for pagination links.
     * @param array $parameters Filter and paging parameters.
     * @return string HTML output of the course report table.
     */
    public static function get_course_info_table($pageurl, $parameters) {
        global $OUTPUT;
        $perpage = ($parameters['perpage']) ?: 50;
        $allcourseinfo = course_data_handler::get_all_course_info($parameters);
        $strdata = new stdClass();
        $strdata->datafrom = $allcourseinfo['meta']['datafrom'];
        $strdata->datato = $allcourseinfo['meta']['datato'];
        $strdata->datatotal = $allcourseinfo['meta']['totalrecords'];
        $tableheader = [
            ['sort' => false, 'field' => 'sn', 'title' => get_string('sn', 'report_usercoursereports')],
            ['sort' => true, 'field' => 'fullname', 'title' => get_string('fullname')],
            ['sort' => true, 'field' => 'category', 'title' => get_string('category')],
            ['sort' => true, 'field' => 'participants', 'title' => get_string('participants')],
            ['sort' => false, 'field' => 'format', 'title' => get_string('courseformat', 'report_usercoursereports')],
            ['sort' => false, 'field' => 'visible', 'title' => get_string('coursevisibility', 'report_usercoursereports')],
            ['sort' => false, 'field' => 'enrol', 'title' => get_string('enrolmentmethods', 'report_usercoursereports')],
            ['sort' => false, 'field' => 'startdate', 'title' => get_string('startdateto', 'report_usercoursereports')],
            ['sort' => false, 'field' => 'timecreated', 'title' => get_string('createddate', 'report_usercoursereports')],
        ];

        // Display the filter area content.
        $contents = '';
        $contents .= html_writer::start_tag('div', [
            'id' => 'report-usercoursereports-filter-area',
            'usercoursereports-filter-type' => $parameters['type'],
            'totalrecords' => $allcourseinfo['meta']['totalrecords'],
            'datafrom' => $allcourseinfo['meta']['datafrom'],
            'datato' => $allcourseinfo['meta']['datato'],
            'pagenumber' => $allcourseinfo['meta']['pagenumber'],
        ]);
        $contents .= html_writer::tag('p', get_string('showingreportdatanumber', 'report_usercoursereports', $strdata));
        $contents .= html_writer::start_tag('table', ['id' => 'course-report-table', 'class' => 'generaltable generalbox']);
        $contents .= self::get_table_header($tableheader, $parameters);
        $contents .= html_writer::start_tag('tbody', ['data-type' => 'course-report']);
        foreach ($allcourseinfo['data'] as $course) {
            // ... output item row
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
            $contents .= html_writer::tag('td', $course['count_participants']);
            $contents .= html_writer::tag('td', get_string('pluginname', 'format_' . $course['course_format']));
            $contents .= html_writer::tag('td', $course['course_visible'] ? get_string('show') : get_string('hide'));
            $contents .= html_writer::tag(
                'td',
                html_writer::alist(
                    array_column($course['enrollment_methods'], 'name'),
                    ['style' => 'list-style: none; padding-left: 0; margin: 0;'],
                )
            );
            $contents .= html_writer::tag('td', $course['course_startdate']);
            $contents .= html_writer::tag('td', $course['course_timecreated']);
            $contents .= html_writer::end_tag('tr');
        }
        $contents .= html_writer::end_tag('tbody');
        $contents .= html_writer::end_tag('table');
        $contents .= $OUTPUT->paging_bar(
            $allcourseinfo['meta']['totalrecords'],
            $allcourseinfo['meta']['pagenumber'],
            $perpage,
            $pageurl
        );
        $contents .= html_writer::tag('div', $OUTPUT->render_from_template("core/loading", []), [
            'id' => 'filter-loading-wrapper',
            'style' => 'display:none;',
        ]);
        $contents .= html_writer::end_tag('div');

        return $contents;
    }

    /**
     * Generate a table of users with metadata and filters applied.
     *
     * Fetches user data and renders a paginated table.
     *
     * @param moodle_url $pageurl Current page URL for pagination links.
     * @param array $parameters Filter and paging parameters.
     * @return string HTML output of the user report table.
     */
    public static function get_user_info_table($pageurl, $parameters) {
        global $OUTPUT;
        $perpage = ($parameters['perpage']) ?: 50;
        $alluserinfo = user_data_handler::get_all_user_info($parameters);
        $strdata = new stdClass();
        $strdata->datafrom = $alluserinfo['meta']['datafrom'];
        $strdata->datato = $alluserinfo['meta']['datato'];
        $strdata->datatotal = $alluserinfo['meta']['totalrecords'];
        $tableheader = [
            ['sort' => false, 'field' => 'sn', 'title' => get_string('sn', 'report_usercoursereports')],
            ['sort' => true, 'field' => 'firstname', 'title' => get_string('fullname')],
            ['sort' => true, 'field' => 'email', 'title' => get_string('email')],
            ['sort' => false, 'field' => 'city', 'title' => get_string('city')],
            ['sort' => false, 'field' => 'roles', 'title' => get_string('roles')],
            ['sort' => true, 'field' => 'enrolledcourses', 'title' => get_string('enrolledcourses', 'report_usercoursereports')],
            ['sort' => true, 'field' => 'lastaccess', 'title' => get_string('lastaccess', 'report_usercoursereports')],
            ['sort' => false, 'field' => '', 'title' => ''],
        ];

        // Display the filter area content.
        $contents = '';
        $contents .= html_writer::start_tag('div', [
            'id' => 'report-usercoursereports-filter-area',
            'usercoursereports-filter-type' => $parameters['type'],
            'totalrecords' => $alluserinfo['meta']['totalrecords'],
            'datafrom' => $alluserinfo['meta']['datafrom'],
            'datato' => $alluserinfo['meta']['datato'],
            'pagenumber' => $alluserinfo['meta']['pagenumber'],
        ]);
        $contents .= html_writer::tag('p', get_string('showingreportdatanumber', 'report_usercoursereports', $strdata));
        $contents .= html_writer::start_tag('table', ['id' => 'user-report-table', 'class' => 'generaltable generalbox']);
        $contents .= self::get_table_header($tableheader, $parameters);
        $contents .= html_writer::start_tag('tbody', ['data-type' => 'user-report']);
        foreach ($alluserinfo['data'] as $user) {
            // ... output item row
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
                        ['class' => 'user-thumbnail']
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
            $contents .= html_writer::tag('td', $user['city']);
            $contents .= html_writer::tag(
                'td',
                html_writer::alist(
                    array_column($user['roles'], 'name'),
                    ['style' => 'list-style: none; padding-left: 0; margin: 0;'],
                )
            );
            $contents .= html_writer::tag('td', $user['count_enrolled_courses']);
            $contents .= html_writer::tag('td', $user['lastaccess']);
            $contents .= html_writer::tag(
                'td',
                html_writer::link(
                    new moodle_url($parameters['pagepath'], ['type' => 'user', 'id' => $user['id']]),
                    get_string('viewdetail', 'report_usercoursereports'),
                    ['class' => 'view-user-detail']
                )
            );
            $contents .= html_writer::end_tag('tr');
        }
        $contents .= html_writer::end_tag('tbody');
        $contents .= html_writer::end_tag('table');
        $contents .= $OUTPUT->paging_bar(
            $alluserinfo['meta']['totalrecords'],
            $alluserinfo['meta']['pagenumber'],
            $perpage,
            $pageurl
        );
        $contents .= html_writer::tag('div', $OUTPUT->render_from_template("core/loading", []), [
            'id' => 'filter-loading-wrapper',
            'style' => 'display:none;',
        ]);
        $contents .= html_writer::end_tag('div');

        return $contents;
    }


    /**
     * Generates the <thead> section of a table for the report.
     *
     * @param array $tableheader Array of table header definitions.
     *  Each element should be an associative array with keys:
     *   - 'sort'  => bool, whether the column is sortable
     *   - 'field' => string, field name used for sorting
     *   - 'title' => string, the text to display in the header
     * @param array $parameters Array of parameters including:
     *   - 'urlparams' => array of current URL params
     *   - 'sortby'   => string, current sort field
     *   - 'sortdir'  => int, current sort direction (SORT_ASC or SORT_DESC)
     *   - 'pagepath' => string, base page URL
     * @return string HTML output for the table <thead> section
     */
    public static function get_table_header($tableheader, $parameters) {
        $contents = '';
        $contents .= html_writer::start_tag('thead');
        $contents .= html_writer::start_tag('tr');
        $headerindex = 0;
        foreach ($tableheader as $key => $headervalue) {
            $headerfield = $headervalue['field'];
            if ($headervalue['sort']) {
                $urlparams = $parameters['urlparams'];
                $sortdir = SORT_DESC;
                if ($parameters['sortby'] == $headerfield) {
                    $sortdir = ($parameters['sortdir'] == SORT_ASC) ? SORT_DESC : SORT_ASC;
                }
                $urlparams['sortby'] = $headerfield;
                $urlparams['sortdir'] = $sortdir;
                $value = html_writer::link(
                    new moodle_url(
                        $parameters['pagepath'],
                        $urlparams
                    ),
                    $headervalue['title'],
                    ['class' => 'sort-link']
                );
                $value .= ($parameters['sortby'] == $headerfield) ? self::column_sort_icon($parameters['sortdir']) : '';
            } else {
                $value = $headervalue['title'];
            }

            $contents .= html_writer::tag(
                'th',
                $value,
                [
                    "class" => "header c{$headerindex} {$headerfield}",
                    "scope" => "col",
                ]
            );
            $headerindex++;
        }
        $contents .= html_writer::end_tag('tr');
        $contents .= html_writer::end_tag('thead');
        return $contents;
    }


    /**
     * Returns the HTML for a sort icon based on the sort direction.
     *
     * This function outputs a Font Awesome icon indicating ascending or descending sort.
     *
     * @param int|string $sortdir The sort direction. Typically SORT_ASC (3), SORT_DESC (4), or empty for no sort.
     * @return string HTML markup for the sort icon, or an empty string if no sort direction is specified.
     */
    public static function column_sort_icon($sortdir = '') {
        if ($sortdir == SORT_ASC) {
            return '<i class="icon fa fa-sort-asc fa-fw " title="Ascending" role="img" aria-label="Ascending"></i>';
        } else if ($sortdir == SORT_DESC) {
            return '<i class="icon fa fa-sort-desc fa-fw " title="Descending" role="img" aria-label="Descending"></i>';
        } else {
            return '';
        }
    }

    /**
     * Generates a detailed profile view for a single user.
     *
     * @param int $userid The ID of the user to display.
     * @return string HTML output for the user's profile and enrolled courses.
     */
    public static function get_singleuser_info($userid) {
        global $OUTPUT;
        $userinfo = user_data_handler::get_user_info($userid, false);
        $languages = get_string_manager()->get_list_of_translations();
        $enrolledcourses = $userinfo['enrolled_courses'];
        unset($userinfo['enrolled_courses']);
        $userdetaillist = [
            ['label' => get_string('fullname'), 'value' => $userinfo['firstname'] . ' ' . $userinfo['lastname']],
            ['label' => get_string('username'), 'value' => $userinfo['username']],
            ['label' => get_string('email'), 'value' => $userinfo['email']],
            ['label' => get_string('city'), 'value' => $userinfo['city']],
            ['label' => get_string('country'), 'value' => $userinfo['country_name']],
            ['label' => get_string('address'), 'value' => $userinfo['address']],
            ['label' => get_string('language'), 'value' => $languages[$userinfo['language']]],
            ['label' => get_string('timezone'), 'value' => $userinfo['timezone']],
            ['label' => get_string('institution'), 'value' => $userinfo['institution']],
            ['label' => get_string('department'), 'value' => $userinfo['department']],
            ['label' => get_string('phone1'), 'value' => $userinfo['phone1']],
            ['label' => get_string('phone2'), 'value' => $userinfo['phone2']],
            ['label' => get_string('accountcreated', 'report_usercoursereports'), 'value' => $userinfo['timecreated']],
            ['label' => get_string('accountmodified', 'report_usercoursereports'), 'value' => $userinfo['timemodified']],
            ['label' => get_string('firstaccess'), 'value' => $userinfo['firstaccess']],
            ['label' => get_string('lastaccess'), 'value' => $userinfo['lastaccess']],
            ['label' => get_string('lastlogin'), 'value' => $userinfo['lastlogin']],
            ['label' => get_string('interests'), 'value' => $userinfo['interests']],
            ['label' => get_string('roles'), 'value' => implode(", ", array_column($userinfo['roles'], 'name'))],
        ];
        if ($userinfo['customofields'] && is_array($userinfo['customofields'])) {
            foreach ($userinfo['customofields'] as $key => $customofields) {
                $userdetaillist[] = [
                    'customofields' => true,
                    'categoryname' => $customofields['categoryname'],
                    'label' => $customofields['name'],
                    'value' => $customofields['displayvalue'],
                ];
            }
        }
        $context = [
            'userinfo' => $userinfo,
            'userdetaillist' => $userdetaillist,
        ];

        // ... output content
        $contents = '';
        $contents .= $OUTPUT->render_from_template("report_usercoursereports/singleuserdetails", $context);

        // ... user enrolled courses.
        $contents .= html_writer::start_div('my-enrolled-courses mt-4 mb-4');
        $contents .= html_writer::tag('h3', get_string('mycourses'));
        $contents .= html_writer::start_tag('table', ['id' => 'user-enrolled-course-table', 'class' => 'generaltable generalbox']);
        $contents .= html_writer::start_tag('thead');
        $contents .= html_writer::tag(
            'tr',
            html_writer::tag('th', get_string('coursename', 'report_usercoursereports')) .
                html_writer::tag('th', get_string('enrolldate', 'report_usercoursereports')) .
                html_writer::tag('th', get_string('courseprogress', 'report_usercoursereports')) .
                html_writer::tag('th', get_string('courserole', 'report_usercoursereports'))
        );
        $contents .= html_writer::end_tag('thead');
        $contents .= html_writer::start_tag('tbody', ['data-type' => 'user-course-report']);
        if ($enrolledcourses && is_array($enrolledcourses)) {
            foreach ($enrolledcourses as $key => $course) {
                $course = (array)$course;
                $contents .= html_writer::start_tag(
                    'tr',
                    [
                        'data-id' => $course['id'],
                        'data-fullname' => $course['fullname'],
                    ]
                );
                $contents .= html_writer::tag(
                    'td',
                    html_writer::link(
                        $course['course_link'],
                        $course['fullname']
                    )
                );
                $contents .= html_writer::tag('td', $course['enrolments_timecreated']);
                $contents .= html_writer::tag('td', $course['percentage'] . "%");
                $contents .= html_writer::tag(
                    'td',
                    html_writer::alist(
                        array_column($course['mycourseroles'], 'name'),
                        ['style' => 'list-style: none; padding-left: 0; margin: 0;'],
                    )
                );
                $contents .= html_writer::end_tag('tr');
            }
        } else {
            $contents .= html_writer::tag(
                'tr',
                html_writer::tag('td', get_string('nodata_available', 'report_usercoursereports'), ['colspan' => 4])
            );
        }
        $contents .= html_writer::end_tag('tbody');
        $contents .= html_writer::end_tag('table');
        $contents .= html_writer::end_div();

        return $contents;
    }
}
