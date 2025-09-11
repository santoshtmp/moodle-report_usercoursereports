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

    const filterFormId = 'usercoursereports-filter';
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
        // Make AJAX call.
        const request = {
            methodname: 'report_usercoursereports_get_report_table',
            args: {querystring: formquerystring}
        };
        const ajaxrequest = Ajax.call([request])[0];
        ajaxrequest.done(function(response) {
            // Update report filter table content.
            if (response.status && response.reporttable) {
                window.history.replaceState('', 'url', response.pageurl);
                $('#' + filterAreaId).replaceWith(response.reporttable);
            }
            // Field validation and error.
            $('#' + filterFormId + ' [id^=id_error_]').html('').hide();
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
            $('#error-response-message').remove();
            if (!response.status && response.message) {
                $('#' + filterAreaId).prepend(
                    '<p id="error-response-message" class="invalid-feedback" style="display:block;">' + response.message + '</p>'
                );
            }

        });
        ajaxrequest.fail(function(response) {
            window.console.log(response);
            $('#error-response-message').remove();
            $('#' + filterAreaId).prepend(
                '<p id="error-response-message" class="invalid-feedback" style="display:block;">' + response.message + '</p>'
            );
        });
        ajaxrequest.always(function() {
            $('#' + filterAreaId).removeAttr('aria-busy');
            $('#' + applyFilterBtnId).prop('disabled', false);
            $('#filter-loading-wrapper').hide();
        });
    }

    /**
     * Single report usercoursereports btn toggle.
     */
    function usercoursereportsToggleBtn() {

        // Toggle course detail content.
        $(document).on('click', '.usercoursereports [aria-controls="reportgeneraldetailcontent"]', function() {
            const $icon = $(this).find('.fa');
            if ($(this).attr('aria-expanded') === 'true') {
                $icon.removeClass('fa-chevron-down').addClass('fa-chevron-up');
                $(this).attr('aria-expanded', false);
            } else {
                $icon.removeClass('fa-chevron-up').addClass('fa-chevron-down');
                $(this).attr('aria-expanded', true);
            }
            $('#reportgeneraldetailcontent').toggle('show');
        });

        // Read more / show less for summary.
        if (document.querySelector('.usercoursereports .readmore-btn')) {
            const readmorePromise = str.get_string('readmore', 'report_usercoursereports');
            const showlessPromise = str.get_string('showless', 'report_usercoursereports');
            Promise.all([readmorePromise, showlessPromise]).then(([readmoreText, showlessText]) => {
                document.querySelectorAll(".usercoursereports").forEach(card => {
                    const summary = card.querySelector(".course-summary");
                    const btn = card.querySelector(".readmore-btn");

                    if (!summary || !btn) {
                        return false;
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
            }).catch(err => {
                window.console.error(err);
            });
        }
    }

    /**
     *
     * @param {*} filterFormId
     */
    function formReset(filterFormId) {
        const form = $("#" + filterFormId);
        if (!form.length) {
            return false;
        }

        // Reset the native form.
        form[0].reset();

        // Clear autocomplete selections.
        form.find(".form-autocomplete-selection").html('');

        // Reset all fields inside wrappers.
        form.find(".usercoursereports-filter-field").each(function() {
            var $wrapper = $(this);

            $wrapper.find('input, select, textarea').each(function() {
                var $el = $(this);
                var type = $el.attr('type');

                if ($el.is('select')) {
                    if ($el.find('option[value="0"]').length) {
                        $el.val('0');
                    } else if ($el.find('option[value="all"]').length) {
                        $el.val('all');
                    }
                } else if (type === 'checkbox' || type === 'radio') {
                    $el.prop('checked', false);
                } else if (type === 'number') {
                    $el.val($el.attr('default-value') || 50);
                } else {
                    $el.val('');
                }
            });

            // Trigger change if any UI plugin relies on it.
            $wrapper.find('input, select, textarea').trigger('change');
        });

    }

    return {
        init: function(pagedata) {
            // Remove .col-md-3 and .col-md-9 from divs inside .usercoursereports-filter-field.
            $('.usercoursereports-filter-field div.col-md-3, .usercoursereports-filter-field div.col-md-9').each(function() {
                $(this).removeClass('col-md-3 col-md-9');
            });

            // Change per page field type from text to number.
            if (document.getElementById('id_perpage')) {
                document.getElementById('id_perpage').setAttribute('type', 'number');
            }

            // Handle course summary read more/less button toggle.
            usercoursereportsToggleBtn();

            // Remove name from autocomplete hidden field.
            $('input[value="_qf__force_multiselect_submission"]').each(function() {
                $(this).val('');
                $(this).attr('name', '');
            });

            // Filter on form submit.
            $('#' + filterFormId).on('submit', function(e) {
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
            $(document).on('click', '#' + filterAreaId + ' thead th.header a[data-sortable="1"]', function(e) {
                e.preventDefault();
                const formquerystring = $(this).attr('href').split('?')[1];
                if (formquerystring) {
                    getFilterReportTable(formquerystring);
                }
            });

            // Filter the table on reset link click.
            $(document).on('click', '#' + filterAreaId + ' .resettable a', function(e) {
                e.preventDefault();
                const formquerystring = $(this).attr('href').split('?')[1];
                if (formquerystring) {
                    getFilterReportTable(formquerystring);
                    formReset(filterFormId);
                }
            });

            // Filter the table clear btn click.
            $(document).on('click', '#' + filterFormId + ' #clearfilter', function(e) {
                // const formquerystring = (pagedata.pagereseturl).split('?')[1];
                let formquerystring = '';
                if (pagedata.pagereseturl) {
                    let url = new URL(pagedata.pagereseturl, window.location.origin);
                    formquerystring = url.searchParams.toString();
                }
                if (formquerystring) {
                    getFilterReportTable(formquerystring);
                    formReset(filterFormId);
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    return false;
                }
            });

            // Filter on single select field change.
            $('#usercoursereports-single-search select#id_id').on('change', function() {
                var selectedValue = $(this).val();
                if (selectedValue) {
                    var form = $(this).closest('form');
                    form.attr('data-form-dirty', false);
                    form.submit();
                }
            });

            // On load user single detail load the course.
            if (
                $('.singleuserdetail.my-enrolled-courses #report-usercoursereports-filter-area').length ||
                $('.singlecoursedetails.courseparticipation #report-usercoursereports-filter-area').length
            ) {
                let formquerystring = '';
                if (pagedata.pagereseturl) {
                    let url = new URL(pagedata.pagereseturl, window.location.origin);
                    formquerystring = url.searchParams.toString();
                }
                if (formquerystring) {
                    getFilterReportTable(formquerystring);
                }
            }

        }
    };
});
