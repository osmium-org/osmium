/* This is a suggestion for a Varnish configuration if you decide to *
 * use Varnish. Tweak to your liking. */

vcl 4.0;

backend default {
		.host = "127.0.0.1";
		.port = "81";
}

sub vcl_recv {
	/* Transfer the original client IP to X-Forwarded-For. Use this if
	 * varnish is the proxy that communicates with the actual
	 * clients. You will have to change the trust_x_forwarded_for
	 * setting in config.ini to On. */
	unset req.http.X-Forwarded-For;
	set req.http.X-Forwarded-For = client.ip;

	if(req.method != "GET" && req.method != "HEAD") {
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
			unset req.http.Accept-Encoding;
		}
	}

	/* Strip cookies for static requests, so that Varnish can cache
	 * them. */
	if(req.url ~ "^/static(-[1-9][0-9]*)?/") {
		unset req.http.Cookie;
	}

	/* Canonicalize the hostname - optional. */
	if(req.http.host == "smium.org" || req.http.host == "www.smium.org") {
		return(synth(750, "Moved Temporarily"));
	}

	/* Do not cache any request with a cookie. */
	if(req.http.Cookie) {
		return(pass);
	}

	return(hash);
}

sub vcl_backend_response {
	unset beresp.http.Server;

	return(deliver);
}

sub vcl_deliver {
	/* Unset useless headers to save a tiny bit of bandwidth and to
	 * not leak server info. */
	unset resp.http.Age;
	unset resp.http.Via;
	unset resp.http.X-Powered-By;
	unset resp.http.X-Varnish;

	/* Fail-safe to prevent indexing duplicate content if you have
	 * multiple domain names. */
	if(req.http.host != "o.smium.org") {
		set resp.http.X-Robots-Tag = "noindex, nofollow";
	}

	return(deliver);
}

sub vcl_synth {
	/* Hostname canonicalization - see above. */
	if(resp.status == 750) {
		set resp.http.Location = "http://o.smium.org/";
		set resp.status = 302;
		return(deliver);
	}
}
