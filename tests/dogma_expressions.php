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

class DogmaExpressions extends PHPUnit_Framework_TestCase {
	/** 
	 * @group expensive
	 * @group dogma
	 * @group engine
	 */
	public function testAllShips() {
		/* Do not use cached skill attributes, we actually want to
		 * test the code that generates the cache! */
		\Osmium\State\set_cache_enabled(false);
		\Osmium\Fit\create($fit);

		$q = \Osmium\Db\query('SELECT typeid FROM osmium.invships ORDER BY typeid ASC');
		while($r = \Osmium\Db\fetch_row($q)) {
			\Osmium\Fit\select_ship($fit, $r[0]);
		}

		\Osmium\Fit\destroy($fit);
		\Osmium\State\pop_cache_enabled();
	}

	/**
	 * @group expensive
	 * @group dogma
	 * @group engine
	 */
	public function testAllModules() {
		\Osmium\Fit\create($fit);
		\Osmium\Fit\select_ship($fit, 644); /* Typhoon */

		$q = \Osmium\Db\query('SELECT typeid FROM osmium.invmodules ORDER BY typeid ASC');
		while($r = \Osmium\Db\fetch_row($q)) {
			\Osmium\Fit\add_module($fit, 0, $r[0]);
			\Osmium\Fit\remove_module($fit, 0, $r[0]);
		}

		\Osmium\Fit\destroy($fit);
	}

	/**
	 * @group expensive
	 * @group dogma
	 * @group engine
	 */
	public function testAllModulesWithCharges() {
		\Osmium\Fit\create($fit);
		\Osmium\Fit\select_ship($fit, 29984); /* Tengu */

		$q = \Osmium\Db\query('SELECT moduleid, chargeid FROM osmium.invcharges ORDER BY moduleid ASC, chargeid ASC');

		$fitted_module = null;
		while($r = \Osmium\Db\fetch_row($q)) {
			list($moduleid, $chargeid) = $r;
			if($moduleid !== $fitted_module) {
				/* add_module() will automatically remove the previous
				 * module if any */
				\Osmium\Fit\add_module($fit, 0, $moduleid);
				$fitted_module = $moduleid;

				/* The module type is need for add_charge() */
				$type = \Osmium\Fit\get_module_slottype($fit, $moduleid);
			}

			/* Same here, old charge will automatically be removed */
			\Osmium\Fit\add_charge($fit, $type, 0, $moduleid);
		}

		\Osmium\Fit\destroy($fit);
	}
}