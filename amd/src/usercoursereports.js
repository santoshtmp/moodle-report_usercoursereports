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
 * AMD module for User Course Reports filter
 *
 * @copyright  2025 https://santoshmagar.com.np/
 * @author     santoshtmp7
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/ajax', 'core/str'], function($, Ajax, str) {
    'use strict';

    const filterAreaId = 'report-usercoursereports-filter-area';
    const applyFilterBtnId = 'applyfilter';

    /**
     * Fetches and updates the report table via AJAX.
     * @param {string} formquerystring
     */
    function getFilterReportTable(formquerystring) {
        $('#' + filterAreaId).attr('aria-busy', 'true');
        $('#' + applyFilterBtnId).prop('disabled', true);
        $('#filter-loading-wrapper').show();
        // Make AJAX call
        const request = {
            methodname: 'report_usercoursereports_get_report_table',
            args: { querystring: formquerystring }
        };
        const ajaxrequest = Ajax.call([request])[0];
        ajaxrequest.done(function(response) {
            // Update report filter table content
            if (response.status && response.reporttable) {
                window.history.replaceState('', 'url', response.pageurl);
                $('#' + filterAreaId).replaceWith(response.reporttable);
            }
            // Field validation and error
            $('#usercoursereports-filter [id^=id_error_]').html('').hide();
            if (!response.is_validated) {
                const validationErrors = response.validation_errors || [];
                validationErrors.forEach(element => {
                    let errorContainer = $('#id_error_' + element.field);
                    if (errorContainer.length) {
                        errorContainer.html(element.error).show();
                    }
                });
            }
            // Error message show when status is false.
            if (!response.status && response.message) {
                $('#error-response-message').remove();
                $('#' + filterAreaId).prepend(
                    '<p id="error-response-message" class="invalid-feedback" style="display:block;">' + response.message + '</p>'
                );
            }
        });
        ajaxrequest.fail(function(response) {
            window.console.log(response);
        });
        ajaxrequest.always(function() {
            $('#' + filterAreaId).removeAttr('aria-busy');
            $('#' + applyFilterBtnId).prop('disabled', false);
            $('#filter-loading-wrapper').hide();
        });
    }

    /**
     * Check and handle course summary read more/less button toggle
     */
    function checkHandleCourseSummaryToggle() {
        const readmorePromise = str.get_string('readmore', 'report_usercoursereports');
        const showlessPromise = str.get_string('showless', 'report_usercoursereports');

        Promise.all([readmorePromise, showlessPromise]).then(([readmoreText, showlessText]) => {
            document.querySelectorAll(".singlecoursedetails").forEach(card => {
                const summary = card.querySelector(".course-summary");
                const btn = card.querySelector(".readmore-btn");

                if (!summary || !btn) {
                    return;
                }

                if (summary.scrollHeight > summary.clientHeight) {
                    btn.classList.remove("d-none");
                }

                btn.addEventListener("click", () => {
                    summary.classList.toggle("collapsed-summary");
                    btn.textContent = summary.classList.contains("collapsed-summary")
                        ? readmoreText
                        : showlessText;
                });
            });
        });

    }


    return {
        init: function() {
            // Remove .col-md-3 and .col-md-9 from divs inside .usercoursereports-filter-field
            $('.usercoursereports-filter-field div.col-md-3, .usercoursereports-filter-field div.col-md-9').each(function() {
                $(this).removeClass('col-md-3 col-md-9');
            });

            // Change per page field type from text to number
            if (document.getElementById('id_perpage')) {
                document.getElementById('id_perpage').setAttribute('type', 'number');
            }

            // Handle course summary read more/less button toggle
            checkHandleCourseSummaryToggle();

            // Filter on form submit
            $('#usercoursereports-filter').on('submit', function(e) {
                const clickedButton = $(this).find('input[type=submit]:focus').attr('name');
                if (clickedButton !== 'cancel') {
                    e.preventDefault();
                    const formquerystring = $(this).serialize();
                    getFilterReportTable(formquerystring);
                }
            });

            // Filter on Pagination number click.
            $(document).on('click', '#' + filterAreaId + ' nav.pagination a.page-link', function(e) {
                e.preventDefault();
                const formquerystring = $(this).attr('href').split('?')[1];
                if (formquerystring) {
                    getFilterReportTable(formquerystring);
                }
            });

            // Filter on table column header for sorting click.
            $(document).on('click', '#' + filterAreaId + ' thead th.header a.sort-link', function(e) {
                e.preventDefault();
                const formquerystring = $(this).attr('href').split('?')[1];
                if (formquerystring) {
                    getFilterReportTable(formquerystring);
                }
            });

            // Filter on single select field change
            $('#usercoursereports-single-search select#id_id').on('change', function() {
                var selectedValue = $(this).val();
                if (selectedValue) {
                    var form = $(this).closest('form');
                    form.attr('data-form-dirty', false);
                    form.submit();
                }
            });

        }
    };
});
