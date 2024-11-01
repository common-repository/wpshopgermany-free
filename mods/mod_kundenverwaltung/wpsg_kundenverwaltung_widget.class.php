<?php

	/**
	 * Widget Klasse für die Anzeige des Kundenlogin Formulars  
	 */
	class wpsg_kundenverwaltung_widget extends WP_Widget 
	{
		
		var $id = "wpsg_kundenverwaltung_widget";
		
		function __construct()
		{
						
	    	parent::__construct('wpsg_kundenverwaltung_widget', 'wpShopGermany Login', array(
	    		"description" => __("wpShopGermany Login", 'wpsg')
	    	));
	    	
	  	} // function wpsg_login()
	 
	  	function widget($args, $settings)
	  	{

		    $GLOBALS['wpsg_sc']->view['widget_args'] = $args;
		    $GLOBALS['wpsg_sc']->view['widget_settings'] = $settings;

	  		$GLOBALS['wpsg_sc']->render(WPSG_PATH_VIEW.'/mods/mod_kundenverwaltung/widget.phtml');
	  		
	  	} // function widget($args, $settings)
	 
	  	function form($instance) { } // function form($instance)
	 	  
		function update($new_instance, $old_instance) { return $old_instance; } // function update( $new_instance, $old_instance ) {
	  	
	} // class wpsg_kundenverwaltung_widget Extends WP_Widget 
	
?>