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
 * Testcase for custom field plugins.
 *
 * @package    customfield
 * @copyright  2019 Guy Thomas <guy.thomas@tituslearning.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_customfield\tests;

defined('MOODLE_INTERNAL') || die();

/**
 * Testcase for custom field plugins.
 *
 * @package    customfield
 * @copyright  2019 Guy Thomas <guy.thomas@tituslearning.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class plugin_testcase extends \advanced_testcase {
    /** @var stdClass[] */
    protected $courses = [];
    /** @var \core_customfield\category_controller */
    protected $cfcat;
    /** @var \core_customfield\field_controller[] */
    protected $cfields;
    /** @var \core_customfield\data_controller[] */
    protected $cfdata;
    /** @var \core_customfield\field_controller */
    protected $lockedfield;

    /**
     * Make sure that locked fields are included on the form for regular users.
     * @throws ReflectionException
     */
    public function test_instance_form_locked_fields() {
        global $CFG;

        if (empty($this->lockedfield)) {
            $this->markTestSkipped('Custom field plugin has not set a locked field in the setUp step. Unable to test.');
        }

        require_once($CFG->dirroot . '/customfield/tests/fixtures/test_instance_form.php');

        $handler = $this->cfcat->get_handler();

        $form = new \core_customfield_test_instance_form('POST',
            ['handler' => $handler, 'instance' => $this->courses[1]]);
        $shortname = 'customfield_'.$this->lockedfield->get('shortname');

        // Dirty access to quick form so that we can test that locked fields are included for non admin users.
        $reflection = new \ReflectionClass($form);
        $property = $reflection->getProperty('_form');
        $property->setAccessible(true);
        $quickform = $property->getValue($form);
        $fieldexists = $quickform->elementExists($shortname) ||
            $quickform->elementExists($shortname.'_editor') ||
            $quickform->elementExists($shortname.'_static');
        $this->assertTrue($fieldexists,
            'Failed to assert that locked field '.$shortname.' is present on form for non-admin user.');
    }
}