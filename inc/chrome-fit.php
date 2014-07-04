<?php
/* Osmium
 * Copyright (C) 2012, 2013, 2014 Romain "Artefact2" Dalmaso <artefact2@gmail.com>
 * Copyright (C) 2013 Josiah Boning <jboning@gmail.com>
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

namespace Osmium\Chrome;



function get_remotes() {
	static $types = array(
		'hull' => [ "Raw hull hitpoints repaired per second", 524 ],
		'armor' => [ "Raw armor hitpoints repaired per second", 523 ],
		'shield' => [ "Raw shield hitpoints transferred per second", 405 ],
		'capacitor' => [ "Giga joules transferred per second", 529 ],
		'neutralization' => [ "Giga joules neutralized per second", 533 ],
		'leech' => [ "Giga joules leeched per second (in the best case)", 530 ],
	);

	return $types;
}

/** @deprecated */
function print_formatted_attribute_category($identifier, $title, $titledata, $titleclass, $contents) {
	if($titleclass) $titleclass = " class='$titleclass'";
	echo "<section id='$identifier'>\n";
	echo "<h4$titleclass>$title <small>$titledata</small></h4>\n";
	echo "<div>\n$contents</div>\n";
	echo "</section>\n";
}

function print_formatted_incoming(&$fit, $relative, $ehp) {
	ob_start();

	$incoming = \Osmium\Fit\get_incoming($fit);
	$best = 0;
	$fbest = '';

	foreach(get_remotes() as $t => $info) {
		if(!isset($incoming[$t])) continue;
		list($amount, $unit) = $incoming[$t];
		list($title, $img) = $info;
		if($amount < 1e-300) continue;

		if(isset($ehp[$t]) && $unit === 'HP') {
			$avgresonance = 0;
			foreach($fit['damageprofile']['damages'] as $type => $dmg) {
				$avgresonance += $dmg * $ehp[$t]['resonance'][$type];
			}

			$amount /= $avgresonance;
			$unit = 'EHP';
			$title = str_replace('Raw ', 'Effective ', $title);
		}

		$unit .= '/s';
		$amount *= 1000;
		$famount = format($amount).' '.$unit;

		if($amount > $best) {
			$best = $amount;
			$fbest = "<span title='{$title}'>{$t}: {$famount}</span>";
		}

		echo "<p title='{$title}'>"
			."<img src='//image.eveonline.com/Type/{$img}_64.png' alt='{$t}' />"
			.$famount
			."</p>\n";
	}

	$contents = ob_get_clean();

	if($contents) {
		print_formatted_attribute_category(
			'incoming', 'Incoming', $fbest,
			'', $contents
		);
	}
}

function print_formatted_outgoing(&$fit, $relative) {
	ob_start();

	$outgoing = \Osmium\Fit\get_outgoing($fit);
	$best = 0;
	$fbest = '';

	foreach(get_remotes() as $t => $info) {
		if(!isset($outgoing[$t])) continue;
		list($amount, $unit) = $outgoing[$t];
		list($title, $img) = $info;
		if($amount < 1e-300) continue;

		$unit .= '/s';
		$amount *= 1000;
		$famount = format($amount).' '.$unit;

		if($amount > $best) {
			$best = $amount;
			$fbest = "<span title='{$title}'>{$t}: {$famount}</span>";
		}

		echo "<p title='{$title}'>"
			."<img src='//image.eveonline.com/Type/{$img}_64.png' alt='{$t}' />"
			.$famount
			."</p>\n";
	}

	$contents = ob_get_clean();

	if($contents) {
		print_formatted_attribute_category(
			'outgoing', 'Outgoing', $fbest,
			'', $contents
		);
	}
}
