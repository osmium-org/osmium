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
require \Osmium\ROOT.'/inc/login-common.php';

const MASK = '********';
const NICKNAME_CHANGE_WINDOW = 1209600;

$p = new \Osmium\DOM\Page();
$ctx = new \Osmium\DOM\RenderContext();
$p->title = 'Account settings';
$ctx->relative = '.';

\Osmium\State\assume_logged_in($ctx->relative);
$a = \Osmium\State\get_state('a');

if(isset($_POST['unverify'])) {
	\Osmium\State\unverify_account($a['accountid']);
	\Osmium\State\do_post_login($a['accountid']);
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
		\Osmium\State\do_post_login($a['accountid']);
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
$ul->appendCreate('li')->appendCreate('a', [ 'href' => '#s_characters', 'Characters and skills' ]);
$ul->appendCreate('li')->appendCreate('a', [ 'href' => '#s_apiauth', 'Account status' ]);
$ul->appendCreate('li')->appendCreate('a', [ 'href' => '#s_accountauth', 'Authentication methods' ]);
$ul->appendCreate('li')->appendCreate('a', [ 'href' => '#s_changenick', 'Change nickname' ]);



$section = $div->appendCreate('section#s_changenick');
$section->appendCreate('h1', 'Change nickname');

$lastchange = \Osmium\Db\fetch_row(\Osmium\Db\query_params(
	'SELECT lastnicknamechange FROM osmium.accounts
	WHERE accountid = $1',
	[ $a['accountid'] ]
))[0];

$now = time();
$cutoff = $now - NICKNAME_CHANGE_WINDOW;

if(($lastchange === null || $lastchange <= $cutoff) && isset($_POST['newnick'])
   && \Osmium\Login\check_nickname($p, 'newnick')) {
	/* XXX: require sudo mode */

	$lastchange = time();
	$a['nickname'] = $_POST['newnick'];
	\Osmium\State\put_state('a', $a);

	\Osmium\Db\query_params(
		'UPDATE osmium.accounts SET nickname = $2, lastnicknamechange = $3 WHERE accountid = $1',
		[ $a['accountid'], $a['nickname'], $lastchange ]
	);

	$section->appendCreate('p.notice_box', 'Nickname was successfully changed.');
}

$canchange = ($lastchange === null || $lastchange <= $cutoff);

if(!$canchange) {
	$section->appendCreate(
		'p',
		'You last changed your nickname '.$p->formatDuration($now - $lastchange, false, 1)
		.' ago. You will be able to change your nickname again in '
		.$p->formatDuration($lastchange + NICKNAME_CHANGE_WINDOW - $now, false, 1).'.'
	);
}

if($canchange) {
	$section->appendCreate('p')->appendCreate(
		'strong',
		'To prevent abuse, you can only change your nickname every '
		.$p->formatDuration(NICKNAME_CHANGE_WINDOW).'.'
	);
}

$tbody = $section
	->appendCreate('o-form', [ 'action' => '#s_changenick', 'method' => 'post' ])
	                     ->appendCreate('table')
	->appendCreate('tbody')
	;

$tbody->append($p->makeFormRawRow(
	[[ 'label', 'Current nickname' ]],
	[[ 'input', [
		'readonly' => 'readonly',
		'type' => 'text',
		'value' => $a['nickname'],
	] ]]
));

if($canchange) {
	$tbody->append($p->makeFormInputRow('text', 'newnick', 'New nickname'));
	$tbody->append($p->makeFormSubmitRow('Change nickname'));
}



$section = $div->appendCreate('section#s_accountauth');
$section->appendCreate('h1', 'Authentication methods');

if(isset($_POST['delete'])) {
	$id = key($_POST['delete']);

	\Osmium\Db\query('BEGIN');

	$count = \Osmium\Db\fetch_row(\Osmium\Db\query_params(
		'SELECT COUNT(accountcredentialsid)
		FROM osmium.accountcredentials
		WHERE accountid = $1',
		[ $a['accountid'] ]
	))[0];

	if($count < 2) {
		$section->appendCreate(
			'p.error_box',
			'You cannot delete your only way of signing in to your account. That\'s silly!'
		);

		\Osmium\Db\query('ROLLBACK');
	} else {
		/* XXX: require sudo mode */
		\Osmium\Db\query_params(
			'DELETE FROM osmium.accountcredentials
			WHERE accountcredentialsid = $1 AND accountid = $2',
			[ $id, $a['accountid'] ]
		);
		\Osmium\Db\query('COMMIT');
	}
}

if(isset($_POST['changepw'])) {
	$id = key($_POST['changepw']);

	if(\Osmium\Login\check_passphrase(
		$p, 'newpw['.$id.']', 'newpw2['.$id.']',
		$_POST['newpw'][$id], $_POST['newpw2'][$id]
	)) {
		/* XXX: require sudo mode for this */

		$newhash = \Osmium\State\hash_password($_POST['newpw'][$id]);
		\Osmium\Db\query_params(
			'UPDATE osmium.accountcredentials SET passwordhash = $1
			WHERE accountcredentialsid = $3 AND accountid = $2 AND passwordhash IS NOT NULL',
			[ $newhash, $a['accountid'], $id ]
		);

		$section->appendCreate('p.notice_box', 'Passphrase was successfully changed.');	
	}
}

if(isset($_POST['username'])
   && \Osmium\Login\check_username_and_passphrase($p, 'username', 'passphrase', 'passphrase2')) {
	\Osmium\Db\query_params(
		'INSERT INTO osmium.accountcredentials (accountid, username, passwordhash)
		VALUES ($1, $2, $3)', [
			$a['accountid'],
			$_POST['username'],
			\Osmium\State\hash_password($_POST['passphrase']),
	]);
}

if(isset($_POST['ccpssoinit'])) {
	/* XXX: require sudo mode */
	\Osmium\State\ccp_oauth_redirect([
		'action' => 'associate',
		'accountid' => $a['accountid'],
	]);
}

$table = $section->appendCreate('table.d');

$thead = $table->appendCreate('thead');
$tbody = $table->appendCreate('tbody');

$trh = $thead->appendCreate('tr');
$trh->appendCreate('th', '#');
$trh->appendCreate('th', 'Type');
$trh->appendCreate('th', 'UID');
$trh->appendCreate('th', [ 'colspan' => '2', 'Actions' ]);

$crq = \Osmium\Db\query_params(
	'SELECT accountcredentialsid, username, passwordhash, ccpoauthcharacterid, ccpoauthownerhash
	FROM osmium.accountcredentials
	WHERE accountid = $1
	ORDER BY accountcredentialsid ASC',
	[ $a['accountid'] ]
);

while($row = \Osmium\Db\fetch_assoc($crq)) {
	$tr = $tbody->appendCreate('tr');
	
	$id = $row['accountcredentialsid'];
	$tr->appendCreate('th', '#'.$id);
	$type = $tr->appendCreate('th');
	$uid = $tr->appendCreate('td');

	$actions = $tr
		->appendCreate('td.actions')
		->appendCreate('o-form', [ 'action' => '#s_accountauth', 'method' => 'post' ]);

	$tr
		->appendCreate('td')
		->appendCreate('o-form', [ 'action' => '#s_accountauth', 'method' => 'post' ])
		->appendCreate('input.confirm.dangerous', [
			'type' => 'submit',
			'name' => 'delete['.$id.']',
			'value' => 'Delete this method',
		]);

	if($row['username'] !== null) {
		$type->append('Username and passphrase');
		$uid->appendCreate('code', $row['username']);

		$actions->appendCreate('o-input', [
			'type' => 'password',
			'placeholder' => 'New passphrase…',
			'name' => 'newpw['.$id.']',
		]);
		$actions->appendCreate('o-input', [
			'type' => 'password',
			'placeholder' => 'Confirm passphrase…',
			'name' => 'newpw2['.$id.']',
		]);
		$actions->appendCreate('input', [
			'type' => 'submit',
			'name' => 'changepw['.$id.']',
			'value' => 'Change passphrase'
		]);
	} else if($row['ccpoauthcharacterid'] !== null) {
		$type->append('CCP OAuth2 (Single Sign On)');
		$uid->setAttribute('class', 'sso');
		$uid->appendCreate(
			'o-eve-img',
			[ 'alt' => '', 'src' => '/Character/'.$row['ccpoauthcharacterid'].'_128.jpg' ]
		);
		$code = $uid->appendCreate('code');
		$code->append('Character #'.$row['ccpoauthcharacterid']);
		$code->appendCreate('br');
		$code->append('OwnerHash '.$row['ccpoauthownerhash']);
	}
}

$section->appendCreate('h1', 'Add a new authentication method');
$ul = $section->appendCreate('ul');

$li = $ul->appendCreate('li');
$li->appendCreate('h3', 'Username and passphrase');
$tbody = $li
	->appendCreate('o-form', [ 'action' => '#s_accountauth', 'method' => 'post' ])
	->appendCreate('table')
	->appendCreate('tbody')
	;

$tbody->append($p->makeFormInputRow('text', 'username', 'User name'));
$tbody->append($p->makeFormInputRow('password', 'passphrase', 'Passphrase'));
$tbody->append($p->makeFormInputRow('password', 'passphrase2', [ 'Passphrase', [ 'br' ], [ 'small', '(confirm)' ] ]));
$tbody->append($p->makeFormSubmitRow('Add username and passphrase'));

$li = $p->element('li');
$li->appendCreate('h3', 'CCP OAuth2 (Single Sign On)');
$li->appendCreate('p')->appendCreate(
	'o-form',
	[ 'method' => 'post', 'action' => '#s_accountauth' ]
)->appendCreate(
	'input',
	[ 'type' => 'submit', 'value' => 'Associate my EVE character', 'name' => 'ccpssoinit' ]
);
if(\Osmium\get_ini_setting('ccp_oauth_available')) $ul->append($li);



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

		if($keyinfo === false) {
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


$p->snippets[] = 'account_settings';
$p->render($ctx);
