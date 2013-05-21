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
 * Form for editing HTML block instances.
 *
 * @package   block_html
 * @copyright 2009 Tim Hunt
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Form for editing HTML block instances.
 *
 * @copyright 2009 Tim Hunt
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_roster_tbird_edit_form extends block_edit_form {
	
    protected function specific_definition($mform) {
        // Section header title according to language file.
        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));
        
        // Specific settings for the roster block
        $mform->addElement('text', 'config_title', get_string('setblocktitle', 'block_roster_tbird'));
        $mform->setDefault('config_title', get_string('blocktitle', 'block_roster_tbird'));
        $mform->setType('config_title', PARAM_MULTILANG);
        
        $mform->addElement('advcheckbox', 'config_flaglinknames', get_string('configflagnames', 'block_roster_tbird'));
        $mform->setDefault('config_flaglinknames',1);
        
        //$mform->addElement('advcheckbox', 'config_flaglinkfull', get_string('configflagfull', 'block_roster_tbird'));
        //$mform->setDefault('config_flaglinkfull',1);
        
        $mform->addElement('advcheckbox', 'config_flaglinkdescription', get_string('configflagdescription', 'block_roster_tbird'));
        $mform->setDefault('config_flaglinkdescription',1);
        
        $mform->addElement('advcheckbox', 'config_flaglinkusermanagement', get_string('configflagusermanagement', 'block_roster_tbird'));
        $mform->setDefault('config_flaglinkusermanagement',1);
        
    }

}
