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
 * usercoursereports filter form
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
 * singlesearch form.
 *
 * @package   report_usercoursereports
 * @copyright 2025 https://santoshmagar.com.np/
 * @author    santoshtmp7
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
class singlesearch extends \moodleform {

    /**
     * Form definition.
     */
    public function definition() {
        $mform = $this->_form;
        // ... get custom data.
        $type = $this->_customdata['type'] ?? '';

        if ($type === 'course') {
            // ... course search option.
            $mform->addElement(
                'course',
                'id',
                '',
                [
                    'multiple' => false,
                    'noselectionstring' => get_string('allcourses', 'report_usercoursereports'),
                    'class' => 'usercoursereports-filter-field',
                    'placeholder' => get_string('coursesearch', 'report_usercoursereports'),
                ]
            );
        } else if ($type === 'user') {
            // ... user search option.
            $mform->addElement(
                'autocomplete',
                'id',
                '',
                [],
                [
                    'multiple' => false,
                    'class' => 'usercoursereports-filter-field',
                    'ajax' => 'core_user/form_user_selector',
                    'placeholder' => get_string('searchusers', 'report_usercoursereports'),
                ]
            );
        } else {
            // ... default hide option.
            $mform->addElement('hidden', 'id');
        }
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', 0);

        // Add a hidden field called 'type'.
        $mform->addElement('hidden', 'type');
        $mform->setType('type', PARAM_TEXT);
        $mform->setDefault('type', $type);
    }

    /**
     * Custom validation for the form.
     *
     * @param array $data Submitted form data.
     * @param array $files Uploaded files (not used here).
     * @return array Array of errors, empty if no errors.
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        return $errors;
    }

    /**
     * Hook called after definition().
     */
    protected function after_definition() {
        // Remove auto-added hidden elements.
        if ($this->_form->elementExists('sesskey')) {
            $this->_form->removeElement('sesskey');
        }
        foreach ($this->_form->_elements as $key => $element) {
            if (strpos($element->getName(), '_qf__') === 0) {
                unset($this->_form->_elements[$key]);
            }
        }
    }
}
