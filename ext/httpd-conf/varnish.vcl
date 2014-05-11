/* This is a suggestion for a Varnish configuration if you decide to *
 * use Varnish. Tweak to your liking. */

backend default {
		.host = "127.0.0.1";
		.port = "81";
}

sub vcl_recv {
	/* Transfer the original client IP to X-Forwarded-For. Osmium
	 * won't use X-Forwarded-For, so you have to configure your
	 * webserver to translate X-Forwarded-For to something that will
	 * show up in $_SERVER['REMOTE_ADDR']. For lighttpd, you can use
	 * mod_extforward. */
	remove req.http.X-Forwarded-For;
	set req.http.X-Forwarded-For = client.ip;

	if(req.request != "GET" && req.request != "HEAD") {
		return(pass);
	}

	/* Normalize Accept-Encoding values, so Varnish can cache them
	 * more efficiently.
	 * https://www.varnish-cache.org/trac/wiki/FAQ/Compression */
	if(req.http.Accept-Encoding) {
		if(req.http.Accept-Encoding ~ "gzip") {
			set req.http.Accept-Encoding = "gzip";
		} elsif(req.http.Accept-Encoding ~ "deflate") {
			set req.http.Accept-Encoding = "deflate";
		} else {
			remove req.http.Accept-Encoding;
		}
	}

	/* Strip cookies for static requests, so that Varnish can cache
	 * them. */
	if(req.url ~ "^/static(-[1-9][0-9]*)?/") {
		unset req.http.Cookie;
	}

	/* Canonicalize the hostname - optional. */
	if(req.http.host == "smium.org" || req.http.host == "www.smium.org") {
		error 750 "Moved Temporarily";
	}

	return(lookup);    
}

sub vcl_fetch {
	unset beresp.http.Server;

	return(deliver);
}

sub vcl_deliver {
	/* Remove useless headers to save a tiny bit of bandwidth and to
	 * not leak server info. */
	remove resp.http.Age;
	remove resp.http.Via;
	remove resp.http.X-Powered-By;
	remove resp.http.X-Varnish;

	/* Fail-safe to prevent indexing duplicate content if you have
	 * multiple domain names. */
	if(req.http.host != "o.smium.org") {
		set resp.http.X-Robots-Tag = "noindex, nofollow";
	}

	return(deliver);
}

sub vcl_error {
	/* Hostname canonicalization - see above. */
	if(obj.status == 750) {
		set obj.http.Location = "http://o.smium.org/";
		set obj.status = 302;
		return(deliver);
	}
}
