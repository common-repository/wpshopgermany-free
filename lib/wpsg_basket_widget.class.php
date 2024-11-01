<?php

	/**
	 * Widget für die Anzeige des Warenkorbes
	 * @author daniel
	 */
	class wpsg_basket_widget extends WP_Widget
	{
		
		var $id = "wpsg_basket_widget";
		
		function __construct()
		{
					 
	    	parent::__construct('wpsg_basket_widget', 'wpShopGermany Warenkorb Widget', array(
	    		"description" => __("wpShopGermany Warenkorb Widget", 'wpsg')
	    	));
	    	
	  	} // function wpsg_login()
	 
	  	function widget($args, $settings)
	  	{
	  			  		 	  		
	  		$GLOBALS['wpsg_sc']->basket->reset();
	  		$GLOBALS['wpsg_sc']->basket->initFromSession();
	  		$GLOBALS['wpsg_sc']->view['basket'] = $GLOBALS['wpsg_sc']->basket->toArray();

	  		if (is_array($GLOBALS['wpsg_sc']->view['basket']) && sizeof($GLOBALS['wpsg_sc']->view['basket']['produkte']) > 0)
	  		{
	  			$GLOBALS['wpsg_sc']->view['countArtikel'] = sizeof($GLOBALS['wpsg_sc']->view['basket']['produkte']);
	  		}
	  		else
	  		{
	  			$GLOBALS['wpsg_sc']->view['countArtikel'] = 0;
	  		}
	  		
	  		$GLOBALS['wpsg_sc']->view['wpsg_versandhinweis'] = $settings['wpsg_versandhinweis'];

            $GLOBALS['wpsg_sc']->view['widget_args'] = $args;
		    $GLOBALS['wpsg_sc']->view['widget_settings'] = $settings;

	  		$GLOBALS['wpsg_sc']->render(WPSG_PATH_VIEW.'/warenkorb/index.phtml');
		  
			$GLOBALS['wpsg_sc']->basket->reset();
		  
		} // function widget($args, $settings)
		
	  	function form($instance) 
	  	{ 
 			
	  		if (isset($instance['wpsg_requestpage'])) { $wpsg_requestpage = $instance['wpsg_requestpage']; } else { $wpsg_requestpage = 0; }
 			if (isset($instance['wpsg_agbpage'])) { $wpsg_agbpage = $instance['wpsg_agbpage']; } else { $wpsg_agbpage = 0; }
 			if (isset($instance['wpsg_wrpage'])) { $wpsg_wrpage = $instance['wpsg_wrpage']; } else { $wpsg_wrpage = 0; }
 			if (isset($instance['wpsg_dspage'])) { $wpsg_dspage = $instance['wpsg_dspage']; } else { $wpsg_dspage = 0; }
 			if (isset($instance['wpsg_vkpage'])) { $wpsg_vkpage = $instance['wpsg_vkpage']; } else { $wpsg_vkpage = 0; }
 			if (isset($instance['wpsg_odrpage'])) { $wpsg_odrpage = $instance['wpsg_odrpage']; } else { $wpsg_odrpage = 0; }
 			if (isset($instance['wpsg_imppage'])) { $wpsg_imppage = $instance['wpsg_imppage']; } else { $wpsg_imppage = 0; }
 			if (isset($instance['wpsg_versandhinweis'])) { $wpsg_versandhinweis = $instance['wpsg_versandhinweis']; } else { $wpsg_versandhinweis = ''; }
 			 
 			?>
 			
	  		<span style="font-weight:bold;"><?php echo __("Seiten unterhalb des Widgets", "wpsg"); ?>:</span><br />
	  		
	  		<?php // if ($this->hasMod('wpsg_mod_request') == '1') { ?>
	  			<label><input type="checkbox" value="1" <?php echo (($wpsg_requestpage == '1')?'checked="checked"':''); ?> name="<?php echo $this->get_field_name('wpsg_requestpage'); ?>" id="<?php echo $this->get_field_id('wpsg_requestpage'); ?>" />&nbsp;<?php echo __('Anfrageliste', 'wpsg'); ?><br /></label>	  		
	  		<?php // } ?>
	  		
	  		<label><input type="checkbox" value="1" <?php echo (($wpsg_agbpage == '1')?'checked="checked"':''); ?> name="<?php echo $this->get_field_name('wpsg_agbpage'); ?>" id="<?php echo $this->get_field_id('wpsg_agbpage'); ?>" />&nbsp;<?php echo __('AGB', 'wpsg'); ?><br /></label>	  	
	  		<label><input type="checkbox" value="1" <?php echo (($wpsg_dspage == '1')?'checked="checked"':''); ?> name="<?php echo $this->get_field_name('wpsg_dspage'); ?>" id="<?php echo $this->get_field_id('wpsg_dspage'); ?>" />&nbsp;<?php echo __('Datenschutzrichtlinien', 'wpsg'); ?><br /></label>
	  		<label><input type="checkbox" value="1" <?php echo (($wpsg_imppage == '1')?'checked="checked"':''); ?> name="<?php echo $this->get_field_name('wpsg_imppage'); ?>" id="<?php echo $this->get_field_id('wpsg_imppage'); ?>" />&nbsp;<?php echo __('Impressum', 'wpsg'); ?><br /></label>
	  		<label><input type="checkbox" value="1" <?php echo (($wpsg_odrpage == '1')?'checked="checked"':''); ?> name="<?php echo $this->get_field_name('wpsg_odrpage'); ?>" id="<?php echo $this->get_field_id('wpsg_odrpage'); ?>" />&nbsp;<?php echo __('Online Streitbeilegung', 'wpsg'); ?><br /></label>
	  		<label><input type="checkbox" value="1" <?php echo (($wpsg_vkpage == '1')?'checked="checked"':''); ?> name="<?php echo $this->get_field_name('wpsg_vkpage'); ?>" id="<?php echo $this->get_field_id('wpsg_vkpage'); ?>" />&nbsp;<?php echo __('Versandkosten', 'wpsg'); ?><br /></label>	  			  			
	  		<label><input type="checkbox" value="1" <?php echo (($wpsg_wrpage == '1')?'checked="checked"':''); ?> name="<?php echo $this->get_field_name('wpsg_wrpage'); ?>" id="<?php echo $this->get_field_id('wpsg_wrpage'); ?>" />&nbsp;<?php echo __('Widerrufsbelehrung', 'wpsg'); ?><br /></label>
	  		<br /> 
	  		<span style="font-weight:bold;"><?php echo __("Versandhinweis", "wpsg"); ?>:</span><br />
	  		<textarea name="<?php echo $this->get_field_name('wpsg_versandhinweis'); ?>" id="<?php echo $this->get_field_id('wpsg_versandhinweis'); ?>" style="width:100%;"><?php echo wpsg_hspc($wpsg_versandhinweis); ?></textarea><br />
	  		<i><?php echo __("Wenn nichts angezeigt wird, so wird der Standardtext \"Alle Preise inklusive MwSt. und zzgl. Versandkosten\" angezeigt!", "wpsg"); ?><br /><?php echo __("Im Text ist HTML Code erlaubt.", "wpsg"); ?></i>
	  		
 			<?php 
	  		
	  	} // function form($instance)
	 	  
		function update($new_instance, $old_instance) 
		{ 

			$instance = array();
			
			$instance['wpsg_requestpage'] = $new_instance['wpsg_requestpage'];
			$instance['wpsg_agbpage'] = $new_instance['wpsg_agbpage'];
			$instance['wpsg_wrpage'] = $new_instance['wpsg_wrpage'];
			$instance['wpsg_dspage'] = $new_instance['wpsg_dspage'];
			$instance['wpsg_vkpage'] = $new_instance['wpsg_vkpage'];
			$instance['wpsg_odrpage'] = $new_instance['wpsg_odrpage'];
			$instance['wpsg_imppage'] = $new_instance['wpsg_imppage'];
			$instance['wpsg_versandhinweis'] = $new_instance['wpsg_versandhinweis'];
			
			return $instance; 
		
		} // function update($new_instance, $old_instance)
	  	
	} // class wpsg_basket_widget extends WP_Widget

?>