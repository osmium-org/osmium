<?php

/* Just pretend there there is no cap booster charge in the cargohold,
 * make this module behave like a regular shield booster. Below is the
 * shieldBoosting effect: */

return array (
	'op' => 'INC',
	'arg1' => 
	array (
		'op' => 'ATT',
		'arg1' => 
		array (
			'op' => 'DEFENVIDX',
			'value' => 'Ship',
			),
		'arg2' => 
		array (
			'op' => 'DEFATTRIBUTE',
			'name' => 'shieldCharge',
			'attributeid' => '264',
			),
		),
	'arg2' => 
	array (
		'op' => 'DEFATTRIBUTE',
		'name' => 'shieldBonus',
		'attributeid' => '68',
		),
	);