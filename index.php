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
 * Main entry for User Course Reports.
 *
 * @package    report_usercoursereports
 * @copyright  2024 https://santoshmagar.com.np/
 * @author     santoshtmp7
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use report_usercoursereports\form\filter_form;
use report_usercoursereports\usercoursereports;

// Get require config file.
require_once(dirname(__FILE__) . '/../../config.php');
defined('MOODLE_INTERNAL') || die();

// Access checks and Capability check.
require_login(null, false);
$context = \context_system::instance();
if (!has_capability('report/usercoursereports:view', $context)) {
    throw new moodle_exception('invalidaccess', 'report_usercoursereports');
}
$type = required_param('type', PARAM_TEXT);
if (!$type || !in_array($type, ['course', 'user'])) {
    throw new moodle_exception('invalidtypeparam', 'report_usercoursereports');
}

// Get request parameters (with defaults).
$parameters = [
    'type'              => $type,
    'id'                => optional_param('id', 0, PARAM_INT),
    'search'            => optional_param('search', '', PARAM_TEXT),
    'page'              => optional_param('page', 0, PARAM_INT),
    'perpage'           => optional_param('perpage', 0, PARAM_INT),
    'courseformat'      => optional_param('courseformat', '', PARAM_TEXT),
    'coursevisibility'  => optional_param('coursevisibility', '', PARAM_TEXT),
    'createdfrom'       => optional_param('createdfrom', 0, PARAM_INT),
    'createdto'         => optional_param('createdto', 0, PARAM_INT),
    'download'          => optional_param('download', 0, PARAM_INT),
    'categoryids'       => optional_param_array('categoryids', 0, PARAM_INT),
    'courseids'         => optional_param_array('courseids', 0, PARAM_INT),
    'roleids'           => optional_param_array('roleids', 0, PARAM_INT),
];

// Prepare the page information. 
$pagepath       = '/report/usercoursereports/index.php';
$urlparams      = usercoursereports::urlparam($parameters);
$pageurl        = new moodle_url($pagepath, $urlparams);
$redirecturl    = new moodle_url($pagepath, ['type' => $type]);
$page_title     = 'usercoursereports-' . $type;

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

// Load AMD module.
$PAGE->requires->js_call_amd(
    'report_usercoursereports/usercoursereports',
    'init',
    [
        'urlpath' => $pagepath,
        'type' => $type,
    ]
);
// Load filter.
$filter_form = new filter_form(
    $redirecturl,
    $parameters,
    'GET',
    '',
    [
        'id' => 'usercoursereports-filter',
        'class' => 'mform report-usercoursereports-filter pt-3 pb-3 me',
        'data-usercoursereports-type' => $type,
    ]
);
if ($filter_form->is_cancelled()) {
    redirect($redirecturl);
}

//  Get the data and display.
$contents = '';
$contents .= usercoursereports::get_report_list($type, $pagepath);
$contents .= $filter_form->render();
if ($type == 'course') {
    $contents .= usercoursereports::get_course_info_table($pageurl, $parameters);
} elseif ($type == 'user') {
    $contents .= usercoursereports::get_user_info_table($pageurl, $parameters);
} else {
    $contents .= '<div> Please select the type.</div>';
}

// Output Content.
echo $OUTPUT->header();
echo $contents;
echo $OUTPUT->footer();
