<?php
namespace local_wamakademi\hook;

defined('MOODLE_INTERNAL') || die();

/**
 * Eğitim numarası alanını elle girişe kapatır.
 */
class course_form {
    /**
     * @param \core_course\hook\after_form_definition $hook
     */
    public static function after_definition(\core_course\hook\after_form_definition $hook): void {
        $mform = $hook->mform;
        $course = $hook->formwrapper->get_course();
        self::replace_course_image($mform, $course);
        if (!$mform->elementExists('idnumber')) {
            return;
        }
        if (!empty($course->id)) {
            return;
        }
        $info = $mform->createElement(
            'static',
            'wamcoursenumberinfo',
            '',
            get_string('coursenumberauto', 'local_wamakademi')
        );
        $mform->insertElementBefore($info, 'idnumber');
    }

    /**
     * Eğitim görselini tek resim yüklemesi ve katalog kapağı önizlemesi yapar.
     *
     * @param \MoodleQuickForm $mform
     * @param \stdClass $course
     */
    private static function replace_course_image(\MoodleQuickForm $mform, \stdClass $course): void {
        global $CFG, $PAGE;

        if (!$mform->elementExists('overviewfiles_filemanager') || !function_exists('course_overviewfiles_options')) {
            return;
        }
        require_once($CFG->dirroot . '/course/lib.php');
        require_once($CFG->dirroot . '/repository/lib.php');
        $options = course_overviewfiles_options($course);
        if (!$options) {
            return;
        }
        $options['maxfiles'] = 1;
        $options['subdirs'] = 0;
        $options['accepted_types'] = ['web_image'];
        $options['return_types'] = FILE_INTERNAL;

        $mform->removeElement('overviewfiles_filemanager');
        $label = get_string('courseoverviewfiles');
        $element = $mform->createElement('filemanager', 'overviewfiles_filemanager', $label, null, $options);
        if ($mform->elementExists('courseformathdr')) {
            $mform->insertElementBefore($element, 'courseformathdr');
        } else {
            $mform->addElement($element);
        }
        $mform->addHelpButton('overviewfiles_filemanager', 'courseoverviewfiles');

        $name = trim((string)($course->shortname ?? ''));
        if ($name === '') {
            $name = get_string('courseoverviewfiles');
        }
        $preview = \html_writer::div(
            \html_writer::div(
                \html_writer::empty_tag('img', ['alt' => '', 'hidden' => 'hidden']) .
                \html_writer::div(s($name), 'wam-course-image-preview__empty', ['data-wam-course-image-empty' => '1']),
                'wam-course-image-preview__frame'
            ) .
            \html_writer::div(get_string('courseimagepreview', 'local_wamakademi'), 'wam-course-image-preview__caption'),
            'wam-course-image-preview',
            ['data-wam-course-image' => '1']
        );
        $static = $mform->createElement('static', 'wamcourseimagepreview', '', $preview);
        $mform->insertElementBefore($static, 'overviewfiles_filemanager');

        $script = $CFG->dirroot . '/local/wamakademi/js/course_image.js';
        $PAGE->requires->js(new \moodle_url('/local/wamakademi/js/course_image.js', [
            'v' => is_readable($script) ? filemtime($script) : time(),
        ]));
    }

    /**
     * @param \core_course\hook\after_form_definition_after_data $hook
     */
    public static function after_data(\core_course\hook\after_form_definition_after_data $hook): void {
        $mform = $hook->mform;
        if (!$mform->elementExists('idnumber')) {
            return;
        }
        $course = $hook->formwrapper->get_course();
        if (!empty($course->id)) {
            $mform->hardFreeze('idnumber');
            return;
        }
        $current = $mform->getElementValue('idnumber');
        $current = is_array($current) ? trim((string)($current[0] ?? '')) : trim((string)$current);
        if (!\local_wamakademi\coursenumber::is_pending($current)) {
            $current = \local_wamakademi\coursenumber::reserve_for_form();
        }
        $mform->setConstant('idnumber', $current);
        $mform->getElement('idnumber')->setValue($current);
        $mform->getElement('idnumber')->updateAttributes(['readonly' => 'readonly']);
    }

    /**
     * @param \core_course\hook\after_form_validation $hook
     */
    public static function validate(\core_course\hook\after_form_validation $hook): void {
        $data = $hook->get_data();
        $course = $hook->formwrapper->get_course();
        $errors = [];
        if (empty($course->id)) {
            $code = trim((string)($data['idnumber'] ?? ''));
            if (!\local_wamakademi\coursenumber::is_pending($code)) {
                $errors['idnumber'] = get_string('coursenumbermanual', 'local_wamakademi');
            }
        } else if (isset($data['idnumber']) && (string)$data['idnumber'] !== (string)$course->idnumber) {
            $errors['idnumber'] = get_string('coursenumberlocked', 'local_wamakademi');
        }
        if ($errors) {
            $hook->add_errors($errors);
        }
    }
}
