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

use report_usercoursereports\ReportTable;

// Get require config file.
require_once(dirname(__FILE__) . '/../../config.php');
defined('MOODLE_INTERNAL') || die();

// Get parameters.
$id = optional_param('id', 0, PARAM_INT);
$type = optional_param('type', '', PARAM_TEXT);
$search = optional_param('search', '', PARAM_TEXT);
$page = optional_param('page', 0, PARAM_INT);
$per_page = optional_param('per_page', 50, PARAM_INT);
$category_id = optional_param('category_id', 0, PARAM_INT);
$download = optional_param('download', 0, PARAM_INT);
$context = \context_system::instance();

// Access checks and validate.
require_login(null, false);
if (!has_capability('moodle/site:config', $context)) {
    throw new moodle_exception('invalidaccess', 'report_usercoursereports');
}

// Prepare the page information. 
$page_path = '/report/usercoursereports/index.php';
if ($type) {
    $url_param['type'] = $type;
}
if ($id) {
    $url_param['id'] = $id;
}
if ($category_id) {
    $url_param['category_id'] = $category_id;
}
if ($page) {
    $url_param['page'] = $page;
}
if ($per_page) {
    $url_param['per_page'] = $per_page;
}
if ($search) {
    $params['search'] = $search;
}
$page_url = new moodle_url($page_path, $url_param);
$redirect_url = new moodle_url($page_path, ['type' => $type]);
$page_title = 'usercoursereports-' . $type;

// setup page information.
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
 *     Get the data and display
 * ========================================================
 */
$contents = '';
$contents .= ReportTable::get_report_list($page_path);

if ($type == 'course') {
    $contents .= ReportTable::get_course_info_table($page_url, $per_page, $page, $search, $category_id);
} elseif ($type == 'user') {
    $contents .= ReportTable::get_user_info_table($page_url, $per_page, $page, $search);
} else {
    $contents .= '<div> Please select the type.</div>';
}




/**
 * ========================================================
 * -------------------  Output Content  -------------------
 * ========================================================
 */
echo $OUTPUT->header();
echo $contents;
echo $OUTPUT->footer();
