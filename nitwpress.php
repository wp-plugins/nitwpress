<?php
/*
Plugin Name: NiTwPress
Plugin URI: http://sakuratan.biz/
Description: NiTwPress is a Twitter client for WordPress sidebar widget. It displays your twit on the WordPress sidebar with comment scrolling like Niconico-doga. (NiTwPress is an abbreviation of `NIconico-doga like TWitter client for wordPRESS'.)
Author: sakuratan
Author URI: http://sakuratan.biz/
Version: 0.9.1
*/

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

define('NITWPRESS_PLUGINS', '/wp-content/plugins/nitwpress/');
define('NITWPRESS_CACHES', NITWPRESS_PLUGINS . 'caches/');
define('NITWPRESS_CACHEDIR', ABSPATH . NITWPRESS_CACHES);

/*
 * Update cache files.
 */
function nitwpress_update_caches() {
    require_once dirname(__file__) . '/twitter.php';

    $options = get_option('nitwpress_options');
    if (!$options)
	return;
    if (!array_key_exists('username', $options) || !$options['username'])
	return;

    nitwpress_twitter_update_caches(NITWPRESS_CACHEDIR, $options['username'],
				    $options['password']);
}

/*
 * The widget.
 */
function nitwpress_sidebar_widget($args) {
    extract($args);

    echo $before_widget;

    $options = get_option('nitwpress_options');
    if ($options) :
	$username = htmlspecialchars($options['username']);
	$siteurl = get_option('siteurl');
	$plugin = $siteurl . NITWPRESS_PLUGINS;
	$swf = htmlspecialchars("{$plugin}nitwpress.swf");
	$base = htmlspecialchars("{$siteurl}" . NITWPRESS_CACHES);

?>
<div style="text-align:center">
  <object codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,0,0" width="154" height="154" classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000">
    <param name="movie" value="<?php echo $swf ?>" />
    <param name="quality" value="high" />
    <param name="bgcolor" value="#ffffff" />
    <param name="base" value="<?php echo $base ?>" />
    <script type="text/javascript">
//<!--
      document.write('<embed pluginspage="http://www.macromedia.com/go/getflashplayer" type="application/x-shockwave-flash" width="154" height="154" src="<?php echo $swf ?>" quality="high" base="<?php echo $base ?>" ></embed>');
      document.write('<noembed><div><a href="http://www.adobe.com/go/getflashplayer"><img src="http://www.adobe.com/images/shared/download_buttons/get_flash_player.gif" alt="Get Adobe Flash player" /></a></div></noembed>');
//-->
    </script>
    <noscript>
      <div><a href="http://www.adobe.com/go/getflashplayer"><img src="http://www.adobe.com/images/shared/download_buttons/get_flash_player.gif" alt="Get Adobe Flash player" /></a></div>
    </noscript>
  </object>
  <div><a href="http://twitter.com/<?php echo $username ?>">follow <?php echo $username ?> at http://twitter.com</a></div>
</div>
<?php

    endif;

    echo $after_widget;
}

/*
 * Widget manager.
 */
function nitwpress_widget_control() {
    $defaults = array(
	'username' => '',
	'password' => '',
	'interval' => 15
    );

    if (array_key_exists('nitwpress_action', $_POST)) {
	$options = array();
	foreach ($defaults as $key => $value) {
	    if (array_key_exists($key, $_POST)) {
		$options[$key] = $_POST[$key];
	    } else {
		$options[$key] = $value;
	    }
	}
	update_option('nitwpress_options', $options);
	nitwpress_update_caches();

    }

    $options = get_option('nitwpress_options', $defaults);

?>
<form method="post">
  <p>Enter your Twitter account.<input type="hidden" name="nitwpress_action" /></p>
  <table>
    <tr>
      <td>Username</td>
      <td><input type="text" name="username" value="<?php echo htmlspecialchars($options['username']) ?>" /></td>
    </tr>

    <tr>
      <td>Password</td>
      <td><input type="password" name="password" value="<?php echo htmlspecialchars($options['password']) ?>" /></td>
    </tr>

    <tr>
      <td>Timeline</td>
      <td>Update timeline cache at every <input type="text" name="interval" value="<?php echo htmlspecialchars($options['interval']) ?>" size="3" /> minutes.</td>
    </tr>
  </table>
</form>
<?php

    if (!is_dir(NITWPRESS_CACHEDIR)) :
?>
<p style="color:red">ERROR: Missing permissions for writing on
<?php echo NITWPRESS_CACHEDIR ?><br />
Fix the error before enter your Twitter account</p>
<?php
    endif;

    if (!function_exists('curl_init')) {
?>
<p style="color:red">ERROR: Missing cURL module.</p>
<?php
    }
}

/*
 * Module initializer.
 */
function nitwpress_init() {
    require_once(ABSPATH . 'wp-includes/widgets.php');
    register_sidebar_widget('NiTwPress', 'nitwpress_sidebar_widget');
    register_widget_control('NiTwPress', 'nitwpress_widget_control');
}

add_action('init', 'nitwpress_init');

/*
 * Schedule cron task.
 */
function hitwpress_activation() {
    wp_schedule_event(time(), 'nitwpress', 'nitwpress_hourly_event');
}

/*
 * Unschedule cron task.
 */
function nitwpress_deactivation() {
    wp_clear_scheduled_hook('nitwpress_hourly_event');
}

/*
 * Filter for wp_cron that appends nitwpress schedule event.
 */
function nitwpress_add_cron($sched) {
    if (!array_key_exists('nitwpress', $sched)) {
	$options = get_option('nitwpress_options');
	if (array_key_exists('interval', $options)) {
	    $sched['nitwpress'] = array(
		'interval' => (int)$options['interval'] * 60,
		'display' => __('Schedule for NiTwPress plugins')
	    );
	}
    }
    return $sched;
}

register_activation_hook(__FILE__, 'hitwpress_activation');
register_deactivation_hook(__FILE__, 'nitwpress_deactivation');

add_action('nitwpress_hourly_event', 'nitwpress_update_caches');
add_filter('cron_schedules', 'nitwpress_add_cron');

?>
