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
 * Block showing course roster
 *
 * @package   block_roster_tbird
 * @copyright 2013 onwards Johan Reinalda (http://www.thunderbird.edu)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
	$choices = Array();
	// get all the global roles
	$allroles = get_all_roles();
	foreach ($allroles as $role) {
		$choices[$role->id] = '&nbsp;' . $role->name;
	}
	$default = Array();
	$default[5] = 1;	//5 = Student
	//and then allow each role to be selected for showing in the roster reports.
	//note we store the variable globally (not block specific), so we can get to it from view.php
	$settings->add(new admin_setting_configmulticheckbox('block_roster_tbird_rolestoshow', get_string('rolestoshow', 'block_roster_tbird'),
			get_string('rolestoshowdescription', 'block_roster_tbird'), $default, $choices));
	
	$settings->add(new admin_setting_configtext('block_roster_tbird_picsperrow', get_string('picsperrow', 'block_roster_tbird'),
	        get_string('picsperrowdescr', 'block_roster_tbird'),
	        5, PARAM_INT, 5 ));
	
	$settings->add(new admin_setting_configtext('block_roster_tbird_picsize', get_string('picsize', 'block_roster_tbird'),
	        get_string('picsizedescr', 'block_roster_tbird'),
	        200, PARAM_INT, 5 ));
}

