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

use report_usercoursereports\form\filter_form;
use report_usercoursereports\report_content;

// Get require config file.
require_once(dirname(__FILE__) . '/../../config.php');
defined('MOODLE_INTERNAL') || die();

// Get parameters.
$id = optional_param('id', 0, PARAM_INT);
$type = optional_param('type', '', PARAM_TEXT);
$search = optional_param('search', '', PARAM_TEXT);
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('per_page', 50, PARAM_INT);
$courseids = optional_param_array('ids', 0, PARAM_INT);
$categoryids = optional_param_array('categoryids', 0, PARAM_INT);
$download = optional_param('download', 0, PARAM_INT);
$context = \context_system::instance();

// Access checks and validate.
require_login(null, false);
if (!has_capability('moodle/site:config', $context)) {
    throw new moodle_exception('invalidaccess', 'report_usercoursereports');
}
// require_capability('report/usercoursereports:view', $context);

// Prepare the page information. 
$pagepath = '/report/usercoursereports/index.php';
if ($type) {
    $urlparam['type'] = $type;
}
if ($id) {
    $urlparam['id'] = $id;
}
if ($categoryid) {
    $urlparam['category_id'] = $categoryid;
}
if ($page) {
    $urlparam['page'] = $page;
}
if ($perpage) {
    $urlparam['per_page'] = $perpage;
}
if ($search) {
    $params['search'] = $search;
}
$pageurl = new moodle_url($pagepath, $urlparam);
$redirecturl = new moodle_url($pagepath, ['type' => $type]);
$page_title = 'usercoursereports-' . $type;

// setup page information.
$PAGE->set_context($context);
$PAGE->set_url($pageurl);
$PAGE->set_pagelayout('report');
$PAGE->set_pagetype('report_usercoursereports');
$PAGE->set_subpage((string)$type);
$PAGE->set_title($page_title);
$PAGE->set_heading($page_title);
$PAGE->add_body_class('report-usercoursereports');
$PAGE->navbar->add($page_title, $pageurl);
$PAGE->requires->jquery();
// 
$filter_form = new filter_form($pageurl, [
    'type' => $type,
    'search' => $search,
    'courseids' => $courseids,
    'categoryids' => $categoryids,
    'courseformat' => $courseformat,

]);
if ($filter_form->is_cancelled()) {
    redirect($redirecturl);
}
//  Get the data and display.
$contents = '';
$contents .= report_content::get_report_list($pagepath);
$contents .= $filter_form->render();
if ($type == 'course') {
    $contents .= report_content::get_course_info_table($pageurl, $perpage, $page, $search, $categoryids);
} elseif ($type == 'user') {
    $contents .= report_content::get_user_info_table($pageurl, $perpage, $page, $search);
} else {
    $contents .= '<div> Please select the type.</div>';
}

// Output Content.
echo $OUTPUT->header();
echo $contents;
echo $OUTPUT->footer();
