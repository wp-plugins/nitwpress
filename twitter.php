<?php

/*
Copyright (c) 2009 sakuratan.

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
*/

/*
 * Pickup XML elements flagment.
 */
function nitwpress_twitter_xml_pickup($fh, $flagment, $elemnames) {
    $repl = array('&amp;lt;' => '&lt;', '&amp;gt;' => '&gt;');
    foreach ($elemnames as $elem) {
	if (preg_match(",<{$elem}>.*?</{$elem}>,s", $flagment, $matches)) {
	    fwrite($fh, strtr($matches[0], $repl));
	    fwrite($fh, "\n");
	}
    }
}

/*
 * Update cache files.
 */
function nitwpress_twitter_update_caches($dir, $username, $password) {
    if (!@is_dir($dir)) {
	if (!mkdir($dir, 0755, true))
	    return false;
    }

    $timeline = "{$dir}/user_timeline.xml";

    // Load previous profile_background_image_url
    $old_profile_background_image_url = null;
    $ctx = @file_get_contents($timeline);
    if ($ctx !== false) {
	if (preg_match(',<profile_background_image_url>(.*?)</profile_background_image_url>,s', $ctx, $matches)) {
	    $old_profile_background_image_url = $matches[1];
	}
    }

    // Call Twitter UserTimeline API
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_URL,
		'http://twitter.com/statuses/user_timeline.xml');
    curl_setopt($ch, CURLOPT_USERPWD, "{$username}:{$password}");
    $ctx = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if (!$ctx)
	return false;
    if ($code != 200) {
	trigger_error("Twitter api returns error with {$code}",
		      E_USER_WARNING);
	return false;
    }


    // Strip and save result XML
    $tmpfile = tempnam($dir, 'tmp');
    $fh = fopen($tmpfile, 'wb');
    if (!$fh) {
	unlink($tmpfile);
	return false;
    }
    chmod($tmpfile, 0644);
    $profile_background_image_url = null;
    $flagment_pickup = array('created_at', 'text');
    $user_pickup = array(
	'profile_image_url', 'profile_background_color',
	'profile_text_color', 'profile_link_color',
	'profile_sidebar_fill_color', 'profile_sidebar_border_color',
	'profile_background_image_url', 'profile_background_tile');
    fwrite($fh, "<?xml version=\"1.0\" encoding=\"UTF-8\"".'?'.">\n");
    fwrite($fh, "<statuses type=\"array\">\n");
    if (preg_match_all(',<status>.*?</status>,s', $ctx, $status_matches)) {
	foreach ($status_matches[0] as $flagment) {
	    fwrite($fh, "<status>\n");
	    if (preg_match(',<user>.*?</user>,s', $flagment, $matches)) {
		$user = $matches[0];
		$flagment = preg_replace(',<user>.*?</user>,s', '', $flagment);
		if (!$profile_background_image_url &&
		    preg_match(',<profile_background_image_url>(.*?)</profile_background_image_url>,s', $user, $matches))
		    $profile_background_image_url = $matches[1];
	    } else {
		$user = '';
	    }
	    nitwpress_twitter_xml_pickup($fh, $flagment, $flagment_pickup);
	    if ($user) {
		fwrite($fh, "<user>\n");
		nitwpress_twitter_xml_pickup($fh, $user, $user_pickup);
		fwrite($fh, "</user>\n");
	    }
	    fwrite($fh, "</status>\n");
	}
    }
    fwrite($fh, "</statuses>\n");
    fclose($fh);

    if (!rename($tmpfile, $timeline)) {
	unlink($tmpfile);
	return false;
    }

    // Download background image because it cannot access from swf file
    if ($profile_background_image_url) {
	$filename = "{$dir}/" . basename($profile_background_image_url);
	$tmpfile = tempnam($dir, 'tmp');
	if (!$tmpfile)
	    return false;
	chmod($tmpfile, 0644);
	$fh = fopen($tmpfile, 'wb');
	if (!$fh) {
	    unlink($tmpfile);
	    return false;
	}
	$mtime = @filemtime($filename);

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
	curl_setopt($ch, CURLOPT_URL, $profile_background_image_url);
	curl_setopt($ch, CURLOPT_FILE, $fh);
	if ($mtime !== false) {
	    $mod = preg_replace('/\+0000$/', 'GMT', gmdate('r', $mtime));
	    curl_setopt($ch, CURLOPT_HTTPHEADER,
			array("If-Modified-Since: {$mod}"));
	}
	$rv = curl_exec($ch);
	if (!$rv) {
	    curl_close($ch);
	    fclose($fh);
	    unlink($tmpfile);
	    return false;
	}
	$sb = stat($tmpfile);

	$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);
	fflush($fh);
	fclose($fh);
	if ($code < 200 || $code >= 300) {
	    unlink($tmpfile);
	    if ($code != 304) {
		trigger_error("Failed to fetch profile_background_image_url with http error {$code}", E_USER_WARNING);
		return false;
	    }
	    return true;
	}

	if (!rename($tmpfile, $filename)) {
	    unlink($tmpfile);
	    return false;
	}

	if ($old_profile_background_image_url) {
	    $oldfile = "{$dir}/" . basename($old_profile_background_image_url);
	    if ($oldfile != $filename)
		unlink($oldfile);
	}

    } elseif ($old_profile_background_image_url) {
	$oldfile = "{$dir}/" . basename($old_profile_background_image_url);
	unlink($oldfile);
    }

    return true;
}

?>
