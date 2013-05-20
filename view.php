<?php

//  Lists all the users within a given course

    require_once('../../config.php');
    require_once($CFG->libdir.'/tablelib.php');
    require_once($CFG->libdir.'/filelib.php');
    require_once('lib.php');
    
    //during development
    error_reporting(E_ALL);
    ini_set('display_errors','stdout');
    ini_set('display_startup_errors', TRUE);
    
    define('USER_SMALL_CLASS', 20);   // Below this is considered small
    define('USER_LARGE_CLASS', 200);  // Above this is considered large
    define('DEFAULT_PAGE_SIZE', 250);
    define('SHOW_ALL_PAGE_SIZE', 5000);
    
    $page         = optional_param('page', 0, PARAM_INT);                     // which page to show
    $perpage      = optional_param('perpage', DEFAULT_PAGE_SIZE, PARAM_INT);  // how many per page
    $mode         = optional_param('mode', MODE_NAMES , PARAM_INT);                  // use the MODE_ constants
    $accesssince  = optional_param('accesssince',0,PARAM_INT);                // filter by last access. -1 = never
    $search       = optional_param('search','',PARAM_RAW);                    // make sure it is processed with p() or s() when sending to output!
    $roleid       = optional_param('roleid', 0, PARAM_INT);                   // optional roleid, 0 means all enrolled users (or all on the frontpage)

    $contextid    = optional_param('contextid', 0, PARAM_INT);                // one of this or
    $courseid     = optional_param('id', 0, PARAM_INT);                       // this are required
    
    $PAGE->set_url('/blocks/roster_tbird/view.php', array(
            'page' => $page,
            'perpage' => $perpage,
            'mode' => $mode,
            'accesssince' => $accesssince,
            'search' => $search,
            'roleid' => $roleid,
            'contextid' => $contextid,
            'id' => $courseid));

    if ($contextid) {
        $context = get_context_instance_by_id($contextid, MUST_EXIST);
        if ($context->contextlevel != CONTEXT_COURSE) {
            print_error('invalidcontext');
        }
        $course = $DB->get_record('course', array('id'=>$context->instanceid), '*', MUST_EXIST);
    } else {
    	error('Invalid context id');
    }
    // not needed anymore
    //unset($contextid); //needed below
    unset($courseid);

    require_login($course);

    $systemcontext = get_context_instance(CONTEXT_SYSTEM);

    if ($course->id == SITEID) {	//should not happen because block() applicable_formats.
    	error('Roster only works in courses');
    } else {
    	//no block for maximum screen space and prettier printing
        //$PAGE->set_pagelayout('incourse');
        $PAGE->set_pagelayout('base');
        require_capability('moodle/course:viewparticipants', $context);
    }

    //get the roles to show from global config.
    $rolestoshow = $CFG->block_roster_tbird_rolestoshow;
    if($rolestoshow == '') {
    	error('Your administrator needs to set the roles to show in this report!');
    }
    
    $rolenamesurl = new moodle_url("$CFG->wwwroot/blocks/roster_tbird/view.php?contextid=$context->id&sifirst=&silast=");

    $allroles = get_all_roles();
    $roles = get_profile_roles($context);
    $allrolenames = array();
    $rolenames = array(0=>get_string('allparticipants'));

    foreach ($allroles as $role) {
        $allrolenames[$role->id] = strip_tags(role_get_name($role, $context));   // Used in menus etc later on
        if (isset($roles[$role->id])) {
            $rolenames[$role->id] = $allrolenames[$role->id];
        }
    }

    // make sure other roles may not be selected by any means
    if (empty($rolenames[$roleid])) {
        print_error('noparticipants');
    }

    // no roles to display yet?
    if (empty($rolenames)) {
        if (has_capability('moodle/role:assign', $context)) {
            redirect($CFG->wwwroot.'/'.$CFG->admin.'/roles/assign.php?contextid='.$context->id);
        } else {
            print_error('noparticipants');
        }
    }

    add_to_log($course->id, 'user', 'view roster', 'view.php?id='.$course->id, '');

    $countries = get_string_manager()->get_list_of_countries();

    $strnever = get_string('never');

    $datestring = new stdClass();
    $datestring->year  = get_string('year');
    $datestring->years = get_string('years');
    $datestring->day   = get_string('day');
    $datestring->days  = get_string('days');
    $datestring->hour  = get_string('hour');
    $datestring->hours = get_string('hours');
    $datestring->min   = get_string('min');
    $datestring->mins  = get_string('mins');
    $datestring->sec   = get_string('sec');
    $datestring->secs  = get_string('secs');

    $PAGE->set_title("$course->shortname: ".get_string('roster','block_roster_tbird'));
    $PAGE->set_heading($course->fullname);
    $PAGE->set_pagetype('course-view-' . $course->format);
    $PAGE->add_body_class('path-user');                     // So we can style it independently
    $PAGE->set_other_editing_capability('moodle/course:manageactivities');

    echo $OUTPUT->header();

    echo '<div class="userlist">';

    // Should use this variable so that we don't break stuff every time a variable is added or changed.
    $baseurl = new moodle_url('/blocks/roster_tbird/view.php', array(
            'contextid' => $context->id,
            'roleid' => $roleid,
            'id' => $course->id,
            'perpage' => $perpage,
            'accesssince' => $accesssince,
            'search' => s($search)));

/// setting up tags
    $filtertype = 'course';
    $filterselect = $course->id;


/// Get the hidden field list
    if (has_capability('moodle/course:viewhiddenuserfields', $context)) {
        $hiddenfields = array();  // teachers and admins are allowed to see everything
    } else {
        $hiddenfields = array_flip(explode(',', $CFG->hiddenuserfields));
    }

    if (isset($hiddenfields['lastaccess'])) {
        // do not allow access since filtering
        $accesssince = 0;
    }

    if (!isset($hiddenfields['lastaccess'])) {
        // get minimum lastaccess for this course and display a dropbox to filter by lastaccess going back this far.
        $minlastaccess = $DB->get_field_sql('SELECT min(timeaccess)
                                                   FROM {user_lastaccess}
                                                  WHERE courseid = ?
                                                        AND timeaccess != 0', array($course->id));
        $lastaccess0exists = $DB->record_exists('user_lastaccess', array('courseid'=>$course->id, 'timeaccess'=>0));

        $now = usergetmidnight(time());
        $timeaccess = array();
        $baseurl->remove_params('accesssince');

        // makes sense for this to go first.
        $timeoptions[0] = get_string('selectperiod');

        // days
        for ($i = 1; $i < 7; $i++) {
            if (strtotime('-'.$i.' days',$now) >= $minlastaccess) {
                $timeoptions[strtotime('-'.$i.' days',$now)] = get_string('numdays','moodle',$i);
            }
        }
        // weeks
        for ($i = 1; $i < 10; $i++) {
            if (strtotime('-'.$i.' weeks',$now) >= $minlastaccess) {
                $timeoptions[strtotime('-'.$i.' weeks',$now)] = get_string('numweeks','moodle',$i);
            }
        }
        // months
        for ($i = 2; $i < 12; $i++) {
            if (strtotime('-'.$i.' months',$now) >= $minlastaccess) {
                $timeoptions[strtotime('-'.$i.' months',$now)] = get_string('nummonths','moodle',$i);
            }
        }
        // try a year
        if (strtotime('-1 year',$now) >= $minlastaccess) {
            $timeoptions[strtotime('-1 year',$now)] = get_string('lastyear');
        }

        if (!empty($lastaccess0exists)) {
            $timeoptions[-1] = get_string('never');
        }

    }

    //list menu options in a row in table
    $colnum = 0;
    $menuchoices = Array();
	$menuchoices[$colnum] = new html_table_cell();
	$menuchoices[$colnum]->attrutes['class'] = 'center';
	if($mode == MODE_NAMES) {
		$menuchoices[$colnum]->text = get_string('linknames','block_roster_tbird');
	} else {
		$menuchoices[$colnum]->text = '<a title="'.get_string('listnames','block_roster_tbird').'" href=' . $baseurl . '&mode=' . MODE_NAMES . '>' . get_string('linknames','block_roster_tbird').'</a>';
	}
	
	//$colnum++;
	//$menuchoices[$colnum] = new html_table_cell();
	//$menuchoices[$colnum]->attrutes['class'] = 'center';
	//if($mode == MODE_DETAILS) {
	//	$menuchoices[$colnum]->text = get_string('linkdetails','block_roster_tbird');
	//} else {
	//	$menuchoices[$colnum]->text = '<a title="'.get_string('listdetails','block_roster_tbird').'" href=' . $baseurl . '&mode=' . MODE_DETAILS . '>' . get_string('linkdetails','block_roster_tbird').'</a>';
	//}
	
	$colnum++;
	$menuchoices[$colnum] = new html_table_cell();
	$menuchoices[$colnum]->attrutes['class'] = 'center';
	if($mode == MODE_DESCRIPTION) {
		$menuchoices[$colnum]->text = get_string('linkdescription','block_roster_tbird');
	} else {
		$menuchoices[$colnum]->text = '<a title="'.get_string('listdescriptions','block_roster_tbird').'" href=' . $baseurl . '&mode=' . MODE_DESCRIPTION . '>' . get_string('linkdescription','block_roster_tbird').'</a>';
	}
	
	$colnum++;
	$menuchoices[$colnum] = new html_table_cell();
	$menuchoices[$colnum]->attrutes['class'] = 'center';
	$menuchoices[$colnum]->text = '<a title="'.get_string('listofallpeople').'" href=' . $CFG->wwwroot.'/user/index.php?contextid=' . $contextid . '>' . get_string('linkusermanagement','block_roster_tbird').'</a>';
	
	$menutable = new html_table();
	//$menutable->attributes['class'] = 'controls';
	$menustable->cellspacing = 0;
	$menutable->data[] = $menuchoices;
	
	echo html_writer::table($menutable);
	
    /// Define a table showing a list of users in the current role selection

    $tablecolumns = array('userpic', 'fullname');
    $extrafields = get_extra_user_fields($context);
    $tableheaders = array(get_string('userpic'), get_string('fullnameuser'));
    if ($mode === MODE_NAMES) {
        foreach ($extrafields as $field) {
            $tablecolumns[] = $field;
            $tableheaders[] = get_user_field_name($field);
        }
    }
    if ($mode === MODE_NAMES && !isset($hiddenfields['city'])) {
        $tablecolumns[] = 'city';
        $tableheaders[] = get_string('city');
    }
    if ($mode === MODE_NAMES && !isset($hiddenfields['country'])) {
        $tablecolumns[] = 'country';
        $tableheaders[] = get_string('country');
    }
    if (!isset($hiddenfields['lastaccess'])) {
        $tablecolumns[] = 'lastaccess';
        $tableheaders[] = get_string('lastaccess');
    }

    $table = new flexible_table('user-index-participants-'.$course->id);
    $table->define_columns($tablecolumns);
    $table->define_headers($tableheaders);
    $table->define_baseurl($baseurl->out());

    if (!isset($hiddenfields['lastaccess'])) {
        $table->sortable(true, 'lastaccess', SORT_DESC);
    } else {
        $table->sortable(true, 'firstname', SORT_ASC);
    }

    $table->no_sorting('roles');
    //$table->no_sorting('groups');
    //$table->no_sorting('groupings');
    $table->no_sorting('select');

    $table->set_attribute('cellspacing', '0');
    $table->set_attribute('id', 'participants');
    $table->set_attribute('class', 'generaltable generalbox');

    $table->set_control_variables(array(
                TABLE_VAR_SORT    => 'ssort',
                TABLE_VAR_HIDE    => 'shide',
                TABLE_VAR_SHOW    => 'sshow',
                TABLE_VAR_IFIRST  => 'sifirst',
                TABLE_VAR_ILAST   => 'silast',
                TABLE_VAR_PAGE    => 'spage'
                ));
    $table->setup();

    // we are looking for all users with this role assigned in this context or higher
    $contextlist = get_related_contexts_string($context);

    //list($esql, $params) = get_enrolled_sql($context, NULL, $currentgroup, true);
    list($esql, $params) = get_enrolled_sql($context, NULL, NULL, true);
    $joins = array("FROM {user} u");
    $wheres = array();

    $extrasql = get_extra_user_fields_sql($context, 'u', '', array(
            'id', 'username', 'firstname', 'lastname', 'email', 'city', 'country', 'description',
            'picture', 'lang', 'timezone', 'maildisplay', 'imagealt', 'lastaccess'));

    $select = "SELECT u.id, u.username, u.firstname, u.lastname,
                      u.email, u.city, u.country, u.description, u.picture,
                      u.lang, u.timezone, u.maildisplay, u.imagealt,
                      COALESCE(ul.timeaccess, 0) AS lastaccess$extrasql";
    $joins[] = "JOIN ($esql) e ON e.id = u.id"; // course enrolled users only
    $joins[] = "LEFT JOIN {user_lastaccess} ul ON (ul.userid = u.id AND ul.courseid = :courseid)"; // not everybody accessed course yet
    $params['courseid'] = $course->id;
    if ($accesssince) {
        $wheres[] = get_course_lastaccess_sql($accesssince);
    }

    // performance hacks - we preload user contexts together with accounts
    list($ccselect, $ccjoin) = context_instance_preload_sql('u.id', CONTEXT_USER, 'ctx');
    $select .= $ccselect;
    $joins[] = $ccjoin;


    // limit list to users with some role only
    
    //this lists all roles, but teacher, and non-editing teacher
    //if ($roleid) {
        //$wheres[] = "u.id IN (SELECT userid FROM {role_assignments} WHERE roleid = :roleid AND contextid $contextlist)";
        //hardcode roles to avoid:
        //$wheres[] = "u.id IN (SELECT userid FROM {role_assignments} WHERE roleid != 3 and roleid != 4 AND contextid $contextlist)";
    	//use globally set roles to show in report
    	$wheres[] = "u.id IN (SELECT userid FROM {role_assignments} WHERE roleid IN ($rolestoshow) AND contextid $contextlist)";
    	$params['roleid'] = $roleid;
    //}
    //need to force roleid = 0;
    $roleid = 0;

    $from = implode("\n", $joins);
    if ($wheres) {
        $where = "WHERE " . implode(" AND ", $wheres);
    } else {
        $where = "";
    }

    $totalcount = $DB->count_records_sql("SELECT COUNT(u.id) $from $where", $params);

    if (!empty($search)) {
        $fullname = $DB->sql_fullname('u.firstname','u.lastname');
        $wheres[] = "(". $DB->sql_like($fullname, ':search1', false, false) .
                    " OR ". $DB->sql_like('email', ':search2', false, false) .
                    " OR ". $DB->sql_like('idnumber', ':search3', false, false) .") ";
        $params['search1'] = "%$search%";
        $params['search2'] = "%$search%";
        $params['search3'] = "%$search%";
    }

    list($twhere, $tparams) = $table->get_sql_where();
    if ($twhere) {
        $wheres[] = $twhere;
        $params = array_merge($params, $tparams);
    }

    $from = implode("\n", $joins);
    if ($wheres) {
        $where = "WHERE " . implode(" AND ", $wheres);
    } else {
        $where = "";
    }

    if ($table->get_sql_sort()) {
        $sort = ' ORDER BY '.$table->get_sql_sort();
    } else {
        $sort = '';
    }

    $matchcount = $DB->count_records_sql("SELECT COUNT(u.id) $from $where", $params);

    $table->pagesize($perpage, $matchcount);

    // list of users at the current visible page - paging makes it relatively short
    $userlist = $DB->get_recordset_sql("$select $from $where $sort", $params, $table->get_page_start(), $table->get_page_size());

    /// If there are multiple Roles in the course, then show a drop down menu for switching
    if (false and count($rolenames) > 1) {
        echo '<div class="rolesform">';
        echo '<label for="rolesform_jump">'.get_string('currentrole', 'role').'&nbsp;</label>';
        echo $OUTPUT->single_select($rolenamesurl, 'roleid', $rolenames, $roleid, null, 'rolesform');
        echo '</div>';

    } else if (count($rolenames) == 1) {
        // when all users with the same role - print its name
        echo '<div class="rolesform">';
        echo get_string('role').get_string('labelsep', 'langconfig');
        $rolename = reset($rolenames);
        echo $rolename; 
        echo '</div>';
    }

    //header showing number of enrolments
    $strallparticipants = get_string('allparticipants');
    if ($matchcount < $totalcount) {
        echo $OUTPUT->heading($strallparticipants.get_string('labelsep', 'langconfig').$matchcount.'/'.$totalcount, 3);
    } else {
        //echo $OUTPUT->heading($strallparticipants.get_string('labelsep', 'langconfig').$matchcount, 2,'rosterheading');
        echo $OUTPUT->heading($strallparticipants.get_string('labelsep', 'langconfig').$matchcount, 2);
    }


    if ($mode === MODE_DESCRIPTION) {    // Print simple listing
        if ($totalcount < 1) {
            echo $OUTPUT->heading(get_string('nothingtodisplay'));
        } else {
            if ($totalcount > $perpage) {

                $firstinitial = $table->get_initial_first();
                $lastinitial  = $table->get_initial_last();
                $strall = get_string('all');
                $alpha  = explode(',', get_string('alphabet', 'langconfig'));
            }

            if ($matchcount > 0) {
                $usersprinted = array();
                foreach ($userlist as $user) {
                    if (in_array($user->id, $usersprinted)) { /// Prevent duplicates by r.hidden - MDL-13935
                        continue;
                    }
                    $usersprinted[] = $user->id; /// Add new user to the array of users printed

                    context_instance_preload($user);

                    $context = get_context_instance(CONTEXT_COURSE, $course->id);
                    $usercontext = get_context_instance(CONTEXT_USER, $user->id);

                    $countries = get_string_manager()->get_list_of_countries();

                    /// Get the hidden field list
                    if (has_capability('moodle/course:viewhiddenuserfields', $context)) {
                        $hiddenfields = array();
                    } else {
                        $hiddenfields = array_flip(explode(',', $CFG->hiddenuserfields));
                    }
                    $table = new html_table();
                    $table->attributes['class'] = 'userinfobox';
                    //$table->attributes['class'] = 'rosteruserinfobox';
                    
                    $row = new html_table_row();
                    $row->cells[0] = new html_table_cell();
                    $row->cells[0]->attributes['class'] = 'left side';

                    $row->cells[0]->text = $OUTPUT->user_picture($user, array('size' => 100, 'courseid'=>$course->id));
                    $row->cells[1] = new html_table_cell();
                    $row->cells[1]->attributes['class'] = 'content';

                    $row->cells[1]->text = $OUTPUT->container(fullname($user, has_capability('moodle/site:viewfullnames', $context)), 'username');
                    $row->cells[1]->text .= $OUTPUT->container_start('info');

                    if (!empty($user->role)) {
                        $row->cells[1]->text .= get_string('role').get_string('labelsep', 'langconfig').$user->role.'<br />';
                    }
                    if ($user->maildisplay == 1 or ($user->maildisplay == 2 and ($course->id != SITEID) and !isguestuser()) or
                                has_capability('moodle/course:viewhiddenuserfields', $context) or
                                in_array('email', $extrafields)) {
                        $row->cells[1]->text .= get_string('email').get_string('labelsep', 'langconfig').html_writer::link("mailto:$user->email", $user->email) . '<br />';
                    }
                    foreach ($extrafields as $field) {
                        if ($field === 'email') {
                            // Skip email because it was displayed with different
                            // logic above (because this page is intended for
                            // students too)
                            continue;
                        }
                        $row->cells[1]->text .= get_user_field_name($field) .
                                get_string('labelsep', 'langconfig') . s($user->{$field}) . '<br />';
                    }
                    if (($user->city or $user->country) and (!isset($hiddenfields['city']) or !isset($hiddenfields['country']))) {
                        $row->cells[1]->text .= get_string('city').get_string('labelsep', 'langconfig');
                        if ($user->city && !isset($hiddenfields['city'])) {
                            $row->cells[1]->text .= $user->city;
                        }
                        if (!empty($countries[$user->country]) && !isset($hiddenfields['country'])) {
                            if ($user->city && !isset($hiddenfields['city'])) {
                                $row->cells[1]->text .= ', ';
                            }
                            $row->cells[1]->text .= $countries[$user->country];
                        }
                        $row->cells[1]->text .= '<br />';
                    }

                    if (!isset($hiddenfields['lastaccess'])) {
                        if ($user->lastaccess) {
                            $row->cells[1]->text .= get_string('lastaccess').get_string('labelsep', 'langconfig').userdate($user->lastaccess);
                            $row->cells[1]->text .= '&nbsp; ('. format_time(time() - $user->lastaccess, $datestring) .')';
                        } else {
                            $row->cells[1]->text .= get_string('lastaccess').get_string('labelsep', 'langconfig').get_string('never');
                        }
                        $row->cells[1]->text .= '<br />';
                    }
                    
                    //here we add the description
                    if(!empty($user->description)) {
                    	// $row->cells[1]->text .= get_string('description').get_string('labelsep', 'langconfig').'<br />'.$user->description;
                    	$row->cells[1]->text .= '<br />'.$user->description;
                    }// else {
                    	//$row->cells[1]->text .= get_string('descriptionnotset','block_roster_tbird');
                    //}

                    $row->cells[1]->text .= $OUTPUT->container_end();

if(false) {                    
                    $row->cells[2] = new html_table_cell();
                    $row->cells[2]->attributes['class'] = 'links';
                    $row->cells[2]->text = '';

                    $links = array();

                    if ($CFG->bloglevel > 0) {
                        $links[] = html_writer::link(new moodle_url('/blog/index.php?userid='.$user->id), get_string('blogs','blog'));
                    }

                    if (!empty($CFG->enablenotes) and (has_capability('moodle/notes:manage', $context) || has_capability('moodle/notes:view', $context))) {
                        $links[] = html_writer::link(new moodle_url('/notes/index.php?course=' . $course->id. '&user='.$user->id), get_string('notes','notes'));
                    }

                    if (has_capability('moodle/site:viewreports', $context) or has_capability('moodle/user:viewuseractivitiesreport', $usercontext)) {
                        $links[] = html_writer::link(new moodle_url('/course/user.php?id='. $course->id .'&user='. $user->id), get_string('activity'));
                    }

                    if ($USER->id != $user->id && !session_is_loggedinas() && has_capability('moodle/user:loginas', $context) && !is_siteadmin($user->id)) {
                        $links[] = html_writer::link(new moodle_url('/course/loginas.php?id='. $course->id .'&user='. $user->id .'&sesskey='. sesskey()), get_string('loginas'));
                    }

                    $links[] = html_writer::link(new moodle_url('/user/view.php?id='. $user->id .'&course='. $course->id), get_string('fullprofile') . '...');

                    $row->cells[2]->text .= implode('', $links);
} //false

                    $table->data = array($row);
                    echo html_writer::table($table);
                }

            } else {
                echo $OUTPUT->heading(get_string('nothingtodisplay'));
            }
        }

    } else {	//if $mode
        $countrysort = (strpos($sort, 'country') !== false);
        $timeformat = get_string('strftimedate');


        if ($userlist)  {

            $usersprinted = array();
            foreach ($userlist as $user) {
                if (in_array($user->id, $usersprinted)) { /// Prevent duplicates by r.hidden - MDL-13935
                    continue;
                }
                $usersprinted[] = $user->id; /// Add new user to the array of users printed

                context_instance_preload($user);

                if ($user->lastaccess) {
                    $lastaccess = format_time(time() - $user->lastaccess, $datestring);
                } else {
                    $lastaccess = $strnever;
                }

                if (empty($user->country)) {
                    $country = '';

                } else {
                    if($countrysort) {
                        $country = '('.$user->country.') '.$countries[$user->country];
                    }
                    else {
                        $country = $countries[$user->country];
                    }
                }

                $usercontext = get_context_instance(CONTEXT_USER, $user->id);

                if ($piclink = ($USER->id == $user->id || has_capability('moodle/user:viewdetails', $context) || has_capability('moodle/user:viewdetails', $usercontext))) {
                    $profilelink = '<strong><a href="'.$CFG->wwwroot.'/user/view.php?id='.$user->id.'&amp;course='.$course->id.'">'.fullname($user).'</a></strong>';
                } else {
                    $profilelink = '<strong>'.fullname($user).'</strong>';
                }

                $data = array ($OUTPUT->user_picture($user, array('size' => 35, 'courseid'=>$course->id)), $profilelink);

                if ($mode === MODE_NAMES) {
                    foreach ($extrafields as $field) {
                        $data[] = $user->{$field};
                    }
                }
                if ($mode === MODE_NAMES && !isset($hiddenfields['city'])) {
                    $data[] = $user->city;
                }
                if ($mode === MODE_NAMES && !isset($hiddenfields['country'])) {
                    $data[] = $country;
                }
                if (!isset($hiddenfields['lastaccess'])) {
                    $data[] = $lastaccess;
                }

                if (isset($userlist_extra) && isset($userlist_extra[$user->id])) {
                    $ras = $userlist_extra[$user->id]['ra'];
                    $rastring = '';
                    foreach ($ras AS $key=>$ra) {
                        $rolename = $allrolenames[$ra['roleid']] ;
                        if ($ra['ctxlevel'] == CONTEXT_COURSECAT) {
                            $rastring .= $rolename. ' @ ' . '<a href="'.$CFG->wwwroot.'/course/category.php?id='.$ra['ctxinstanceid'].'">'.s($ra['ccname']).'</a>';
                        } elseif ($ra['ctxlevel'] == CONTEXT_SYSTEM) {
                            $rastring .= $rolename. ' - ' . get_string('globalrole','role');
                        } else {
                            $rastring .= $rolename;
                        }
                    }
                    $data[] = $rastring;
                    if ($groupmode != 0) {
                        // htmlescape with s() and implode the array
                        $data[] = implode(', ', array_map('s',$userlist_extra[$user->id]['group']));
                        $data[] = implode(', ', array_map('s', $userlist_extra[$user->id]['gping']));
                    }
                }

                $table->attributes['class'] = 'centered';

                $table->add_data($data);
            }
        }

        $table->print_html();

    }

    if (has_capability('moodle/site:viewparticipants', $context) && $totalcount > ($perpage*3)) {
        echo '<form action="index.php" class="searchform"><div><input type="hidden" name="id" value="'.$course->id.'" />';
        echo '<label for="search">' . get_string('search', 'search') . ' </label>';
        echo '<input type="text" id="search" name="search" value="'.s($search).'" />&nbsp;<input type="submit" value="'.get_string('search').'" /></div></form>'."\n";
    }

    $perpageurl = clone($baseurl);
    $perpageurl->remove_params('perpage');
    if ($perpage == SHOW_ALL_PAGE_SIZE) {
        $perpageurl->param('perpage', DEFAULT_PAGE_SIZE);
        echo $OUTPUT->container(html_writer::link($perpageurl, get_string('showperpage', '', DEFAULT_PAGE_SIZE)), array(), 'showall');

    } else if ($matchcount > 0 && $perpage < $matchcount) {
        $perpageurl->param('perpage', SHOW_ALL_PAGE_SIZE);
        echo $OUTPUT->container(html_writer::link($perpageurl, get_string('showall', '', $matchcount)), array(), 'showall');
    }

    echo '</div>';  // userlist

    echo $OUTPUT->footer();

    if ($userlist) {
        $userlist->close();
    }


function get_course_lastaccess_sql($accesssince='') {
    if (empty($accesssince)) {
        return '';
    }
    if ($accesssince == -1) { // never
        return 'ul.timeaccess = 0';
    } else {
        return 'ul.timeaccess != 0 AND ul.timeaccess < '.$accesssince;
    }
}

function get_user_lastaccess_sql($accesssince='') {
    if (empty($accesssince)) {
        return '';
    }
    if ($accesssince == -1) { // never
        return 'u.lastaccess = 0';
    } else {
        return 'u.lastaccess != 0 AND u.lastaccess < '.$accesssince;
    }
}
