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
        $mform = $this->_form;
        $type = $this->_customdata['type'];
        $search = $this->_customdata['search'];
        $courseids = $this->_customdata['courseids'];
        $categoryids = $this->_customdata['categoryids'];
        $courseformat = $this->_customdata['courseformat'];
        $filterfieldwrapper_expanded = false;

        // ... header
        $mform->addElement('header', 'filterfieldwrapper', get_string($type . 'filter', 'report_usercoursereports'));
        if ($type == 'course' && ($search || $categoryids || $courseformat)) {
            $filterfieldwrapper_expanded = true;
        }
        $mform->setExpanded('filterfieldwrapper', $filterfieldwrapper_expanded);

        // ... start filter form grid in two column.
        $mform->addElement('html', '<div class="filter-grid">');

        // ... search text.
        $mform->addElement('text', 'search', get_string('search'), ['size' => 50, 'class' => 'usercoursereports-filter-field']);
        $mform->setType('search', PARAM_TEXT);
        $mform->setDefault('search', $search);


        if ($type == 'user') {
            // ... course id search.
            $mform->addElement(
                'course',
                'courseids',
                get_string('course'),
                [
                    'multiple' => true,
                    'noselectionstring' => get_string('allcourses', 'report_usercoursereports'),
                    'class' => 'usercoursereports-filter-field'
                ]
            );
            $mform->setType('courseids', PARAM_INT);
            $mform->setDefault('courseids', $courseids);

            // User role search..
            $mform->addElement(
                'autocomplete',
                'roleids',
                get_string('roles'),
                \report_usercoursereports\user_data_handler::get_all_roles(),
                [
                    'multiple' => true,
                    'noselectionstring' => get_string('allroles'),
                    'class' => 'usercoursereports-filter-field'
                ]
            );
            $mform->setType('categoryids', PARAM_INT);
            $mform->setDefault('categoryids', $categoryids);

            // Country filter.
            $countries = get_string_manager()->get_list_of_countries();
            $countryoptions = ['' => get_string('allcountries', 'report_usercoursereports')] + $countries;
            $mform->addElement('select', 'country', get_string('country'), $countryoptions, ['class' => 'usercoursereports-filter-field']);
            $mform->setType('country', PARAM_TEXT);
            $mform->setDefault('country', '');
        }


        if ($type == 'course') {
            // Course category id search.
            $mform->addElement(
                'autocomplete',
                'categoryids',
                get_string('categories'),
                \core_course_category::make_categories_list(),
                [
                    'multiple' => true,
                    'noselectionstring' => get_string('allcategories'),
                    'class' => 'usercoursereports-filter-field'
                ]
            );
            $mform->setType('categoryids', PARAM_INT);
            $mform->setDefault('categoryids', $categoryids);

            // Course format dropdown.
            $formats = \core_component::get_plugin_list('format');
            $formatoptions = ['all' => get_string('all')];
            foreach ($formats as $formatname => $formatpath) {
                $formatoptions[$formatname] = get_string('pluginname', "format_{$formatname}");
            }
            $mform->addElement('select', 'courseformat', get_string('courseformat', 'report_usercoursereports'), $formatoptions, ['class' => 'usercoursereports-filter-field']);
            $mform->setType('courseformat', PARAM_ALPHANUMEXT);
            $mform->setDefault('courseformat', '');

            // Course visibility dropdown.
            $visibilityoptions = [
                'all'  => get_string('all'),
                '1' => get_string('show'),
                '0' => get_string('hide'),
            ];
            $mform->addElement('select', 'coursevisibility', get_string('coursevisibility', 'report_usercoursereports'), $visibilityoptions, ['class' => 'usercoursereports-filter-field']);
            $mform->setType('coursevisibility', PARAM_ALPHANUMEXT);
            $mform->setDefault('coursevisibility', '');

            // Created from date.
            $mform->addElement('date_selector', 'createdfrom', get_string('coursecreatedfrom', 'report_usercoursereports'), [
                'optional' => true,
            ]);
            $mform->setType('createdfrom', PARAM_INT);
            $mform->getElement('createdfrom')->setAttributes(['class' => 'usercoursereports-filter-field']);


            // Created to date.
            $mform->addElement('date_selector', 'createdto', get_string('coursecreatedto', 'report_usercoursereports'), [
                'optional' => true,
            ]);
            $mform->setType('createdto', PARAM_INT);
            $mform->getElement('createdto')->setAttributes(['class' => 'usercoursereports-filter-field']);
        }
        // Close two-column grid
        $mform->addElement('html', '</div>');

        // Action btn.
        $buttonarray = [];
        $buttonarray[] = &$mform->createElement('submit', 'applyfilter', get_string('applyfilter', 'report_usercoursereports'), ['id' => 'applyfilter', 'class' => 'apply-filter form-submit']);
        $buttonarray[] = &$mform->createElement('cancel', '', get_string('clear'), ['id' => 'clearfilter']);
        $mform->addGroup($buttonarray, 'buttonar', '', array(''), false);
    }

    /**
     * Custom validation for the form.
     *
     * @param array $data Submitted form data.
     * @param array $files Uploaded files (not used here).
     * @return array Array of errors, empty if no errors.
     */
    function validation($data, $files) {

        $errors = parent::validation($data, $files);

        // Ensure "from" date is not greater than "to" date.
        if (!empty($data['createdfrom']) && !empty($data['createdto'])) {
            if ($data['createdfrom'] > $data['createdto']) {
                $errors['createdto'] = get_string('invaliddaterange', 'report_usercoursereports');
            }
        }

        return $errors;
    }
}
