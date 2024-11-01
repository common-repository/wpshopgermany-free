(function() {
 
	tinymce.create('tinymce.plugins.wpsgPlugin', {
		
		init : function(ed, url) {
 
			ed.addCommand('mcewpsg', function() {
				ed.windowManager.open({
					file : 'admin.php?page=wpsg-Produkt&action=select&noheader=1',
					width : 350,
					height : 450,
					inline : 1
				}, {
					plugin_url : url
				});
			});
 
			ed.addButton('wpsg', {
				title : 'Produkt aus dem Produktkatalog einfügen',
				cmd : 'mcewpsg',
				image : url + '/../gfx/basket_select.png'
			});
 
			ed.onNodeChange.add(function(ed, cm, n) {
				cm.setActive('wpsg', n.nodeName == 'IMG');
			});
		},
 
		createControl : function(n, cm) {
			
			return null;
			
		},
 
		getInfo : function() {
			return {
				longname : 'wpShopGermany Produktauswahl',
				author : 'maennchen1.de',
				authorurl : 'http://maennchen1.de',
				version : "3.0"
			};
		}
		
	});

	tinymce.PluginManager.add('wpsg', tinymce.plugins.wpsgPlugin);
	
})();
