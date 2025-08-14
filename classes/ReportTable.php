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

namespace report_usercoursereports;

use action_menu;
use html_writer;
use moodle_url;
use pix_icon;
use stdClass;

defined('MOODLE_INTERNAL') || die;

/**
 * class handler to get report table
 *
 * @package    report_usercoursereports
 * @copyright  2025 santoshtmp <https://santoshmagar.com.np/>
 * @author     santoshtmp
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ReportTable {

    /**
     * Get menu items table.
     *
     * @param string $type
     * @return string
     */
    public static function get_menu_items_table($type) {
        global $PAGE, $OUTPUT;

        $menus = easycustmenu_handler::get_menu_items($type, 10);
        $tableid = $type . '-table';
        $PAGE->requires->js_call_amd('local_easycustmenu/menu_items', 'menu_item_reorder', [$tableid]);
        $PAGE->requires->js_call_amd('local_easycustmenu/conformdelete', 'init');

        $child_indentation = $OUTPUT->pix_icon('child_indentation', 'child-indentation', 'local_easycustmenu', ['class' => 'child-icon indentation']);
        $child_arrow = $OUTPUT->pix_icon('child_arrow', 'child-arrow-icon', 'local_easycustmenu', ['class' => 'child-icon child-arrow']);

        $contents = '';
        $contents .= html_writer::start_tag('table', ['id' => $tableid, 'class' => 'generaltable']);
        $contents .= html_writer::start_tag('thead');
        $contents .= html_writer::tag(
            'tr',
            html_writer::tag('th', 'Label') .
                html_writer::tag('th', 'Context') .
                html_writer::tag('th', 'Action')
        );
        $contents .= html_writer::end_tag('thead');

        $contents .= html_writer::start_tag('tbody', ['data-type' => $type, 'data-action' => 'reorder']);
        $contextoptions = easycustmenu_handler::get_menu_context_level();

        foreach ($menus as $menu) {
            // action menu
            $core_renderer = $PAGE->get_renderer('core');
            $action_menu = new action_menu();
            $action_menu->set_kebab_trigger('Action', $core_renderer);
            $action_menu->set_additional_classes('fields-actions');
            $action_url_param = [
                'type' => $type,
                'id' => $menu->id,
                'sesskey' => sesskey()
            ];
            $action_menu->add(new \action_menu_link(
                new moodle_url('', ['action' => 'edit'] + $action_url_param),
                new pix_icon('i/edit', 'edit'),
                get_string('edit', 'local_easycustmenu'),
                false,
                [
                    'data-id' => $menu->id,
                ]
            ));
            $action_menu->add(new \action_menu_link(
                new moodle_url('', ['action' => 'delete'] +  $action_url_param),
                new pix_icon('i/delete', 'delete'),
                get_string('delete', 'local_easycustmenu'),
                false,
                [
                    'class' => 'text-danger delete-action',
                    'data-id' => $menu->id,
                    'data-title' => format_string($menu->menu_label),
                    'data-heading' => get_string('delete_conform_heading', 'local_easycustmenu')
                ]
            ));

            // output the menu item row
            $contents .= html_writer::start_tag(
                'tr',
                [
                    'data-id' => $menu->id,
                    'data-depth' => (int)$menu->depth,
                    'data-parent' => (int)$menu->parent,
                    'data-menu_order' => (int)$menu->menu_order,
                    'data-menu_label' => $menu->menu_label
                ]
            );
            $child_indentation_icon = '';
            if ($menu->depth) {
                for ($i = 0; $i < (int)$menu->depth - 1; $i++) {
                    $child_indentation_icon .= $child_indentation;
                }
                $child_indentation_icon .= $child_arrow;
            }

            $contents .= html_writer::tag(
                'td',
                html_writer::tag(
                    'span',
                    html_writer::tag('span', $child_indentation_icon, ['class' => 'child-icon-wrapper']) .
                        html_writer::tag('i', '', ['class' => 'icon fa fa-arrows-up-down-left-right fa-fw', 'role' => "img"]),
                    ['class' => 'float-start drag-handle', "data-drag-type" => "move"]
                ) .
                    html_writer::tag(
                        'span',
                        format_string($menu->menu_label),
                        ['class' => 'menu-label']
                    )
            );
            $contents .= html_writer::tag(
                'td',
                html_writer::tag(
                    'span',
                    format_string($contextoptions[$menu->context_level]),
                    ['class' => 'menu-context']
                )
            );
            $contents .= html_writer::tag('td', $core_renderer->render($action_menu));
            $contents .= html_writer::end_tag('tr');
        }

        $contents .= html_writer::end_tag('tbody');
        $contents .= html_writer::end_tag('table');
        $contents .= html_writer::tag(
            'button',
            get_string('save_order', 'local_easycustmenu'),
            [
                'id' => 'save_menu_reorder',
                'class' => 'btn btn-primary mt-3',
                'type' => 'button',
                'style' => 'display: none;'
            ]
        );
        $contents .= html_writer::tag(
            'div',
            html_writer::tag('div', $child_indentation, ['id' => 'child_indentation', 'style' => 'display: none;']) .
                html_writer::tag('div', $child_arrow, ['id' => 'child_arrow', 'style' => 'display: none;']),
            [
                'id' => 'depth-reusable-icon',
                'style' => 'display: none;'
            ]
        );

        return $contents;
    }
}
