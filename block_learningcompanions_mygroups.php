<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Block learningcompanions_mygroups is defined here.
 *
 * @package     block_learningcompanions_mygroups
 * @copyright   2022 ICON Vernetzte Kommunikation GmbH <info@iconnewmedia.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_learningcompanions_mygroups extends block_base {

    public function init() {
        $this->title = get_string('pluginname', 'block_learningcompanions_mygroups');
    }

    public function has_config() {
        return true;
    }

    public function hide_header() {
        return false;
    }

    function instance_allow_multiple() {
        return false;
    }

    public function applicable_formats() {
        return array(
            'admin' => true,
            'site' => true,
            'course' => true,
            'mod' => true,
            'my' => true
        );
    }

    public function specialization() {
        if (empty($this->config->title)) {
            $this->title = get_string('pluginname', 'block_learningcompanions_mygroups');
        } else {
            $this->title = $this->config->title;
        }
    }

    public function get_content() {
        global $CFG, $OUTPUT, $USER;

        require_once $CFG->dirroot.'/local/learningcompanions/lib.php';

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();

        if (empty($this->instance)) {
            $this->content = '';
            return $this->content;
        }

        $groups = \local_learningcompanions\groups::get_groups_of_user($USER->id);
        $groups = array_values(array_slice($groups, 0, 3));
        foreach($groups as $group) {
            $group->comments_since_last_visit = \local_learningcompanions\groups::count_comments_since_last_visit($group->id);
            $group->has_new_comments = $group->comments_since_last_visit > 0;
            $group->lastcomment = substr(strip_tags($group->get_last_comment()), 0, 100);
        }

        $this->content->text = $OUTPUT->render_from_template('block_learningcompanions_mygroups/main',
            array('groups' => $groups, // ICTODO: Groups should be sorted by last post
                  'allmygroupsurl' => $CFG->wwwroot.'/local/learningcompanions/group/index.php'));

        return $this->content;
    }
}
