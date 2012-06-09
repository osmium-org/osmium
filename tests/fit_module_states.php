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



class FitModuleStates extends PHPUnit_Framework_TestCase {
	/**
	 * @group fit
	 * @group engine
	 */
	public function testModuleStates() {
		static $overloadable = 2281; /* Invulnerability Field II */
		static $otype = 'medium';

		static $passive = 1422; /* Shield Power Relay II */
		static $ptype = 'low';

		\Osmium\Fit\create($fit);
		\Osmium\Fit\select_ship($fit, 24698); /* Drake */
		\Osmium\Fit\add_module($fit, 0, $overloadable);
		\Osmium\Fit\add_module($fit, 1, $passive);

		/* Default state for activable modules is active */
		$this->assertSame(\Osmium\Fit\STATE_ACTIVE, 
		                  \Osmium\Fit\get_module_state_by_typeid($fit, 0, $overloadable));

		/* Default state for passive modules is online */
		$this->assertSame(\Osmium\Fit\STATE_ONLINE, 
		                  \Osmium\Fit\get_module_state_by_typeid($fit, 1, $passive));

		/* Try to switch states */
		$ostates = array(\Osmium\Fit\STATE_OFFLINE, \Osmium\Fit\STATE_ONLINE,
		                 \Osmium\Fit\STATE_ACTIVE, \Osmium\Fit\STATE_OVERLOADED);
		$pstates = array(\Osmium\Fit\STATE_OFFLINE, \Osmium\Fit\STATE_ACTIVE);

		foreach($ostates as $s) {
			\Osmium\Fit\change_module_state_by_typeid($fit, 0, $overloadable, $s);
			$this->assertSame($s, \Osmium\Fit\get_module_state_by_location($fit, $otype, 0));

		}

		foreach($pstates as $s) {
			\Osmium\Fit\change_module_state_by_typeid($fit, 1, $passive, $s);
			$this->assertSame($s, \Osmium\Fit\get_module_state_by_location($fit, $ptype, 1));

		}

		foreach($ostates as $s) {
			\Osmium\Fit\change_module_state_by_location($fit, $otype, 0, $s);
			$this->assertSame($s, \Osmium\Fit\get_module_state_by_typeid($fit, 0, $overloadable));

		}

		foreach($pstates as $s) {
			\Osmium\Fit\change_module_state_by_location($fit, $ptype, 1, $s);
			$this->assertSame($s, \Osmium\Fit\get_module_state_by_typeid($fit, 1, $passive));

		}

		\Osmium\Fit\destroy($fit);
	}

	/**
	 * @group fit
	 * @group engine
	 */
	public function testStateToggling() {
		static $overloadable = 10842; /* X-Large Shield Booster II */
		static $oslot = 'medium';

		static $activable = 2048; /* Damage Control II */
		static $aslot = 'low';

		static $passive = 2553; /* EM Ward Amplifier II */
		static $pslot = 'medium';

		\Osmium\Fit\create($fit);
		\Osmium\Fit\select_ship($fit, 24694); /* Maelstrom */
		\Osmium\Fit\add_module($fit, 0, $overloadable);
		\Osmium\Fit\add_module($fit, 1, $activable);
		\Osmium\Fit\add_module($fit, 2, $passive);

		\Osmium\Fit\toggle_module_state($fit, 0, $overloadable, true);
		$this->assertSame(\Osmium\Fit\STATE_OVERLOADED, $fit['modules'][$oslot][0]['state']);
		\Osmium\Fit\toggle_module_state($fit, 0, $overloadable, true);
		$this->assertSame(\Osmium\Fit\STATE_OFFLINE, $fit['modules'][$oslot][0]['state']);
		\Osmium\Fit\toggle_module_state($fit, 0, $overloadable, true);
		$this->assertSame(\Osmium\Fit\STATE_ONLINE, $fit['modules'][$oslot][0]['state']);
		\Osmium\Fit\toggle_module_state($fit, 0, $overloadable, true);
		$this->assertSame(\Osmium\Fit\STATE_ACTIVE, $fit['modules'][$oslot][0]['state']);
		\Osmium\Fit\toggle_module_state($fit, 0, $overloadable, true);
		$this->assertSame(\Osmium\Fit\STATE_OVERLOADED, $fit['modules'][$oslot][0]['state']);

		\Osmium\Fit\toggle_module_state($fit, 0, $overloadable, false);
		$this->assertSame(\Osmium\Fit\STATE_ACTIVE, $fit['modules'][$oslot][0]['state']);
		\Osmium\Fit\toggle_module_state($fit, 0, $overloadable, false);
		$this->assertSame(\Osmium\Fit\STATE_ONLINE, $fit['modules'][$oslot][0]['state']);
		\Osmium\Fit\toggle_module_state($fit, 0, $overloadable, false);
		$this->assertSame(\Osmium\Fit\STATE_OFFLINE, $fit['modules'][$oslot][0]['state']);
		\Osmium\Fit\toggle_module_state($fit, 0, $overloadable, false);
		$this->assertSame(\Osmium\Fit\STATE_OVERLOADED, $fit['modules'][$oslot][0]['state']);
		\Osmium\Fit\toggle_module_state($fit, 0, $overloadable, false);
		$this->assertSame(\Osmium\Fit\STATE_ACTIVE, $fit['modules'][$oslot][0]['state']);

		\Osmium\Fit\toggle_module_state($fit, 1, $activable, true);
		$this->assertSame(\Osmium\Fit\STATE_OFFLINE, $fit['modules'][$aslot][1]['state']);
		\Osmium\Fit\toggle_module_state($fit, 1, $activable, true);
		$this->assertSame(\Osmium\Fit\STATE_ONLINE, $fit['modules'][$aslot][1]['state']);
		\Osmium\Fit\toggle_module_state($fit, 1, $activable, true);
		$this->assertSame(\Osmium\Fit\STATE_ACTIVE, $fit['modules'][$aslot][1]['state']);
		\Osmium\Fit\toggle_module_state($fit, 1, $activable, true);
		$this->assertSame(\Osmium\Fit\STATE_OFFLINE, $fit['modules'][$aslot][1]['state']);

		\Osmium\Fit\toggle_module_state($fit, 1, $activable, false);
		$this->assertSame(\Osmium\Fit\STATE_ACTIVE, $fit['modules'][$aslot][1]['state']);
		\Osmium\Fit\toggle_module_state($fit, 1, $activable, false);
		$this->assertSame(\Osmium\Fit\STATE_ONLINE, $fit['modules'][$aslot][1]['state']);
		\Osmium\Fit\toggle_module_state($fit, 1, $activable, false);
		$this->assertSame(\Osmium\Fit\STATE_OFFLINE, $fit['modules'][$aslot][1]['state']);
		\Osmium\Fit\toggle_module_state($fit, 1, $activable, false);
		$this->assertSame(\Osmium\Fit\STATE_ACTIVE, $fit['modules'][$aslot][1]['state']);

		\Osmium\Fit\toggle_module_state($fit, 2, $passive, true);
		$this->assertSame(\Osmium\Fit\STATE_OFFLINE, $fit['modules'][$pslot][2]['state']);
		\Osmium\Fit\toggle_module_state($fit, 2, $passive, true);
		$this->assertSame(\Osmium\Fit\STATE_ONLINE, $fit['modules'][$pslot][2]['state']);
		\Osmium\Fit\toggle_module_state($fit, 2, $passive, true);
		$this->assertSame(\Osmium\Fit\STATE_OFFLINE, $fit['modules'][$pslot][2]['state']);

		\Osmium\Fit\toggle_module_state($fit, 2, $passive, false);
		$this->assertSame(\Osmium\Fit\STATE_ONLINE, $fit['modules'][$pslot][2]['state']);
		\Osmium\Fit\toggle_module_state($fit, 2, $passive, false);
		$this->assertSame(\Osmium\Fit\STATE_OFFLINE, $fit['modules'][$pslot][2]['state']);
		\Osmium\Fit\toggle_module_state($fit, 2, $passive, false);
		$this->assertSame(\Osmium\Fit\STATE_ONLINE, $fit['modules'][$pslot][2]['state']);

		\Osmium\Fit\destroy($fit);
	}
}
