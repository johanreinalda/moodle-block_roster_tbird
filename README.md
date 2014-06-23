This is a block with a modified course roster.
(c) 2013-2014, Thunderbird School of Global Management
Written by Johan Reinalda,  johan dot reinalda at thunderbird dot edu

DESCRIPTION:
This is a simple HTML Block that creates a course roster page with some more information about students
then provided by the original participant list found in Moodle 2.

NOTE:
This block is tested in Moodle v2.3+ only!

INSTALLATION:
Unzip these files to the appropriate directories under your Moodle install <blocks> folder
(If you get this from github.com, the path should be <html>/blocks/roster_tbird/

Then as Moodle admin, go to the Notifications entry of your Admin block.
The block should be found and added to the list of available block.

USAGE:
* enable the block in Site Admin => Modules => Blocks => Manage Block; click on the closed eye.

* next, configure it. Click on the Settings link behind the block. Go to Site Admin => Modules => Blocks
  Settings:
  -what roles to show as student in the roster page. By default, this is only the "Student' role.
  -for the 'Pictures' display, set the number of pictures per row, and the size of the image. 
  -if you do not want 'Last Accessed' in the Names or Description display, check the box.
  (You can also make the 'Last Accessed' field globally hidden for most users, see
  Site Admin => Users => Permissions => User Policies => Hide user fields )
   
* add to a course as usual.

OUTPUT:

This block will show a list of options for the roster. The roster will be generated in the main page,
depending on the option clicked.

Names - a list of student names, with their photos, email address, city/town, country and last access

Description - a list of student names, photo, and their profile description.
  This helps faculty with a quick overview of the students and their backgrounds. Useful when assigning
  groups to make sure you get diversity in student experiences.

Pictures - a list of profile photos, for an easy cheat sheet for faculty

Filter & Sort -  this links to the regular Moodle built-in participant Filter & Sort functionailty 
     
	
VERSION CHANGES:

2014050100 - v1.2 optional removing of 'Last Accessed' from rosters
2014040300 - v1.1 with bugfixes for relative images in navigation and cell spacing,
             Thanks to Bernhard Harrer.
2014021200 - v1.1 for Moodle 2.3, 2.4 & 2.6
2013052000 - Initial version 1.0, for Moodle 2.3
