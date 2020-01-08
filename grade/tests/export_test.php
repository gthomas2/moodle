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
 * Unit tests for grade/export/lib.php.
 *
 * @package  core_grades
 * @category phpunit
 * @copyright   Andrew Nicols <andrew@nicols.co.uk>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot.'/grade/lib.php');
require_once($CFG->dirroot.'/grade/export/lib.php');
require_once($CFG->dirroot.'/grade/export/txt/grade_export_txt.php');
/**
 * A test class used to test grade_report, the abstract grade report parent class
 */
class core_grade_export_test extends advanced_testcase {

    /**
     * Ensure that feedback is correct formatted. Test the default implementation of format_feedback
     *
     * @dataProvider    format_feedback_provider
     * @param   string  $input The input string to test
     * @param   int     $inputformat The format of the input string
     * @param   string  $expected The expected result of the format.
     */
    public function test_format_feedback($input, $inputformat, $expected) {
        $feedback = $this->getMockForAbstractClass(
                \grade_export::class,
                [],
                '',
                false
            );

        $this->assertEquals(
            $expected,
            $feedback->format_feedback((object) [
                    'feedback' => $input,
                    'feedbackformat' => $inputformat,
                ])
            );
    }

    /**
     * Ensure that feedback is correctly formatted. Test augmented functionality to handle file links
     */
    public function test_format_feedback_with_grade() {
        $this->resetAfterTest();
        $dg = $this->getDataGenerator();
        $c1 = $dg->create_course();
        $u1 = $dg->create_user();
        $gi1a = new grade_item($dg->create_grade_item(['courseid' => $c1->id]), false);
        $gi1a->update_final_grade($u1->id, 1, 'test');
        $contextid = $gi1a->get_context()->id;
        $gradeid = $gi1a->id;

        $tests = [
            'Has server based image (HTML)' => [
                '<p>See this reference: <img src="@@PLUGINFILE@@/test.img"></p>',
                FORMAT_HTML,
                "See this reference: "
            ],
            'Has server based image and more (HTML)' => [
                '<p>See <img src="@@PLUGINFILE@@/test.img"> for <em>reference</em></p>',
                FORMAT_HTML,
                "See  for reference"
            ],
            'Has server based video and more (HTML)' => [
                '<p>See <video src="@@PLUGINFILE@@/test.img">video of a duck</video> for <em>reference</em></p>',
                FORMAT_HTML,
                'See video of a duck for reference'
            ],
            'Has server based video with text and more (HTML)' => [
                '<p>See <video src="@@PLUGINFILE@@/test.img">@@PLUGINFILE@@/test.img</video> for <em>reference</em></p>',
                FORMAT_HTML,
                "See https://www.example.com/moodle/pluginfile.php/$contextid/grade/feedback/$gradeid/test.img for reference"
            ],
            'Multiple videos (HTML)' => [
                '<p>See <video src="@@PLUGINFILE@@/test.img">video of a duck</video> and '.
                '<video src="http://example.com/myimage.jpg">video of a cat</video> for <em>reference</em></p>',
                FORMAT_HTML,
                'See video of a duck and video of a cat for reference'
            ],
        ];

        $feedback = $this->getMockForAbstractClass(
            \grade_export::class,
            [],
            '',
            false
        );

        foreach ($tests as $key => $testdetails) {
            $expected = $testdetails[2];
            $input = $testdetails[0];
            $inputformat = $testdetails[1];

            $this->assertEquals(
                $expected,
                $feedback->format_feedback((object) [
                    'feedback' => $input,
                    'feedbackformat' => $inputformat,
                ], $gi1a),
                $key
            );
        }
    }

    /**
     * Data provider for the format_feedback tests.
     *
     * @return  array
     */
    public function format_feedback_provider() : array {
        return [
            'Basic string (PLAIN)' => [
                'This is an example string',
                FORMAT_PLAIN,
                'This is an example string',
            ],
            'Basic string (HTML)' => [
                '<p>This is an example string</p>',
                FORMAT_HTML,
                'This is an example string',
            ],
            'Has image (HTML)' => [
                '<p>See this reference: <img src="http://example.com/myimage.jpg"></p>',
                FORMAT_HTML,
                'See this reference: ',
            ],
            'Has image and more (HTML)' => [
                '<p>See <img src="http://example.com/myimage.jpg"> for <em>reference</em></p>',
                FORMAT_HTML,
                'See  for reference',
            ],
            'Has video and more (HTML)' => [
                '<p>See <video src="http://example.com/myimage.jpg">video of a duck</video> for <em>reference</em></p>',
                FORMAT_HTML,
                'See video of a duck for reference',
            ],
            'Multiple videos (HTML)' => [
                '<p>See <video src="http://example.com/myimage.jpg">video of a duck</video> and '.
                '<video src="http://example.com/myimage.jpg">video of a cat</video> for <em>reference</em></p>',
                FORMAT_HTML,
                'See video of a duck and video of a cat for reference'
            ],
            'HTML Looking tags in PLAIN' => [
                'The way you have written the <img thing looks pretty fun >',
                FORMAT_PLAIN,
                'The way you have written the &lt;img thing looks pretty fun &gt;',
            ],

        ];
    }

    public function test_get_export_params() {
        $this->resetAfterTest();

        $dg = $this->getDataGenerator();
        $mock = $this->getMockForAbstractClass(
            \grade_export::class,
            [],
            '',
            false
        );
        $mock->course = $dg->create_course();

        // Note - the mock has to have columns in order to function as we need it.
        $mock->columns = [
            new grade_item($dg->create_grade_item(['courseid' => $mock->course->id]), false)
        ];

        $params = $mock->get_export_params();
        $this->assertArrayHasKey('export_showgroups', $params);
        $this->assertArrayHasKey('export_showcohorts', $params);
        $this->assertFalse($params['export_showgroups']);
        $this->assertFalse($params['export_showcohorts']);

        $mock->showgroups = true;
        $mock->showcohorts = true;

        $params = $mock->get_export_params();
        $this->assertArrayHasKey('export_showgroups', $params);
        $this->assertArrayHasKey('export_showcohorts', $params);
        $this->assertTrue($params['export_showgroups']);
        $this->assertTrue($params['export_showcohorts']);
    }

    /**
     * Get grade export csv output.
     * @param grade_export_txt $gradeexport
     * @return array
     */
    private function get_grade_csv_output(grade_export_txt $gradeexport): array {
        $gradescsv = $gradeexport->print_grades();
        $iid = csv_import_reader::get_new_iid('lib');
        $csvimport = new csv_import_reader($iid, 'lib');
        $csvimport->load_csv_content($gradescsv, 'utf-8', 'comma');
        $csvimport->init();
        $dataset = array();
        $dataset[] = $csvimport->get_columns();
        while ($record = $csvimport->next()) {
            $dataset[] = $record;
        }
        $csvimport->cleanup();
        $csvimport->close();
        return $dataset;
    }

    public function test_grade_group_export() {
        $this->resetAfterTest();

        $dg = $this->getDataGenerator();
        $c1 = $dg->create_course();
        $u1 = $dg->create_user();
        $dg->enrol_user($u1->id, $c1->id, 'student');
        $gi1a = new grade_item($dg->create_grade_item(['courseid' => $c1->id]), false);
        $gi1b = new grade_item($dg->create_grade_item(['courseid' => $c1->id]), false);
        $gi1c = new grade_item($dg->create_grade_item(['courseid' => $c1->id]), false);
        $cht1 = $dg->create_cohort();
        $cht2 = $dg->create_cohort();
        $cht3 = $dg->create_cohort();

        $contextid = $gi1a->get_context()->id;
        $gradeid = $gi1a->id;
        $gp1 = $dg->create_group(['courseid' => $c1->id, 'name' => 'group1']);
        $gp2 = $dg->create_group(['courseid' => $c1->id, 'name' => 'group2']);
        $gp3 = $dg->create_group(['courseid' => $c1->id, 'name' => 'group3']);
        $fdata = (object) [
          'separator' => ','
        ];

        // Test that no group or cohort header is present when not selected for export.
        $gradeexport = new grade_export_txt($c1, 0, $fdata);
        $gradeexport->columns = [
            $gi1a,
            $gi1b,
            $gi1c
        ];
        $gradeexport->displaytype = ['grade' => 1, 'grade' => 1, 'grade' => 1];
        $gradeexport->course = $c1;
        if (grade_needs_regrade_final_grades($c1->id)) {
            grade_regrade_final_grades($c1->id, null, null, null);
        }
        $dataset = $this->get_grade_csv_output($gradeexport);

        $groupslangstr = get_string('group');
        $cohortslangstr = get_string('cohorts', 'cohort');

        $this->assertNotContains($groupslangstr, $dataset[0]);
        $this->assertNotContains($cohortslangstr, $dataset[0]);

        // Test groups show up when selected.
        $gradeexport->showgroups = true;
        if (grade_needs_regrade_final_grades($c1->id)) {
            grade_regrade_final_grades($c1->id, null, null, null);
        }
        $dataset = $this->get_grade_csv_output($gradeexport);

        $this->assertContains($groupslangstr, $dataset[0]);
        $this->assertNotContains($cohortslangstr, $dataset[0]);
        $grouppos = array_search(get_string('group'), $dataset[0]);
        // Test that group cell is empty until students are allocated to group.
        $this->assertEmpty($dataset[1][$grouppos]);
        // Add student to groups and ensure appropriate groups show up in export cell.
        $dg->create_group_member(['userid' => $u1->id, 'groupid' => $gp1->id]);
        $dg->create_group_member(['userid' => $u1->id, 'groupid' => $gp2->id]);
        $dataset = $this->get_grade_csv_output($gradeexport);
        $grouppos = array_search($groupslangstr, $dataset[0]);
        $this->assertNotEmpty($dataset[1][$grouppos]);
        $groupstr = $dataset[1][$grouppos];
        $this->assertContains($gp1->name, $groupstr);
        $this->assertContains($gp2->name, $groupstr);
        $this->assertNotContains($gp3->name, $groupstr);

        // Test cohorts show up when selected.
        $gradeexport->showcohorts = true;
        $dataset = $this->get_grade_csv_output($gradeexport);

        $this->assertContains($cohortslangstr, $dataset[0]);
        // Assert cohorts cell is empty until a student is in the cohort.
        $cohortpos = array_search($cohortslangstr, $dataset[0]);
        $this->assertEmpty($dataset[1][$cohortpos]);
        // Add student to cohorts and ensure appropriate cohorts show up in export cell.
        cohort_add_member($cht1->id, $u1->id);
        cohort_add_member($cht2->id, $u1->id);
        $dataset = $this->get_grade_csv_output($gradeexport);
        $this->assertNotEmpty($dataset[1][$cohortpos]);
        $dataset = $this->get_grade_csv_output($gradeexport);
        $cohortpos = array_search($cohortslangstr, $dataset[0]);
        $this->assertNotEmpty($dataset[1][$cohortpos]);
        $cohortstr = $dataset[1][$cohortpos];
        $this->assertContains($cht1->name, $cohortstr);
        $this->assertContains($cht2->name, $cohortstr);
        $this->assertNotContains($cht3->name, $cohortstr);
    }
}
