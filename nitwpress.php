<?php
/*
Plugin Name: NiTwPress
Plugin URI: http://sakuratan.biz/contents/NiTwPress
Description: NiTwPress is a Twitter client for WordPress sidebar widget. It displays your twit on the WordPress sidebar with comment scrolling like Niconico-doga. (NiTwPress is an abbreviation of `NIconico-doga like TWitter client for wordPRESS'.)
Author: sakuratan
Author URI: http://sakuratan.biz/
Version: 0.9.1.3
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
 * Returns options.
 */
function nitwpress_get_options() {
    $defaults = array(
	'username' => '',
	'password' => '',
	'widgettitle' => '',
	'widgetstyles' => 'text-align:center',
	'fontcolor' => 'auto',
	'linkcolor' => 'auto',
	'interval' => 15,
	'logo' => true,
	'iconframe' => false,
	'iconframecolor' => '#c0c0c0'
    );

    return array_merge($defaults, get_option('nitwpress_options', array()));
}

/*
 * Update options.
 */
function nitwpress_update_options(&$newvars) {
    $options = nitwpress_get_options();

    $options['logo'] = false;
    $options['iconframe'] = false;

    foreach ($options as $key => $value) {
	if (array_key_exists($key, $newvars)) {
	    if (($key == 'logo' || $key == 'iconframe') && $value) {
		$options[$key] = true;
	    } elseif ($key == 'interval') {
		$options[$key] = (int)$newvars[$key];
	    } else {
		$options[$key] = $newvars[$key];
	    }
	}
    }

    update_option('nitwpress_options', $options);
}

/*
 * Update cache files.
 */
function nitwpress_update_caches() {
    require_once dirname(__file__) . '/twitter.php';

    $options = nitwpress_get_options();
    if (!$options['username'])
	return;

    nitwpress_twitter_update_caches(NITWPRESS_CACHEDIR, $options['username'],
				    $options['password']);
}

/*
 * Implode with rawurlencode.
 */
function nitwpress_rawurlencode_array(&$arr) {
    if (!$arr)
	return '';

    $s = '';
    foreach ($arr as $key => $value) {
	if ($s)
	    $s .= '&';
	$s .= rawurlencode($key) . '=' . rawurlencode($value);
    }
    return $s;
}

/*
 * The widget.
 */
function nitwpress_sidebar_widget($args) {
    extract($args);

    echo $before_widget;

    $options = nitwpress_get_options();
    if ($options['username']) :
	$username = htmlspecialchars($options['username']);
	$siteurl = get_option('siteurl');
	$plugin = $siteurl . NITWPRESS_PLUGINS;
	$swf = htmlspecialchars("{$plugin}nitwpress.swf");
	$base = htmlspecialchars("{$siteurl}" . NITWPRESS_CACHES);

	$_flashvars = array();

	if ($options['fontcolor'] &&
	    strcasecmp($options['fontcolor'], 'auto') != 0) {
	    $_flashvars['fontcolor'] = preg_replace('/^#*/', '',
						    $options['fontcolor']);
	}

	if ($options['linkcolor'] &&
	    strcasecmp($options['linkcolor'], 'auto') != 0) {
	    $_flashvars['linkcolor'] = preg_replace('/^#*/', '',
						    $options['linkcolor']);
	}

	if (!$options['logo']) {
	    $_flashvars['disablelogo'] = '1';
	}

	if ($options['iconframe']) {
	    $_flashvars['iconframe'] = '1';
	    $_flashvars['iconframecolor'] = preg_replace('/^#*/', '',
						$options['iconframecolor']);
	}

	$flashvars = nitwpress_rawurlencode_array($_flashvars);

	if ($options['widgetstyles']) {
	    $style = ' style="'.htmlspecialchars($options['widgetstyles']).'"';
	} else {
	    $style = '';
	}

	if ($options['widgettitle']) :
?>
<h2 class="widgettitle"><?php echo htmlspecialchars($options['widgettitle']) ?></h2>
<?php
	endif;
?>
<div class="nitwpress_widget_content"<?php echo $style ?>>
  <object codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,0,0" width="154" height="154" classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000">
    <param name="movie" value="<?php echo $swf ?>" />
    <param name="quality" value="high" />
    <param name="bgcolor" value="#ffffff" />
    <param name="base" value="<?php echo $base ?>" />
    <param name="flashvars" value="<?php echo $flashvars ?>" />
    <script type="text/javascript">
//<!--
      document.write('<embed pluginspage="http://www.macromedia.com/go/getflashplayer" type="application/x-shockwave-flash" width="154" height="154" src="<?php echo $swf ?>" quality="high" base="<?php echo $base ?>" flashvars="<?php echo $flashvars ?>"></embed>');
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
    if (array_key_exists('nitwpress_action', $_POST)) {
	nitwpress_update_options($_POST);
	nitwpress_update_caches();
    }
    $options = nitwpress_get_options();

?>
<form method="post">
  <h3>Twitter account</h3>
  <table>
    <tr>
      <td>Username</td>
      <td><input type="text" name="username" value="<?php echo htmlspecialchars($options['username']) ?>" /></td>
    </tr>

    <tr>
      <td>Password</td>
      <td><input type="password" name="password" value="<?php echo htmlspecialchars($options['password']) ?>" /></td>
    </tr>
  </table>

  <h3>Widget title</h3>

  <div><input type="text" name="widgettitle" value="<?php echo htmlspecialchars($options['widgettitle']) ?>" style="width:100%" /></div>

  <p>(The widget suppress the widget title when this field is empty.)</p>

  <h3>CSS for widget content</h3>

  <div><input type="text" name="widgetstyles" value="<?php echo htmlspecialchars($options['widgetstyles']) ?>" style="width:100%" /></div>
  <p>(The widget content area have &quot;nitwpress_widget_content&quot; class. You can use the CSS class for design the widget with out style attribute of &lt;div&gt; tag for the widget content area. In this case, clear this field for suppress the style attribute.)</p>

  <h3>Font colors</h3>

  <table>
    <tr>
      <td>Comments</td>
      <td><input type="text" name="fontcolor" value="<?php echo htmlspecialchars($options['fontcolor']) ?>" size="7" /></td>
    </tr>
    <tr>
      <td>Links</td>
      <td><input type="text" name="linkcolor" value="<?php echo htmlspecialchars($options['linkcolor']) ?>" size="7" /></td>
    </tr>
  </table>

  <p>(Use hash color code (e.g. #ffffff) or &quot;auto&quot; for these fields.
  HTML color name (e.g. white) is not acceptable.
  The widget will read default font and link colors from Twitter API if you choose &quot;auto&quot;.)</p>

  <h3>Frame for icon image</h3>

  <p><input type="checkbox" id="nitwpress_iconframe_checkbox" name="iconframe" value="1" <?php if ($options['iconframe']) { echo 'checked="checked"'; } ?> />
  <label for="nitwpress_iconframe_checkbox">Enable icon image frame.</label></p>
  <p>Color of icon frame: <input type="text" name="iconframecolor" value="<?php echo htmlspecialchars($options['iconframecolor']) ?>" size="7" /><br />
  (Use hash color code (e.g. #ffffff) for the color of icon frame.
  HTML color name (e.g. white) is not acceptable.)</p>

  <h3>Miscellaneous options</h3>

  <p>Update timeline cache at every <input type="text" name="interval" value="<?php echo htmlspecialchars($options['interval']) ?>" size="3" /> minutes.</p>

  <p><input type="checkbox" id="nitwpress_logo_checkbox" name="logo" value="1" <?php if ($options['logo']) { echo 'checked="checked"'; } ?> />
  <label for="nitwpress_logo_checkbox">Display NiTwPress logo on Flash.</label></p>

  <div><input type="hidden" name="nitwpress_action" /></div>
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
