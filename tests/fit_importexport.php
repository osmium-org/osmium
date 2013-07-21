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

class FitImportExport extends PHPUnit_Framework_TestCase {
	private function assertSlotsAre($fit, $key, $slottype, $slots, $ordermatters = true) {
		$fitslots = array();

		if(isset($fit[$key][$slottype])) {
			foreach($fit[$key][$slottype] as $m) {
				$fitslots[] = $m['typeid'];
			}
		}

		if(!$ordermatters) {
			sort($fitslots);
			sort($slots);
		}

		/* There are better, more efficient ways to compare these, but
		 * at least this method gives an useful error message if the
		 * assertion were to fail. */
		$this->assertSame(json_encode($slots), json_encode($fitslots));
	}

	private function assertDronesAre($fit, $drones, $ordermatters = true) {
		$fitdrones = array();

		foreach($fit['drones'] as $d) {
			if(!isset($fitdrones[$d['typeid']])) {
				$fitdrones[$d['typeid']] = array(0, 0);
			}

			$fitdrones[$d['typeid']] = array(
				$fitdrones[$d['typeid']][0] + $d['quantityinbay'],
				$fitdrones[$d['typeid']][1] + $d['quantityinspace'],
				);
		}

		if(!$ordermatters) {
			ksort($fitslots);
			ksort($slots);
		}

		$this->assertSame(json_encode($drones), json_encode($fitdrones));
	}

	/**
	 * @group fit
	 * @group import
	 */
	public function testDNAImport() {
		/* Exported from Pyfa 1.1.8 */
		$dna = '24692:2048;1:2364;1:11269;2:11325;3:5945;1:3578;1:2262;1:6173;1:7087;8:25894;3:2185;5:2488;5::';

		$errors = array();
		$fit = \Osmium\Fit\try_parse_fit_from_shipdna($dna, 'Foo', $errors);
		$this->assertSame('', implode('', $errors));
		$this->assertSame('Foo', $fit['metadata']['name']);
		$this->assertSame(24692, $fit['ship']['typeid']);
		$this->assertSlotsAre($fit, 'modules', 'high', array(7087, 7087, 7087, 7087, 7087, 7087, 7087, 7087));
		$this->assertSlotsAre($fit, 'modules', 'medium', array(5945, 3578, 2262, 6173));
		$this->assertSlotsAre($fit, 'modules', 'low', array(2048, 2364, 11269, 11269, 11325, 11325, 11325));
		$this->assertSlotsAre($fit, 'modules', 'rig', array(25894, 25894, 25894));
		$this->assertSlotsAre($fit, 'modules', 'subsystem', array());
		$this->assertSlotsAre($fit, 'charges', 'high', array());
		$this->assertSlotsAre($fit, 'charges', 'medium', array());
		$this->assertSlotsAre($fit, 'charges', 'low', array());
		$this->assertDronesAre($fit, array(2185 => array(0, 5), 2488 => array(5, 0)));
	}

	/**
	 * @group fit
	 * @group import
	 */
	public function testDNAImportWithCharges() {
		/* Exported from Osmium 0.1-rc2 (hell yeah, only one supporting charges in DNA at this time) */
		$dna = '17920:3057;3:3578;1:5051;1:12820;3:32014;2::';

		$errors = array();
		$fit = \Osmium\Fit\try_parse_fit_from_shipdna($dna, 'Foo', $errors);
		$this->assertSame('', implode('', $errors));
		$this->assertSame('Foo', $fit['metadata']['name']);
		$this->assertSame(17920, $fit['ship']['typeid']);
		$this->assertSlotsAre($fit, 'modules', 'high', array(3057, 3057, 3057));
		$this->assertSlotsAre($fit, 'modules', 'medium', array(3578, 5051));
		$this->assertSlotsAre($fit, 'modules', 'low', array());
		$this->assertSlotsAre($fit, 'modules', 'rig', array());
		$this->assertSlotsAre($fit, 'modules', 'subsystem', array());
		$this->assertSlotsAre($fit, 'charges', 'high', array(12820, 12820, 12820));
		$this->assertSlotsAre($fit, 'charges', 'medium', array(32014, 32014));
		$this->assertSlotsAre($fit, 'charges', 'low', array());
		$this->assertDronesAre($fit, array());
	}

	/**
	 * @group fit
	 * @group import
	 */
	public function testMixedDronesDNA() {
		$dna = '19720:23563;2:23559;2:23563;3::';

		$errors = array();
		$fit = \Osmium\Fit\try_parse_fit_from_shipdna($dna, 'Foo', $errors);
		$this->assertSame(array(), $errors);
	}
}
