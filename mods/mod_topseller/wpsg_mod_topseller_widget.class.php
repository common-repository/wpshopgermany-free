<?php

	class wpsg_mod_topseller_widget extends WP_Widget
	{
		
		var $id = "wpsg_mod_topseller_widget";
		
		function __construct()
		{

			parent::__construct('wpsg_mod_topseller_widget', 'wpShopGermany TopSeller', array(
	    		"description" => __("wpShopGermany TopSeller", 'wpsg')
	    	));
			
		} // function wpsg_mod_topseller_widget
		
		function widget($args, $settings)
	  	{

	  		$template = false; if (wpsg_isSizedString($settings['wpsg_mod_topseller_template'])) $template = $settings['wpsg_mod_topseller_template'];
	  		$limit = $GLOBALS['wpsg_sc']->get_option('wpsg_mod_topseller_limit'); if (wpsg_isSizedString($settings['wpsg_mod_topseller_limit'])) $limit = $settings['wpsg_mod_topseller_limit'];
		    
            $GLOBALS['wpsg_sc']->view['widget_args'] = $args;
		    $GLOBALS['wpsg_sc']->view['widget_settings'] = $settings;
		    
			$GLOBALS['wpsg_sc']->callMod('wpsg_mod_topseller', 'renderTopSeller', array($template, $limit));
	  		
	  	} // function widget($args, $settings)
		
	  	function form($instance)
	  	{

	  		if (isset($instance['wpsg_mod_topseller_template'])) { $wpsg_mod_topseller_template = $instance['wpsg_mod_topseller_template']; } else { $wpsg_mod_topseller_template = false; }
 			if (isset($instance['wpsg_mod_topseller_limit'])) { $wpsg_mod_topseller_limit = $instance['wpsg_mod_topseller_limit']; } else { $wpsg_mod_topseller_limit = false; }
	  		
	  		$GLOBALS['wpsg_sc']->view['arTemplates'] = $GLOBALS['wpsg_sc']->loadProduktTemplates();
 
	  		$GLOBALS['wpsg_sc']->view['wpsg_mod_topseller_template'] = array(
	  			'value' => $wpsg_mod_topseller_template,
	  			'name' => $this->get_field_name('wpsg_mod_topseller_template'),
	  			'id' => $this->get_field_id('wpsg_mod_topseller_template')
	  		);
	  		
	  		$GLOBALS['wpsg_sc']->view['wpsg_mod_topseller_limit'] = array(
	  			'value' => $wpsg_mod_topseller_limit,
	  			'name' => $this->get_field_name('wpsg_mod_topseller_limit'),
	  			'id' => $this->get_field_id('wpsg_mod_topseller_limit')
	  		);
	  		
	  		$GLOBALS['wpsg_sc']->render(WPSG_PATH_VIEW.'/mods/mod_topseller/widget_form.phtml');
	  		
	  	} // function form($instance)
	  	
		function update($new_instance, $old_instance) 
		{ 
			
			$instance = array();
			
			$instance['wpsg_mod_topseller_template'] = $new_instance['wpsg_mod_topseller_template'];
			$instance['wpsg_mod_topseller_limit'] = $new_instance['wpsg_mod_topseller_limit']; 
			
			return $instance; 
		
		} // function update($new_instance, $old_instance)
	  	
	} // class wpsg_mod_topseller_widget extends WP_Widget

?>