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
use report_usercoursereports\tablereport;
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
$parameters = usercoursereports::get_params();

// Prepare the page information.
$pagepath       = '/report/usercoursereports/index.php';
$urlparams      = usercoursereports::urlparam($parameters);
$urlbaseparams = [];
$urlbaseparams['type'] = $type;
if ($parameters['id']) {
    $urlbaseparams['id'] = $parameters['id'];
}
$pageurl        = new moodle_url($pagepath, $urlparams);
$pagereseturl    = new moodle_url($pagepath, $urlbaseparams);
$pagetitle     = get_string('pluginname', 'report_usercoursereports');
// Add more parameters.
$parameters['pagepath'] = $pagepath;
$parameters['urlparams'] = $urlparams;
$parameters['pageurl'] = $pageurl->out(false);
$parameters['pagereseturl'] = $pagereseturl->out(false);

// Setup page information.
$PAGE->set_context($context);
$PAGE->set_url($pageurl);
$PAGE->set_pagelayout('report');
$PAGE->set_pagetype('report_usercoursereports');
$PAGE->set_subpage((string)$type);
$PAGE->set_title($pagetitle);
$PAGE->set_heading($pagetitle);
$PAGE->add_body_class('report-usercoursereports');
$PAGE->navbar->add(get_string($type . 'reports', 'report_usercoursereports'), $pageurl);
$PAGE->requires->jquery();

// Load AMD module.
$PAGE->requires->js_call_amd(
    'report_usercoursereports/usercoursereports',
    'init',
    [
        [
            'urlpath' => $pagepath,
            'type' => $type,
            'pagereseturl' => $pagereseturl->out(false),
        ],
    ]
);
// Load filter.
$filterform = new filter_form(
    $pagereseturl,
    $parameters,
    'GET',
    '',
    [
        'id' => 'usercoursereports-filter',
        'class' => 'mform report-usercoursereports-filter pt-3 pb-3 me',
        'data-usercoursereports-type' => $type,
    ]
);
if ($filterform->is_cancelled()) {
    redirect($pagereseturl);
}

// Get the data and display.
$contents = '';
$contents .= usercoursereports::get_report_list($parameters);
if ($type == 'user' && $parameters['id']) {
    $contents .= usercoursereports::get_singleuser_info($parameters);
} else if ($type == 'course' && $parameters['id']) {
    $contents .= usercoursereports::get_singlecourse_info($parameters);
} else if ($type == 'course') {
    $contents .= $filterform->render();
    $contents .= tablereport::get_coursereport_table($parameters);
} else if ($type == 'user') {
    $contents .= $filterform->render();
    $contents .= tablereport::get_userinfo_table($parameters);
}

// Output Content.
echo $OUTPUT->header();
echo $contents;
echo $OUTPUT->footer();
