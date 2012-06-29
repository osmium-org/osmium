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

class FitDbCommit extends PHPUnit_Framework_TestCase {
	private $loadoutid = null;
	private $hash = null;

	/**
	 * @group fit
	 * @group database
	 */
	public function testCommitLoadout() {
		\Osmium\Fit\create($fit);

		/* Make a very simple loadout with several presets, charge
		 * presets and drone presets. */

		\Osmium\Fit\select_ship($fit, 24698);

		$add_plumbing = function(&$fit) {
			/* Rigs */
			\Osmium\Fit\add_module($fit, 0, 31790);
			\Osmium\Fit\add_module($fit, 1, 31790);
			\Osmium\Fit\add_module($fit, 2, 31718);

			/* Lows */
			\Osmium\Fit\add_module($fit, 0, 2048);
			\Osmium\Fit\add_module($fit, 1, 2605);
			\Osmium\Fit\add_module($fit, 2, 22291);
			\Osmium\Fit\add_module($fit, 3, 22291);

			/* Mediums */
			\Osmium\Fit\add_module($fit, 0, 5975);
			\Osmium\Fit\add_module($fit, 1, 2281);
			\Osmium\Fit\add_module($fit, 2, 3841);
			\Osmium\Fit\add_module($fit, 3, 19814);
			\Osmium\Fit\add_module($fit, 4, 5399);
			\Osmium\Fit\add_module($fit, 5, 4031);
		};

		$presets = array(
			array('Meta 4 launchers', 8105, 
			      array('Scourge missiles' => 209,
			            'Inferno missiles' => 208,
			            'Scourge FOF missiles' => 1818)
				),
			array('Tech II launchers', 2410,
			      array('Scourge missiles' => 209,
			            'Scourge precision missiles' => 24513,
			            'Scourge fury missiles' => 2629)
				)
			);

		$firstpreset = true;
		foreach($presets as $preset) {
			list($name, $launcherid, $chargepresets) = $preset;

			if($firstpreset) {
				$firstpreset = false;
				$fit['modulepresetname'] = $name;
			} else {
				$presetid = \Osmium\Fit\create_preset($fit, $name, '');
				\Osmium\Fit\use_preset($fit, $presetid);
			}

			for($i = 0; $i < 7; ++$i) {
				\Osmium\Fit\add_module($fit, $i, $launcherid);
			}

			$add_plumbing($fit);
			
			$firstchargepreset = true;
			foreach($chargepresets as $name => $chargeid) {
				if($firstchargepreset) {
					$firstchargepreset = false;
					$fit['chargepresetname'] = $name;
				} else {
					$cpid = \Osmium\Fit\create_charge_preset($fit, $name, '');
					\Osmium\Fit\use_charge_preset($fit, $cpid);
				}

				for($i = 0; $i < 7; ++$i) {
					\Osmium\Fit\add_charge($fit, 'high', $i, $chargeid);
				}
			}
		}

		$fit['dronepresetname'] = 'ECM drones';
		\Osmium\Fit\add_drone($fit, 23707, 0, 5);

		$dpid = \Osmium\Fit\create_drone_preset($fit, 'Combat drones', '');
		\Osmium\Fit\use_drone_preset($fit, $dpid);
		\Osmium\Fit\add_drone($fit, 2488, 0, 5);

		$fit['metadata']['name'] = 'Test loadout (PHPUnit-generated)';
		$fit['metadata']['description'] = '(Not intended for public usage.)';
		$fit['metadata']['view_permission'] = \Osmium\Fit\VIEW_EVERYONE;
		$fit['metadata']['edit_permission'] = \Osmium\Fit\EDIT_OWNER_ONLY;
		$fit['metadata']['visibility'] = \Osmium\Fit\VISIBILITY_PRIVATE;
		$fit['metadata']['tags'] = array('test', 'do-not-use');

		$accountq = \Osmium\Db\query('SELECT accountid FROM osmium.accounts ORDER BY accountid ASC LIMIT 1');
		if($accountq === false) {
			$this->markTestSkipped('Database is probably not running.');
			return;
		}
		$row = \Osmium\Db\fetch_row($accountq);
		if($row === false) {
			$this->markTestSkipped('This test requires at least one registered account.');
			return;
		}
		$accountid = $row[0];

		\Osmium\Fit\commit_loadout($fit, $accountid, $accountid);
		$this->assertSame('', \Osmium\Db\last_error());

		$revision = $fit['metadata']['revision'];
		$hash = $fit['metadata']['hash'];
		$loadoutid = $fit['metadata']['loadoutid'];

		$this->loadoutid = $loadoutid;
		$this->hash = $hash;

		/* Test that committing twice the same loadout does not insert
		 * a new fitting or history entry */
		\Osmium\Fit\commit_loadout($fit, $accountid, $accountid);
		$this->assertsame('', \Osmium\Db\last_error());
		$this->assertSame((int)$revision, (int)$fit['metadata']['revision']);
		$this->assertSame($hash, $fit['metadata']['hash']);
		$this->assertSame($loadoutid, $fit['metadata']['loadoutid']);

		/* Putting and getting $fits from the DB should not change
		 * hashes. */
		\Osmium\State\set_cache_enabled(false);
		$dbfit = \Osmium\Fit\get_fit($loadoutid);
		$dbfitrev = \Osmium\Fit\get_fit($loadoutid, $revision);
		\Osmium\State\pop_cache_enabled();

		$this->assertSame(\Osmium\Fit\get_hash($fit), \Osmium\Fit\get_hash($dbfit));
		$this->assertSame(\Osmium\Fit\get_hash($fit), \Osmium\Fit\get_hash($dbfitrev));
	}

	protected function tearDown() {
		$lid = $this->loadoutid;
		$hash = $this->hash;

		if($lid === null && $hash === null) return;

		\Osmium\Db\query('BEGIN;');
		\Osmium\Db\query_params('DELETE FROM osmium.accountfavorites WHERE loadoutid = $1', array($lid));
		\Osmium\Db\query_params('DELETE FROM osmium.loadouthistory WHERE loadoutid = $1', array($lid));
		\Osmium\Db\query_params('DELETE FROM osmium.loadouts WHERE loadoutid = $1', array($lid));
		\Osmium\Db\query_params('DELETE FROM osmium.fittingcharges WHERE fittinghash = $1', array($hash));
		\Osmium\Db\query_params('DELETE FROM osmium.fittingchargepresets WHERE fittinghash = $1', array($hash));
		\Osmium\Db\query_params('DELETE FROM osmium.fittingmodules WHERE fittinghash = $1', array($hash));
		\Osmium\Db\query_params('DELETE FROM osmium.fittingpresets WHERE fittinghash = $1', array($hash));
		\Osmium\Db\query_params('DELETE FROM osmium.fittingdrones WHERE fittinghash = $1', array($hash));
		\Osmium\Db\query_params('DELETE FROM osmium.fittingdronepresets WHERE fittinghash = $1', array($hash));
		\Osmium\Db\query_params('DELETE FROM osmium.fittingtags WHERE fittinghash = $1', array($hash));
		\Osmium\Db\query_params('DELETE FROM osmium.fittings WHERE fittinghash = $1', array($hash));
		\Osmium\Db\query('COMMIT;');
		\Osmium\Search\unindex($lid);
	}
}
