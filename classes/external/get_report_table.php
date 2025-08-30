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
                'querystring' => new external_value(PARAM_RAW, 'Filter form serialize querystring'),
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
        $filterdata = [
            'status' => true,
            'is_validated' => true,
            'reporttable' => '',
            'message' => '',
        ];
        // Validate the incoming parameters according to execute_parameters().
        $param = self::validate_parameters(self::execute_parameters(), [
            'querystring' => $parameters,
        ]);
        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('report/usercoursereports:view', $context);
        try {
            // ... get query string into variables.
            parse_str($param['querystring'], $data);
            $type = $data['type'];
            // ... check type param
            if (!$type || !in_array($type, ['course', 'user'])) {
                $filterdata['message'] = get_string('invalidtypeparam', 'report_usercoursereports');
                return $filterdata;
            }
            // ... check if the form data or pagination data.
            $formfilterform = $data['_qf__report_usercoursereports_form_filter_form'] ?? 0;
            if ($formfilterform) {
                $filterform = new filter_form(null, $data, 'GET', '', null, true, $data);
                // ... check and get validation message
                $isvalidated = $filterform->is_validated();
                if (!$isvalidated) {
                    $errors = $filterform->validation($data, []);
                    $validationerrors = [];
                    foreach ($errors as $key => $errorvalue) {
                        $validationerrors[] = [
                            'field' => $key,
                            'error' => $errorvalue,
                        ];
                    }
                    $filterdata['status'] = $isvalidated;
                    $filterdata['is_validated'] = $isvalidated;
                    $filterdata['validation_errors'] = $validationerrors;
                } else {
                    // ... get the form data if validation is true
                    $formdata   = (array)$filterform->get_data();
                    $querydata   = ['type' => $type] + $formdata;
                }
            } else {
                $querydata = $data;
            }
            if ($filterdata['status'] && $filterdata['is_validated']) {
                // ... set page url
                $pagepath   = '/report/usercoursereports/index.php';
                $urlparams  = usercoursereports::urlparam($querydata);
                $querydata['pagepath'] = $pagepath;
                $querydata['urlparams'] = $urlparams;
                $pageurl    = new \moodle_url($pagepath, $urlparams);
                $filterdata['pageurl'] = $pageurl->out(false);

                // ... Get the report table.
                if ($type == 'course') {
                    $filterdata['reporttable'] = usercoursereports::get_course_info_table($pageurl, $querydata);
                } else if ($type == 'user') {
                    $filterdata['reporttable'] = usercoursereports::get_user_info_table($pageurl, $querydata);
                }
            }
        } catch (\Throwable $th) {
            $filterdata = [
                'status' => false,
                'is_validated' => false,
                'reporttable' => '',
                'message' => $th->getMessage(),
            ];
        }
        return $filterdata;
    }

    /**
     * Returns description of method result value.
     *
     * @return external_multiple_structure
     */
    public static function execute_returns() {
        return new external_single_structure(
            [
                'status' => new external_value(PARAM_BOOL, 'status'),
                'reporttable' => new external_value(PARAM_RAW, 'Report table with html'),
                'message' => new external_value(PARAM_TEXT, 'Status message'),
                'pageurl' => new external_value(PARAM_TEXT, 'filter page url', VALUE_OPTIONAL),
                'is_validated' => new external_value(PARAM_BOOL, 'filter form validated', VALUE_OPTIONAL),
                'validation_errors' => new external_multiple_structure(
                    new external_single_structure(
                        [
                            'field' => new external_value(PARAM_ALPHANUMEXT, 'Form field name'),
                            'error' => new external_value(PARAM_TEXT, 'Validation error message'),
                        ]
                    ),
                    'Validation errors per field',
                    VALUE_OPTIONAL
                ),
            ]
        );
    }
}
