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

class FitPresets extends PHPUnit_Framework_TestCase {
	/**
	 * @group fit
	 */
	public function testSwitchModulePreset() {
		/* Make sure the module attributes are correctly updated when
		 * switching presets. */

		\Osmium\Fit\create($fit);
		\Osmium\Fit\select_ship($fit, 24698); /* Drake */
		\Osmium\Fit\add_module($fit, 0, 31790); /* Medium CDFE I */
		\Osmium\Fit\add_module($fit, 1, 31790);
		\Osmium\Fit\add_module($fit, 2, 31790);
		\Osmium\Fit\add_module($fit, 0, 2048); /* DC II */

		$fit['modulepresetname'] = 'Tech I';
		$t1 = $fit['modulepresetid'];
		$t2 = \Osmium\Fit\clone_preset($fit, 'Tech II');

		$uniform = array('em' => 1, 'thermal' => 1, 'explosive' => 1, 'kinetic' => 1);

		\Osmium\Fit\add_module($fit, 0, 9632); /* Limited Adaptive Invuln. Field */
		\Osmium\Fit\add_module($fit, 1, 8529); /* Large F-S9 Regolith Shield Induction */

		$t1ehp = \Osmium\Fit\get_ehp_and_resists($fit, $uniform);

		\Osmium\Fit\use_preset($fit, $t2);
		\Osmium\Fit\add_module($fit, 0, 2281); /* Invuln. Field II */
		\Osmium\Fit\add_module($fit, 1, 3841); /* LSE II */

		$t2ehp = \Osmium\Fit\get_ehp_and_resists($fit, $uniform);

		/* Source: Pyfa-c67034e (2013-07-01) */

		$this->assertEquals(56259, $t1ehp['ehp']['avg'], '', 1);
		$this->assertEquals(60941, $t2ehp['ehp']['avg'], '', 1);

		/* Check internal consistency/sanity */
		\Osmium\Fit\use_preset($fit, $t1);
		$newt1ehp = \Osmium\Fit\get_ehp_and_resists($fit, $uniform);
		\Osmium\Fit\use_preset($fit, $t2);
		$newt2ehp = \Osmium\Fit\get_ehp_and_resists($fit, $uniform);
		$this->assertSame(serialize($t1ehp), serialize($newt1ehp));
		$this->assertSame(serialize($t2ehp), serialize($newt2ehp));
	}

	/**
	 * @group fit
	 */
	public function testImplantsWithPresets() {
		\Osmium\Fit\create($fit);
		\Osmium\Fit\add_implant($fit, 9941); /* Memory Augmentation - Basic */

		$p1 = $fit['modulepresetid'];
		$p2 = \Osmium\Fit\clone_preset($fit, 'Other');
		\Osmium\Fit\use_preset($fit, $p2);

		\Osmium\Fit\remove_implant($fit, 9941);
		\Osmium\Fit\add_implant($fit, 10212); /* Neural Boost - Standard */

		$bonus = \Osmium\Dogma\get_implant_attribute($fit, 10212, 'willpowerBonus');
		$this->assertSame(4.0, $bonus);

		$willpower = \Osmium\Dogma\get_char_attribute($fit, 'willpower');
		$this->assertSame(4.0, $willpower);

		\Osmium\Fit\use_preset($fit, $p1);

		$willpower = \Osmium\Dogma\get_char_attribute($fit, 'willpower');
		$memory = \Osmium\Dogma\get_char_attribute($fit, 'memory');
		$this->assertSame(0.0, $willpower);
		$this->assertSame(3.0, $memory);
	}
}
