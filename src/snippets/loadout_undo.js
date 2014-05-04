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

osmium_undo_stack = [];
osmium_undo_stack_position = 0;
osmium_pushed_state_count = 0;

/* Push the current CLF to the undo history. */
osmium_undo_push = function() {
	osmium_undo_stack.push($.extend(true, {}, osmium_clf));
	osmium_undo_stack_position = osmium_undo_stack.length - 1;
	osmium_set_history_undo();
};

/* Restore the previous CLF in the undo history. */
osmium_undo_pop = function(nsteps) {
	if(nsteps === undefined) nsteps = 1;

	if(osmium_undo_stack_position < nsteps) {
		/* No more history to undo */
		return;
	}

	/*  Very similar to the "undo" feature of Emacs. Powerful and
	 *  cannot lose data by undoing stuff then doing modifications. */
	osmium_undo_stack_position -= nsteps;
	osmium_clf = $.extend(true, {}, osmium_undo_stack[osmium_undo_stack_position]);
	osmium_undo_stack.push($.extend(true, {}, osmium_clf));
	osmium_set_history_undo();
}

/* @internal */
osmium_set_history_undo = function() {
	$(window).off('popstate');
	if(!window.history || !window.history.pushState) return;

	while(osmium_pushed_state_count > osmium_undo_stack_position) {
		window.history.back();
		--osmium_pushed_state_count;
	}

	while(osmium_pushed_state_count < osmium_undo_stack_position) {
		++osmium_pushed_state_count;
		history.pushState(osmium_pushed_state_count, null);
	}

	$(window).on('popstate', function(e) {
		var nsteps = osmium_undo_stack_position - e.originalEvent.state;

		osmium_pushed_state_count -= nsteps;
		osmium_undo_pop(nsteps);
		osmium_commit_clf();
		osmium_user_initiated_push(false);
		osmium_gen();
		osmium_user_initiated_pop();
	});
};



osmium_register_keyboard_command('ctrl+z', 'undo', 'Undo the last change made to the loadout.', function() {
	osmium_undo_pop();
	osmium_commit_clf();
	osmium_user_initiated_push(false);
	osmium_gen();
	osmium_user_initiated_pop();
	return false;
});

osmium_register_keyboard_command(null, 'debug-log-clf-undo-stack', 'Print the undo history to the Javascript console.', function() {
	console.log(osmium_undo_stack_position, osmium_undo_stack);
});
