<?php
// This file is part of Moodle - http://moodle.org/
//
// WAM Akademi — Video yükleme, gizli taslak, önizleme (Content Workflow R1).
// Desteklenen konum: /local/wamakademi/upload.php
// Eski yol (/local/wamakademi/js/upload.php) GET yönlendirmesi yapar.

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/course/modlib.php');
require_once($CFG->dirroot . '/mod/resource/lib.php');
require_once($CFG->dirroot . '/mod/resource/locallib.php');
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->libdir . '/resourcelib.php');

require_login();

global $DB, $USER, $PAGE, $OUTPUT, $CFG, $SESSION;

/**
 * Kullanıcının video yönetebildiği kurslar.
 *
 * @return array<int, stdClass>
 */
function local_wamakademi_upload_get_manageable_courses(): array {
    global $USER;

    $courses = get_courses('all', 'c.fullname ASC', 'c.id, c.fullname, c.shortname, c.category, c.visible, c.maxbytes');
    $manageable = [];
    foreach ($courses as $course) {
        if ((int)$course->id === (int)SITEID) {
            continue;
        }
        if (\local_wamakademi\access::can_manage_videos((int)$USER->id, (int)$course->id)) {
            $manageable[(int)$course->id] = $course;
        }
    }
    return $manageable;
}

/**
 * Bölüm listesi (0 hariç).
 *
 * @param stdClass $course
 * @return array<int, string>
 */
function local_wamakademi_upload_get_course_sections(stdClass $course): array {
    $modinfo = get_fast_modinfo($course->id);
    $sections = [];
    foreach ($modinfo->get_section_info_all() as $secnum => $secinfo) {
        if ((int)$secnum === 0) {
            continue;
        }
        $sections[(int)$secnum] = format_string(get_section_name($course, $secinfo));
    }
    if (empty($sections)) {
        course_create_sections_if_missing($course, [1]);
        $modinfo = get_fast_modinfo($course->id, 0, true);
        foreach ($modinfo->get_section_info_all() as $secnum => $secinfo) {
            if ((int)$secnum === 0) {
                continue;
            }
            $sections[(int)$secnum] = format_string(get_section_name($course, $secinfo));
        }
    }
    return $sections;
}

/**
 * Gizli MP4 taslakları.
 *
 * @param stdClass $course
 * @return array
 */
function local_wamakademi_upload_get_hidden_drafts(stdClass $course): array {
    global $USER;
    if (!\local_wamakademi\access::can_manage_videos((int)$USER->id, (int)$course->id)) {
        return [];
    }
    $modinfo = get_fast_modinfo($course->id);
    $fs = get_file_storage();
    $drafts = [];
    foreach ($modinfo->get_cms() as $cm) {
        if ($cm->modname !== 'resource' || !empty($cm->deletioninprogress) || (int)$cm->visible !== 0) {
            continue;
        }
        $modcontext = context_module::instance($cm->id, IGNORE_MISSING);
        if (!$modcontext) {
            continue;
        }
        $files = $fs->get_area_files($modcontext->id, 'mod_resource', 'content', 0, 'sortorder DESC, id ASC', false);
        if (empty($files)) {
            continue;
        }
        $mainfile = reset($files);
        if ($mainfile->get_mimetype() !== 'video/mp4') {
            continue;
        }
        $drafts[] = [
            'cmid' => (int)$cm->id,
            'name' => format_string($cm->name),
            'sectionnum' => (int)$cm->sectionnum,
            'sectionname' => format_string(get_section_name($course, $cm->sectionnum)),
            'filename' => $mainfile->get_filename(),
            'filesize' => display_size($mainfile->get_filesize()),
            'previewurl' => (new moodle_url('/local/wamakademi/upload.php', [
                'courseid' => (int)$course->id,
                'sectionnum' => (int)$cm->sectionnum,
                'previewcmid' => (int)$cm->id,
            ]))->out(false),
        ];
    }
    return $drafts;
}

/**
 * Tek kullanımlık gönderim jetonu.
 */
function local_wamakademi_upload_issue_token(): string {
    global $SESSION;
    if (empty($SESSION->wam_upload_tokens) || !is_array($SESSION->wam_upload_tokens)) {
        $SESSION->wam_upload_tokens = [];
    }
    $token = bin2hex(random_bytes(16));
    $SESSION->wam_upload_tokens[$token] = ['used' => false, 'cmid' => 0, 'time' => time()];
    // Eski jetonları temizle (>2 saat).
    foreach ($SESSION->wam_upload_tokens as $k => $meta) {
        if (($meta['time'] ?? 0) < time() - 7200) {
            unset($SESSION->wam_upload_tokens[$k]);
        }
    }
    return $token;
}

/**
 * Jetonu tüket; daha önce kullanıldıysa mevcut cmid döner.
 *
 * @return array{ok:bool,duplicate:bool,cmid:int}
 */
function local_wamakademi_upload_consume_token(string $token): array {
    global $SESSION;
    if ($token === '' || empty($SESSION->wam_upload_tokens[$token])) {
        return ['ok' => false, 'duplicate' => false, 'cmid' => 0];
    }
    $meta = $SESSION->wam_upload_tokens[$token];
    if (!empty($meta['used'])) {
        return ['ok' => true, 'duplicate' => true, 'cmid' => (int)($meta['cmid'] ?? 0)];
    }
    $SESSION->wam_upload_tokens[$token]['used'] = true;
    return ['ok' => true, 'duplicate' => false, 'cmid' => 0];
}

function local_wamakademi_upload_bind_token(string $token, int $cmid): void {
    global $SESSION;
    if (isset($SESSION->wam_upload_tokens[$token])) {
        $SESSION->wam_upload_tokens[$token]['cmid'] = $cmid;
        $SESSION->wam_upload_tokens[$token]['used'] = true;
    }
}

/**
 * Video yükleme formu.
 */
class local_wamakademi_draft_upload_form extends moodleform {

    protected function definition() {
        $mform = $this->_form;
        $courses = $this->_customdata['courses'];
        $selectedcourse = $this->_customdata['selectedcourse'];
        $sections = $this->_customdata['sections'];
        $maxbytes = $this->_customdata['maxbytes'];
        $courselocked = !empty($this->_customdata['courselocked']);
        $cancelurl = $this->_customdata['cancelurl'] ?? null;

        $courseoptions = [];
        foreach ($courses as $cid => $c) {
            $courseoptions[$cid] = format_string($c->fullname);
        }

        if ($courselocked) {
            $mform->addElement('static', 'courselabel', get_string('uploadcourse', 'local_wamakademi'),
                format_string($selectedcourse->fullname));
            $mform->addElement('hidden', 'courseid', (int)$selectedcourse->id);
        } else {
            $mform->addElement('select', 'courseid', get_string('uploadcourse', 'local_wamakademi'), $courseoptions);
            $mform->addRule('courseid', get_string('required'), 'required', null, 'client');
        }
        $mform->setType('courseid', PARAM_INT);
        $mform->setDefault('courseid', (int)$selectedcourse->id);

        $mform->addElement('select', 'sectionnum', get_string('uploadsection', 'local_wamakademi'), $sections);
        $mform->setType('sectionnum', PARAM_INT);
        if (!empty($this->_customdata['selectedsection'])) {
            $mform->setDefault('sectionnum', (int)$this->_customdata['selectedsection']);
        }

        $mform->addElement('text', 'newsectionname', get_string('uploadnewsection', 'local_wamakademi'),
            ['size' => 48]);
        $mform->setType('newsectionname', PARAM_TEXT);
        $mform->addRule('newsectionname', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $mform->addElement('text', 'name', get_string('uploadlessonname', 'local_wamakademi'), ['size' => 60]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('required'), 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $mform->addElement('textarea', 'intro', get_string('uploadintro', 'local_wamakademi'),
            ['rows' => 3, 'cols' => 60]);
        $mform->setType('intro', PARAM_CLEANHTML);

        $fileoptions = [
            'subdirs' => 0,
            'maxbytes' => $maxbytes,
            'maxfiles' => 1,
            'accepted_types' => ['.mp4', 'video/mp4'],
            'return_types' => FILE_INTERNAL,
        ];
        $mform->addElement('filemanager', 'videofile', get_string('uploadfile', 'local_wamakademi'), null, $fileoptions);
        $mform->addRule('videofile', get_string('required'), 'required', null, 'client');

        $mform->addElement('hidden', 'submissiontoken', $this->_customdata['submissiontoken'] ?? '');
        $mform->setType('submissiontoken', PARAM_ALPHANUMEXT);

        $buttonarray = [];
        $buttonarray[] = $mform->createElement('submit', 'submitbutton', get_string('uploaddraftsave', 'local_wamakademi'));
        if ($cancelurl) {
            $buttonarray[] = $mform->createElement('cancel', 'cancel', get_string('uploadcancel', 'local_wamakademi'));
        }
        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);
    }

    public function validation($data, $files) {
        global $USER, $DB;

        $errors = parent::validation($data, $files);
        $courseid = (int)($data['courseid'] ?? 0);
        $sectionnum = (int)($data['sectionnum'] ?? 0);
        $newsectionname = trim((string)($data['newsectionname'] ?? ''));

        $course = $DB->get_record('course', ['id' => $courseid], '*', IGNORE_MISSING);
        if (!$course || (int)$course->id === (int)SITEID) {
            $errors['courseid'] = get_string('uploadnoperm', 'local_wamakademi');
            return $errors;
        }
        if (!\local_wamakademi\access::can_manage_videos((int)$USER->id, $courseid)) {
            $errors['courseid'] = get_string('uploadnoperm', 'local_wamakademi');
            return $errors;
        }

        if ($newsectionname === '') {
            $sectionrecord = $DB->get_record('course_sections', [
                'course' => $course->id,
                'section' => $sectionnum,
            ], 'id, section', IGNORE_MISSING);
            if (!$sectionrecord || $sectionnum <= 0) {
                $errors['sectionnum'] = get_string('uploadnoperm', 'local_wamakademi');
            }
        }

        if (trim((string)($data['name'] ?? '')) === '') {
            $errors['name'] = get_string('required');
        }

        $draftitemid = (int)($data['videofile'] ?? 0);
        $usercontext = context_user::instance($USER->id);
        $fs = get_file_storage();
        $draftfiles = $fs->get_area_files($usercontext->id, 'user', 'draft', $draftitemid, 'id ASC', false);
        if (empty($draftfiles)) {
            $errors['videofile'] = get_string('required');
            return $errors;
        }
        if (count($draftfiles) > 1) {
            $errors['videofile'] = 'Tek seferde yalnızca 1 adet MP4 video dosyası yüklenebilir.';
            return $errors;
        }
        $file = reset($draftfiles);
        $mimetype = $file->get_mimetype();
        $ext = strtolower(pathinfo($file->get_filename(), PATHINFO_EXTENSION));
        if ($ext !== 'mp4' || $mimetype !== 'video/mp4') {
            $errors['videofile'] = 'Yalnızca geçerli MP4 (video/mp4) dosyaları kabul edilir.';
        }
        $maxbytes = (int)$this->_customdata['maxbytes'];
        if ($maxbytes > 0 && $file->get_filesize() > $maxbytes) {
            $errors['videofile'] = get_string('maxbytesfile', 'error', display_size($maxbytes));
        }

        $token = (string)($data['submissiontoken'] ?? '');
        if ($token === '' || empty($GLOBALS['SESSION']->wam_upload_tokens[$token])) {
            $errors['submissiontoken'] = get_string('publishinvalid', 'local_wamakademi');
        }

        return $errors;
    }
}

// ——— Sayfa girişi ———

$manageablecourses = local_wamakademi_upload_get_manageable_courses();
if (empty($manageablecourses)) {
    throw new moodle_exception('uploadnocourses', 'local_wamakademi');
}

$requestedcourseid = optional_param('courseid', 0, PARAM_INT);
$requestedsection = optional_param('sectionnum', 0, PARAM_INT);
$previewcmid = optional_param('previewcmid', 0, PARAM_INT);
$courselocked = ($requestedcourseid > 0);

if ($requestedcourseid <= 0) {
    $requestedcourseid = (int)array_key_first($manageablecourses);
}

if (!isset($manageablecourses[$requestedcourseid])
        || !\local_wamakademi\access::can_manage_videos((int)$USER->id, $requestedcourseid)) {
    throw new required_capability_exception(
        context_course::instance($requestedcourseid, IGNORE_MISSING) ?: context_system::instance(),
        'local/wamakademi:managevideos',
        'nopermissions',
        ''
    );
}

$selectedcourse = $DB->get_record('course', ['id' => $requestedcourseid], '*', MUST_EXIST);
$coursecontext = context_course::instance($selectedcourse->id);
require_login($selectedcourse, false);

$sections = local_wamakademi_upload_get_course_sections($selectedcourse);
if (!isset($sections[$requestedsection])) {
    $requestedsection = (int)array_key_first($sections);
}

$pageurl = new moodle_url('/local/wamakademi/upload.php', [
    'courseid' => (int)$selectedcourse->id,
    'sectionnum' => (int)$requestedsection,
]);
if ($previewcmid > 0) {
    $pageurl->param('previewcmid', $previewcmid);
}

$PAGE->set_url($pageurl);
$PAGE->set_context($coursecontext);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('uploadtitle', 'local_wamakademi'));
$PAGE->set_heading(get_string('uploadheading', 'local_wamakademi', format_string($selectedcourse->fullname)));
$PAGE->add_body_class('wam-content');
$PAGE->requires->css(new moodle_url('/local/wamakademi/css/wam-content.css', [
    'v' => (string)@filemtime($CFG->dirroot . '/local/wamakademi/css/wam-content.css'),
]));

$maxbytes = get_user_max_upload_file_size($coursecontext, $CFG->maxbytes, $selectedcourse->maxbytes);
$cancelurl = new moodle_url('/course/view.php', ['id' => (int)$selectedcourse->id]);
$submissiontoken = local_wamakademi_upload_issue_token();

$mform = new local_wamakademi_draft_upload_form($pageurl->out(false), [
    'courses' => $manageablecourses,
    'selectedcourse' => $selectedcourse,
    'sections' => $sections,
    'selectedsection' => $requestedsection,
    'maxbytes' => $maxbytes,
    'courselocked' => $courselocked,
    'cancelurl' => $cancelurl,
    'submissiontoken' => $submissiontoken,
]);

$createderror = null;

if ($mform->is_cancelled()) {
    redirect($cancelurl);
}

if ($formdata = $mform->get_data()) {
    require_sesskey();

    $token = (string)($formdata->submissiontoken ?? '');
    $tokenresult = local_wamakademi_upload_consume_token($token);
    if (!$tokenresult['ok']) {
        throw new moodle_exception('invalidrequest', 'error');
    }
    if ($tokenresult['duplicate']) {
        $dupcmid = (int)$tokenresult['cmid'];
        $redirectparams = [
            'courseid' => (int)$selectedcourse->id,
            'sectionnum' => (int)$requestedsection,
        ];
        if ($dupcmid > 0) {
            $redirectparams['previewcmid'] = $dupcmid;
        }
        redirect(
            new moodle_url('/local/wamakademi/upload.php', $redirectparams),
            get_string('uploadduplicate', 'local_wamakademi'),
            null,
            \core\output\notification::NOTIFY_INFO
        );
    }

    $targetcourseid = (int)$formdata->courseid;
    $targetcourse = $DB->get_record('course', ['id' => $targetcourseid], '*', MUST_EXIST);
    if ((int)$targetcourse->id === (int)SITEID) {
        throw new moodle_exception('invalidcourseid');
    }
    $targetcontext = context_course::instance($targetcourse->id, MUST_EXIST);
    require_login($targetcourse, false);

    if (!\local_wamakademi\access::can_manage_videos((int)$USER->id, (int)$targetcourse->id)) {
        throw new required_capability_exception(
            $targetcontext,
            'local/wamakademi:managevideos',
            'nopermissions',
            ''
        );
    }

    $targetsectionnum = (int)$formdata->sectionnum;
    $newsectionname = trim((string)($formdata->newsectionname ?? ''));
    $newcmid = 0;

    $transaction = $DB->start_delegated_transaction();
    try {
        if ($newsectionname !== '') {
            require_capability('moodle/course:update', $targetcontext);
            $newsec = course_create_section($targetcourse->id, 0);
            course_update_section($targetcourse, $newsec, ['name' => $newsectionname]);
            $targetsectionnum = (int)$newsec->section;
        } else {
            $DB->get_record('course_sections', [
                'course' => $targetcourse->id,
                'section' => $targetsectionnum,
            ], '*', MUST_EXIST);
        }

        $moduleinfo = new stdClass();
        $moduleinfo->modulename = 'resource';
        $moduleinfo->course = (int)$targetcourse->id;
        $moduleinfo->section = $targetsectionnum;
        $moduleinfo->name = trim((string)$formdata->name);
        $moduleinfo->introeditor = [
            'text' => (string)($formdata->intro ?? ''),
            'format' => FORMAT_HTML,
            'itemid' => 0,
        ];
        $moduleinfo->showdescription = 0;
        $moduleinfo->visible = 0;
        $moduleinfo->visibleoncoursepage = 1;
        $moduleinfo->display = RESOURCELIB_DISPLAY_EMBED;
        $moduleinfo->printintro = 1;
        $moduleinfo->showsize = 1;
        $moduleinfo->showtype = 1;
        $moduleinfo->showdate = 0;
        $moduleinfo->filterfiles = 0;
        $moduleinfo->files = (int)$formdata->videofile;
        $moduleinfo->cmidnumber = '';
        $moduleinfo->groupmode = 0;
        $moduleinfo->groupingid = 0;
        $moduleinfo->completion = 0;

        $created = create_module($moduleinfo);
        if (empty($created->coursemodule)) {
            throw new moodle_exception('cannotaddcoursemodule');
        }
        $newcmid = (int)$created->coursemodule;
        $modcontext = context_module::instance($newcmid, MUST_EXIST);

        $fs = get_file_storage();
        $storedfiles = $fs->get_area_files($modcontext->id, 'mod_resource', 'content', 0, 'sortorder DESC, id ASC', false);
        if (empty($storedfiles)) {
            throw new moodle_exception('filenotfound', 'resource');
        }
        $mainfile = reset($storedfiles);
        if ($mainfile->get_mimetype() !== 'video/mp4') {
            throw new moodle_exception('invalidfile', 'error');
        }

        $DB->set_field('course_modules', 'visible', 0, ['id' => $newcmid]);
        $DB->set_field('course_modules', 'visibleold', 0, ['id' => $newcmid]);
        rebuild_course_cache($targetcourse->id, true);

        $transaction->allow_commit();
        local_wamakademi_upload_bind_token($token, $newcmid);

        redirect(
            new moodle_url('/local/wamakademi/upload.php', [
                'courseid' => (int)$targetcourse->id,
                'sectionnum' => $targetsectionnum,
                'previewcmid' => $newcmid,
            ]),
            get_string('uploaddraftsaved', 'local_wamakademi'),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    } catch (Throwable $e) {
        try {
            $transaction->rollback($e);
        } catch (Throwable $ignored) {
            // Transaction zaten kapandıysa.
        }
        if ($newcmid > 0) {
            try {
                course_delete_module($newcmid, false);
            } catch (Throwable $ignored) {
                // Temizlik en iyi çaba.
            }
        }
        $createderror = get_string('error') . ': ' . $e->getMessage();
        // Yeni jeton ver (yeniden deneme).
        $submissiontoken = local_wamakademi_upload_issue_token();
        $mform = new local_wamakademi_draft_upload_form($pageurl->out(false), [
            'courses' => $manageablecourses,
            'selectedcourse' => $selectedcourse,
            'sections' => $sections,
            'selectedsection' => $requestedsection,
            'maxbytes' => $maxbytes,
            'courselocked' => $courselocked,
            'cancelurl' => $cancelurl,
            'submissiontoken' => $submissiontoken,
        ]);
    }
}

// Önizleme verisi.
$previewdata = null;
if ($previewcmid > 0) {
    $previewcm = get_coursemodule_from_id('resource', $previewcmid, 0, false, IGNORE_MISSING);
    if ($previewcm && (int)$previewcm->course === (int)$selectedcourse->id) {
        $previewmodcontext = context_module::instance($previewcm->id, IGNORE_MISSING);
        if ($previewmodcontext && \local_wamakademi\access::can_manage_videos((int)$USER->id, (int)$selectedcourse->id)) {
            $resource = $DB->get_record('resource', ['id' => $previewcm->instance], '*', IGNORE_MISSING);
            $fs = get_file_storage();
            $files = $fs->get_area_files($previewmodcontext->id, 'mod_resource', 'content', 0, 'sortorder DESC, id ASC', false);
            if ($resource && !empty($files)) {
                $mainfile = reset($files);
                $pluginfileurl = moodle_url::make_pluginfile_url(
                    $previewmodcontext->id,
                    'mod_resource',
                    'content',
                    $resource->revision,
                    $mainfile->get_filepath(),
                    $mainfile->get_filename()
                );
                $mediamanager = core_media_manager::instance($PAGE);
                $embedhtml = $mediamanager->embed_url($pluginfileurl, format_string($resource->name), 0, 0, [
                    core_media_manager::OPTION_TRUSTED => true,
                    core_media_manager::OPTION_BLOCK => true,
                ]);
                $status = \local_wamakademi\video_status::for_cm($previewcm, $selectedcourse);
                $previewdata = [
                    'cmid' => (int)$previewcm->id,
                    'name' => format_string($resource->name),
                    'coursename' => format_string($selectedcourse->fullname),
                    'sectionname' => format_string(get_section_name($selectedcourse, $previewcm->sectionnum)),
                    'sectionnum' => (int)$previewcm->sectionnum,
                    'filename' => $mainfile->get_filename(),
                    'filesize' => display_size($mainfile->get_filesize()),
                    'embedhtml' => $embedhtml,
                    'status' => $status,
                    'editurl' => (new moodle_url('/course/modedit.php', ['update' => (int)$previewcm->id]))->out(false),
                    'playerurl' => (new moodle_url('/mod/resource/view.php', [
                        'id' => (int)$previewcm->id,
                        'wampreview' => 1,
                    ]))->out(false),
                    'courseurl' => (new moodle_url('/course/view.php', ['id' => (int)$selectedcourse->id]))->out(false),
                ];
            }
        }
    }
}

$hiddendrafts = local_wamakademi_upload_get_hidden_drafts($selectedcourse);

echo $OUTPUT->header();
echo html_writer::tag('p', get_string('uploadscope', 'local_wamakademi'), [
    'class' => 'wam-scope',
    'data-region' => 'wam-scope',
]);

if (!empty($createderror)) {
    echo $OUTPUT->notification($createderror, \core\output\notification::NOTIFY_ERROR);
}

echo html_writer::start_div('wam-content-shell');

if ($previewdata !== null) {
    $statuscode = $previewdata['status']['code'];
    $statusclass = 'wam-status wam-status--' . $statuscode;
    echo html_writer::start_div('wam-content-card', ['aria-labelledby' => 'wam-preview-heading']);
    echo html_writer::span($previewdata['status']['label'], $statusclass);
    echo html_writer::tag('h2', $previewdata['name'], [
        'id' => 'wam-preview-heading',
        'class' => 'wam-content-card__title',
    ]);
    $meta = get_string('uploadcourse', 'local_wamakademi') . ': ' . $previewdata['coursename']
        . ' · ' . get_string('uploadsection', 'local_wamakademi') . ': ' . $previewdata['sectionname']
        . ' · ' . s($previewdata['filename']) . ' (' . s($previewdata['filesize']) . ')';
    if ($previewdata['status']['detail'] !== '') {
        $meta .= ' · ' . $previewdata['status']['detail'];
    }
    echo html_writer::tag('p', $meta, ['class' => 'wam-content-card__meta']);
    echo html_writer::div($previewdata['embedhtml'], 'wam-preview-player');

    echo html_writer::start_div('wam-content-actions');
    echo html_writer::link($previewdata['editurl'], get_string('previewedit', 'local_wamakademi'), [
        'class' => 'btn btn-secondary',
    ]);
    echo html_writer::link($previewdata['playerurl'], get_string('previewopenplayer', 'local_wamakademi'), [
        'class' => 'btn btn-secondary',
        'target' => '_blank',
        'rel' => 'noopener',
    ]);

    $publishurl = new moodle_url('/local/wamakademi/publish.php');
    if ((int)$previewdata['status']['visible'] === 0) {
        echo html_writer::start_tag('form', [
            'method' => 'post',
            'action' => $publishurl->out(false),
        ]);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'cmid', 'value' => $previewdata['cmid']]);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'publish']);
        echo html_writer::empty_tag('input', [
            'type' => 'submit',
            'class' => 'btn btn-primary',
            'value' => get_string('previewpublish', 'local_wamakademi'),
        ]);
        echo html_writer::end_tag('form');
    } else {
        echo html_writer::start_tag('form', [
            'method' => 'post',
            'action' => $publishurl->out(false),
        ]);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'cmid', 'value' => $previewdata['cmid']]);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'unpublish']);
        echo html_writer::empty_tag('input', [
            'type' => 'submit',
            'class' => 'btn btn-secondary',
            'value' => get_string('previewunpublish', 'local_wamakademi'),
        ]);
        echo html_writer::end_tag('form');
    }

    echo html_writer::link($previewdata['courseurl'], get_string('previewback', 'local_wamakademi'), [
        'class' => 'btn btn-link',
    ]);
    echo html_writer::end_div();
    echo html_writer::end_div();
}

echo html_writer::start_div('wam-content-card');
echo html_writer::tag('h2', get_string('uploaddraftsave', 'local_wamakademi'), ['class' => 'wam-content-card__title']);
$mform->display();
echo html_writer::end_div();

echo html_writer::start_div('wam-content-card');
echo html_writer::tag('h2', get_string('hiddendrafts', 'local_wamakademi') . ' (' . count($hiddendrafts) . ')', [
    'class' => 'wam-content-card__title',
]);
if (empty($hiddendrafts)) {
    echo html_writer::tag('p', '—', ['class' => 'wam-content-card__meta']);
} else {
    echo html_writer::start_div('wam-draft-table-wrap');
    echo html_writer::start_tag('table', ['class' => 'wam-draft-table']);
    echo html_writer::start_tag('thead');
    echo html_writer::tag('tr',
        html_writer::tag('th', get_string('uploadsection', 'local_wamakademi')) .
        html_writer::tag('th', get_string('uploadlessonname', 'local_wamakademi')) .
        html_writer::tag('th', get_string('uploadfile', 'local_wamakademi')) .
        html_writer::tag('th', get_string('participantsactions', 'local_wamakademi'))
    );
    echo html_writer::end_tag('thead');
    echo html_writer::start_tag('tbody');
    foreach ($hiddendrafts as $draft) {
        echo html_writer::start_tag('tr');
        echo html_writer::tag('td', $draft['sectionname']);
        echo html_writer::tag('td', $draft['name']);
        echo html_writer::tag('td', s($draft['filename']) . ' (' . s($draft['filesize']) . ')');
        echo html_writer::tag('td', html_writer::link($draft['previewurl'], get_string('previewstatusdraft', 'local_wamakademi'), [
            'class' => 'btn btn-secondary btn-sm',
        ]));
        echo html_writer::end_tag('tr');
    }
    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');
    echo html_writer::end_div();
}
echo html_writer::end_div();

echo html_writer::end_div(); // shell

// Gönderimde düğmeyi kilitle (çift tıklama).
echo html_writer::script("(function(){var f=document.querySelector('#region-main form.mform');if(!f)return;f.addEventListener('submit',function(){if(f.classList.contains('wam-upload-busy')){event.preventDefault();return;}f.classList.add('wam-upload-busy');var b=f.querySelector('input[type=submit],button[type=submit]');if(b){b.disabled=true;b.value=" . json_encode(get_string('uploadsubmitting', 'local_wamakademi')) . ";}});})();");

echo $OUTPUT->footer();
