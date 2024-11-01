<?php

    /**
     * User: Daschmi (daschmi@daschmi.de)
     * Date: 01.09.2017
     * Time: 08:04
     */

    /**
     * In dieser Klasse sind die Funktionen zum Zugriff auf die Produktbilder gekapselt
     * Ersetzt das alte Produktbilder Modul
     */
    class wpsg_imagehandler 
    {
        
        /** @var wpsg_ShopController */
        private $shop = null;
        
        public function __construct()
        {
            
            $this->shop = &$GLOBALS['wpsg_sc'];
            $this->db = &$GLOBALS['wpsg_db'];
            
        } // public function __construct()
        
        /**
		 * Gibt die ID des Anhangs zu einem ProduktKey zurück
		 * 
		 * @author Daschmi
		 * @param $product_key
		 */
		public function getAttachmentID($product_key, $vari_id = false, $all = false) {
			
			$arAttachmentIDs = $this->getAttachmentIDs($product_key, $vari_id, $all);
				
			if (wpsg_isSizedArray($arAttachmentIDs)) {

                // return array_values($arAttachmentIDs)[0];

                $arValues = array_values($arAttachmentIDs);
                return $arValues[0];

            }
			else return false;
			
		} // public function getAttachmentID($product_key)
				
		public function addImageToProduct($file, $product_id) {
			
			// Siehe
			// https://codex.wordpress.org/Function_Reference/wp_insert_attachment
			
			if (!wpsg_isSizedString($file)) return null;
			
			$wp_upload_dir = wp_upload_dir();
			
			$mt_filetype = wp_check_filetype(basename($file), null);
			$mt_filename = $wp_upload_dir['path'].'/'.basename($file);
					
			// Neuer Dateiname muss eindeutig sein Suffixen
			$i = 1; while(true) {
				
				if (!file_exists($mt_filename)) break;
				else {
					
					$mt_filename = preg_replace('/\.(?!.*\.)/', '_'.$i.'.', $mt_filename); 
					
				}
				
				if ($i > 100) throw new \wpsg\Exception("Systemfehler!");
				
				$i ++;
				
			}
			
			copy($file, $mt_filename);
			
			$attachment = array(
				'guid' => wpsg_q($wp_upload_dir['url'].'/'.basename($mt_filename)),
				'post_mime_type' => wpsg_q($mt_filetype['type']),
				'post_title' => wpsg_q(preg_replace('/\.[^.]+$/', '', basename($mt_filename))),
				'post_excerpt' => wpsg_q(basename($file)),
				'post_status' => 'inherit'
			);
			
			$attachment_id = wp_insert_attachment($attachment, $mt_filename, '0');
			
			require_once(ABSPATH.'wp-admin/includes/image.php');
			
			$attach_data = wp_generate_attachment_metadata($attachment_id, $mt_filename);
			wp_update_attachment_metadata($attachment_id, $attach_data);
			
			add_post_meta($attachment_id, 'wpsg_produkt_id', $product_id);
			
			return $attachment_id;
			
		} // public function addImageToProduct($file, $product_id)
	
		/**
		 * Gibt die IDs der Anhänge zurück (Alle Bilder eines Produkts)
		 * Beachtet die Reihenfolge aus der Spalte "postids"
		 *
		 * - Alle Bilder anhand des ProduktKeys:
		 * getAttachmentIDs('pv_1|1:1|2:3');
		 *
		 * - Alle gesetzten Bilder einer speziellen Variation (Nur gesetzte in Reihenfolge)
		 * - Hier darf $product_key nur die ID enthalten
		 * getAttachmentIDs(1, 2)
		 *
		 * - Alle Bilder einer speziellen Variation (In Reihenfolge)
		 * - Hier darf $product_key nur die ID enthalten
		 * getAttachmentIDs(1, 2, true)
		 *
		 *
		 * @author Daschmi
		 * @param String $product_key
		 * @return array
		 * @throws \wpsg\Exception
		 */
		public function getAttachmentIDs($product_key, $vari_id = false, $all = false)
		{
			
			$product_id = $this->shop->getProduktID($product_key);
		 
			$arAttachmentIDs = $this->db->fetchAssocField("
				SELECT 
					P.`ID` 
			  	FROM 
			  		`".$this->shop->prefix."postmeta` AS PM
			  		 	LEFT JOIN `".$this->shop->prefix."posts` AS P ON (PM.`post_id` = P.`ID`)
			  	WHERE 
			  		PM.`meta_key` = 'wpsg_produkt_id' AND 
			  		PM.`meta_value` = '".wpsg_q($product_id)."' AND
			  		P.`ID` IS NOT NULL
			");

            // Keine Sortierung, dann nach IDs, Sortierung kommt weiter unten
            asort($arAttachmentIDs);
            
			// Die Sortierung steht in der Spalte postids im Produkt
			$postids = $this->db->fetchOne("SELECT `postids` FROM `".WPSG_TBL_PRODUCTS."` WHERE `id` = '".wpsg_q($product_id)."' ");
						
			if (strpos($postids, ',') !== false)
			{
				
				$arPostIDsOrder = explode(',', $postids);
				 
				// Die Elemente in der Reihenfolge aus $arPostIDsOrder, die auch in $arAttachmentIDs vorkommen
				$arAttachmentIDsIntersect = array_intersect($arPostIDsOrder, $arAttachmentIDs);
				
				// Elemente, die in $arAttachment drin sind, aber nicht in $arPostIDsOrder
				$arDiff = array_diff($arAttachmentIDs, $arPostIDsOrder);
				$arAttachmentIDs = $arAttachmentIDsIntersect + $arDiff;
				
				
			}  
			 
			// Jetzt sind in $arAttachmentIDs alle Bilder dieses Produktes
			if ($this->shop->hasMod('wpsg_mod_productvariants') && $this->shop->callMod('wpsg_mod_productvariants', 'isVariantsProductKey', array($product_key)))
			{
			
				// Bei einem Variantenprodukt muss ich jetzt noch filtern ...
				$arVariInfo = $this->shop->callMod('wpsg_mod_productvariants', 'getVariantenInfoArray', array($product_key));
				
				return array_values($arVariInfo['images']);
                                
			}
			else if ($this->shop->hasMod('wpsg_mod_productvariants') && wpsg_isSizedInt($vari_id))
			{

				// Daten der Produktvariation laden
				$row = $this->db->fetchRow("SELECT `images`, `images_set` FROM `".WPSG_TBL_PRODUCTS_VARIATION."` WHERE `product_id` = '".wpsg_q($product_id)."' AND `variation_id` = '".wpsg_q($vari_id)."' ");
				
				if ($row !== null) {
					
					list($images, $images_set) = array_values($this->db->fetchRow("SELECT `images`, `images_set` FROM `".WPSG_TBL_PRODUCTS_VARIATION."` WHERE `product_id` = '".wpsg_q($product_id)."' AND `variation_id` = '".wpsg_q($vari_id)."' "));
					
				} else {
					
					$images = '';
					$images_set = '';
					
				}
				
				$arAttachmentIDsProductVari = explode(',', $images);
				
				// Die Elemente, in der Reihenfolge aus $arAttachmentIDs. die auch in $arAttachmentIDsProductVari enthalten sind
				$arAttachmentIDsIntersect = array_intersect($arAttachmentIDsProductVari, $arAttachmentIDs);
				
				// Die Elemente, die in $arAttachment drin sind, aber nicht in $arAttachmentIDsProductVari
				$arDiff = array_diff($arAttachmentIDs, $arAttachmentIDsProductVari);
				$arAttachmentIDs = $arAttachmentIDsIntersect + $arDiff;
				 	
				if ($all === true)
				{
					
					return array_values($arAttachmentIDs);
					
				}
				else
				{
					
					// Jetzt noch aus der Reihenfolge die gesetzten filtern					
					$arSet = wpsg_trim(explode(',', $images_set));
					$arAttachmentIDsReturn = array();
					
					foreach ($arSet as $attachment_id)
					{
						
						if (array_search($attachment_id, $arAttachmentIDs)) $arAttachmentIDsReturn[] = $attachment_id;
						
					}
					
					return array_values($arAttachmentIDsReturn);
					
				}
				
			}
			
			return array_values($arAttachmentIDs); 
			
		} // public function getAttachmentIDs($product_id)

        /* Backend Funktionen */

        /**
         * Zeichnet die Liste der Bilder für das Backend der Produktverwaltung
         * @param $product_id
         */
        public function getProductListBackend($product_id, $size = 'thumbnail')
        {
            
            $this->shop->view['productImages'] = $this->getAttachmentIDs($product_id);
            
			return $this->shop->render(WPSG_PATH_VIEW.'/produkt/images_list.phtml', false);
            
        } // public function getProductListBackend($product_id)
        
    } // class wpsg_imagehandler