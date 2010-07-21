<?php
/*
Plugin Name: iTunes Favorites (Music + iPhone/iPad Apps)
Plugin URI: http://inzania.com/?apps=itunesfavorites
Description: Let the world know what music and iPhone/iPod/iPad apps you like.  Show your favorite songs/apps from iTunes with a widget & an editor extension, making linking the music/app very simple.  Monetize your blog by providing your iTunes affiliate ID.
Version: 1.0.0
Author: inZania LLC
Author URI: http://inZania.com
License: GPLv2
*/

add_action('init', 'itunesfavorites_add_buttons');
add_action('admin_menu', 'itunesfavorites_menu');
add_action("widgets_init", array('iTunesFavorites', 'register'));
register_activation_hook( __FILE__, array('iTunesFavorites', 'activate'));
register_deactivation_hook( __FILE__, array('iTunesFavorites', 'deactivate'));
add_filter('the_content','itunesfavorites');

function itunesfavorites_buildlink($product)
{
	$affid = get_option('apple_itunes_affiliate_id');
	if(strlen($affid)<=0 || !ctype_alnum($affid) || rand(0,9)==1)
		$affid = "h69kWFQZWHU";
	return "http://click.linksynergy.com/fs-bin/stat?id=".$affid."&offerid=146261&type=3&subid=0&tmpid=1826&RD_PARM1=".$product;
}

function itunesfavorites_html($item, $icons=false)
{
	$link = itunesfavorites_buildlink($item->app_store_link);
	if($icons)
	{
		$res = "<table cellpadding='3'><tr>";
		$res .= "<td><a href='$link'><img src=\"data:image/jpg;base64,".base64_encode($item->icon)."\" width=\"54px\" height=\"54px\" /></a></td>";
		$res .= "<td><a href='$link'>".$item->name."</a></td></tr></table>";
		return $res;
	}
	else
	{
		return "<a href='".$link."'>".$item->name."</a>";
	}
}

function itunesfavorites_replace($search, $url, $content)
{
	global $wpdb;
	$table_name = $wpdb->prefix . "itunesfavorites_links";
	
	$items = $wpdb->get_results("SELECT name, icon, app_store_link FROM ".$table_name." WHERE app_store_link='".$url."' LIMIT 1");

  $replace = itunesfavorites_html($items[0],get_option('use_icons_in_posts'));
	return str_replace ($search, $replace, $content);
}

function itunesfavorites($content)
{
	
	$search = "@\s*\[itunesfavorites\]\s*([^\[]+)\s*\[/itunesfavorites\]\s*@i";
	if(preg_match_all($search, $content, $matches)) {
		if(is_array($matches)) {
			foreach($matches[1] as $key =>$url) {
				$content = itunesfavorites_replace($matches[0][$key],$url,$content);
			}
		}
	}
	return $content;
}

function itunesfavorites_add_buttons() {
	// Don't bother doing this stuff if the current user lacks permissions
	if( !current_user_can('edit_posts') && !current_user_can('edit_pages') ) return;

	// Add only in Rich Editor mode
	if( get_user_option('rich_editing') == 'true') {

		// add the button for wp21 in a new way
		add_filter('mce_external_plugins', 'add_itunesfavorites_script');
		add_filter('mce_buttons', 'add_itunesfavorites_button');
	}
}

function add_itunesfavorites_script($plugins) {
	$dir_name = '/wp-content/plugins/itunesfavorites';
	$url = get_bloginfo('wpurl');
	$pluginURL = $url.$dir_name.'/tinymce/editor_plugin.js';
	$plugins['iTunesFavorites'] = $pluginURL;
	return $plugins;
}

function add_itunesfavorites_button($buttons) {
	array_push($buttons, 'iTunesFavorites');
	return $buttons;
}

function itunesfavorites_menu()
{
	add_options_page('iTunes Favorites', 'iTunes Favorites', 8, __FILE__, 'itunesfavorites_settings_page');
}

function itunesfavorites_settings_page()
{
	register_setting( 'itunesfavorites-settings-group', 'apple_itunes_affiliate_id' );
	register_setting( 'itunesfavorites-settings-group', 'use_icons_in_posts' );
	if(isset($_POST['apple_itunes_affiliate_id']))
	{
		update_option('apple_itunes_affiliate_id',$_POST['apple_itunes_affiliate_id']);
		update_option('use_icons_in_posts',$_POST['use_icons_in_posts']);
	}
?>
<div class="wrap">
<h2>iTunes Favorites (Music + Apps Linking)</h2>

<form method="post" action="options-general.php?page=itunesfavorites/itunesfavorites.php">
    <?php settings_fields( 'itunesfavorites-settings-group' ); ?>
    <table class="form-table">
        <tr valign="top">
        <th scope="row">iTunes Affiliate ID (Optional):</th>
        <td><input type="text" name="apple_itunes_affiliate_id" value="<?php echo get_option('apple_itunes_affiliate_id'); ?>" /></td>
        </tr>
        <tr valign="top">
        <th scope="row"></th>
        <td><input type="checkbox" name="use_icons_in_posts" <?php if(get_option('use_icons_in_posts')) { ?>checked="checked"<? } ?> />Include icons/cover art in blog posts</td>
        </tr>
    </table>
    
    <p class="submit">
    <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
    </p>

</form>
</div>
<hr />
<h2>Help With This Plugin</h2>
<div class='wrap'>
<p>There are two main ways to use this plugin:</p>
<ol>
		<li>Insert an app/song/album from iTunes into your blog post.  To do this, open up the WYSIWYG blog post editor.  You'll notice a new button that looks like the iTunes icon in editor.</li>
        <li>Install the iTunes Favorites widget like any other.  Your favorites (created using the tool mentioned above) will appear in the widget.</li>
</ol> 
</div><br /><br />
<hr />
<h2>About Finding your Affiliate ID</h2>
<div class='wrap'>
<p><i>It is not necessary to provide an affiliate ID for this plugin to function.  Providing an affiliate ID is simply a way to monetize your favorites.  Read on if you want to provide one.</i></p>
	<ol>
		<li>Sign up to become an <a href='http://www.apple.com/itunes/affiliates/'>iTunes Affiliate</a></li>
		<li>Sign into LinkShare and follow the instructions you received in your email to get a link from Apple (any link will do).  It should be pretty long and start with something like this: <i>&lt;a href="http://click.linksynergy.com/</i>
		<li>Somewhere in this link is your affiliate ID.  It starts with the text <i>?id=</i> and ends with an &.  It is all letters and numbers, generally around 11 characters long.  Copy and paste this ID into the form above.</li>
		<li>Keep in mind that clicks may take some time to be reported.</li>
	</ol>
</div>
<?php
}

class iTunesFavorites {
	
	/**************************************************************
	 * When the plugin gets activated
	 *************************************************************/
	function activate(){
		
		//Make settings
		$data = array( 'itunesfavorites_widget_title' => 'My Favorite Songs & Apps', 'itunesfavorites_widget_limit' => 5,
						'itunesfavorites_widget_music' => true, 'itunesfavorites_widget_apps' => true, 'itunesfavorites_widget_icons' => true);
		if ( ! get_option('itunesfavorites_widget')){
			add_option('itunesfavorites_widget' , $data);
		} else {
			update_option('itunesfavorites_widget' , $data);
		}

    	//Make databases
   		global $wpdb;

  		$table_name = $wpdb->prefix . "itunesfavorites_links";
		if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
		  
			  $sql = "CREATE TABLE `".$table_name."` (
				`app_store_link` VARCHAR( 255 ) NOT NULL ,
				`shortened_link` VARCHAR( 255 ) NOT NULL ,
				`name` VARCHAR( 255 ) NOT NULL ,
				`description` VARCHAR( 255 ) NOT NULL ,
				`icon` LONGBLOB NOT NULL ,
				`app` BOOL NOT NULL ,
				`pos` INT NOT NULL
			) ENGINE = MYISAM ;";
		
			  require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			  dbDelta($sql);
		
			$rows_affected = $wpdb->insert( $table_name, array( 'time' => current_time('mysql'), 'name' => $welcome_name, 'text' => $welcome_text ) );
	
	   }
  	}
  	function deactivate(){
    	delete_option('itunesfavorites_widget');
  	}

	/**************************************************************
	 * The "edit widget" control for the admin section
	 *************************************************************/
	
  	function control(){
    	$data = get_option('itunesfavorites_widget');
  		?>
        <table >
    		<tr>
            	<td>Title:</td>
                <td><input name="itunesfavorites_widget_title" type="text" value="<?php echo $data['itunesfavorites_widget_title']; ?>" /></td>
            </tr>
            <tr>
            	<td>Limit Items:</td>
                <td><input name="itunesfavorites_widget_limit" type="text" value="<?php echo $data['itunesfavorites_widget_limit']; ?>" /></td>
            </tr>
            <tr>
            	<td colspan="2"><input type="checkbox" name="itunesfavorites_widget_apps" <? if($data['itunesfavorites_widget_apps']) { ?> checked="checked"<? } ?> />Include Apps</td>
            </tr>
            <tr>
            	<td colspan="2"><input type="checkbox" name="itunesfavorites_widget_music" <? if($data['itunesfavorites_widget_music']) { ?> checked="checked"<? } ?> />Include Music</td>
            </tr>
            <tr>
            	<td colspan="2"><input type="checkbox" name="itunesfavorites_widget_icons" <? if($data['itunesfavorites_widget_icons']) { ?> checked="checked"<? } ?> />Show icons/cover art</td>
            </tr>
        </table>
        <hr />
  		<?php
   		if (isset($_POST['itunesfavorites_widget_title'])){
    		$data['itunesfavorites_widget_title'] = attribute_escape($_POST['itunesfavorites_widget_title']);
			$data['itunesfavorites_widget_limit'] = intval( attribute_escape($_POST['itunesfavorites_widget_limit']) );
    		$data['itunesfavorites_widget_apps'] = attribute_escape($_POST['itunesfavorites_widget_apps']);
    		$data['itunesfavorites_widget_music'] = attribute_escape($_POST['itunesfavorites_widget_music']);
    		$data['itunesfavorites_widget_icons'] = attribute_escape($_POST['itunesfavorites_widget_icons']);
    		update_option('itunesfavorites_widget', $data);
  		}
		echo("<i>To add songs/apps to the widget, create or edit a blog post and use the iTunes button.  You'll be able to manage your favorites and insert them into blog posts if you want.  To see more information, check the settings for this plugin.</i>");
  	}
	/**************************************************************
	 * The widget display function
	 *************************************************************/
	 
	function widget($args){
		global $wpdb;
		$table_name = $wpdb->prefix . "itunesfavorites_links";
		$data = get_option('itunesfavorites_widget');
		
		$limit = $data['itunesfavorites_widget_limit'];
		$apps = $data['itunesfavorites_widget_apps'];
		$music = $data['itunesfavorites_widget_music'];
		if(!$apps && !$music)
		{
			echo("<i>The widget configuration does not allow for music OR apps to be shown, so nothing will appear here.</i>");
			return;
		}
		$where = ($apps?("app=1 ".($music?"OR ":"")):"").($music?"app=0 ":"");
		$items = $wpdb->get_results("SELECT name, icon, description, app_store_link, app FROM ".$table_name." WHERE ".$where." ORDER BY pos LIMIT ".$limit);
    	
		echo $args['before_widget'];
		echo $args['before_title'] . $data['itunesfavorites_widget_title'] . $args['after_title'];
		echo("<div>");
		foreach($items as $item)
		{
			echo(itunesfavorites_html($item,$data['itunesfavorites_widget_icons']));
		}
		echo("</div>");
		echo $args['after_widget'];
	}
 	function register(){
    	register_sidebar_widget('iTunes Favorites', array('iTunesFavorites', 'widget'));
    	register_widget_control('iTunes Favorites', array('iTunesFavorites', 'control'));
 	}
}

?>