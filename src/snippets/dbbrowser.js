/* Osmium
 * Copyright (C) 2014, 2015 Romain "Artefact2" Dalmaso <artefact2@gmail.com>
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

/*<<< require snippet tabs >>>*/
/*<<< require snippet perfectscrollbar >>>*/

$(function() {
	var tabs = $("ul.tabs");
	if(tabs.length > 0) {
		for(var i = 0; i < tabs.length; ++i) {
			osmium_tabify($(tabs[i]), 0);
		}
	}

	var csec = $("div#dbb.compare > section.compare");
	if(csec.length > 0) {
		csec.perfectScrollbar({
			suppressScrollY: true,
			wheelPropagation: true, /* <- Doesn't work as advertised? */
		});
		csec.off('mousewheel'); /* XXX: Hackish */

		/* Table sorting */
		var round_sd = function(n, d) {
			if(!n) return n;
			var m = d - Math.floor(Math.log(Math.abs(n)) / Math.log(10)) - 1;
			return n.toFixed(Math.max(0, m));
		};

		var asclambda = function(columnindex) {
			return function(trA, trB) {
				return $(trA).children().eq(columnindex).data('rawval')
					- $(trB).children().eq(columnindex).data('rawval');
			};
		};
		var desclambda = function(columnindex) {
			return function(trA, trB) {
				return $(trB).children().eq(columnindex).data('rawval')
					- $(trA).children().eq(columnindex).data('rawval');
			};
		};
		var unsortlambda = function(trA, trB) {
			return $(trA).data('idx') - $(trB).data('idx');
		};

		var ctable = csec.children('table.d');
		var tbody = ctable.children('tbody');
		ctable
			.children('thead')
			.children('tr')
			.children('th')
			.on('click', function() {
				var th = $(this);
				var asc = th.hasClass('asc');
				var desc = th.hasClass('desc');

				ctable.children('colgroup').removeClass('sorted');
				th.parent().children('th').removeClass('asc desc');

				if(!asc && !desc) {
					th.addClass('asc');
					ctable.children('colgroup').eq(th.index()).addClass('sorted');
					tbody.children('tr').sort(asclambda(th.index())).appendTo(tbody);
				} else if(asc) {
					th.addClass('desc');
					ctable.children('colgroup').eq(th.index()).addClass('sorted');
					tbody.children('tr').sort(desclambda(th.index())).appendTo(tbody);
				} else if(desc) {
					ctable.children('colgroup').eq(th.index()).removeClass('sorted');
					tbody.children('tr').sort(unsortlambda).appendTo(tbody);
				}
			})
		;

		tbody
			.children('tr')
			.children('th:first-child')
			.on('click', function() {
				var tr = $(this).parent();
				var wasbase = tr.hasClass('base');

				tbody.find('small.basecmp').remove();
				tbody.children('tr').removeClass('base')
					.children('td').removeClass('gain loss');
				if(wasbase) return;

				tr.addClass('base');
				tbody.children('tr').each(function() {
					var other = $(this);
					if(other.index() === tr.index()) return;

					other.children('td').each(function() {
						var td = $(this);
						var baseval = tr.children().eq(td.index()).data('rawval');
						if(!baseval) return;

						var otherval = td.data('rawval');
						var delta = 100 * (otherval - baseval) / baseval;
						if(!delta) return;

						td.append(
							$(document.createElement('small'))
								.addClass('basecmp')
								.append(document.createElement('br'))
								.append(document.createTextNode(delta >= 0 ? '+' : '-'))
								.append(document.createTextNode(round_sd(Math.abs(delta), 2) + " %"))
						);

						var highisgood = ctable.children('thead')
							.children('tr').children()
							.eq(td.index()).data('hig');
						var gain = (baseval < otherval);
						if(highisgood) {
							td.addClass(gain ? 'gain' : 'loss');
						} else {
							td.addClass(gain ? 'loss' : 'gain');
						}
					});
				});
			})
		;
	}

	$("div#dbb ul.typelist").each(function() {
		(function(typelist) {
			var asc = -1;
			var lsb = 'name';

			var p = $(document.createElement('p')).addClass('sort');
			p.append('Sort this list by: ');

			p.append($(document.createElement('a')).text('name').on('click', function() {
				if(lsb === 'name') {
					asc *= -1;
				} else {
					lsb = 'name';
					asc = 1;
				}

				typelist.children('li').sort(function(a, b) {
					var au = a.className === 'unpublished';
					var bu = b.className === 'unpublished';

					if(au & !bu) return 1;
					if(bu & !au) return -1;
					
					var as = a.lastChild.textContent.toString();
					var bs = b.lastChild.textContent.toString();
					return asc * ((as < bs) ? -1 : ((as > bs) ? 1 : 0));
				}).appendTo(typelist);
			}).click());

			if(typelist.find('li > span.tval:first-child').length > 0) {
				p.append(', ');

				p.append($(document.createElement('a')).text('attribute value').on('click', function() {
					if(lsb === 'value') {
						asc *= -1;
					} else {
						lsb = 'value';
						asc = 1;
					}

					typelist.children('li').sort(function(a, b) {
						var au = a.className === 'unpublished';
						var bu = b.className === 'unpublished';

						if(au & !bu) return 1;
						if(bu & !au) return -1;
						
						var af = parseFloat(a.firstChild.textContent);
						var bf = parseFloat(b.firstChild.textContent);
						return asc * (af - bf);
					}).appendTo(typelist);
				}));
			}

			typelist.before(p);
		})($(this));
	});

	$("div#dbb ul#type-elements > li").click(function() {
		alert($(this).prop('title'));
	});
});
