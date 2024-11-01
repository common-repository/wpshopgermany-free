<?php

	/**
	 * Widget für die Positionierung des Trusted Shops Widgets auf der Homepage
	 * @author daniel
	 */
	class wpsg_mod_trustedshops_widget extends WP_Widget 
	{
		
		var $id = "wpsg_mod_trustedshops_widget";
		
		function __construct()
		{
						
	    	parent::__construct('wpsg_mod_trustedshops_widget', 'Trusted Shops Widget', array(
	    		"description" => __("Trusted Shops Siegel/Bewertung", 'wpsg')
	    	));
	    	
	  	} // function wpsg_login()
	  	
	  	function widget($args, $settings)
	  	{

	  		$GLOBALS['wpsg_sc']->view['widget'] = &$this;	  		
  			$GLOBALS['wpsg_sc']->view['siegelURL'] = $GLOBALS['wpsg_sc']->callMod('wpsg_mod_trustedshops', 'getSiegelURL');

            $GLOBALS['wpsg_sc']->view['widget_args'] = $args;
		    $GLOBALS['wpsg_sc']->view['widget_settings'] = $settings;

	  		$GLOBALS['wpsg_sc']->render(WPSG_PATH_VIEW.'/mods/mod_trustedshops/widget.phtml');
	  		
	  	} // function widget($args, $settings)
	  	
	  	function form($instance) 
	  	{ 
	  		
	  		$GLOBALS['wpsg_sc']->view['widget'] = &$this;
 
	  		if (wpsg_isSizedInt($instance['wpsg_mod_trustedshops_vote'])) { $GLOBALS['wpsg_sc']->view['wpsg_mod_trustedshops_vote'] = $instance['wpsg_mod_trustedshops_vote']; } else { $GLOBALS['wpsg_sc']->view['wpsg_mod_trustedshops_vote'] = 0; }
 			if (wpsg_isSizedInt($instance['wpsg_mod_trustedshops_badge'])) { $GLOBALS['wpsg_sc']->view['wpsg_mod_trustedshops_badge'] = $instance['wpsg_mod_trustedshops_badge']; } else { $GLOBALS['wpsg_sc']->view['wpsg_mod_trustedshops_badge'] = 0; }
 			 	  		
	  		$GLOBALS['wpsg_sc']->render(WPSG_PATH_VIEW.'/mods/mod_trustedshops/widget_form.phtml');
	  		
	  	} // function form($instance)
	 	  
		function update($new_instance, $old_instance) 
		{
			 
			$instance = array();
 
			$instance['wpsg_mod_trustedshops_vote'] = $new_instance['wpsg_mod_trustedshops_vote'];
			$instance['wpsg_mod_trustedshops_badge'] = $new_instance['wpsg_mod_trustedshops_badge']; 
			
			return $instance;  
		
		} // function update($new_instance, $old_instance) 
		
	} // class wpsg_mod_trustedshops_widget extends WP_Widget

?>