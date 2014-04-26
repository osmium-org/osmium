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

		$this->snippets = array_merge([
			'localstorage_fallback',
			'tabs',
			'modal',
			'context_menu',
			'loadout_common',
			'graph_common',
			'sprite',
			'show_info',
			'formatted_attributes',
			'capacitor',
			'new_loadout-fattribs',
		], $this->snippets);

		$this->finalize($ctx);

		$snippets = (new \DOMXPath($this))->query('//script[@id=\'snippets\']')->item(0);

		/* If these scripts change, update the license info in about.php */

		$snippets->before($this->element('script', [
			'type' => 'application/javascript',
			'src' => $ctx->relative.'/static-1/jquery.jsPlumb-1.5.4-min.js',
		]));

		$snippets->before($this->element('script', [
			'type' => 'application/javascript',
			'src' => $ctx->relative.'/static-1/rawdeflate.min.js',
		]));
	}

}
