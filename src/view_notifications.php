<?php
/* Osmium
 * Copyright (C) 2012, 2014 Romain "Artefact2" Dalmaso <artefact2@gmail.com>
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

namespace Osmium\Page\ViewNotifications;

require __DIR__.'/../inc/root.php';

$p = new \Osmium\DOM\Page();
$p->title = 'Notifications';

$div = $p->content->appendCreate('div', [ 'id' => 'vnotifications' ]);
$div->appendCreate('h2', $p->title);

$tbody = $div->appendCreate('table', [ 'class' => 'd' ])->appendCreate('tbody');

$trtpl = $p->element('tr');
$trtpl->appendCreate('td');
$trtpl->appendCreate('td');

\Osmium\Notification\get_notifications(function($row, $isnew) use($p, $trtpl, $tbody) {
	$tr = $trtpl->cloneNode(true);

	if($isnew) $tr->setAttribute('class', 'new');
	$tr->firstChild->append($p->formatRelativeDate($row['creationdate']));
	$tr->lastChild->append($p->fragment(\Osmium\Notification\get_notification_body($row)) /* XXX */);

	$tbody->appendChild($tr);
});


$ctx = new \Osmium\DOM\RenderContext();
$ctx->relative = '.';
$p->render($ctx);

\Osmium\Notification\reset_notification_count();
