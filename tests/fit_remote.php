<?php
/* Osmium
 * Copyright (C) 2012, 2013 Romain "Artefact2" Dalmaso <artefact2@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

require_once __DIR__.'/../inc/root.php';

class FitRemote extends PHPUnit_Framework_TestCase {
	/**
	 * @group fit
	 * @group engine
	 */
	public function testProjectedSimple() {
		\Osmium\Fit\create($foo);
		\Osmium\Fit\create($bar);

		\Osmium\Fit\select_ship($foo, 587); /* Rifter */
		\Osmium\Fit\select_ship($bar, 587);

		\Osmium\Fit\add_module($bar, 0, 526); /* Stasis Webifier I */
		\Osmium\Fit\add_remote($foo, 'other', $bar);

		/* Source: Pyfa-96bb1b1 (2013-07-30) */

		$this->assertEquals(443.75, \Osmium\Dogma\get_ship_attribute($foo, 'maxVelocity'), '', 0.005);
		\Osmium\Fit\set_module_target_by_location(
			$foo, 'other',
			'medium', 0,
			'local'
		);
		$this->assertEquals(221.875, \Osmium\Dogma\get_ship_attribute($foo, 'maxVelocity'), '', 0.0005);
	}
}
