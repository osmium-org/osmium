if(typeof(localStorage) === 'undefined') {
	localStorage = {
		getItem: function() { return null; },
		setItem: function() {},
		removeItem: function() {},
	};
}

if(typeof(sessionStorage) === 'undefined') {
	sessionStorage = {
		getItem: function() { return null; },
		setItem: function() {},
		removeItem: function() {},
	};
}
