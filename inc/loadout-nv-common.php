<?php
/* Osmium
 * Copyright (C) 2014 Romain "Artefact2" Dalmaso <artefact2@gmail.com>
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

namespace Osmium\LoadoutCommon;

class Page extends \Osmium\DOM\Page {

	function finalizeWithFit(\Osmium\DOM\RenderContext $ctx, array $fit, $clftoken) {
		/* Required by jsPlumb on some browsers. */
		$this->html->setAttribute('xmlns:v', 'urn:schemas-microsoft-com:vml');

		$this->data['cdatastaticver'] = \Osmium\CLIENT_DATA_STATICVER;
		$this->data['staticver'] = \Osmium\STATICVER;

		$this->data['token'] = \Osmium\State\get_token();
		$this->data['clftoken'] = $clftoken;

		$this->data['clf'] = \Osmium\Fit\export_to_common_loadout_format(
			$fit,
			\Osmium\Fit\CLF_EXPORT_MINIFY
			| \Osmium\Fit\CLF_EXPORT_EXTRA_PROPERTIES
			| \Osmium\Fit\CLF_EXPORT_INTERNAL_PROPERTIES
		);

		$this->data['skillsets'] = \Osmium\Fit\get_available_skillset_names_for_account();

		$cdp = \Osmium\State\get_setting('custom_damage_profiles', []);
		$this->data['customdamageprofiles'] = $cdp === [] ? new \stdClass() : $cdp;

		$this->data['fattribshidden'] = \Osmium\State\get_setting('fattribs_hidden', []);

		$this->finalize($ctx);
	}

	function makeRemoteSection(array $fit, $readonly = false) {
		$section = $this->element('section#remote');

		$fleet = $section->appendCreate('section#fleet');
		$fleet->appendCreate('h2', 'Fleet boosters');

		if(!$readonly) {
			$fleet->appendCreate('p', [
				'The fittings you use as boosters will be visible by anyone who can view this loadout.',
				[ 'br' ],
				'The skills will be reset to "All V" when saving the loadout.',
			]);
		} else {
			$fleet->appendCreate('p', [
				'To change fleet boosters or projected fittings, fork the loadout first.'
			]);
		}

		$tbody = $fleet->appendCreate('form')->appendCreate('table')->appendCreate('tbody');

		foreach([ 'fleet', 'wing', 'squad' ] as $ft) {
			$tr = $tbody->appendCreate('tr.booster#'.$ft.'booster', [ 'data-type' => $ft ]);

			$td = $tr->appendCreate('td', [ 'rowspan' => '3' ]);
			$checkbox = $td->appendCreate('input', [
				'type' => 'checkbox',
				'id' => $ft.'_enabled',
				'name' => $ft.'_enabled',
				'class' => 'enabled '.$ft,
			]);

			$td ->appendCreate('label', [ 'for' => $ft.'_enabled' ])
				->appendCreate('strong', ' '.ucfirst($ft).' booster');

			$tr->appendCreate('td')->appendCreate('label', [ 'for' => $ft.'_skillset', 'Use skills: ' ]);
			$select = $tr->appendCreate('td')->appendCreate('select', [
				'name' => $ft.'_skillset',
				'id' => $ft.'_skillset',
				'class' => 'skillset '.$ft,
			]);

			$tr = $tbody->appendCreate('tr', [ 'data-type' => $ft ]);
			$tr->appendCreate('td', [ 'rowspan' => '2' ])->appendCreate('label', [
				'for' => $ft.'_fit',
				'Use fitting: '
			]);

			$fittinginput = $tr->appendCreate('td')->appendCreate('input', [
				'type' => 'text',
				'name' => $ft.'_fit',
				'id' => $ft.'_fit',
				'class' => 'fit '.$ft,
				'placeholder' => 'Loadout URI, DNA string, gzclf:// data',
			]);

			$td = $tbody->appendCreate('tr', [ 'data-type' => $ft ])->appendCreate('td');

			if(!$readonly) {
				$td->appendCreate('input', [
					'type' => 'button',
					'class' => 'set '.$ft,
					'value' => 'Set fit',
				]);
				$td->appendCreate('input', [
					'type' => 'button',
					'class' => 'clear '.$ft,
					'value' => 'Clear fit',
				]);
			} else {
				$fittinginput->setAttribute('readonly', 'readonly');
			}

			if(isset($fit['fleet'][$ft])) {
				$checkbox->setAttribute('checked', 'checked');
				$fittinginput->setAttribute('value', $fit['fleet'][$ft]['__id']);

				$ss = isset($fit['fleet'][$ft]['skillset']['name']) ?
					$fit['fleet'][$ft]['skillset']['name'] : 'All V';
				$select->appendCreate('option', [ 'value' => $ss, $ss ]);

				if($readonly && $fit['fleet'][$ft]['__id'] !== '(empty fitting)') {
					$td->appendCreate('a', [
						'o-rel-href' => explode('?', $_SERVER['REQUEST_URI'])[0].'/booster/'.$ft,
						'View fitting'
					]);
				}
			} else {
				$select->appendCreate('option', [ 'value' => 'All V', 'All V' ]);
			}
		}

		$projected = $section->appendCreate('section#projected');
		$h2 = $projected->appendCreate('h2', 'Projected effects');
		$form = $h2->appendCreate('form', [ 'method' => 'get', 'action' => '?' ]);

		if(!$readonly) {
			$form->appendCreate('input', [
				'type' => 'button',
				'value' => 'Add projected fit',
				'id' => 'createprojected',
			]);
		}

		$form->appendCreate('input', [
			'type' => 'button',
			'value' => 'Toggle fullscreen',
			'id' => 'projectedfstoggle',
		]);
		$projected->appendCreate('p#rearrange', [
			'Press ESC or click background to exit',
			[ 'br' ],
			'Rearrange loadouts: ',
			[ 'a#rearrange-grid', 'grid' ],
			', ',
			[ 'a#rearrange-circle', 'circle' ],
		]);
		$projected->appendCreate('form#projected-list', [ 'method' => 'get', 'action' => '?' ]);

		return $section;
	}

}
