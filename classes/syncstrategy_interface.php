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
 * (Role-based relationships) Sync strategy interface.
 *
 * @package    enrol_dbuserrel
 * @copyright  2019 Segun Babalola <segun@babalola.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * (Role-based relationships mapping) Strategy interface.
 * This interface represents the contract that future sync strategies need to comply with to be used with the
 * dbuserrel plugin.
 *
 * A default implementation has been provided, hopefully more will follow in future. Each avaialble strategy will
 * need to be presented as a drop-dronw select option in the plugin admin screen.
 *
 * @package    enrol_dbuserrel
 * @copyright  2019 Segun Babalola <segun@babalola.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface enrol_dbuserrel_syncstrategy_interface {

    /**
     * Constructor.
     *
     * @param \enrol_dbuserrel_dataport_interface $externaldataport
     * @param \enrol_dbuserrel_dataport_interface $internaldataport
     */
    public function __construct(
        \enrol_dbuserrel_dataport_interface $externaldataport,
        \enrol_dbuserrel_dataport_interface $internaldataport
    );

    /**
     * Embodies the sync algorithm for the straetgy.
     *
     * @param null|int $userid User Id filter.
     * @param boolean $verbose Extent of tracing/info logging required.
     */
    public function sync_relationships($userid, $verbose);
}