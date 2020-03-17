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
 * Extended spout Cell to accommodate percentages.
 *
 * @package    dataformat_excel
 * @copyright  2020 Guy Thomas (dev@citri.city)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace dataformat_excel;

defined('MOODLE_INTERNAL') || die();

// @codingStandardsIgnoreFile
// phpcs:ignoreFile

/**
 * Extended spout Cell to accommodate percentages by using custom CellTypeHelper.
 *
 * @package    dataformat_excel
 * @copyright  2020 Guy Thomas (dev@citri.city)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cell extends \Box\Spout\Common\Entity\Cell {
    protected function detectType($value) {
        if (cell_type_helper::isBoolean($value)) {
            return self::TYPE_BOOLEAN;
        }
        if (cell_type_helper::isEmpty($value)) {
            return self::TYPE_EMPTY;
        }
        if (cell_type_helper::isNumeric($value)) {
            return self::TYPE_NUMERIC;
        }
        if (cell_type_helper::isDateTimeOrDateInterval($value)) {
            return self::TYPE_DATE;
        }
        if (cell_type_helper::isNonEmptyString($value)) {
            return self::TYPE_STRING;
        }

        return self::TYPE_ERROR;
    }
}