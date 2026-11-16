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
 * English strings for local_wamakademi.
 *
 * @package    local_wamakademi
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'WAM Akademi';
$string['adminnav'] = 'Site administration categories';
$string['adminnavtitle'] = 'Site administration';
$string['adminnavopen'] = 'Administration menu';
$string['adminnavclose'] = 'Close administration menu';
$string['adminsearchlabel'] = 'Search administration settings';
$string['adminsearchplaceholder'] = 'Search administration settings…';
$string['adminsearchhint'] = 'Searches every setting on this site, not only the links shown on this page.';
$string['adminsearchsubmit'] = 'Search settings';
$string['adminsearchresults'] = 'Search results';
$string['adminsearchnoresults'] = 'No settings match your search';
$string['adminsearchnoresultshelp'] = 'Check the spelling, try a shorter word, or browse the categories in the administration menu.';
$string['adminsearchclear'] = 'Clear search';
$string['adminhomedesc'] = 'Manage site-wide settings, users, courses, plugins and reports. Pick a category on the left or search for a setting.';
$string['adminbreadcrumb'] = 'Administration';
$string['adminsearchcount'] = '{$a} matching pages';
$string['adminsearchtooshort'] = 'Type at least two characters to search the settings';
$string['courseaddparticipants'] = 'Add participants';
$string['coursesearchlabel'] = 'Search by name or email';
$string['coursesearchplaceholder'] = 'Name, surname or email…';
$string['coursesearchsubmit'] = 'Search';
$string['coursesearchclear'] = 'Clear search';
$string['courseadvancedfilters'] = 'Advanced filters';
$string['courseinitials'] = 'By initial';
$string['courseindexshort'] = 'Course index';
$string['participantsfirstname'] = 'First name';
$string['participantslastname'] = 'Last name';
$string['participantsemail'] = 'Email';
$string['participantsrole'] = 'Role';
$string['participantsgroup'] = 'Group';
$string['participantslastaccess'] = 'Last access';
$string['participantsstatus'] = 'Status';
$string['participantsactions'] = 'Actions';
$string['participantsactionsfor'] = 'Actions: {$a}';
$string['participantseditroles'] = 'Edit role';
$string['participantseditgroups'] = 'Edit group';
$string['participantsenroldetails'] = 'Enrolment details';
$string['participantseditenrol'] = 'Edit enrolment';
$string['participantsunenrol'] = 'Remove course enrolment';
$string['participantstablelabel'] = 'Participants table';
$string['formrequired'] = 'Required';

// User and participant screens (User R1): context naming and scope descriptions.
$string['participantsrolecourse'] = 'Course role';
$string['participantsrolesite'] = 'Front page role';
$string['participantslastaccesscourse'] = 'Last course access';
$string['participantslastaccesssite'] = 'Last site access';
$string['participantstitlecourse'] = '{$a} — Participants';
$string['participantstitlesite'] = 'Site front page — Participants';
$string['participantsscopecourse'] = 'Users enrolled in this course; role, group, status and last access apply to this course only. “Enrol users” enrols an existing account in the course and does not create a new account.';
$string['participantsscopesite'] = 'All user accounts on the site; role, group and last access belong to the site front page context and do not show course enrolments.';
$string['participantsscopesitenew'] = 'To create a new account, use {$a}.';
$string['userslisttitle'] = 'System users';
$string['userslistscope'] = 'All user accounts on the site. Course enrolments, course roles and groups are managed on each course’s Participants screen.';
$string['usersdeleteaccount'] = 'Delete account';
$string['usersnewscope'] = 'Creates a new system account; it does not enrol the account in a course. To enrol, use “Enrol users” on the course’s Participants screen.';
$string['userseditscope'] = 'Edits the system account details. Course roles and enrolments do not change here; manage them on the course’s Participants screen.';
$string['userseditheading'] = 'Edit user — {$a}';

// Content workflow (Content Workflow R1).
$string['wamakademi:managevideos'] = 'Upload, preview and publish course videos';
$string['menucreatecourse'] = 'Create course';
$string['menuuploadvideo'] = 'Upload video';
$string['uploadtitle'] = 'Upload video';
$string['uploadheading'] = '{$a} — Upload video';
$string['uploadscope'] = 'Saves the MP4 as a hidden draft students cannot see. Preview it, then publish when ready.';
$string['uploadcourse'] = 'Course';
$string['uploadsection'] = 'Section';
$string['uploadnewsection'] = 'New section name (optional)';
$string['uploadlessonname'] = 'Lesson title';
$string['uploadintro'] = 'Description (optional)';
$string['uploadfile'] = 'MP4 file';
$string['uploaddraftsave'] = 'Save draft';
$string['uploadcancel'] = 'Cancel';
$string['uploadsubmitting'] = 'Uploading…';
$string['uploaddraftsaved'] = 'Video saved as a hidden draft.';
$string['uploadduplicate'] = 'This submission was already processed; redirected to the existing draft.';
$string['uploadnoperm'] = 'You cannot manage videos in this course.';
$string['uploadnocourses'] = 'No course is available for video upload.';
$string['previewstatusdraft'] = 'Draft';
$string['previewstatuspublished'] = 'Published';
$string['previewstatusrestricted'] = 'Published but closed to students';
$string['previewedit'] = 'Edit';
$string['previewpublish'] = 'Publish';
$string['previewunpublish'] = 'Unpublish';
$string['previewback'] = 'Back to course';
$string['previewopenplayer'] = 'Open in WAM player';
$string['publishsuccess'] = 'Published; eligible students can see it.';
$string['unpublishsuccess'] = 'Unpublished; students cannot see it.';
$string['publishforbidden'] = 'You cannot publish this activity.';
$string['publishinvalid'] = 'Invalid request.';
$string['hiddendrafts'] = 'Hidden drafts';
$string['norestriction'] = '';
$string['restrictioncourse'] = 'Course is hidden';
$string['restrictionsection'] = 'Section is hidden';
$string['restrictionavailability'] = 'Availability restriction: {$a}';
