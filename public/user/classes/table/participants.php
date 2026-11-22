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
 * Contains the class used for the displaying the participants table.
 *
 * @package    core_user
 * @copyright  2017 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
declare(strict_types=1);

namespace core_user\table;

use DateTime;
use context;
use core_table\dynamic as dynamic_table;
use core_table\local\filter\filterset;
use core_user\output\status_field;
use core_user\table\participants_search;
use moodle_url;

defined('MOODLE_INTERNAL') || die;

global $CFG;

require_once($CFG->libdir . '/tablelib.php');
require_once($CFG->dirroot . '/user/lib.php');

/**
 * Class for the displaying the participants table.
 *
 * @package    core_user
 * @copyright  2017 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class participants extends \table_sql implements dynamic_table {

    /**
     * @var int $courseid The course id
     */
    protected $courseid;

    /**
     * @var string[] The list of countries.
     */
    protected $countries;

    /**
     * @var \stdClass[] The list of groups with membership info for the course.
     */
    protected $groups;

    /**
     * @var string[] Extra fields to display.
     */
    protected $extrafields;

    /**
     * @var \stdClass $course The course details.
     */
    protected $course;

    /**
     * @var  context $context The course context.
     */
    protected $context;

    /**
     * @var \stdClass[] List of roles indexed by roleid.
     */
    protected $allroles;

    /**
     * @var \stdClass[] List of roles indexed by roleid.
     */
    protected $allroleassignments;

    /**
     * @var \stdClass[] Assignable roles in this course.
     */
    protected $assignableroles;

    /**
     * @var \stdClass[] Profile roles in this course.
     */
    protected $profileroles;

    /**
     * @var filterset Filterset describing which participants to include.
     */
    protected $filterset;

    /** @var \stdClass[] $viewableroles */
    private $viewableroles;

    /** @var moodle_url $baseurl The base URL for the report. */
    public $baseurl;

    /**
     * WAM: per-user state collected while the row cells are built, used by the single "Actions" menu.
     *
     * @var array[] Indexed by user id: ['roles' => bool, 'groups' => bool, 'enrolments' => array].
     */
    protected $wamrowstate = [];

    /**
     * WAM: Get a plugin string with a core fallback when the plugin is not installed.
     *
     * @param string $identifier String identifier in local_wamakademi.
     * @param string $fallback Text used when the string is unavailable.
     * @param mixed $a Optional string parameter.
     * @return string
     */
    protected function wam_string(string $identifier, string $fallback, $a = null): string {
        $manager = get_string_manager();
        if ($manager->string_exists($identifier, 'local_wamakademi')) {
            return get_string($identifier, 'local_wamakademi', $a);
        }
        return $fallback;
    }

    /**
     * Render the participants table.
     *
     * @param int $pagesize Size of page for paginated displayed table.
     * @param bool $useinitialsbar Whether to use the initials bar which will only be used if there is a fullname column defined.
     * @param string $downloadhelpbutton
     */
    public function out($pagesize, $useinitialsbar, $downloadhelpbutton = '') {
        global $CFG, $OUTPUT, $PAGE;

        // Define the headers and columns.
        $headers = [];
        $columns = [];

        // At the very least, the user viewing this table will be able to use bulk actions to export it, so add 'select' column.
        $togglercheckbox = new \core\output\checkbox_toggleall('participants-table', true, [
            'id' => 'select-all-participants',
            'name' => 'select-all-participants',
            'label' => get_string('selectall'),
            'labelclasses' => 'visually-hidden',
            'classes' => 'm-1',
            'checked' => false,
        ]);
        $headers[] = $OUTPUT->render($togglercheckbox);
        $columns[] = 'select';

        $headers[] = get_string('fullname');
        $columns[] = 'fullname';

        $extrafields = \core_user\fields::get_identity_fields($this->context);
        foreach ($extrafields as $field) {
            $displayname = \core_user\fields::get_display_name($field);
            // WAM: short column titles ("E-posta" instead of "E-posta adresi").
            $headers[] = $field === 'email' ? $this->wam_string('participantsemail', $displayname) : $displayname;
            $columns[] = $field;
        }

        // WAM: role, group, status and last access belong to the current context (course or site front page);
        // the role title says which one, because "Role" alone is ambiguous next to system roles.
        $issite = ($this->courseid == SITEID);
        $headers[] = $issite
            ? $this->wam_string('participantsrolesite', get_string('roles'))
            : $this->wam_string('participantsrolecourse', get_string('roles'));
        $columns[] = 'roles';

        // Get the list of fields we have to hide.
        $hiddenfields = array();
        if (!has_capability('moodle/course:viewhiddenuserfields', $this->context)) {
            $hiddenfields = array_flip(explode(',', $CFG->hiddenuserfields));
        }

        // Add column for groups if the user can view them.
        $canseegroups = !isset($hiddenfields['groups']);
        if ($canseegroups) {
            $headers[] = $this->wam_string('participantsgroup', get_string('groups'));
            $columns[] = 'groups';
        }

        // Do not show the columns if it exists in the hiddenfields array.
        if (!isset($hiddenfields['lastaccess'])) {
            if ($this->courseid == SITEID) {
                $headers[] = $this->wam_string('participantslastaccesssite', get_string('lastsiteaccess'));
            } else {
                $headers[] = $this->wam_string('participantslastaccesscourse', get_string('lastcourseaccess'));
            }
            $columns[] = 'lastaccess';
        }

        $canreviewenrol = has_capability('moodle/course:enrolreview', $this->context);
        if ($canreviewenrol && $this->courseid != SITEID) {
            $columns[] = 'status';
            $headers[] = $this->wam_string('participantsstatus', get_string('participationstatus', 'enrol'));
            $this->no_sorting('status');
        };

        // WAM: one "Actions" menu per row (role, group and enrolment actions) instead of inline icons.
        $wamshowactions = ($canreviewenrol && $this->courseid != SITEID)
            || has_capability('moodle/role:assign', $this->context)
            || has_capability('moodle/course:managegroups', $this->context);
        if ($wamshowactions) {
            $columns[] = 'wamactions';
            $headers[] = $this->wam_string('participantsactions', get_string('actions'));
            $this->no_sorting('wamactions');
        }

        $this->define_columns($columns);
        $this->define_headers($headers);

        // WAM: stable column classes used for the table layout (css/wam-course.css).
        $this->column_class('fullname', 'wam-pc-name');
        $this->column_class('email', 'wam-pc-email');
        $this->column_class('roles', 'wam-pc-role');
        $this->column_class('groups', 'wam-pc-group');
        $this->column_class('lastaccess', 'wam-pc-lastaccess');
        $this->column_class('status', 'wam-pc-status');
        $this->column_class('wamactions', 'wam-pc-actions');

        // WAM: no per-column hide/show controls in the header; they only added empty link marks.
        $this->collapsible(false);

        // The name column is a header.
        $this->define_header_column('fullname');

        // Make this table sorted by last name by default.
        $this->sortable(true, 'lastname');

        $this->no_sorting('select');
        $this->no_sorting('roles');
        if ($canseegroups) {
            $this->no_sorting('groups');
        }

        $this->set_default_per_page(20);

        $this->set_attribute('id', 'participants');

        $this->countries = get_string_manager()->get_list_of_countries(true);
        $this->extrafields = $extrafields;
        if ($canseegroups) {
            $this->groups = groups_get_all_groups($this->courseid, 0, 0, 'g.*', true);
        }

        // If user has capability to review enrol, show them both role names.
        $allrolesnamedisplay = ($canreviewenrol ? ROLENAME_BOTH : ROLENAME_ALIAS);
        $this->allroles = role_fix_names(get_all_roles($this->context), $this->context, $allrolesnamedisplay);
        $this->assignableroles = get_assignable_roles($this->context, ROLENAME_BOTH, false);
        $this->profileroles = get_profile_roles($this->context);
        $this->viewableroles = get_viewable_roles($this->context);

        parent::out($pagesize, $useinitialsbar, $downloadhelpbutton);

        if (has_capability('moodle/course:enrolreview', $this->context)) {
            $params = [
                'contextid' => $this->context->id,
                'uniqueid' => $this->uniqueid,
            ];
            $PAGE->requires->js_call_amd('core_user/status_field', 'init', [$params]);
        }
    }

    /**
     * Generate the select column.
     *
     * @param \stdClass $data
     * @return string
     */
    public function col_select($data) {
        global $OUTPUT;

        $checkbox = new \core\output\checkbox_toggleall('participants-table', false, [
            'classes' => 'usercheckbox m-1',
            'id' => 'user' . $data->id,
            'name' => 'user' . $data->id,
            'checked' => false,
            'label' => get_string('selectitem', 'moodle', fullname($data)),
            'labelclasses' => 'accesshide',
        ]);

        return $OUTPUT->render($checkbox);
    }

    /**
     * Generate the fullname column.
     *
     * @param \stdClass $data
     * @return string
     */
    public function col_fullname($data) {
        global $OUTPUT;

        return $OUTPUT->render(\core_user::get_profile_picture(
            $data,
            null,
            [
                'courseid' => $this->course->id,
                'includefullname' => true,
                'showsuspended' => true,
            ]
        ));
    }

    /**
     * User roles column.
     *
     * @param \stdClass $data
     * @return string
     */
    public function col_roles($data) {
        global $OUTPUT;

        $roles = isset($this->allroleassignments[$data->id]) ? $this->allroleassignments[$data->id] : [];
        $editable = new \core_user\output\user_roles_editable($this->course,
                                                              $this->context,
                                                              $data,
                                                              $this->allroles,
                                                              $this->assignableroles,
                                                              $this->profileroles,
                                                              $roles,
                                                              $this->viewableroles);

        $exported = $editable->export_for_template($OUTPUT);
        // WAM: remember whether the roles can be edited; the "Actions" menu offers the edit item.
        $this->wamrowstate[$data->id]['roles'] = !empty($exported['component']);

        return $OUTPUT->render_from_template('core/inplace_editable', $exported);
    }

    /**
     * Generate the groups column.
     *
     * @param \stdClass $data
     * @return string
     */
    public function col_groups($data) {
        global $OUTPUT;

        $usergroups = [];
        foreach ($this->groups as $coursegroup) {
            if (isset($coursegroup->members[$data->id])) {
                $usergroups[] = $coursegroup->id;
            }
        }
        $editable = new \core_group\output\user_groups_editable($this->course, $this->context, $data, $this->groups, $usergroups);
        $exported = $editable->export_for_template($OUTPUT);
        // WAM: remember whether the groups can be edited; the "Actions" menu offers the edit item.
        $this->wamrowstate[$data->id]['groups'] = !empty($exported['component']);

        return $OUTPUT->render_from_template('core/inplace_editable', $exported);
    }

    /**
     * Generate the country column.
     *
     * @param \stdClass $data
     * @return string
     */
    public function col_country($data) {
        if (!empty($this->countries[$data->country])) {
            return $this->countries[$data->country];
        }
        return '';
    }

    /**
     * Generate the last access column.
     *
     * @param \stdClass $data
     * @return string
     */
    public function col_lastaccess($data) {
        if ($data->lastaccess) {
            return format_time(time() - $data->lastaccess);
        }

        return get_string('never');
    }

    /**
     * Generate the status column.
     *
     * @param \stdClass $data The data object.
     * @return string
     */
    public function col_status($data) {
        global $CFG, $OUTPUT, $PAGE;

        $enrolstatusoutput = '';
        $canreviewenrol = has_capability('moodle/course:enrolreview', $this->context);
        if ($canreviewenrol) {
            $canviewfullnames = has_capability('moodle/site:viewfullnames', $this->context);
            $fullname = htmlspecialchars(fullname($data, $canviewfullnames), ENT_QUOTES, 'utf-8');
            $coursename = format_string($this->course->fullname, true, array('context' => $this->context));
            require_once($CFG->dirroot . '/enrol/locallib.php');
            $manager = new \course_enrolment_manager($PAGE, $this->course);
            $userenrolments = $manager->get_user_enrolments($data->id);
            foreach ($userenrolments as $ue) {
                $timestart = $ue->timestart;
                $timeend = $ue->timeend;
                $timeenrolled = $ue->timecreated;
                $actions = $ue->enrolmentplugin->get_user_enrolment_actions($manager, $ue);
                $instancename = $ue->enrolmentinstancename;

                // Default status field label and value.
                $status = get_string('participationactive', 'enrol');
                $statusval = status_field::STATUS_ACTIVE;
                switch ($ue->status) {
                    case ENROL_USER_ACTIVE:
                        $currentdate = new DateTime();
                        $now = $currentdate->getTimestamp();
                        $isexpired = $timestart > $now || ($timeend > 0 && $timeend < $now);
                        $enrolmentdisabled = $ue->enrolmentinstance->status == ENROL_INSTANCE_DISABLED;
                        // If user enrolment status has not yet started/already ended or the enrolment instance is disabled.
                        if ($isexpired || $enrolmentdisabled) {
                            $status = get_string('participationnotcurrent', 'enrol');
                            $statusval = status_field::STATUS_NOT_CURRENT;
                        }
                        break;
                    case ENROL_USER_SUSPENDED:
                        $status = get_string('participationsuspended', 'enrol');
                        $statusval = status_field::STATUS_SUSPENDED;
                        break;
                }

                $statusfield = new status_field($instancename, $coursename, $fullname, $status, $timestart, $timeend,
                    $actions, $timeenrolled);
                $statusfielddata = $statusfield->set_status($statusval)->export_for_template($OUTPUT);

                // WAM: the status cell only shows the status label; the enrolment actions
                // (details, edit, remove) live in the row's single "Actions" menu.
                $this->wamrowstate[$data->id]['enrolments'][] = $statusfielddata;
                $badgeclass = 'bg-success text-white';
                if (!empty($statusfielddata->suspended)) {
                    $badgeclass = 'bg-warning text-dark';
                } else if (!empty($statusfielddata->notcurrent)) {
                    $badgeclass = 'bg-secondary text-dark';
                }
                $enrolstatusoutput .= \html_writer::span(s($status), 'badge ' . $badgeclass);
            }
        }
        return $enrolstatusoutput;
    }

    /**
     * WAM: Generate the single "Actions" menu of a row.
     *
     * Items depend on the current permissions: role and group editing are only offered when the inline
     * editors exist for this row, enrolment items come from the enrolment plugins (same actions as before).
     *
     * @param \stdClass $data
     * @return string
     */
    public function col_wamactions($data) {
        global $OUTPUT;

        $state = $this->wamrowstate[$data->id] ?? [];
        // Rol/grup düzenleme üstteki master Düzenle/Kaydet ile yapılır; satır menüsünde yalnız kayıt işlemleri kalır.
        $caneditroles = false;
        $caneditgroups = false;

        $enrolments = [];
        foreach (($state['enrolments'] ?? []) as $field) {
            $actions = [];
            foreach ($field->enrolactions as $enrolaction) {
                $attributes = [];
                $class = '';
                $label = '';
                foreach ($enrolaction->attributes as $attribute) {
                    if ($attribute->name === 'class') {
                        $class = $attribute->value;
                    } else if ($attribute->name === 'title') {
                        $label = $attribute->value;
                    } else {
                        $attributes[] = $attribute;
                    }
                }
                foreach ($attributes as $attribute) {
                    if ($attribute->name === 'data-action' && $attribute->value === 'editenrolment') {
                        $label = $this->wam_string('participantseditenrol', $label);
                    } else if ($attribute->name === 'data-action' && $attribute->value === 'unenrol') {
                        $label = $this->wam_string('participantsunenrol', $label);
                    }
                }
                $actions[] = [
                    'url' => $enrolaction->url,
                    'label' => $label,
                    'class' => $class,
                    'attributes' => $attributes,
                ];
            }
            $enrolments[] = [
                'fullname' => $field->fullname,
                'coursename' => $field->coursename,
                'enrolinstancename' => $field->enrolinstancename,
                'status' => $field->status,
                'timestart' => $field->timestart ?? '',
                'timeend' => $field->timeend ?? '',
                'timeenrolled' => $field->timeenrolled ?? '',
                'multiple' => count($state['enrolments']) > 1,
                'actions' => $actions,
            ];
        }

        if (!$caneditroles && !$caneditgroups && empty($enrolments)) {
            return '';
        }

        $canviewfullnames = has_capability('moodle/site:viewfullnames', $this->context);
        $menuid = 'wam-rowmenu-' . $this->uniqueid . '-' . $data->id;

        return $OUTPUT->render_from_template('local_wamakademi/participants_actions', [
            'triggerid' => $menuid . '-btn',
            'menuid' => $menuid,
            'label' => $this->wam_string('participantsactions', get_string('actions')),
            'triggerlabel' => $this->wam_string(
                'participantsactionsfor',
                get_string('actions'),
                fullname($data, $canviewfullnames)
            ),
            'caneditroles' => $caneditroles,
            'caneditgroups' => $caneditgroups,
            'editroleslabel' => $this->wam_string('participantseditroles', get_string('edit')),
            'editgroupslabel' => $this->wam_string('participantseditgroups', get_string('edit')),
            'detailslabel' => $this->wam_string('participantsenroldetails', get_string('details')),
            'enrolments' => $enrolments,
        ]);
    }

    /**
     * WAM: Sort links of the full name column are labelled "Ad" and "Soyad" (the sort icon stays next to the link).
     *
     * @param string $text The link text.
     * @param string $column The sort column.
     * @param bool $isprimary Whether the column is the primary sort column.
     * @param int $order SORT_ASC or SORT_DESC.
     * @return string
     */
    protected function sort_link($text, $column, $isprimary, $order) {
        if ($column === 'firstname') {
            $text = $this->wam_string('participantsfirstname', $text);
        } else if ($column === 'lastname') {
            $text = $this->wam_string('participantslastname', $text);
        }
        return parent::sort_link($text, $column, $isprimary, $order);
    }

    /**
     * WAM: Print the headers, then tidy them: one line per header ("Ad Soyad" without the slash separator),
     * no empty command containers and aria-sort on the sorted column.
     */
    public function print_headers() {
        $primary = $this->get_primary_sort_order();

        ob_start();
        parent::print_headers();
        $html = ob_get_clean();

        // "Ad / Soyad" becomes "Ad Soyad": both words stay separate sort links.
        $html = preg_replace('~(</a>|</i>)\s+/\s+(<a )~', '$1 $2', $html);
        // Empty containers left by the removed hide/show controls.
        $html = str_replace('<div class="commands"></div>', '', $html);

        // Expose the sort direction on the sorted column header.
        $sortby = $primary['sortby'] ?? '';
        $column = in_array($sortby, ['firstname', 'lastname'], true) ? 'fullname' : $sortby;
        if ($column && isset($this->columns[$column]) && $this->is_sortable($column)) {
            $index = $this->columns[$column];
            $direction = ($primary['sortorder'] ?? SORT_ASC) == SORT_ASC ? 'ascending' : 'descending';
            $html = preg_replace(
                '~(<th class="header c' . (int)$index . '[ "][^>]*?)(>)~',
                '$1 aria-sort="' . $direction . '"$2',
                $html,
                1
            );
        }

        echo $html;
    }

    /**
     * WAM: The scrollable table region is focusable and named, so keyboard users can scroll it horizontally.
     */
    public function start_html() {
        ob_start();
        parent::start_html();
        $html = ob_get_clean();

        $label = s($this->wam_string('participantstablelabel', get_string('participants')));
        echo str_replace(
            '<div class="table-responsive">',
            '<div class="table-responsive" role="region" tabindex="0" aria-label="' . $label . '">',
            $html
        );
    }

    /**
     * This function is used for the extra user fields.
     *
     * These are being dynamically added to the table so there are no functions 'col_<userfieldname>' as
     * the list has the potential to increase in the future and we don't want to have to remember to add
     * a new method to this class. We also don't want to pollute this class with unnecessary methods.
     *
     * @param string $colname The column name
     * @param \stdClass $data
     * @return string
     */
    public function other_cols($colname, $data) {
        // Do not process if it is not a part of the extra fields.
        if (!in_array($colname, $this->extrafields)) {
            return '';
        }

        return s($data->{$colname});
    }

    /**
     * Query the database for results to display in the table.
     *
     * @param int $pagesize size of page for paginated displayed table.
     * @param bool $useinitialsbar do you want to use the initials bar.
     */
    public function query_db($pagesize, $useinitialsbar = true) {
        global $DB;

        list($twhere, $tparams) = $this->get_sql_where();
        $psearch = new participants_search($this->course, $this->context, $this->filterset);

        $sort = $this->get_sql_sort();

        $this->use_pages = true;
        $rawdata = $psearch->get_participants($twhere, $tparams, $sort, $this->get_page_start(), $this->get_page_size());
        $total = $rawdata->current()->fullcount ?? 0;
        $this->pagesize($pagesize, $total);

        $this->rawdata = [];
        foreach ($rawdata as $user) {
            $this->rawdata[$user->id] = $user;
        }
        $rawdata->close();

        if ($this->rawdata) {
            $this->allroleassignments = get_users_roles($this->context, array_keys($this->rawdata),
                    true, 'c.contextlevel DESC, r.sortorder ASC');
        } else {
            $this->allroleassignments = [];
        }

        // Set initial bars.
        if ($useinitialsbar) {
            $this->initialbars(true);
        }
    }

    /**
     * Override the table show_hide_link to not show for select column.
     *
     * @param string $column the column name, index into various names.
     * @param int $index numerical index of the column.
     * @return string HTML fragment.
     */
    protected function show_hide_link($column, $index) {
        if ($index > 0) {
            return parent::show_hide_link($column, $index);
        }
        return '';
    }

    /**
     * Set filters and build table structure.
     *
     * @param filterset $filterset The filterset object to get the filters from.
     */
    public function set_filterset(filterset $filterset): void {
        // Get the context.
        $this->courseid = $filterset->get_filter('courseid')->current();
        $this->course = get_course($this->courseid);
        $this->context = \context_course::instance($this->courseid, MUST_EXIST);

        // Process the filterset.
        parent::set_filterset($filterset);
    }

    /**
     * Guess the base url for the participants table.
     */
    public function guess_base_url(): void {
        $this->baseurl = new moodle_url('/user/index.php', ['id' => $this->courseid]);
    }

    /**
     * Get the context of the current table.
     *
     * Note: This function should not be called until after the filterset has been provided.
     *
     * @return context
     */
    public function get_context(): context {
        return $this->context;
    }

    /**
     * Check if the user has the capability to access this table.
     *
     * @return bool Return true if capability check passed.
     */
    public function has_capability(): bool {
        global $CFG;
        require_once($CFG->dirroot . '/course/lib.php');

        $context = $this->course->id == SITEID ? \context_system::instance() : $this->get_context();
        return course_can_view_participants($context);
    }
}
