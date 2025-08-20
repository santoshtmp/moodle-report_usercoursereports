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
 *
 */

namespace report_usercoursereports\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use report_usercoursereports\form\filter_form;
use report_usercoursereports\usercoursereports;

defined('MOODLE_INTERNAL') || die();

/**
 * External API to get course/user report data as a table.
 *
 * @package    report_usercoursereports
 * @category   external
 * @copyright  2025 https://santoshmagar.com.np/
 * @author     santoshtmp7 https://github.com/santoshtmp/moodle-report_usercoursereports
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_report_table extends external_api {

    /**
     * Defines the parameters expected by the API.
     *
     * @return external_function_parameters Parameters for this external API function.
     */
    public static function execute_parameters() {
        return new external_function_parameters(
            [
                'querystring' => new external_value(PARAM_RAW, 'Filter form serialize querystring',),
            ]
        );
    }


    /**
     * Executes the API call to retrieve the report table.
     *
     * @param array $parameters
     * @return array List of courses or users
     */
    public static function execute($parameters) {
        // Validate the incoming parameters according to execute_parameters().
        $param = self::validate_parameters(self::execute_parameters(), [
            'querystring' => $parameters,
        ]);
        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('report/usercoursereports:view', $context);
        // 
        parse_str($param['querystring'], $data);
        $type = $data['type'];
        $filter_form = new filter_form(
            null,
            $data,
            'GET',
            '',
            null,
            true,
            $data
        );
        $formdata   = (array)$filter_form->get_data();
        $formdata   = ['type' => $type] + $formdata;
        $pagepath   = '/report/usercoursereports/index.php';
        $urlparams  = usercoursereports::urlparam($formdata);
        $pageurl    = new \moodle_url($pagepath, $urlparams);
        //  Get the report table.
        $contents = '';
        if ($type == 'course') {
            $contents .= usercoursereports::get_course_info_table($pageurl, $formdata);
        } elseif ($type == 'user') {
            $contents .= usercoursereports::get_user_info_table($pageurl, $formdata);
        } else {
            $contents .= '<div> Please select the type.</div>';
        }
        // Prepare response values.
        return [
            'status' => true,
            'reporttable' => $contents,
            'message' => 'message',
        ];
    }

    /**
     * Returns description of method result value.
     *
     * @return external_multiple_structure
     */
    public static function execute_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_BOOL, 'status'),
            'reporttable' => new external_value(PARAM_RAW, 'Report table with html'),
            'message' => new external_value(PARAM_TEXT, 'Status message'),
        ]);
    }
}
