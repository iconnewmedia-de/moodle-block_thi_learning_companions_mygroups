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
 * Block thi_learning_companions_mygroups is defined here.
 *
 * @package     block_thi_learning_companions_mygroups
 * @copyright   2022 ICON Vernetzte Kommunikation GmbH <info@iconnewmedia.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_thi_learning_companions_mygroups extends block_base {
    protected static $groupLimit = 3; // only show that many groups upon loading the page
    public function init() {
        $this->title = get_string('pluginname', 'block_thi_learning_companions_mygroups');
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
            $this->title = get_string('pluginname', 'block_thi_learning_companions_mygroups');
        } else {
            $this->title = $this->config->title;
        }
    }

    public function get_content() {
        global $CFG, $OUTPUT, $USER, $COURSE;

        require_once $CFG->dirroot.'/local/thi_learning_companions/lib.php';
        require_once $CFG->dirroot.'/local/thi_learning_companions/classes/mentors.php';

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();

        if (empty($this->instance)) {
            $this->content = '';
            return $this->content;
        }

        $groups = \local_thi_learning_companions\groups::get_groups_of_user($USER->id); // ICTODO: Check why this changes the PAGE context to context_system
        $firstgroups = array_values(array_slice($groups, 0, self::$groupLimit));

        if (count($groups) > self::$groupLimit) {
            $lastgroups = array_values(array_slice($groups, self::$groupLimit));
        } else {
            $lastgroups = [];
        }
        $moregroupsCount = count($lastgroups);
        $hasmoregroups = $moregroupsCount > 0;
        foreach($groups as $group) {
            $group->comments_since_last_visit = \local_thi_learning_companions\groups::count_comments_since_last_visit($group->id);
            $group->has_new_comments = $group->comments_since_last_visit > 0;
            $lastcomment = strip_tags($group->get_last_comment(), '<img>');
            // replace any img tags with an img icon
            $lastcomment = preg_replace('/<img[^>]+>/i', '<i class="fa fa-file-image-o"></i>', $lastcomment);
            $group->lastcomment = $lastcomment;
            if (strlen($lastcomment) > 100) {
                $group->lastcomment = substr($lastcomment, 0, 97).'...';
            }
        }
        $groupmeupURL = $CFG->wwwroot.'/local/thi_learning_companions/group/search.php';
        if ($COURSE->id > 1) {
            $groupmeupURL .= '?courseid=' . (int)$COURSE->id;
        }
        if (has_capability('local/thi_learning_companions:group_manage', context_system::instance())) {
            $mayManageGroups = true;
        } else {
            $mayManageGroups = false;
        }
        $noGroupsHelp = new stdClass();
        $noGroupsHelp->title = get_string('groups_help_title', 'block_thi_learning_companions_mygroups');
        $noGroupsHelp->url = "";
        $noGroupsHelp->linktext = "";
        $noGroupsHelp->text = get_string('groups_help_text', 'block_thi_learning_companions_mygroups');
        $noGroupsHelp->icon = [
            "attributes" => [
                ["name" => "class", "value" => "iconhelp"],
                ["name" => "src", "value" => "../../../pix/help.svg"],
                ["name" => "alt", "value" => "Help icon"]
            ]
        ];

        $qualifiedAsMentor = \local_thi_learning_companions\mentors::is_qualified_as_mentor($USER->id);

        $this->content->text = $OUTPUT->render_from_template('block_thi_learning_companions_mygroups/main',
            [
                'groups' => $firstgroups, // ICTODO: Groups should be sorted by last post
                'moregroups' => $lastgroups,
                'allmygroupsurl' => $CFG->wwwroot.'/local/thi_learning_companions/group/index.php',
                'groupmeupurl' => $groupmeupURL,
                'moregroupscount' => $moregroupsCount,
                'hasmoregroups' => $hasmoregroups,
                'maymanagegroups' => $mayManageGroups,
                'no_groups_help' => $noGroupsHelp,
                'qualifiedasmentor' => $qualifiedAsMentor,
                'cfg' => $CFG
            ]
        );

        return $this->content;
    }
}
