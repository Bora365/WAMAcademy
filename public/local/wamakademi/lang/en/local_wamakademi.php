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
$string['participantsedit'] = 'Edit';
$string['participantssave'] = 'Save';
$string['participantsediting'] = 'Table is in edit mode';
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
$string['coursenumberauto'] = 'This number is assigned automatically when the training is saved. A deleted training’s number is never used again.';
$string['coursenumbermanual'] = 'The training number cannot be typed. It is assigned automatically when the training is saved.';
$string['coursenumberlocked'] = 'The training number cannot be changed.';
$string['courseimagepreview'] = 'This is how the cover looks on the trainings page. The image is cropped to fill the card.';
$string['menuuploadvideo'] = 'Upload video';
$string['menueditcourse'] = 'Training settings';
$string['menuaddcontent'] = 'Add content';
$string['menuaddassign'] = 'Add assignment';
$string['menuaddquiz'] = 'Add quiz';
$string['menuaddsectionprompt'] = 'Which section should it go in?';
$string['menuaddsectionback'] = 'Back';

$string['uploadtitle'] = 'Upload video';
$string['uploadheading'] = '{$a} — Upload video';
$string['uploadscope'] = 'Saves the MP4 or MOV as a hidden draft students cannot see. Preview it, then publish when ready.';
$string['uploadcourse'] = 'Course';
$string['uploadsection'] = 'Section';
$string['uploadnewsection'] = 'New section name (optional)';
$string['uploadlessonname'] = 'Lesson title';
$string['uploadintro'] = 'Description (optional)';
$string['uploadfile'] = 'Video file (MP4 or MOV)';
$string['uploadonevideo'] = 'Only one video file can be uploaded at a time.';
$string['uploadvideotype'] = 'Only valid MP4 or MOV video files are accepted.';
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
$string['videostab'] = 'Videos';
$string['videostitle'] = 'Videos';
$string['videosempty'] = 'No videos have been added to this training yet';
$string['videospreview'] = 'Preview';
$string['videosmoveup'] = 'Move up';
$string['videosmovedown'] = 'Move down';
$string['videosmoved'] = 'Video order updated.';
$string['videosstatus'] = 'Status';
$string['videosactions'] = 'Actions';
$string['videosorder'] = 'Order';
$string['cardmanagecourse'] = 'Manage training';
$string['cardopencourse'] = 'Open training';
$string['cardcontinue'] = 'Continue';

// Progress and assignment reports.
$string['wamakademi:viewreports'] = 'View training progress and assignment reports';
$string['reporttab'] = 'Progress';
$string['reportcoursetab'] = 'Progress report';
$string['reportcoursetitle'] = '{$a} — Progress report';
$string['reportcoursescope'] = 'Video progress and assignment status for students enrolled in this training only. Hidden videos are excluded.';
$string['reportoverviewtitle'] = 'Training overview';
$string['reportoverviewscope'] = 'Enrolment, start and completion totals for trainings you can report on. There is no department or company breakdown.';
$string['reportoverviewlink'] = 'Full overview';
$string['reportnoperm'] = 'You do not have permission to view this report.';
$string['reporttabparticipants'] = 'Participants';
$string['reporttabassignments'] = 'Assignments';
$string['reportsummary'] = 'Summary';
$string['reportenrolled'] = 'Enrolled';
$string['reportstatus'] = 'Status';
$string['reportstatusall'] = 'All';
$string['reportstatusassigned'] = 'Not started';
$string['reportstatusactive'] = 'In progress';
$string['reportstatuscompleted'] = 'Completed';
$string['reportlessons'] = 'Lessons';
$string['reportlesson'] = 'Lesson';
$string['reportlessonsdone'] = 'Lessons done';
$string['reportprogress'] = 'Progress';
$string['reportparticipant'] = 'Participant';
$string['reportlastactivity'] = 'Last activity';
$string['reportactions'] = 'Actions';
$string['reportviewvideos'] = 'Videos';
$string['reportuserdetail'] = 'User videos';
$string['reportvideostatus'] = 'Video status';
$string['reportwatched'] = 'Watched';
$string['reportsearchuser'] = 'Search user';
$string['reportsearchuserph'] = 'Name or email…';
$string['reportapplyfilters'] = 'Apply';
$string['reportclearfilters'] = 'Clear';
$string['reportexport'] = 'Download CSV';
$string['reportempty'] = 'No participants match these filters.';
$string['reportemptyassign'] = 'No assignment rows in this training, or none match the filters.';
$string['reportemptyoverview'] = 'No trainings are available for reporting.';
$string['reportassignment'] = 'Assignment';
$string['reportdue'] = 'Due date';
$string['reportsubmitted'] = 'Submitted';
$string['reportgrade'] = 'Grade';
$string['reportassignmissing'] = 'Not submitted';
$string['reportassigndraft'] = 'Draft';
$string['reportassignsubmitted'] = 'Submitted';
$string['reportassignlate'] = 'Late';
$string['reportassigngraded'] = 'Graded';
$string['reportcourses'] = 'Trainings';
$string['reportcourse'] = 'Training';
$string['reportcategory'] = 'Category';
$string['reportopencourse'] = 'Details';

// Evaluation reports.
$string['evalreportheading'] = 'Evaluation reports';
$string['evalreporttitle'] = '{$a} — Evaluation reports';
$string['evalreportscope'] = 'Topic/criterion success and grade distribution for this course quiz and assignment. Ungraded attempts or submissions are excluded from the distribution.';
$string['evalreportlink'] = 'Evaluation reports';
$string['evalreportpickactivity'] = 'Choose an activity';
$string['evalreportnoactivities'] = 'No demo quiz or assignment is available in this course.';
$string['evalreporttypequiz'] = 'Quiz';
$string['evalreporttypeassign'] = 'Assignment';
$string['evalreportquiztopics'] = 'Topic mastery';
$string['evalreportquizdist'] = 'Grade distribution';
$string['evalreportassigncriteria'] = 'Criterion mastery';
$string['evalreportassigndist'] = 'Grade distribution';
$string['evalreporttable'] = 'Table';
$string['evalreportchart'] = 'Chart';
$string['evalreportviewtoggle'] = 'View';
$string['evalreportempty'] = 'No graded students yet.';
$string['evalreportlabel'] = 'Item';
$string['evalreportvalue'] = 'Value';
$string['evalreportdetail'] = 'Detail';
$string['evalreportaverage'] = 'Average';
$string['evalreportpass'] = 'Pass rate';
$string['evalreportstatussummary'] = 'Status summary';
$string['evalreportstatusmissing'] = 'No submission';
$string['evalreportstatuspending'] = 'Awaiting grade';
$string['evalreportstatusgraded'] = 'Graded';

