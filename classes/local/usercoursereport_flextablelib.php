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
 * User course reports handler class.
 *
 * @package   report_usercoursereports
 * @copyright 2025 https://santoshmagar.com.np/
 * @author    santoshtmp7
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_usercoursereports\local;

use html_writer;
use moodle_url;

require_once($CFG->libdir . '/tablelib.php');

/**
 * Class usercoursereport_tablelib
 *
 * @package    report_usercoursereports
 * @copyright  2025 santoshtmp <https://santoshmagar.com.np/>
 * @author     santoshtmp
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
class usercoursereport_flextablelib extends \flexible_table {

    /** @var moodle_url the base url for the table */
    var $reseturl = NULL;

    /**
     * This function is not part of the public api.
     */
    function print_nothing_to_display() {

        // Render the dynamic table header.
        echo $this->get_dynamic_table_html_start();

        // Render button to allow user to reset table preferences.
        echo $this->render_reset_button(true);

        $this->print_initials_bar();

        echo html_writer::tag('p', get_string('nodata_available', 'report_usercoursereports'), ['colspan' => 4]);

        // Render the dynamic table footer.
        echo $this->get_dynamic_table_html_end();
    }

    /**
     * Define the URL to use for the table preferences reset button.
     *
     * @param string|moodle_url $url the url to use
     */
    public function define_reseturl($url) {
        $this->reseturl = new moodle_url($url);
    }

    /**
     * Generate the HTML for the table preferences reset button.
     *
     * @return string HTML fragment, empty string if no need to reset
     */
    protected function render_reset_button($showreset = false) {

        if (!$showreset) {
            if (!$this->can_be_reset()) {
                return '';
            }
        }

        if ($this->reseturl) {
            $url = $this->reseturl->out(false, array($this->request[TABLE_VAR_RESET] => 1));
        } else {
            $url = $this->baseurl->out(false, array($this->request[TABLE_VAR_RESET] => 1));
        }

        $html  = html_writer::start_div('resettable mdl-right');
        $html .= html_writer::link($url, get_string('resettable'), ['role' => 'button']);
        $html .= html_writer::end_div();

        return $html;
    }

}
