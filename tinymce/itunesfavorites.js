function init() {
	tinyMCEPopup.resizeToInnerSize();
}

function insertiTunesFavoritesLink(url) {
	
	var tagtext;
	var add_text = true;
	var error = true;

	var itf = document.getElementById('itunesfavorites_panel');

	//var url = document.getElementById('itunesfavoriteslink').value;;
	var tagtext = "[itunesfavorites]"+url+"[/itunesfavorites]";
	
	if(add_text) {
		window.tinyMCEPopup.execCommand('mceInsertContent', false, tagtext);
	}
	window.tinyMCEPopup.close();
}
