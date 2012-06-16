<?php
/* Osmium
 * Copyright (C) 2012 Romain "Artefact2" Dalmaso <artefact2@gmail.com>
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

class FitPresets extends PHPUnit_Framework_TestCase {
	/**
	 * @group fit
	 */
	public function testSwitchModulePreset() {
		/* Make sure the module attributes are correctly updated in
		 * $fit['dogma'] when switching presets. */

		\Osmium\Fit\create($fit);
		\Osmium\Fit\select_ship($fit, 24698);
		\Osmium\Fit\add_module($fit, 0, 31790); /* Medium CDFE I */
		\Osmium\Fit\add_module($fit, 1, 31790);
		\Osmium\Fit\add_module($fit, 2, 31790);
		\Osmium\Fit\add_module($fit, 0, 2048); /* DC II */

		$fit['modulepresetname'] = 'Tech I';
		$t1 = $fit['modulepresetid'];
		$t2 = \Osmium\Fit\clone_preset($fit, 'Tech II');

		\Osmium\Fit\add_module($fit, 0, 9632); /* Limited Adaptive Invuln. Field */
		\Osmium\Fit\add_module($fit, 1, 8529); /* Large F-S9 Regolith Shield Induction */

		$t1ehp = \Osmium\Fit\get_ehp_and_resists($fit);

		\Osmium\Fit\use_preset($fit, $t2);
		\Osmium\Fit\add_module($fit, 0, 2281); /* Invuln. Field II */
		\Osmium\Fit\add_module($fit, 1, 3841); /* LSE II */

		$t2ehp = \Osmium\Fit\get_ehp_and_resists($fit);

		/* Pyfa 1.1.7-git */		
		$this->assertEquals(61840, $t1ehp['ehp']['avg'], '', 1);
		$this->assertEquals(66918, $t2ehp['ehp']['avg'], '', 1);

		/* Check internal consistency/sanity */
		\Osmium\Fit\use_preset($fit, $t1);
		$newt1ehp = \Osmium\Fit\get_ehp_and_resists($fit);
		\Osmium\Fit\use_preset($fit, $t2);
		$newt2ehp = \Osmium\Fit\get_ehp_and_resists($fit);
		$this->assertSame(serialize($t1ehp), serialize($newt1ehp));
		$this->assertSame(serialize($t2ehp), serialize($newt2ehp));
	}
}