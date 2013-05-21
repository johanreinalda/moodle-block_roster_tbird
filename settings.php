<?php

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
}

