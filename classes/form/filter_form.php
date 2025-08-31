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
 * filter form.
 *
 * @package   report_usercoursereports
 * @copyright 2025 https://santoshmagar.com.np/
 * @author    santoshtmp7
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
class filter_form extends \moodleform {

    /**
     * Form definition.
     */
    public function definition() {
        $mform = $this->_form;
        // ... get custom data.
        $type = $this->_customdata['type'] ?? '';
        $search = $this->_customdata['search'] ?? '';
        $courseids = $this->_customdata['courseids'] ?? [];
        $roleids = $this->_customdata['roleids'] ?? [];
        $categoryids = $this->_customdata['categoryids'] ?? [];
        $courseformat = $this->_customdata['courseformat'] ?? '';
        $enrolmethod = $this->_customdata['enrolmethod'] ?? '';
        $coursevisibility = $this->_customdata['coursevisibility'] ?? '';
        $createdfrom = (int)$this->_customdata['createdfrom'] ?? '';
        $createdto = (int)$this->_customdata['createdto'] ?? '';
        $startdatefrom = (int)$this->_customdata['startdatefrom'] ?? '';
        $startdateto = (int)$this->_customdata['startdateto'] ?? '';
        $perpage = (int)$this->_customdata['perpage'] ?? 50;
        $suspended = $this->_customdata['suspended'] ?? '';
        $confirmed = $this->_customdata['confirmed'] ?? '';
        $filterfieldwrapperexpanded = false;
        if (
            ($type == 'course' &&  ($categoryids || $courseformat || $coursevisibility || $enrolmethod ||
                $createdfrom || $createdto || $startdatefrom || $startdateto)) ||
            ($type == 'user' &&  ($courseids || $roleids || $suspended || $confirmed)) ||
            ($search || $perpage != 50)
        ) {
            $filterfieldwrapperexpanded = true;
        }

        // ... header
        $mform->addElement('header', 'filterfieldwrapper', get_string($type . 'filter', 'report_usercoursereports'));
        $mform->setExpanded('filterfieldwrapper', $filterfieldwrapperexpanded);

        // ... start filter form grid in two column.
        $mform->addElement('html', '<div class="filter-grid">');

        // ... search text.
        $mform->addElement('text', 'search', get_string('search'), ['size' => 50, 'class' => 'usercoursereports-filter-field']);
        $mform->setType('search', PARAM_TEXT);
        $mform->setDefault('search', $search);

        // ... user report filter
        if ($type == 'user') {
            // ... course id search.
            $mform->addElement(
                'course',
                'courseids',
                get_string('course'),
                [
                    'multiple' => true,
                    'noselectionstring' => get_string('allcourses', 'report_usercoursereports'),
                    'class' => 'usercoursereports-filter-field',
                ]
            );
            $mform->setType('courseids', PARAM_INT);
            $mform->setDefault('courseids', $courseids);

            // User role search..
            $mform->addElement(
                'autocomplete',
                'roleids',
                get_string('roles'),
                \report_usercoursereports\user_data_handler::get_all_roles(0, [7, 8]),
                [
                    'multiple' => true,
                    'noselectionstring' => get_string('allroles', 'report_usercoursereports'),
                    'class' => 'usercoursereports-filter-field',
                ]
            );
            $mform->setType('roleids', PARAM_INT);
            $mform->setDefault('roleids', $roleids);

            // Suspended account radios.
            $suspendedradio = [];
            $suspendedradio[] = $mform->createElement('radio', 'suspended', '', get_string('any'), 'all');
            $suspendedradio[] = $mform->createElement('radio', 'suspended', '', get_string('yes'), 'yes');
            $suspendedradio[] = $mform->createElement('radio', 'suspended', '', get_string('no'), 'no');
            $suspendedgroup = $mform->createElement(
                'group',
                'suspendedgroup',
                get_string('accountsuspended', 'report_usercoursereports'),
                $suspendedradio,
                ' ',
                false
            );
            $suspendedgroup->setAttributes(['class' => 'accountstatus suspendedgroup']);

            // Confirmed account radios.
            $confirmedradio = [];
            $confirmedradio[] = $mform->createElement('radio', 'confirmed', '', get_string('any'), 'all');
            $confirmedradio[] = $mform->createElement('radio', 'confirmed', '', get_string('yes'), 'yes');
            $confirmedradio[] = $mform->createElement('radio', 'confirmed', '', get_string('no'), 'no');
            $confirmedgroup = $mform->createElement(
                'group',
                'confirmedgroup',
                get_string('accountconfirmed', 'report_usercoursereports'),
                $confirmedradio,
                ' ',
                false
            );
            $confirmedgroup->setAttributes(['class' => 'accountstatus confirmedgroup']);

            // Group suspended and confirmedtogether under "Account status".
            $mform->addGroup(
                [$suspendedgroup, $confirmedgroup],
                'accountstatus',
                get_string('accountstatus', 'report_usercoursereports'),
                '',
                false
            );
            $mform->setType('suspended', PARAM_TEXT);
            $mform->setDefault('suspended', $suspended ?: 'all');
            $mform->setType('confirmed', PARAM_TEXT);
            $mform->setDefault('confirmed', $confirmed ?: 'all');
            $mform->getElement('accountstatus')->setAttributes(['class' => 'usercoursereports-filter-field']);
        }

        // ... course report filter
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
                    'class' => 'usercoursereports-filter-field',
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
            $mform->addElement('select', 'courseformat', get_string('courseformat', 'report_usercoursereports'), $formatoptions, [
                'class' => 'usercoursereports-filter-field',
            ]);
            $mform->setType('courseformat', PARAM_TEXT);
            $mform->setDefault('courseformat', $courseformat);

            // Course visibility dropdown.
            $visibilityoptions = [
                'all'  => get_string('all'),
                'show' => get_string('show'),
                'hide' => get_string('hide'),
            ];
            $mform->addElement(
                'select',
                'coursevisibility',
                get_string('coursevisibility', 'report_usercoursereports'),
                $visibilityoptions,
                [
                    'class' => 'usercoursereports-filter-field',
                ]
            );
            $mform->setType('coursevisibility', PARAM_TEXT);
            $mform->setDefault('coursevisibility', $coursevisibility);

            // ... Start date group.
            $startdategroup = [];
            $startdategroup[] = $mform->createElement('date_selector', 'startdatefrom', '', ['optional' => true]);
            $startdategroup[] = $mform->createElement('date_selector', 'startdateto', '', ['optional' => true]);
            $mform->addGroup(
                $startdategroup,
                'startdategroup',
                get_string('coursestartdatefromto', 'report_usercoursereports'),
                null,
                false
            );
            $mform->setType('startdatefrom', PARAM_INT);
            $mform->setDefault('startdatefrom', $startdatefrom);
            $mform->setType('startdateto', PARAM_INT);
            $mform->setDefault('startdateto', $startdateto);
            $mform->getElement('startdategroup')->setAttributes(['class' => 'usercoursereports-filter-field']);

            // ... created date group.
            $createddategroup = [];
            $createddategroup[] = $mform->createElement('date_selector', 'createdfrom', '', ['optional' => true]);
            $createddategroup[] = $mform->createElement('date_selector', 'createdto', '', ['optional' => true]);
            $mform->addGroup(
                $createddategroup,
                'createddategroup',
                get_string('coursecreateddatefromto', 'report_usercoursereports'),
                null,
                false
            );
            $mform->setType('createdfrom', PARAM_INT);
            $mform->setDefault('createdfrom', $createdfrom);
            $mform->setType('createdto', PARAM_INT);
            $mform->setDefault('createdto', $createdto);
            $mform->getElement('createddategroup')->setAttributes(['class' => 'usercoursereports-filter-field']);

            // ... Enrollment method
            $enroloptions = ['all' => get_string('all')];
            foreach (enrol_get_plugins(true) as $pluginname => $plugin) {
                $enroloptions[$pluginname] = $plugin->get_name();
            }
            // Enrolment method dropdown.
            $mform->addElement(
                'select',
                'enrolmethod',
                get_string('enrolmentmethods', 'report_usercoursereports'),
                $enroloptions,
                ['class' => 'usercoursereports-filter-field']
            );
            $mform->setType('enrolmethod', PARAM_TEXT);
            $mform->setDefault('enrolmethod', $enrolmethod);
        }

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

        // Action btn.
        $buttonarray = [];
        $buttonarray[] = $mform->createElement(
            'submit',
            'applyfilter',
            get_string('applyfilter', 'report_usercoursereports'),
            ['id' => 'applyfilter', 'class' => 'apply-filter form-submit']
        );
        $buttonarray[] = $mform->createElement('cancel', '', get_string('clear'), ['id' => 'clearfilter']);
        $mform->addGroup($buttonarray, 'buttonar', '', [''], false);
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
        // Ensure perpage date is between 1-100.
        if (!empty($data['perpage']) && ($data['perpage'] < 1 || $data['perpage'] > 1000)) {
            $errors['perpage'] = get_string('invalidperpage', 'report_usercoursereports');
        }
        // Ensure created "from" date is not greater than create "to" date.
        if (!empty($data['createdfrom']) && !empty($data['createdto'])) {
            if ($data['createdfrom'] > $data['createdto']) {
                $errors['createdfrom'] = get_string('invalidcreatedfromdate', 'report_usercoursereports');
                $errors['createdto'] = get_string('invalidcreatedtodate', 'report_usercoursereports');
            }
        }
        // Ensure start "from" date is not greater than start "to" date.
        if (!empty($data['startdatefrom']) && !empty($data['startdateto'])) {
            if ($data['startdatefrom'] > $data['startdateto']) {
                $errors['startdatefrom'] = get_string('invalidstartdatefrom', 'report_usercoursereports');
                $errors['startdateto'] = get_string('invalidstartdateto', 'report_usercoursereports');
            }
        }

        return $errors;
    }
}
