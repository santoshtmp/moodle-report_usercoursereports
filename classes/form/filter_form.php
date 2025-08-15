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
 * @package    report_usercoursereports
 * @copyright  
 */
class filter_form extends \moodleform {

    /**
     * Form definition.
     */
    public function definition() {
        global $DB, $PAGE;

        $mform = $this->_form;
        $type = $this->_customdata['type'];
        $search = $this->_customdata['search'];
        $courseids = $this->_customdata['courseids'];
        $categoryids = $this->_customdata['categoryids'];
        $courseformat = $this->_customdata['courseformat'];
        $filterfieldwrapper_expanded = false;

        // header
        $mform->addElement('header', 'filterfieldwrapper', get_string($type . 'filter', 'report_usercoursereports'));
        if ($type == 'course' && ($search || $categoryids || $courseformat)) {
            $filterfieldwrapper_expanded = true;
        }
        $mform->setExpanded('filterfieldwrapper', $filterfieldwrapper_expanded);

        // search text.
        $mform->addElement('text', 'search', get_string('search'), ['size' => 50]);
        $mform->setType('search', PARAM_TEXT);
        $mform->setDefault('search', $search);


        if ($type == 'user') {
            // course id search
            $mform->addElement(
                'course',
                'courseids',
                get_string('course'),
                [
                    'multiple' => true,
                    'noselectionstring' => get_string('allcourses', 'report_usercoursereports'),
                ]
            );
            $mform->setType('courseids', PARAM_INT);
            $mform->setDefault('courseids', 0);
        }
        // Category id search.
        $mform->addElement(
            'autocomplete',
            'categoryids',
            get_string('categories'),
            \core_course_category::make_categories_list(),
            [
                'multiple' => true,
                'noselectionstring' => get_string('allcategories'),
            ]
        );
        $mform->setType('categoryids', PARAM_INT);
        $mform->setDefault('categoryids', 0);

        // Course format dropdown.
        if ($type == 'course') {
            $formats = \core_component::get_plugin_list('format');
            $formatoptions = ['' => get_string('all')];
            foreach ($formats as $formatname => $formatpath) {
                $formatoptions[$formatname] = get_string('pluginname', "format_{$formatname}");
            }
            $mform->addElement('select', 'courseformat', get_string('courseformat', 'report_usercoursereports'), $formatoptions);
            $mform->setType('courseformat', PARAM_ALPHANUMEXT);
            $mform->setDefault('courseformat', '');
        }

        // id
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', 0);

        // Set form ID correctly
        $mform->updateAttributes([
            'id' => ($type) ? $type . '-usercoursereports-filter' : 'usercoursereports-filter',
            'class' => 'mform report-usercoursereports-filter pt-3 pb-3'
        ]);
        // //
        // $buttonarray = [];
        // $buttonarray[] = $mform->createElement('button', 'filterbutton', get_string('applyfilter', 'report_usercoursereports'));
        // $buttonarray[] = $mform->createElement('button', 'clearbutton', get_string('clear'));
        // $mform->addGroup($buttonarray, 'filterbuttons', '', array(' '), false);

        $this->add_action_buttons();
    }

    /**
     * Custom validation for the form.
     *
     * @param array $data Submitted form data.
     * @param array $files Uploaded files (not used here).
     * @return array Array of errors, empty if no errors.
     */
    function validation($data, $files) {
        global $CFG, $DB;

        $errors = parent::validation($data, $files);

        return $errors;
    }
}
