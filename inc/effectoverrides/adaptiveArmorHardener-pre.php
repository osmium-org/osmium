<?php

/* Pretend there is no incoming damage and the hardener keeps the
 * constant -15% resists to all four types. */

/* The attributes used are the same as a Damage Control, which
 * suggests its bonus may be stacking penalized when using one. */

/* After testing on the Singularity server, revision 382269, it
 * appears that the hardener is not penalized by "regular" armor
 * hardeners (like EANMs), but it is penalized by a Damage Control. So
 * the expression below is correct. */

return array (
	'op' => 'COMBINE',
	'arg1' => 
	array (
		'op' => 'COMBINE',
		'arg1' => 
		array (
			'op' => 'AIM',
			'arg1' => 
			array (
				'op' => 'EFF',
				'arg1' => 
				array (
					'op' => 'DEFASSOCIATION',
					'value' => 'PreMul',
					),
				'arg2' => 
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
						'name' => 'armorEmDamageResonance',
						'attributeid' => '267',
						),
					),
				),
			'arg2' => 
			array (
				'op' => 'DEFATTRIBUTE',
				'name' => 'armorEmDamageResonance',
				'attributeid' => '267',
				),
			),
		'arg2' => 
		array (
			'op' => 'AIM',
			'arg1' => 
			array (
				'op' => 'EFF',
				'arg1' => 
				array (
					'op' => 'DEFASSOCIATION',
					'value' => 'PreMul',
					),
				'arg2' => 
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
						'name' => 'armorExplosiveDamageResonance',
						'attributeid' => '268',
						),
					),
				),
			'arg2' => 
			array (
				'op' => 'DEFATTRIBUTE',
				'name' => 'armorExplosiveDamageResonance',
				'attributeid' => '268',
				),
			),
		),
	'arg2' => 
	array (
		'op' => 'COMBINE',
		'arg1' => 
		array (
			'op' => 'AIM',
			'arg1' => 
			array (
				'op' => 'EFF',
				'arg1' => 
				array (
					'op' => 'DEFASSOCIATION',
					'value' => 'PreMul',
					),
				'arg2' => 
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
						'name' => 'armorKineticDamageResonance',
						'attributeid' => '269',
						),
					),
				),
			'arg2' => 
			array (
				'op' => 'DEFATTRIBUTE',
				'name' => 'armorKineticDamageResonance',
				'attributeid' => '269',
				),
			),
		'arg2' => 
		array (
			'op' => 'AIM',
			'arg1' => 
			array (
				'op' => 'EFF',
				'arg1' => 
				array (
					'op' => 'DEFASSOCIATION',
					'value' => 'PreMul',
					),
				'arg2' => 
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
						'name' => 'armorThermalDamageResonance',
						'attributeid' => '270',
						),
					),
				),
			'arg2' => 
			array (
				'op' => 'DEFATTRIBUTE',
				'name' => 'armorThermalDamageResonance',
				'attributeid' => '270',
				),
			),
		),
	);