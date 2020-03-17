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
 * Extended spout WriterEntityFactory.
 *
 * @package    dataformat_excel
 * @copyright  2020 Guy Thomas (dev@citri.city)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace dataformat_excel;

defined('MOODLE_INTERNAL') || die();

// @codingStandardsIgnoreFile
// phpcs:ignoreFile

use Box\Spout\Common\Entity\Style\Style;
use Box\Spout\Common\Entity\Row;

/**
 * Extended spout WriterEntityFactory.
 *
 * @package    dataformat_excel
 * @copyright  2020 Guy Thomas (dev@citri.city)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class writer_entity_factory extends \Box\Spout\Writer\Common\Creator\WriterEntityFactory {
    /**
     * This method is exactly the same as the parent method, except it uses our version of
     * the Cell class to allow for percentage exports.
     * @param array $cellValues
     * @param Style|null $rowStyle
     * @return Row
     */
    public static function createRowFromArray(array $cellValues = [], Style $rowStyle = null) {
        $cells = array_map(function ($cellValue) {
            return new cell($cellValue);
        }, $cellValues);

        return new Row($cells, $rowStyle);
    }

    /**
     * This method is exactly the same as the parent method, except it uses our version of
     * the Cell class to allow for percentage exports.
     * @param mixed $cellValue
     * @param Style|null $cellStyle
     * @return cell
     */
    public static function createCell($cellValue, Style $cellStyle = null) {
        return new cell($cellValue, $cellStyle);
    }
}