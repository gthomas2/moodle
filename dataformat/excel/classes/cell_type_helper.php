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
class cell_type_helper extends \Box\Spout\Common\Helper\CellTypeHelper {
    /**
     * Returns whether the given value is numeric.
     * A numeric value is from type "integer" or "double" ("float" is not returned by gettype).
     *
     * @param $value
     * @return bool Whether the given value is numeric
     */
    public static function isNumeric($value) {
        $valueType = gettype($value);

        if ($valueType === 'string') {
            return preg_match('/^([+-]?)(?=\d|\.\d)\d*(\.\d*)?([Ee]([+-]?\d+))?(%|)?$/', $value) === 1;
        }

        return ($valueType === 'integer' || $valueType === 'double');
    }
}