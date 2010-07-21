//

(function() {

   tinymce.create('tinymce.plugins.iTunesFavoritesPlugin', {
      init : function(ed, url) {
         ed.addCommand('mceiTunesFavorites', function() {
            ed.windowManager.open({
               file : url + '/mce_itunesfavorites.php',
               width : 400,
               height : 500,
               inline : 1
            }, {
                  plugin_url : url, 
                  some_custom_arg : 'custom arg' 
            });
         });
         

         ed.addButton('iTunesFavorites', {
            title : 'Apps & Music',
            cmd : 'mceiTunesFavorites',
            image : url + '/itunes_icon.png'
         });

         ed.onNodeChange.add(function(ed, cm, n) {
            cm.setActive('iTunesFavorites', n.nodeName == 'IMG');
         });
      },

      createControl : function(n, cm) {
         return null;
      },

      getInfo : function() {
         return {
            longname : 'iTunesFavorites plugin',
            author : 'inZania LLC',
            authorurl : 'http://www.inZania.com',
            infourl : 'http://www.inZania.com/',
            version : "1.0"
         };
      }
   });

   tinymce.PluginManager.add('iTunesFavorites', tinymce.plugins.iTunesFavoritesPlugin);
})();
