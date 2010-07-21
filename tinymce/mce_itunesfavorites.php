<?php
$wpconfig = realpath("../../../../wp-config.php");

function itunesfavorites_GetChunk($str,$prefix,$suffix)
{
	$tmp = $prefix==NULL?$str:strstr($str,$prefix);
	if($tmp!==FALSE)
	{
		$str = $prefix==NULL?$tmp:substr($tmp,strlen($prefix));
		$tmp = strpos($str,$suffix);
		if($tmp!==FALSE)
		{
			$str = substr($str,0,$tmp);
		}
		return $str;
	}
	return FALSE;
}
function itunesfavorites_getAppStoreData($url)
{
	 $useragent = "iTunes/9.0.2 (Macintosh; Intel Mac OS X 10.5.8) AppleWebKit/531.21.8";
	 $header = array("X-Apple-Store-Front: 143441-1");
	
	 $ch = curl_init();
	 curl_setopt($ch, CURLOPT_URL, $url);
	 curl_setopt($ch, CURLOPT_FAILONERROR, 1);
	 curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
	 curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	 curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
	
	 $result = curl_exec($ch);
	
	 curl_close($ch);
	 
	 if($result==NULL || strlen($result)<=0)
	 	return NULL;
	 if(strpos($result,"iTunes")<0)
	 	return NULL;
	
	 $name = trim(itunesfavorites_GetChunk($result,"<iTunes>","</iTunes>"));
	 //$pic_src = itunesfavorites_GetChunk($result,"<View alt=\"\">","</PictureView");
	 //$pic_src = ($pic_src!==FALSE && strlen($pic_src)>0) ? $pic_src : itunesfavorites_GetChunk($result,"<View alt=\"\" />","</PictureView");
	 $pic_src = split("<PictureView leftInset=\"0\" proportional=\"1\"",$result);
	 $pic = itunesfavorites_GetChunk(array_pop($pic_src),"url=\"","\"");
	 $description = itunesfavorites_GetChunk($result,"<TextView topInset=\"0\" leftInset=\"0\" rightInset=\"0\" styleSet=\"normal11\"><SetFontStyle normalStyle=\"descriptionTextColor\">","</SetFontStyle");
	 $description = strlen($description) > 250 ? (substr($description,0,250)."...") : $description;
	 
	 return array("name"=>$name,"icon"=>$pic,"description"=>$description,"url"=>$url);
}

if (!file_exists($wpconfig)) {
	echo "Could not found wp-config.php. Error in path :\n\n".$wpconfig ;	
	die;	
}// stop when wp-config is not there

require_once($wpconfig);
require_once(ABSPATH.'/wp-admin/admin.php');

// check for rights
if(!current_user_can('edit_posts')) die;

global $wpdb;
$table_name = $wpdb->prefix . "itunesfavorites_links";
$message = "";
$tab = "existing";
$item = NULL;

//Verify a link?
if(isset($_POST['itunesfavoriteslink']))
{
	$tab = "url";
	$link = $_POST['itunesfavoriteslink'];
	$item = itunesfavorites_getAppStoreData($link);
	if($item==NULL)
	{
		$message = "<i>Sorry, the URL could not be opened.  Please ensure you properly copied it from iTunes and try again.</i><hr/>";
	}
	else
	{
		$existing = $wpdb->get_results("SELECT name, icon, description, app_store_link FROM ".$table_name." WHERE app_store_link='".$item['url']."'");
		if($existing && is_array($existing) && sizeof($existing))
		{
			$message = "<i>This product is already in your favorites; please select it from that tab (or delete it).</i><hr/>";
			$item = NULL;
		}
	}
}
//Insert a link?
if(isset($_POST['itunesfavorites_confirm']))
{
	$content = '';
	if ($fp = fopen($_POST['itunesfavorites_icon'], 'r')) {
	   while ($line = fgets($fp, 1024)) {
		  $content .= $line;
	   }
	}
	$app = strpos($_POST['itunesfavorites_url'],"/app/") !== false;
	$wpdb->insert( $table_name, array( 'app_store_link' => $_POST['itunesfavorites_url'], 'name' => $_POST['itunesfavorites_name'], 'description' => $_POST['itunesfavorites_description'], 'icon' => $content, 'app' => ($app?1:0)) );
	$message = "<i>The app/music has been added to your favorites.  Select the 'My Favorites' tab above to view it and/or add it to your blog.</i><hr/>";
}
//Remove a link?
if(isset($_POST['itunesfavorites_remove_url']))
{
	$q = "DELETE FROM `".$table_name."` WHERE `app_store_link` = '".$_POST['itunesfavorites_remove_url']."'";
	$wpdb->query($q);
}
if(isset($_POST['itunesfavorites_move_url']))
{
	$url = $_POST['itunesfavorites_move_url'];
	$oldpos = $_POST['pos'];
	$newpos = $oldpos + $_POST['dir'];
	$wpdb->query("UPDATE `".$table_name."` SET pos=".$oldpos." WHERE pos=".$newpos);
	$wpdb->query("UPDATE `".$table_name."` SET pos=".$newpos." WHERE `app_store_link` = '".$url."'");
}

$items = $wpdb->get_results("SELECT name, icon, description, app_store_link, app, pos FROM ".$table_name." WHERE 1 ORDER BY pos");

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>iTunes Favorites (Apps & Music)</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<script language="javascript" type="text/javascript" src="<?php echo get_option('siteurl') ?>/wp-includes/js/tinymce/tiny_mce_popup.js"></script>
	<script language="javascript" type="text/javascript" src="<?php echo get_option('siteurl') ?>/wp-includes/js/tinymce/utils/mctabs.js"></script>
	<script language="javascript" type="text/javascript" src="<?php echo get_option('siteurl') ?>/wp-includes/js/tinymce/utils/form_utils.js"></script>
	<script language="javascript" type="text/javascript" src="<?php echo get_option('siteurl') ?>/wp-content/plugins/itunesfavorites/tinymce/itunesfavorites.js"></script>
	<script language="javascript" type="text/javascript">
		var old=false;
	</script>
	<base target="_self" />
</head>
<body id="link" onload="tinyMCEPopup.executeOnLoad('init();');document.body.style.display='';document.getElementById('youtuber_tab').focus();" style="display: none">
<!-- <form onsubmit="insertLink();return false;" action="#"> -->
	<div class="tabs">
		<ul>			
			<li id="itunesfavorites_existing_tab" <? if($tab=="existing") { ?>class="current" <? } ?>><span><a href="javascript:mcTabs.displayTab('itunesfavorites_existing_tab','itunesfavorites_existing_panel');" onmousedown="return false;">My Favorites (add to blog post)</a></span></li>
			<li id="itunesfavorites_url_tab" <? if($tab=="url") { ?>class="current" <? } ?>><span><a href="javascript:mcTabs.displayTab('itunesfavorites_url_tab','itunesfavorites_panel');" onmousedown="return false;">Import New App/Music</a></span></li>
			<li id="itunesfavorites_help_tab"><span><a href="javascript:mcTabs.displayTab('itunesfavorites_help_tab','itunesfavorites_help_panel');" onmousedown="return false;">Help</a></span></li>
		</ul>
	</div>
	
	<div class="panel_wrapper" style="height: 440px;">
        
        <!-- PANEL FOR EXISTING FAVORITES -->
		<div id="itunesfavorites_existing_panel" style="overflow:scroll; height:440px;" class="panel<? if($tab=="existing") { ?> current<? } ?>">
		<br />
        <table>
        <? if(!is_array($items) || sizeof($items)<=0) { ?>
        <i>You do not have any favorites yet.  Use the 'Import new App/Music' tab.</i>
        <? } else { foreach($items as $k=>$i) { ?>
        
        <tr>
        	<td colspan="4">
            	<b><?= $i->app ? "[App]" : "[Music]" ?> <?= $i->name ?></b>
            </td>
        </tr>
        <tr>
        	<td valign="top"><img src="data:image/jpg;base64,<?= base64_encode($i->icon); ?>" width="54px" height="54px" /></td>
            <td align="left">
                <form name="iTunesFavorites" action="mce_itunesfavorites.php" method="post">
                	<input type="submit" id="insert" name="insert_in_post" style="width:300xp;" value="<?php _e("Insert in Post", 'itunesfavorites'); ?>" onclick="insertiTunesFavoritesLink('<?= $i->app_store_link ?>');" />
                </form>
            </td>
            <td width="100px" />
            <td>
                <form name="iTunesFavorites" action="mce_itunesfavorites.php" method="post">
                	<input type="hidden" name="itunesfavorites_remove_url" value="<?= $i->app_store_link ?>" />
                	<input type="submit" id="insert" name="remove" value="<?php _e("Delete Fav.", 'itunesfavorites'); ?>" />
                </form>
            </td>
        </tr>
        <tr>
        	<td></td>
            <td><? if($k < (sizeof($items)-1)) { ?>
            	<form name="iTunesFavorites" action="mce_itunesfavorites.php" method="post">
                	<input type="hidden" name="pos" value="<?= $i->pos ?>" />
                	<input type="hidden" name="dir" value="1" />
                    <input type="hidden" name="itunesfavorites_move_url" value="<?= $i->app_store_link ?>" />
            		<input type="submit" id="insert" name="remove" value="<?php _e("Move Down", 'itunesfavorites'); ?>" />
                </form> <? } ?>
            </td>
            <td></td>
            <td align="right"><? if($k > 0) { ?>
            	<form name="iTunesFavorites" action="mce_itunesfavorites.php" method="post">
                	<input type="hidden" name="pos" value="<?= $i->pos ?>" />
                	<input type="hidden" name="dir" value="-1" />
                    <input type="hidden" name="itunesfavorites_move_url" value="<?= $i->app_store_link ?>" />
            		<input type="submit" id="insert" name="remove" value="<?php _e("Move Up", 'itunesfavorites'); ?>" />
                </form><? } ?>
            </td>
        </tr>
        <tr>
        	<td colspan="4"><hr /></td>
        </tr>
        <? } } ?>
        </table>
		</div>
    	
    	<!-- PANEL FOR NEW SELECTIONS -->
		<div id="itunesfavorites_panel" class="panel<? if($tab=="url") { ?> current<? } ?>">
			<form name="iTunesFavorites" action="<? if($item==NULL) { echo("mce_itunesfavorites.php"); } else { echo("#"); } ?>" method="post">
			<br />
        	<?= $message ?>
            <? if($item==NULL) { ?>
			<table border="0" cellpadding="3" cellspacing="0" width="100%">
				<tr>
					<td nowrap="nowrap" valign="top">
						<label><?php _e('Paste the URL to the iTunes page for the app/song/album here.<br/>For help, see the help tab above.', 'itunesfavorites'); ?></label>
					</td>
				</tr>
				<tr>
					<td  nowrap="nowrap" valign="top">
						<input type="text" id="itunesfavoriteslink" name="itunesfavoriteslink" style="width: 100%" value="<?php _e('URL', 'itunesfavorites'); ?>" onclick="if(!old) { this.value=''; old=true; }"/>
					</td>
				</tr>
			</table>
            <? } else { ?>
            	<input type="hidden" name="itunesfavorites_confirm" value="true" />
            	<table border="0" cellpadding="3" cellspacing="0" width="100%">
				<tr>
					<td nowrap="nowrap" valign="top" align="left">
            			<a href="<?= $item['url'] ?>" target="_blank"><img src="<?= $item['icon'] ?>" border="0" /></a>
                        <input type="hidden" name="itunesfavorites_icon" value="<?= $item['icon'] ?>" />
                        <input type="hidden" name="itunesfavorites_url" value="<?= $item['url'] ?>" />
					</td>
					<td align="left">
                    	<b><input type="text" id="itunesfavorites_name" name="itunesfavorites_name" style="width: 100%" value="<?php _e($item['name'], 'itunesfavorites'); ?>" onclick="if(!old) { this.value=''; old=true; }"/></b>
                        <i><textarea style="width:100%; height:150px;" name="itunesfavorites_description"><?= $item['description'] ?></textarea></i>
                    </td>
                </tr>
                </table>
            <? } ?>
            
            <div class="mceActionPanel">
                <div style="float: right">
                    <input type="submit" id="insert" name="insert" value="<?php _e($item==NULL ? "Find" : "Confirm", 'itunesfavorites'); ?>" />
                </div>
            </div>
			</form>
		</div>
        
        <!-- PANEL FOR HELP -->
		<div id="itunesfavorites_help_panel" class="panel">
		<br />
        To add a new favorite, you need to have iTunes installed on your computer.  Open up the iTunes Store within iTunes and search for any album, app, music, etc.  Once you've found one you like, right click on it (cmd+click on Mac) and choose "Copy Link."  Paste this link into the "Import App/Music" tab above and viola!  You have added the song/app as a favorite.  Now all you have to do is open up the favorites tab and pick one to insert into your blog post.<br /><br />If you have the widget installed, your favorites will be shown in the widget.  More customization options are available in the settings.
		</div>
	</div>

</body>
</html>
