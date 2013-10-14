This is a block with a modified course roster.
(c) 2013, Thunderbird School of Global Management
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

* add to a course as usual.

OUTPUT:

This block will show a list of options for the roster. The roster will be generated in the main page,
depending on the option clicked.

Names - a list of student names, with their photos, email address, city/town, country and last access
Description - a list of student names, photo, and their profile description.
  This helps faculty with a quick overview of the students and their backgrounds. Useful when assigning
  groups to make sure you get diversity in student experiences.
Filter & Sort -  this links to the regular Moodle built-in participant Filter & Sort functionailty 
     
	
VERSION CHANGES:

2013052000 - Initial version
