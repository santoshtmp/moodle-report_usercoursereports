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
 * usercoursereports filter form for filter_courseusers
 * 
 * @package   report_usercoursereports
 * @copyright 2025 https://santoshmagar.com.np/
 * @author    santoshtmp7
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_usercoursereports\form;

use moodleform;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');

/**
 * filter form.
 *
 * @package   report_usercoursereports
 * @copyright 2025 https://santoshmagar.com.np/
 * @author    santoshtmp7
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
class filter_courseusers extends \moodleform {

    /**
     * Form definition.
     */
    public function definition() {
        $mform = $this->_form;
        $type = $this->_customdata['type'] ?? '';
        $search = $this->_customdata['search'] ?? '';
        $roleids = $this->_customdata['roleids'] ?? [];
        $perpage = (int)$this->_customdata['perpage'] ?? 50;
        $filterfieldwrapperexpanded = false;

        if ($search || ($perpage && $perpage != 50)) {
            $filterfieldwrapperexpanded = true;
        }
        // ... header
        $mform->addElement('header', 'filterfieldwrapper', get_string($type . 'userfilter', 'report_usercoursereports'));
        $mform->setExpanded('filterfieldwrapper', $filterfieldwrapperexpanded);

        // ... start filter form grid in two column.
        $mform->addElement('html', '<div class="filter-grid">');

        // ... search text.
        $mform->addElement('text', 'search', get_string('search'), ['size' => 50, 'class' => 'usercoursereports-filter-field']);
        $mform->setType('search', PARAM_TEXT);
        $mform->setDefault('search', $search);

        // // User role search..
        // $mform->addElement(
        //     'autocomplete',
        //     'roleids',
        //     get_string('roles'),
        //     \report_usercoursereports\user_data_handler::get_all_roles(0, [-1], [], CONTEXT_COURSE),
        //     [
        //         'multiple' => true,
        //         'noselectionstring' => get_string('allroles', 'report_usercoursereports'),
        //         'class' => 'usercoursereports-filter-field',
        //     ]
        // );
        // $mform->setType('roleids', PARAM_INT);
        // $mform->setDefault('roleids', $roleids);

        // ... Per page number input (max 1000).
        $mform->addElement('text', 'perpage', get_string('perpage', 'report_usercoursereports'), [
            'min' => 1,
            'max' => 1000,
            'size' => 25,
            'class' => 'usercoursereports-filter-field',
        ]);
        $mform->setType('perpage', PARAM_INT);
        $mform->setDefault('perpage', $perpage ?: 50);

        // Close two-column grid.
        $mform->addElement('html', '</div>');

        // ... reset table
        $mform->addElement('hidden', 'treset');
        $mform->setType('treset', PARAM_INT);
        $mform->setDefault('treset', 1);

        // Action btn.
        $buttonarray = [];
        $buttonarray[] = $mform->createElement(
            'submit',
            'applyfilter',
            get_string('applyfilter', 'report_usercoursereports'),
            ['id' => 'applyfilter', 'class' => 'apply-filter form-submit mt-4']
        );
        $buttonarray[] = $mform->createElement(
            'cancel',
            '',
            get_string('clear'),
            ['id' => 'clearfilter', 'class' => 'clear-filter form-submit mt-4']
        );
        $mform->addGroup($buttonarray, 'buttonar', '', [''], false);
    }
}
