<?php
/* Osmium
 * Copyright (C) 2012, 2013, 2014 Romain "Artefact2" Dalmaso <artefact2@gmail.com>
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

namespace Osmium\Page\Settings;

require __DIR__.'/../inc/root.php';

const MASK = '********';

$p = new \Osmium\DOM\Page();
$ctx = new \Osmium\DOM\RenderContext();
$p->title = 'Account settings';
$ctx->relative = '.';

\Osmium\State\assume_logged_in($ctx->relative);
$a = \Osmium\State\get_state('a');

if(isset($_POST['unverify'])) {
	\Osmium\State\unverify_account($a['accountid']);
	\Osmium\State\do_post_login($a['accountname']);
	$a = \Osmium\State\get_state('a');
	unset($_POST['key_id']);
	unset($_POST['v_code']);
} else if(isset($_POST['verify'])) {
	$key_id = $_POST['key_id'];
	$v_code = $_POST['v_code'];

	if(isset($a['verificationcode']) && substr($v_code, 0, strlen(MASK)) === MASK
	   && strlen($v_code) == strlen(MASK) + 4) {
		$v_code = $a['verificationcode'];
	}

	$verified = \Osmium\State\register_eve_api_key_account_auth(
		$a['accountid'], $key_id, $v_code,
		$etype, $estr
	);

	if($verified === false) {
		$p->formerrors['key_id'][] = '('.$etype.') '.$estr;
	} else {
		\Osmium\State\do_post_login($a['accountname']);
		$a = \Osmium\State\get_state('a');
	}

	if(isset($a['notwhitelisted']) && $a['notwhitelisted']) {
		if(\Osmium\State\check_whitelist($a)) {
			unset($a['notwhitelisted']);
			\Osmium\State\put_state('a', $a);
		}
	}
} else {
	if(isset($a['keyid'])) {
		$_POST['key_id'] = $a['keyid'];
		$_POST['v_code'] = MASK.substr($a['verificationcode'], -4);
	}
}

$div = $p->content->appendCreate('div#account_settings');

$ul = $div->appendCreate('ul.tindex');
$ul->appendCreate('li')->appendCreate('a', [ 'href' => '#s_changepw', 'Change passphrase' ]);
$ul->appendCreate('li')->appendCreate('a', [ 'href' => '#s_apiauth', 'Account status' ]);
$ul->appendCreate('li')->appendCreate('a', [ 'href' => '#s_characters', 'Characters and skills' ]);



$section = $div->appendCreate('section#s_changepw');
$section->appendCreate('h1', 'Change passphrase');

if(isset($_POST['curpw'])) {
	$cur = $_POST['curpw'];
	$new = $_POST['newpw'];

	if($new !== $_POST['newpw2']) {
		$p->formerrors['newpw2'][] = 'The two passphrases are not equal.';
	} else if(($s = \Osmium\State\is_password_sane($new)) !== true) {
		$p->formerrors['newpw'][] = $s;
	} else {
		$accountid = \Osmium\State\get_state('a')['accountid'];
		$apw = \Osmium\Db\fetch_row(
			\Osmium\Db\query_params(
				'SELECT passwordhash FROM osmium.accounts
				WHERE accountid = $1',
				array($accountid)
			)
		)[0];

		if(!\Osmium\State\check_password($cur, $apw)) {
			$p->formerrors['curpw'][] = [
				'Incorrect passphrase. If you forgot your passphrase, sign out and use the ',
				[ 'a', [ 'o-rel-href' => '/resetpassword', 'reset passphrase' ] ],
				' page.',
			];
		} else {
			$newhash = \Osmium\State\hash_password($new);
			\Osmium\Db\query_params(
				'UPDATE osmium.accounts SET passwordhash = $1
				WHERE accountid = $2',
				array($newhash, $accountid)
			);

			$section->appendCreate('p.notice_box', 'Passphrase was successfully changed.');
		}
	}
}

$tbody = $section
	->appendCreate('o-form', [ 'action' => '#s_changepw', 'method' => 'post' ])
	->appendCreate('table')
	->appendCreate('tbody')
	;

$tbody->append($p->makeFormInputRow('password', 'curpw', 'Current passphrase'));
$tbody->append($p->makeFormInputRow('password', 'newpw', 'New passphrase'));
$tbody->append($p->makeFormInputRow('password', 'newpw2', [
	'New passphrase',
	[ 'br' ],
	[ 'small', '(confirm)' ],
]));
$tbody->append($p->makeFormSubmitRow('Update passphrase'));



$section = $div->appendCreate('section#s_apiauth');
$section->appendCreate('h1', 'Account status');

if($a['apiverified'] === 't') {
	if(isset($a['notwhitelisted']) && $a['notwhitelisted']) {
		$section->appendCreate(
			'p.error_box',
			'Your API credentials are correct, but your character is not allowed to access this Osmium instance. Please contact the administrators if you have trouble authenticating your character.'
		);
	} else {
		$section->appendCreate('p.notice_box', 'Your account is API-verified.');
	}
} else {
	if(isset($a['notwhitelisted']) && $a['notwhitelisted']) {
		$section->appendCreate(
			'p.error_box',
			'You need to API-verify your account before you can access this Osmium instance.'
		);
	} else {
		$section->appendCreate('p.warning_box', [
			'Your account is ',
			[ 'strong', 'not' ],
			' API-verified.',
		]);
	}

	$section->appendCreate('p', 'Verifying your account with an API key will allow you to:');
	$ul = $section->appendCreate('ul');
	$ul->appendCreate('li', 'Share loadouts with your corporation or alliance, and access corporation or alliance-restricted loadouts;');
	$ul->appendCreate('li', 'Have your character name used instead of your nickname;');
	$ul->appendCreate('li', 'Reset your passphrase if you ever forget it.');
}

$section->appendCreate('h2', 'API credentials');
$section->append(\Osmium\State\make_api_link());

$tbody = $section
	->appendCreate('o-form', [ 'action' => '#s_apiauth', 'method' => 'post' ])
	->appendCreate('table')
	->appendCreate('tbody')
	;

$tbody->append($p->makeFormInputRow('text', 'key_id', 'API Key ID'));
$tbody->append($p->makeFormInputRow('text', 'v_code', 'Verification Code'));
$tbody->append($p->makeFormRawRow(
	'', [
		[ 'input', [ 'type' => 'submit', 'name' => 'verify', 'value' => 'Set API credentials' ] ],
		' ',
		[ 'input', [ 'type' => 'submit', 'name' => 'unverify', 'value' => 'Remove API credentials' ] ],
	]
));



$section = $div->appendCreate('section#s_characters');
$section->appendCreate('h1', 'Characters');
$section->appendCreate('p', 'Here you can add characters with custom skills and attributes to use in loadouts.');

$section->appendCreate('h3', 'Create a new character');

if(isset($_POST['newcharname']) && $_POST['newcharname'] !== '') {
	$name = $_POST['newcharname'];
	list($exists) = \Osmium\Db\fetch_row(\Osmium\Db\query_params(
		'SELECT COUNT(accountid) FROM osmium.accountcharacters
		WHERE accountid = $1 AND name = $2',
		array($a['accountid'], $name)
	));

	if($name == 'All 0' || $name == 'All V') {
		$p->formerrors['newcharname'][] = 'This name has a special meaning and cannot be used.';
	} else if($exists) {
		$p->formerrors['newcharname'][] = 'There is already a character with the same name.';
	} else {
		\Osmium\Db\query_params(
			'INSERT INTO osmium.accountcharacters (accountid, name)
			VALUES ($1, $2)',
			array($a['accountid'], $name)
		);
		unset($_POST['newcharname']);
	}
}

$tbody = $section
	->appendCreate('o-form', [ 'action' => '#s_characters', 'method' => 'post' ])
	->appendCreate('table')
	->appendCreate('tbody')
	;

$tbody->append($p->makeFormInputRow('text', 'newcharname', 'Character name'));
$tbody->append($p->makeFormSubmitRow('Create character'));


$section->appendCreate('h3', 'Manage characters');
$csapi = 'https://support.eveonline.com/api/Key/CreatePredefined/8';
$section->appendCreate('p', [
	'You can use any API key for importing skills and attributes as long as it has CharacterSheet access.',
	[ 'br' ],
	'Create an API key here: ',
	[ 'strong', [[ 'a', 'href' => $csapi, $csapi ]] ],
]);

if(isset($_POST['delete']) && is_array($_POST['delete'])) {
	reset($_POST['delete']);
	$cname = key($_POST['delete']);

	\Osmium\Db\query_params(
		'DELETE FROM osmium.accountcharacters
		WHERE accountid = $1 AND name = $2',
		array($a['accountid'], $cname)
	);
} else if(isset($_POST['fetch']) && is_array($_POST['fetch'])) {
	reset($_POST['fetch']);
	$cname = key($_POST['fetch']);

	list($keyid, $vcode) = \Osmium\Db\fetch_row(\Osmium\Db\query_params(
		'SELECT ac.keyid, eak.verificationcode
		FROM osmium.accountcharacters ac
		LEFT JOIN osmium.eveapikeys eak ON eak.owneraccountid = ac.accountid AND eak.keyid = ac.keyid
		WHERE ac.accountid = $1 AND ac.name = $2',
		array($a['accountid'], $cname)
	));

	$pkeyid = $_POST['keyid'][$cname];
	$pvcode = $_POST['vcode'][$cname];
	$piname = $_POST['iname'][$cname];

	if($pkeyid === '') $pkeyid = $keyid;
	if($pvcode === '' || (substr($pvcode, 0, strlen(MASK)) === MASK
	       && strlen($pvcode) == strlen(MASK) + 4)) {
		$pvcode = $vcode;
	}

	if((string)$pkeyid === '') {
		$p->formerrors['keyid['.$cname.']'][] = 'Must supply a key ID.';
	} else if((string)$pvcode === '') {
		$p->formerrors['vcode['.$cname.']'][] = 'Must supply a verification code.';
	} else {
		$keyinfo = \Osmium\EveApi\fetch(
			'/account/APIKeyInfo.xml.aspx', 
			[ 'keyID' => $pkeyid, 'vCode' => $pvcode ],
			null, $etype, $estr
		);

		if($keyinfo === null) {
			$section->appendCreate(
				'p.error_box',
				'An error occured while fetching API key info: ('.$etype.') '.$estr
			);
		} else if(!((int)$keyinfo->result->key['accessMask'] & \Osmium\State\CHARACTER_SHEET_ACCESS_MASK)) {
			$p->formerrors['keyid['.$cname.']'][] = 'No CharacterSheet access.';
		} else {
			$apicharid = null;

			foreach($keyinfo->result->key->rowset->row as $row) {
				if((string)$piname === '') {
					/* Use first character available */
					$piname = (string)$row['characterName'];
					$apicharid = (int)$row['characterID'];
					break;
				}

				if((string)$row['characterName'] === $piname) {
					$apicharid = (int)$row['characterID'];
					break;
				}
			}

			if($apicharid === null) {
				$p->formerrors['keyid['.$cname.']'][] = [ 'Character ', [ 'strong', $piname ], ' not found.' ];
			} else if(\Osmium\State\register_eve_api_key($a['accountid'], $pkeyid, $pvcode, $etype, $estr) === false) {
				$p->formerrors['keyid['.$cname.']'][] = '('.$etype.') '.$estr;
			} else {
				\Osmium\Db\query_params(
					'UPDATE osmium.accountcharacters
					SET keyid = $1, importname = $2
					WHERE accountid = $3 AND name = $4',
					array(
						$pkeyid,
						$piname,
						$a['accountid'],
						$cname,
					)
				);

				$sheet = \Osmium\EveApi\fetch(
					'/char/CharacterSheet.xml.aspx',
					array(
						'keyID' => $pkeyid,
						'vCode' => $pvcode,
						'characterID' => $apicharid,
					),
					null, $etype, $estr
				);

				if($sheet === false) {
					$section->appendCreate(
						'p.error_box',
						'An error occured while fetching character sheet: ('.$etype.') '.$estr);
				} else {
					/* Update skills */
					$skills = array();
					foreach($sheet->result->rowset as $rowset) {
						if(!isset($rowset['name']) || (string)$rowset['name'] !== 'skills') continue;

						foreach($rowset->row as $row) {
							$skills[(string)$row['typeID']] = (int)$row['level'];
						}

						break;
					}

					ksort($skills);
					\Osmium\Db\query_params(
						'UPDATE osmium.accountcharacters SET importedskillset = $1, lastimportdate = $2
						WHERE accountid = $3 AND name = $4',
						array(
							json_encode($skills),
							time(),
							$a['accountid'],
							$cname,
						));

					/* Update attributes */
					$attribs = [
						'perception' => null,
						'willpower' => null,
						'intelligence' => null,
						'memory' => null,
						'charisma' => null,
					];
					foreach($attribs as $attr => &$v) {
						$val = (int)$sheet->result->attributes->$attr;
						if(isset($sheet->result->attributeEnhancers->{$attr.'Bonus'}->augmentatorValue)) {
							$val += (int)$sheet->result->attributeEnhancers->{$attr.'Bonus'}->augmentatorValue;
						}

						$v = $attr.' = '.$val;
					}
					\Osmium\Db\query_params(
						'UPDATE osmium.accountcharacters SET
						'.implode(', ', $attribs).'
						WHERE accountid = $1 AND name = $2',
						array($a['accountid'], $cname)
					);
				}
			}
		}
	}
} else if(isset($_POST['edit']) && is_array($_POST['edit'])) {
	reset($_POST['edit']);
	$cname = key($_POST['edit']);

	header('Location: ./editcharacter/'.urlencode($cname));
	die();
}

$table = $section
	->appendCreate('o-form', [ 'action' => '#s_characters', 'method' => 'post' ])
	->appendCreate('table.d.scharacters')
	;

$headtr = $p->element('tr', [
	[ 'th', 'Name' ],
	[ 'th', 'Key ID' ],
	[ 'th', 'Verification code' ],
	[ 'th', 'Import character name' ],
	[ 'th', 'Last import date' ],
	[ 'th', 'Actions' ],
]);
$table->appendCreate('thead', $headtr);

$table->appendCreate('tfoot');
$tbody = $table->appendCreate('tbody');

$cq = \Osmium\Db\query_params(
	'SELECT name, ac.keyid, eak.verificationcode, importname, lastimportdate
	FROM osmium.accountcharacters ac
	LEFT JOIN osmium.eveapikeys eak ON eak.owneraccountid = ac.accountid AND eak.keyid = ac.keyid
	WHERE accountid = $1',
	array($a['accountid'])
);
$haschars = false;
while($c = \Osmium\Db\fetch_assoc($cq)) {
	$haschars = true;
	$vcode = $c['verificationcode'];
	if($vcode === null) $vcode = '';
	else $vcode = MASK.substr($vcode, -4);

	$cname = $c['name'];

	$tr = $tbody->appendCreate('tr');
	$tr->appendCreate('td')->appendCreate('strong', $cname);
	$tr->appendCreate('td')->appendCreate('o-input', [
		'type' => 'text',
		'name' => 'keyid['.$cname.']',
		'value' => $c['keyid'],
	]);
	$tr->appendCreate('td')->appendCreate('o-input', [
		'type' => 'text',
		'name' => 'vcode['.$cname.']',
		'value' => $vcode,
	]);
	$tr->appendCreate('td')->appendCreate('o-input', [
		'type' => 'text',
		'name' => 'iname['.$cname.']',
		'value' => $c['importname'],
	]);

	$tr->appendCreate('td')->append(
		$c['lastimportdate'] === null ? $p->element('em', 'never') : $p->formatRelativeDate($c['lastimportdate'])
	);

	$td = $tr->appendCreate('td');

	$td->appendCreate('input', [
		'type' => 'submit',
		'name' => 'fetch['.$cname.']',
		'value' => 'Update from API',
	]);
	$td->appendCreate('input', [
		'type' => 'submit',
		'name' => 'edit['.$cname.']',
		'value' => 'Edit skills and attributes',
	]);
	$td->appendCreate('input', [
		'type' => 'submit',
		'name' => 'delete['.$cname.']',
		'value' => 'Delete character',
	]);
}
if(!$haschars) {
	$tbody
		->appendCreate('tr')
		->appendCreate('td', [ 'colspan' => (string)$headtr->childNodes->length ])
		->appendCreate('p.placeholder', 'No characters.')
		;
}


$p->snippets[] = 'settings';
$p->render($ctx);
