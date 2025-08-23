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

define(['jquery', 'core/ajax'], function ($, Ajax) {

    var filter_area_id = 'report-usercoursereports-filter-area';
    var applyfilterbtn_id = 'applyfilter';

    /**
     *
     * @param {*} formquerystring
     */
    function get_filter_report_table(formquerystring) {
        $('#' + filter_area_id).attr('aria-busy', 'true');
        $('#' + applyfilterbtn_id).prop('disabled', true);
        $('#filter-loading-wrapper').show();
        // make ajax call
        const request = {
            methodname: 'report_usercoursereports_get_report_table',
            args: {
                querystring: formquerystring,
            }
        };
        const ajaxrequest = Ajax.call([request])[0];
        ajaxrequest.done(function (response) {
            // update report filter table content
            if (response.status && response.reporttable) {
                window.history.replaceState('', 'url', response.pageurl);
                $('#' + filter_area_id).replaceWith(response.reporttable);
            }
            // field validation and error
            $('#usercoursereports-filter [id^=id_error_]').html('').hide();
            if (!response.is_validated) {
                let validation_errors = response.validation_errors || [];
                validation_errors.forEach(element => {
                    let $errorContainer = $('#id_error_' + element.field);
                    if ($errorContainer.length) {
                        $errorContainer.html(element.error).show();
                    }
                });
            }
            // error message priint when status is false.
            if (!response.status && response.message) {
                $('#error-response-message').remove();
                $('#' + filter_area_id).prepend(
                    '<p id="error-response-message" class="invalid-feedback" style="display:block;">' + response.message + '</p>'
                );
            }
        });
        ajaxrequest.fail(function (response) {
            window.console.log(response);
        });
        ajaxrequest.always(function () {
            $('#' + filter_area_id).removeAttr('aria-busy');
            $('#' + applyfilterbtn_id).prop('disabled', false);
            $('#filter-loading-wrapper').hide();
        });
    }

    return {
        init: function () {

            // Remove .col-md-3 and .col-md-9 from divs inside .usercoursereports-filter-field
            $('.usercoursereports-filter-field div.col-md-3, .usercoursereports-filter-field div.col-md-9').each(function () {
                $(this).removeClass('col-md-3 col-md-9');
            });

            // Change per page field type from text to number
            if (document.getElementById('id_perpage')) {
                document.getElementById('id_perpage').setAttribute('type', 'number');
            }

            // On form submit
            $('#usercoursereports-filter').on('submit', function (e) {
                let clickedButton = $(this).find('input[type=submit]:focus').attr('name');
                if (clickedButton != 'cancel') {
                    e.preventDefault();
                    const formquerystring = $(this).serialize();
                    get_filter_report_table(formquerystring);
                }
            });

            // Pagination.
            $(document).on('click', '#' + filter_area_id + ' nav.pagination a.page-link', function (e) {
                e.preventDefault();
                const formquerystring = $(this).attr('href').split('?')[1];
                if (formquerystring) {
                    get_filter_report_table(formquerystring);
                }
            });

        }
    };
});
