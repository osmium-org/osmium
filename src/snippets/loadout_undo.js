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
osmium_popstate_disablefor = 0;



/* Push the current CLF to the undo history. */
osmium_undo_push = function() {
	osmium_undo_stack.push($.extend(true, {}, osmium_clf));
	osmium_undo_stack_position = osmium_undo_stack.length - 1;
	osmium_set_history_undo();
};

/* Restore the previous CLF in the undo history. Negative values also
 * supported, in which case, use fallback_clf if going too far in the
 * future. */
osmium_undo_pop = function(nsteps, fallback_clf) {
	if(nsteps === undefined) nsteps = 1;

	if(osmium_undo_stack_position < nsteps) {
		/* No more history to undo */
		return false;
	}

	/*  Very similar to the "undo" feature of Emacs. Powerful and
	 *  cannot lose data by undoing stuff then doing modifications. */

	osmium_undo_stack_position -= nsteps;

	if(fallback_clf !== undefined) {
		osmium_undo_stack[osmium_undo_stack_position] = $.extend(true, {}, fallback_clf);
	}

	var clf = osmium_undo_stack[osmium_undo_stack_position];
	if(clf === undefined) {
		return false;
	}

	osmium_clf = $.extend(true, {}, clf);
	osmium_undo_stack.push($.extend(true, {}, clf));
	osmium_set_history_undo();
	return true;
}

/* @internal */
osmium_set_history_undo = function() {
	if(!window.history || !window.history.pushState) return;

	/* This sucks, but popstate is async */
	osmium_popstate_disablefor += Math.max(0, osmium_pushed_state_count - osmium_undo_stack_position);
	while(osmium_pushed_state_count > osmium_undo_stack_position) {
		history.back();
		--osmium_pushed_state_count;
	}

	while(osmium_pushed_state_count < osmium_undo_stack_position) {
		++osmium_pushed_state_count;
		history.pushState([
			osmium_pushed_state_count,
			osmium_undo_stack[osmium_pushed_state_count],
		], null);
	}
};



$(function() {
	$(window).on('popstate', function(e) {
		if(osmium_popstate_disablefor > 0) {
			--osmium_popstate_disablefor;
			return;
		}

		var state = e.originalEvent.state;
		if(state === null) state = [ 0, undefined ];
		var nsteps = osmium_undo_stack_position - state[0];

		osmium_pushed_state_count -= nsteps;
		if(osmium_undo_pop(nsteps, state[1]) === false) return;
		osmium_commit_clf();
		osmium_user_initiated_push(false);
		osmium_gen();
		osmium_user_initiated_pop();
	});
});



osmium_register_keyboard_command('ctrl+z', 'undo', 'Undo the last change made to the loadout.', function() {
	if(osmium_undo_pop() === false) return false;
	osmium_commit_clf();
	osmium_user_initiated_push(false);
	osmium_gen();
	osmium_user_initiated_pop();
	return false;
});

osmium_register_keyboard_command(null, 'debug-log-clf-undo-stack', 'Print the undo history to the Javascript console.', function() {
	console.log(osmium_undo_stack_position, osmium_undo_stack);
});
