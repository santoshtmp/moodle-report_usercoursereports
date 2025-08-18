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
 * AMD module
 *
 * @copyright  2025 https://santoshmagar.com.np/
 * @author     santoshtmp7 https://github.com/santoshtmp/moodle-local_easycustmenu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

define(['jquery'], function ($) {
    return {
        init: function () {
            $(document).ready(function () {

                // Remove .col-md-3 and .col-md-9 from divs inside .usercoursereports-filter-field
                $('.usercoursereports-filter-field div.col-md-3, .usercoursereports-filter-field div.col-md-9').each(function () {
                    $(this).removeClass('col-md-3 col-md-9');
                });

            });
        }
    };
});