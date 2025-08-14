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
 *
 * @package    report_usercoursereports
 * @copyright  2024 https://santoshmagar.com.np/
 * @author     santoshtmp7
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use report_usercoursereports\CourseDataHandler;
use report_usercoursereports\UserDataHandler;
use theme_skilllab\util\UtilReport_handler;

// Get require config file.
require_once(dirname(__FILE__) . '/../../config.php');
defined('MOODLE_INTERNAL') || die();

// Get parameters.
$id = optional_param('id', 0, PARAM_INT);
$type = optional_param('type', '', PARAM_TEXT);
$page_number = optional_param('page', 0, PARAM_INT);
$search = optional_param('search', '', PARAM_TEXT);
$download = optional_param('download', 0, PARAM_INT);
$context = \context_system::instance();
$search_category_id = '';
$per_page_data = 20;

// Validate.
require_login(null, false);
$allowedtypes = ['user', 'course'];
if (!array_key_exists($type, $allowedtypes)) {
    throw new moodle_exception('invalidtypeparam', 'report_usercoursereports');
}
if (!has_capability('moodle/site:config', $context)) {
    throw new moodle_exception('invalidaccess', 'report_usercoursereports');
}

// Prepare the page information. 
$page_path = '/report/usercoursereports/index.php';
$url_param = ['type' => $type];
if ($id) {
    $url_param['id'] = $id;
}
if ($search) {
    $params['search'] = $search;
}
$url = new moodle_url($page_path, $url_param);
$redirect_url = new moodle_url($page_path, ['type' => $type]);
$page_title = UtilReport_handler::get_report_page_title($type);

// setup page information
$PAGE->set_context($context);
$PAGE->set_url($page_url);
$PAGE->set_pagelayout('admin');
$PAGE->set_pagetype('report_usercoursereports');
$PAGE->set_subpage((string)$type);
$PAGE->set_title($page_title);
$PAGE->set_heading($page_title);
$PAGE->add_body_class('report-usercoursereports');
$PAGE->navbar->add($page_title, $page_url);
$PAGE->requires->jquery();

/**
 * ========================================================
 *     Access checks.
 * ========================================================
 */
require_login(null, false);

/**
 * ========================================================
 *     Get the data and display
 * ========================================================
 */
$contents = '';
if (!has_capability('moodle/site:config', $context)) {
    $contents .= "You don't have permission to access this pages";
    $contents .= "<br>";
    $contents .= "<a href='/'> Return Back</a>";
} else {

    $contents = UtilReport_handler::get_report_list();

    if ($type == 'course') {
        $all_course_info = CourseDataHandler::get_all_course_info(
            $per_page_data,
            $page_number,
            $search,
            $search_category_id
        );
        $pagination = $OUTPUT->paging_bar(
            $all_course_info['meta']['total_record'],
            $page_number,
            $per_page_data,
            $search_page_url
        );
        // 
        $template_content = [
            'course_info' => $all_course_info['data'],
            'has_data' => ($all_course_info['meta']['page_data_count']) ? true : false,
            'pagination' => $pagination,
            'search_form' => UtilReport_handler::get_search_form_content($page_url, [['name' => 'type', 'value' => $type]]),
        ];
        $contents .= $OUTPUT->render_from_template('theme_skilllab/pages/report/course_report', $template_content);
    } elseif ($type == 'user') {
        $all_user_info = UserDataHandler::get_all_user_info(
            $per_page_data,
            $page_number,
            $search,
        );
        $pagination = $OUTPUT->paging_bar(
            $all_user_info['meta']['total_record'],
            $page_number,
            $per_page_data,
            $search_page_url
        );
        $template_content = [
            'user_info' => $all_user_info['data'],
            'has_data' => ($all_user_info['meta']['page_data_count']) ? true : false,
            'pagination' => $pagination,
            'search_form' => UtilReport_handler::get_search_form_content($page_url, [['name' => 'type', 'value' => $type]]),
        ];
        $contents .= $OUTPUT->render_from_template('theme_skilllab/pages/report/user_report', $template_content);
    } else {
        $contents .= '<div> Please select the type.</div>';
    }
}



/**
 * ========================================================
 * -------------------  Output Content  -------------------
 * ========================================================
 */
echo $OUTPUT->header();
echo $contents;
echo $OUTPUT->footer();
