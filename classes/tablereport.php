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
 * User course reports table handler class.
 *
 * @package   report_usercoursereports
 * @copyright 2025 https://santoshmagar.com.np/
 * @author    santoshtmp7
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_usercoursereports;

use core_course\reportbuilder\local\formatters\enrolment;
use html_writer;
use moodle_url;
use report_usercoursereports\local\usercoursereport_flextablelib;
use stdClass;

/**
 * Class tablereport
 *
 * @package    report_usercoursereports
 * @copyright  2025 santoshtmp <https://santoshmagar.com.np/>
 * @author     santoshtmp
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
class tablereport {

    /**
     * Default number of records per page.
     *
     * @var int
     */
    public static $defaultperpage = 50;

    /**
     * Generate a table of courses with metadata and filters applied.
     *
     * Fetches course data and renders a paginated table.
     *
     * @param array $parameters Filter and paging parameters.
     * @return string HTML output of the course report table.
     */
    public static function get_coursereport_table($parameters) {
        global $OUTPUT;
        $parameters['perpage'] = ($parameters['perpage']) ?: self::$defaultperpage;
        $download = $parameters['download'] ?? 0;
        $reportrecords = course_data_handler::get_all_course_info($parameters);
        $datafrom = $reportrecords['meta']['datafrom'];
        $datato = $reportrecords['meta']['datato'];
        $datatotalrecords = $reportrecords['meta']['totalrecords'];
        $summary = new stdClass();
        $summary->datafrom = $datafrom;
        $summary->datato = $datato;
        $summary->datatotal = $datatotalrecords;
        // Table information setup.
        $tablename = 'course-report-table';
        $tablecolumns = [
            'sn',
            'coursename',
            'category',
            'participants',
            'format',
            'visible',
            'enrol',
            'startdate',
            'timecreated',
            'action',
        ];
        $tableheaders = [
            get_string('sn', 'report_usercoursereports'),
            get_string('fullname'),
            get_string('category'),
            get_string('participants'),
            get_string('courseformat', 'report_usercoursereports'),
            get_string('coursevisibility', 'report_usercoursereports'),
            get_string('enrolmentmethods', 'report_usercoursereports'),
            get_string('startdateto', 'report_usercoursereports'),
            get_string('createddate', 'report_usercoursereports'),
            get_string('action'),
        ];
        $colsorting = ['coursename', 'category', 'participants', 'startdate', 'timecreated'];
        $tableattributes = [
            'id' => $tablename,
            'class' => 'generaltable generalbox',
        ];

        // Initialize table.
        $table = new usercoursereport_flextablelib($tablename);
        $table->define_columns($tablecolumns);
        $table->define_headers($tableheaders);
        $table->define_baseurl($parameters['pageurl']);
        $table->define_reseturl($parameters['pagereseturl']);
        $table->set_page_number($parameters['spage'] + 1);
        $table->pagesize($parameters['perpage'], $datatotalrecords);
        $table->sortable(true);
        foreach ($tablecolumns as $col) {
            if (!in_array($col, $colsorting)) {
                $table->no_sorting($col);
            }
        }
        foreach ($tablecolumns as $col) {
            $table->column_class($col, 'col-' . $col);
        }
        foreach ($tableattributes as $key => $value) {
            $table->set_attribute($key, $value);
        }
        $table->pagesize($parameters['perpage'], $datatotalrecords);
        $table->set_control_variables(
            [
                TABLE_VAR_SORT   => 'sortby',
                TABLE_VAR_DIR    => 'sortdir',
                TABLE_VAR_IFIRST => 'sifirst',
                TABLE_VAR_ILAST  => 'silast',
                TABLE_VAR_PAGE   => 'spage',
            ]
        );

        // Reset handling.
        if (isset($parameters['treset']) && $parameters['treset'] == 1) {
            $table->mark_table_to_reset();
        }
        // Download handling.
        $table->show_download_buttons_at([TABLE_P_BOTTOM]);
        if ($table->is_downloading($download, $tablename, $tablename . '-' . time())) {
            raise_memory_limit(MEMORY_EXTRA);
        }

        // Setup table.
        $table->setup();
        ob_start();
        foreach ($reportrecords['data'] as $record) {
            // ... output item row
            $row = [];
            if ($download) {
                $row[] = $datafrom++;
                $row[] = format_string($record->fullname);
                $row[] = format_string($record->category_name);
                $row[] = $record->participants;
                $row[] = get_string('pluginname', 'format_' . $record->format);
                $row[] = $record->visible ? get_string('show') : get_string('hide');
                $row[] = implode(", ", course_data_handler::get_course_enrollmentmethods($record->id, true));
                $row[] = user_data_handler::get_user_date_time($record->startdate);
                $row[] = user_data_handler::get_user_date_time($record->timecreated);
                $row[] = (new moodle_url($parameters['pagepath'], ['type' => 'course', 'id' => $record->id]))->out(false);
            } else {
                $row[] = $datafrom++;
                $row[] = html_writer::link(
                    new \moodle_url('/course/view.php', ['id' => $record->id]),
                    html_writer::img(
                        course_data_handler::get_course_image($record, true),
                        format_string($record->fullname),
                        ['class' => 'course-thumbnail']
                    ) .
                        html_writer::tag(
                            'div',
                            format_string($record->fullname),
                            ['class' => 'course-name pl-3 ']
                        ),
                    ['class' => 'course-link d-flex justify-content-start']
                );
                $row[] = html_writer::link(
                    new \moodle_url('/course/index.php', ['categoryid' => $record->category]),
                    format_string($record->category_name)
                );
                $row[] = $record->participants;
                $row[] = get_string('pluginname', 'format_' . $record->format);
                $row[] = $record->visible ? get_string('show') : get_string('hide');
                $row[] = html_writer::alist(
                    course_data_handler::get_course_enrollmentmethods($record->id, true),
                    ['style' => 'list-style: none; padding-left: 0; margin: 0;'],
                );
                $row[] = user_data_handler::get_user_date_time($record->startdate);
                $row[] = user_data_handler::get_user_date_time($record->timecreated);
                $row[] = html_writer::link(
                    new moodle_url($parameters['pagepath'], ['type' => 'course', 'id' => $record->id]),
                    get_string('viewdetail', 'report_usercoursereports'),
                    ['class' => 'view-user-detail']
                );
            }

            $table->add_data($row);
        }
        $table->finish_output();
        $outputreportdatatable = ob_get_contents();
        ob_end_clean();
        // If downloading, output the table and terminate.
        if ($download) {
            echo $outputreportdatatable;
            die;
        }
        // Display the filter area content.
        $contents = '';
        $contents .= html_writer::start_tag('div', [
            'id' => 'report-usercoursereports-filter-area',
            'class' => 'no-overflow',
            'usercoursereports-filter-type' => $parameters['type'] ?? '',
            'totalrecords' => $datatotalrecords,
            'datafrom' => $datafrom,
            'datato' => $datato,
            'pagenumber' => $parameters['spage'] ?? 1,
        ]);
        $contents .= html_writer::tag('p', get_string('showingreportdatanumber', 'report_usercoursereports', $summary));
        $contents .= $outputreportdatatable;
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
     * @param array $parameters Filter and paging parameters.
     * @return string HTML output of the user report table.
     */
    public static function get_userinfo_table($parameters) {
        global $OUTPUT;
        $parameters['perpage'] = ($parameters['perpage']) ?: self::$defaultperpage;
        $download = $parameters['download'] ?? 0;
        $reportrecords = user_data_handler::get_all_user_info($parameters);
        $datafrom = $reportrecords['meta']['datafrom'];
        $datato = $reportrecords['meta']['datato'];
        $datatotalrecords = $reportrecords['meta']['totalrecords'];
        $summary = new stdClass();
        $summary->datafrom = $datafrom;
        $summary->datato = $datato;
        $summary->datatotal = $datatotalrecords;
        // Table information setup.
        $tablename = 'user-report-table';
        $tablecolumns = [
            'sn',
            'firstname',
            'email',
            'city',
            'roles',
            'enrolledcourses',
            'lastaccess',
            'action',
        ];
        $tableheaders = [
            get_string('sn', 'report_usercoursereports'),
            get_string('fullname'),
            get_string('email'),
            get_string('city'),
            get_string('roles'),
            get_string('enrolledcourses', 'report_usercoursereports'),
            get_string('lastaccess', 'report_usercoursereports'),
            get_string('action'),
        ];
        $colsorting = ['firstname', 'email', 'city', 'enrolledcourses', 'lastaccess'];
        $tableattributes = [
            'id' => $tablename,
            'class' => 'generaltable generalbox',
        ];
        // Initialize table.
        $table = new usercoursereport_flextablelib($tablename);
        $table->define_columns($tablecolumns);
        $table->define_headers($tableheaders);
        $table->define_baseurl($parameters['pageurl']);
        $table->define_reseturl($parameters['pagereseturl']);
        $table->set_page_number($parameters['spage'] + 1);
        $table->pagesize($parameters['perpage'], $datatotalrecords);
        $table->sortable(true);
        foreach ($tablecolumns as $col) {
            if (!in_array($col, $colsorting)) {
                $table->no_sorting($col);
            }
        }
        foreach ($tablecolumns as $col) {
            $table->column_class($col, 'col-' . $col);
        }
        foreach ($tableattributes as $key => $value) {
            $table->set_attribute($key, $value);
        }
        $table->pagesize($parameters['perpage'], $datatotalrecords);
        $table->set_control_variables(
            [
                TABLE_VAR_SORT   => 'sortby',
                TABLE_VAR_DIR    => 'sortdir',
                TABLE_VAR_IFIRST => 'sifirst',
                TABLE_VAR_ILAST  => 'silast',
                TABLE_VAR_PAGE   => 'spage',
            ]
        );

        // Reset handling.
        if (isset($parameters['treset']) && $parameters['treset'] == 1) {
            $table->mark_table_to_reset();
        }
        // Download handling.
        $table->show_download_buttons_at([TABLE_P_BOTTOM]);
        if ($table->is_downloading($download, $tablename, $tablename . '-' . time())) {
            raise_memory_limit(MEMORY_EXTRA);
        }

        // Setup table.
        $table->setup();
        ob_start();
        foreach ($reportrecords['data'] as $record) {
            // ... output item row
            $row = [];
            if ($download) {
                $row[] = $datafrom++;
                $row[] = $record->firstname . ' ' . $record->lastname;
                $row[] = $record->email;
                $row[] = $record->city;
                $row[] = implode(", ", array_column(user_data_handler::get_all_roles($record->id), 'name'));
                $row[] = $record->enrolledcourses;
                $row[] = user_data_handler::get_user_date_time($record->lastaccess, '');
                $row[] = (new moodle_url($parameters['pagepath'], ['type' => 'user', 'id' => $record->id]))->out(false);
            } else {
                $row[] = $datafrom++;
                $row[] = html_writer::link(
                    new moodle_url('/user/profile.php', ['id' => $record->id]),
                    html_writer::img(
                        user_data_handler::get_user_profile_image($record->id),
                        $record->username,
                        ['class' => 'user-thumbnail']
                    ) .
                        html_writer::tag(
                            'div',
                            html_writer::tag('span', $record->firstname . ' ' . $record->lastname) .
                                html_writer::tag('span',  '(' . $record->username . ')'),
                            ['class' => 'pl-3 d-flex flex-column justify-content-start']
                        ),
                    ['class' => 'd-flex justify-content-start']
                );
                $row[] = $record->email;
                $row[] = $record->city;
                $row[] = html_writer::alist(
                    array_column(user_data_handler::get_all_roles($record->id), 'name'),
                    ['style' => 'list-style: none; padding-left: 0; margin: 0;'],
                );
                $row[] = is_array($record->enrolledcourses)
                    ? implode(', ', $record->enrolledcourses)
                    : $record->enrolledcourses;
                $row[] = user_data_handler::get_user_date_time($record->lastaccess, '');
                $row[] = html_writer::link(
                    new moodle_url($parameters['pagepath'], ['type' => 'user', 'id' => $record->id]),
                    get_string('viewdetail', 'report_usercoursereports'),
                    ['class' => 'view-user-detail']
                );
            }

            $table->add_data($row);
        }
        $table->finish_output();
        $outputreportrecords = ob_get_contents();
        ob_end_clean();
        // If downloading, output the table and terminate.
        if ($download) {
            echo $outputreportrecords;
            die;
        }
        // Display the filter area content.
        $contents = '';
        $contents .= html_writer::start_tag('div', [
            'id' => 'report-usercoursereports-filter-area',
            'class' => 'no-overflow',
            'usercoursereports-filter-type' => $parameters['type'] ?? '',
            'totalrecords' => $datatotalrecords,
            'datafrom' => $datafrom,
            'datato' => $datato,
            'pagenumber' => $parameters['spage'] ?? 1,
        ]);
        $contents .= html_writer::tag('p', get_string('showingreportdatanumber', 'report_usercoursereports', $summary));
        $contents .= $outputreportrecords;
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
    public static function get_custom_thead($tableheader, $parameters) {
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
                    ['class' => 'sort-link', "data-sortable" => "1"]
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
     * Generate a table of users enrolled in a specific course with metadata and filters applied.
     *
     * Fetches enrolled user data for a course and renders a paginated table.
     *
     * @param array $parameters Filter and paging parameters including:
     *   - 'id' => int, course ID
     *   - 'perpage' => int, number of records per page
     *   - 'pagepath' => string, base page URL
     *   - 'urlparams' => array of current URL params
     * @return string HTML output of the enrolled users report table.
     */
    public static function course_enrolled_users($parameters) {

        $courseid = $parameters['id'];
        $parameters['perpage'] = ($parameters['perpage']) ?: self::$defaultperpage;
        $download = $parameters['download'] ?? 0;
        if (!$courseid) {
            return '';
        }

        $course = get_course($courseid);
        $coursecontext = \context_course::instance($courseid);
        $reportrecords = user_data_handler::course_get_enrolled_users($parameters);
        $datafrom = $reportrecords['meta']['datafrom'];
        $datato = $reportrecords['meta']['datato'];
        $datatotal = $reportrecords['meta']['totalrecords'];
        $summary = new stdClass();
        $summary->datafrom = $datafrom;
        $summary->datato = $datato;
        $summary->datatotal = $datatotal;
        // Table information setup.
        $tablecolumns = [
            'sn',
            'firstname',
            'courserole',
            'groups',
            'progress',
            'enrolldate',
            'enrollmethod',
            'lastcourseaccess',
            'status',
        ];
        $tableheaders = [
            get_string('sn', 'report_usercoursereports'),
            get_string('fullname'),
            get_string('courserole', 'report_usercoursereports'),
            get_string('groups'),
            get_string('courseprogress', 'report_usercoursereports'),
            get_string('enrolldate', 'report_usercoursereports'),
            get_string('enrolmentmethods', 'report_usercoursereports'),
            get_string('lastcourseaccess'),
            get_string('status'),
        ];
        $nosorting = ['sn', 'courserole', 'groups', 'progress', 'enrolldate', 'enrollmethod', 'status'];
        $tablename = 'course-enrolled-users';
        $tableattributes = [
            'id' => $tablename,
            'class' => 'generaltable generalbox',
        ];
        // Initialize table.
        $table = new usercoursereport_flextablelib($tablename);
        $table->define_columns($tablecolumns);
        $table->define_headers($tableheaders);
        $table->define_baseurl($parameters['pageurl']);
        $table->define_reseturl($parameters['pagereseturl']);
        $table->set_page_number($parameters['spage'] + 1);
        $table->pagesize($parameters['perpage'], $reportrecords['meta']['totalrecords']);
        $table->sortable(true);
        foreach ($nosorting as $ns) {
            $table->no_sorting($ns);
        }
        foreach ($tablecolumns as $col) {
            $table->column_class($col, 'col-' . $col);
        }
        foreach ($tableattributes as $key => $value) {
            $table->set_attribute($key, $value);
        }
        $table->pagesize($parameters['perpage'], $reportrecords['meta']['totalrecords']);
        $table->set_control_variables(
            [
                TABLE_VAR_SORT   => 'sortby',
                TABLE_VAR_DIR    => 'sortdir',
                TABLE_VAR_IFIRST => 'sifirst',
                TABLE_VAR_ILAST  => 'silast',
                TABLE_VAR_PAGE   => 'spage',
            ]
        );
        // Reset handling.
        if (isset($parameters['treset']) && $parameters['treset'] == 1) {
            $table->mark_table_to_reset();
        }
        // Download handling.
        $table->show_download_buttons_at([TABLE_P_BOTTOM]);
        if ($table->is_downloading($download, $tablename . "-" . $courseid, $tablename . "-" . $courseid)) {
            raise_memory_limit(MEMORY_EXTRA);
        }

        // Setup table.
        $table->setup();
        ob_start();
        foreach ($reportrecords['data'] as $record) {
            $enrolldate = [];
            $enrollmethod = [];
            $status = [];

            $statusvalues = enrolment::enrolment_values();
            $usercourseenrolments = user_data_handler::get_user_course_enrolments($record->id, $courseid);
            foreach ($usercourseenrolments as $key => $enrolinstance) {
                $enrolldate[] = user_data_handler::get_user_date_time($enrolinstance->timecreated);
                $enrollmethod[] = enrol_get_plugin($enrolinstance->enrol)->get_instance_name($enrolinstance);
                $status[] = $statusvalues[$enrolinstance->ue_status ? 1 : ($enrolinstance->enrol_status ? 2 : 0)];
            }
            $courseroles = get_user_roles($coursecontext, $record->id);
            foreach ($courseroles as $key => &$role) {
                $role->name = $role->name ?: role_get_name($role);
            }
            $groups = groups_get_all_groups($course->id, $record->id);

            // ... output item row
            $row = [];
            if ($download) {
                $row[] = $datafrom++;
                $row[] = $record->firstname . ' ' . $record->lastname . ", " . $record->email;
                $row[] = implode(", ", array_column($courseroles, 'name'));
                $row[] = implode(", ", array_column($groups, 'name'));
                $row[] = user_data_handler::get_user_course_progress($course, $record->id) . '%';
                $row[] = implode(", ", $enrolldate);
                $row[] = implode(", ", $enrollmethod);
                $row[] = user_data_handler::get_user_date_time($record->lastcourseaccess, '');
                $row[] = implode(", ", $status);
            } else {
                $row[] = $datafrom++;
                $row[] = html_writer::link(
                    new moodle_url('/user/profile.php', ['id' => $record->id]),
                    html_writer::img(
                        user_data_handler::get_user_profile_image(
                            $record->id,
                            true
                        ),
                        $record->firstname . ' ' . $record->lastname,
                        ['class' => 'user-thumbnail']
                    ) .
                        html_writer::tag(
                            'div',
                            html_writer::tag(
                                'span',
                                $record->firstname . ' ' . $record->lastname,
                                ['user-field' => 'fullname', 'title' => "fullname"]
                            ) .
                                html_writer::tag(
                                    'span',
                                    '(' . $record->email . ')',
                                    ['user-field' => 'email', 'title' => 'email', 'class' => 'break-anywhere']
                                ),
                            ['class' => 'pl-3 d-flex flex-column justify-content-start']
                        ),
                    ['class' => 'd-flex justify-content-start ']
                );
                $row[] = html_writer::alist(
                    array_column($courseroles, 'name'),
                    ['style' => 'list-style: none; padding-left: 0; margin: 0;']
                );
                $row[] = html_writer::alist(
                    array_column($groups, 'name'),
                    ['style' => 'list-style: none; padding-left: 0; margin: 0;']
                );
                $row[] = user_data_handler::get_user_course_progress($course, $record->id) . '%';
                $row[] = html_writer::alist($enrolldate, ['style' => 'list-style: none; padding-left: 0; margin: 0;']);
                $row[] = html_writer::alist($enrollmethod, ['style' => 'list-style: none; padding-left: 0; margin: 0;']);
                $row[] = html_writer::tag(
                    'div',
                    html_writer::tag('span', user_data_handler::get_user_date_time($record->lastcourseaccess, '')) .
                        ($record->lastcourseaccess ? html_writer::tag(
                            'span',
                            "(" . format_time(time() - $record->lastcourseaccess) . ")"
                        ) : ''),
                    ['class' => 'd-flex flex-column justify-content-start']
                );
                $row[] = html_writer::alist($status, ['style' => 'list-style: none; padding-left: 0; margin: 0;']);
            }

            $table->add_data($row);
        }
        $table->finish_output();
        $outputreportdatatable = ob_get_contents();
        ob_end_clean();
        // If downloading, output the table and terminate.
        if ($download) {
            echo $outputreportdatatable;
            die;
        }
        // Display the filter area content.
        $contents = '';
        $contents .= html_writer::start_tag('div', [
            'id' => 'report-usercoursereports-filter-area',
            'class' => 'no-overflow',
            'usercoursereports-filter-type' => $parameters['type'] ?? '',
            'totalrecords' => $datatotal,
            'datafrom' => $datafrom,
            'datato' => $datato,
            'pagenumber' => $reportrecords['meta']['pagenumber'] ?? 1,
        ]);
        $contents .= html_writer::tag('p', get_string('showingreportdatanumber', 'report_usercoursereports', $summary));
        $contents .= $outputreportdatatable;
        $contents .= html_writer::end_tag('div');

        return $contents;
    }

    /**
     * @param array $parameters
     * @return string HTML output of the report table.
     */
    public static function user_enrolled_courses($parameters) {
        global $DB;
        $userid = $parameters['id'];
        $parameters['perpage'] = ($parameters['perpage']) ?: self::$defaultperpage;
        $download = $parameters['download'] ?? 0;
        if (!$userid) {
            return '';
        }

        $reportrecords = user_data_handler::user_get_enrolled_courses($parameters);
        // Table information setup.
        $tablecolumns = [
            'sn',
            'coursename',
            'courserole',
            'groups',
            'courseprogress',
            'enrolldate',
            'enrolmentmethods',
            'lastcourseaccess',
            'status',
        ];
        $tableheaders = [
            get_string('sn', 'report_usercoursereports'),
            get_string('coursename', 'report_usercoursereports'),
            get_string('courserole', 'report_usercoursereports'),
            get_string('groups'),
            get_string('courseprogress', 'report_usercoursereports'),
            get_string('enrolldate', 'report_usercoursereports'),
            get_string('enrolmentmethods', 'report_usercoursereports'),
            get_string('lastcourseaccess'),
            get_string('status'),
        ];
        $colsorting = [''];
        $tablename = 'user-enrolled-courses';
        $tableattributes = [
            'id' => $tablename,
            'class' => 'generaltable generalbox',
        ];
        $totalrecord = count($reportrecords);
        // Initialize table.
        $table = new usercoursereport_flextablelib($tablename);
        $table->define_columns($tablecolumns);
        $table->define_headers($tableheaders);
        $table->define_baseurl($parameters['pageurl']);
        $table->define_reseturl($parameters['pagereseturl']);
        $table->set_page_number($parameters['spage'] + 1);
        $table->pagesize($totalrecord, $totalrecord);
        $table->sortable(true);
        foreach ($tablecolumns as $col) {
            if (!in_array($col, $colsorting)) {
                $table->no_sorting($col);
            }
        }
        foreach ($tablecolumns as $col) {
            $table->column_class($col, 'col-' . $col);
        }
        foreach ($tableattributes as $key => $value) {
            $table->set_attribute($key, $value);
        }
        $table->pagesize($totalrecord, $totalrecord);
        $table->set_control_variables(
            [
                TABLE_VAR_SORT   => 'sortby',
                TABLE_VAR_DIR    => 'sortdir',
                TABLE_VAR_IFIRST => 'sifirst',
                TABLE_VAR_ILAST  => 'silast',
                TABLE_VAR_PAGE   => 'spage',
            ]
        );
        // Reset handling.
        if (isset($parameters['treset']) && $parameters['treset'] == 1) {
            $table->mark_table_to_reset();
        }
        // Download handling.
        $table->show_download_buttons_at([TABLE_P_BOTTOM]);
        if ($table->is_downloading($download, $tablename . "-" . $userid, $tablename . "-" . $userid)) {
            raise_memory_limit(MEMORY_EXTRA);
        }

        // Setup table.
        $table->setup();
        ob_start();
        $datafrom = 1;
        foreach ($reportrecords as $record) {
            $enrolldate = [];
            $enrollmethod = [];
            $status = [];
            $coursecontext = \context_course::instance($record->id);
            $coursecategories = $DB->get_record('course_categories', ['id' => $record->category]);
            $statusvalues = enrolment::enrolment_values();
            $usercourseenrolments = user_data_handler::get_user_course_enrolments($userid, $record->id);
            foreach ($usercourseenrolments as $key => $enrolinstance) {
                $enrolldate[] = user_data_handler::get_user_date_time($enrolinstance->timecreated);
                $enrollmethod[] = enrol_get_plugin($enrolinstance->enrol)->get_instance_name($enrolinstance);
                $status[] = $statusvalues[$enrolinstance->ue_status ? 1 : ($enrolinstance->enrol_status ? 2 : 0)];
            }
            $courseroles = get_user_roles($coursecontext, $userid);
            foreach ($courseroles as $key => &$role) {
                $role->name = $role->name ?: role_get_name($role);
            }
            $groups = groups_get_all_groups($record->id, $userid);
            $record->lastcourseaccess = $DB->get_field('user_lastaccess', 'timeaccess', [
                'courseid' => $record->id,
                'userid' => $userid,
            ]);

            // ... output item row
            $row = [];
            if ($download) {
                $row[] = $datafrom++;
                $row[] = format_string($record->fullname) . ", " . format_string($coursecategories->name);
                $row[] = implode(", ", array_column($courseroles, 'name'));
                $row[] = implode(", ", array_column($groups, 'name'));
                $row[] = user_data_handler::get_user_course_progress($record, $userid) . '%';
                $row[] = implode(", ", $enrolldate);
                $row[] = implode(", ", $enrollmethod);
                $row[] = user_data_handler::get_user_date_time($record->lastcourseaccess, '');
                $row[] = implode(", ", $status);
            } else {
                $row[] = $datafrom++;
                $row[] = html_writer::link(
                    new \moodle_url('/course/view.php', ['id' => $record->id]),
                    html_writer::img(
                        course_data_handler::get_course_image($record, true),
                        format_string($record->fullname),
                        ['class' => 'course-thumbnail']
                    ) .
                        html_writer::tag(
                            'div',
                            html_writer::tag('span', format_string($record->fullname)) .
                                html_writer::tag('span', "(" . format_string($coursecategories->name) . ")"),
                            ['class' => 'course-name pl-3 d-flex flex-column justify-content-start']
                        ),
                    ['class' => 'course-link d-flex justify-content-start']
                );
                $row[] = html_writer::alist(
                    array_column($courseroles, 'name'),
                    ['style' => 'list-style: none; padding-left: 0; margin: 0;']
                );
                $row[] = html_writer::alist(
                    array_column($groups, 'name'),
                    ['style' => 'list-style: none; padding-left: 0; margin: 0;']
                );
                $row[] = user_data_handler::get_user_course_progress($record, $userid) . '%';
                $row[] = html_writer::alist($enrolldate, ['style' => 'list-style: none; padding-left: 0; margin: 0;']);
                $row[] = html_writer::alist($enrollmethod, ['style' => 'list-style: none; padding-left: 0; margin: 0;']);
                $row[] = html_writer::tag(
                    'div',
                    html_writer::tag('span', user_data_handler::get_user_date_time($record->lastcourseaccess, '')) .
                        ($record->lastcourseaccess ? html_writer::tag(
                            'span',
                            "(" . format_time(time() - $record->lastcourseaccess) . ")"
                        ) : ''),
                    ['class' => 'd-flex flex-column justify-content-start']
                );
                $row[] = html_writer::alist($status, ['style' => 'list-style: none; padding-left: 0; margin: 0;']);
            }

            $table->add_data($row);
        }
        $table->finish_output();
        $outputreportdatatable = ob_get_contents();
        ob_end_clean();
        // If downloading, output the table and terminate.
        if ($download) {
            echo $outputreportdatatable;
            die;
        }
        // Display the filter area content.
        $contents = '';
        $contents .= html_writer::start_tag('div', [
            'id' => 'report-usercoursereports-filter-area',
            'class' => 'no-overflow',
            'usercoursereports-filter-type' => $parameters['type'] ?? '',
        ]);
        $contents .= $outputreportdatatable;
        $contents .= html_writer::end_tag('div');

        return $contents;
    }
}
