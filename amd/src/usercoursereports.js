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
 * AMD module for User Course Reports filter + AJAX call
 *
 * @copyright  2025 https://santoshmagar.com.np/
 * @author     santoshtmp7
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/notification'], function ($, Ajax, Notification) {
    return {
        init: function () {

            // Remove .col-md-3 and .col-md-9 from divs inside .usercoursereports-filter-field
            $('.usercoursereports-filter-field div.col-md-3, .usercoursereports-filter-field div.col-md-9').each(function () {
                $(this).removeClass('col-md-3 col-md-9');
            });


            // On form submit
            $('#usercoursereports-filter').on('submit', function (e) {
                e.preventDefault();

                // Collect form data into an object
                const formData = $(this).serializeArray();
                const dataObj = {};
                formData.forEach(field => {
                    if (dataObj[field.name]) {
                        if (!Array.isArray(dataObj[field.name])) {
                            dataObj[field.name] = [dataObj[field.name]];
                        }
                        dataObj[field.name].push(field.value);
                    } else {
                        dataObj[field.name] = field.value;
                    }
                });

                window.console.log('Sending params to WS:', dataObj);

                // Call Moodle WebService via core/ajax
                const request = {
                    methodname: 'report_usercoursereports_get_report_table',
                    args: dataObj
                };
                const ajaxrequest = Ajax.call([request])[0];

                ajaxrequest.done(function (response) {
                    if (response && response.reporttable) {
                        $('#report-container').html(response.reporttable);
                    } else {
                        $('#report-container').html('<div class="alert alert-warning">No data found</div>');
                    }
                });
                ajaxrequest.fail(Notification.exception);
                ajaxrequest.always(function () {
                });

            });

        }
    };
});
