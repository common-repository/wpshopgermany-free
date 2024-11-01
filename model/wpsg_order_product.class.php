<?php 

	/**
	 * Klasse, die ein bestelltes Produkt behandelt
	 */
	class wpsg_order_product extends wpsg_model
	{

		public $number;
						
		/**
		 * Lädt die Daten des bestellten Produkts
		 */
		public function load($order_product_id)
		{
		
			parent::load($order_product_id);
				
			$this->data = $this->db->fetchRow("SELECT OP.* FROM `".WPSG_TBL_ORDERPRODUCT."` AS OP WHERE OP.`id` = '".wpsg_q($order_product_id)."' ");
			
			if ($this->data['id'] != $order_product_id) throw new \wpsg\Exception(__('Die Daten eines bestellten Produktes konnten nicht geladen werden', 'wpsg')); 
			
		} // public function load($order_product_id)
				
		public function getTaxValue()
		{
				
			return $this->mwst_value;
				
		} // public function getTaxValue()
		
		public function getOneTaxAmount()
		{
			
			return wpsg_tf(($this->price_brutto) - ($this->price_netto));
			
		} // public function getOneTaxAmount()
		
		/**
		 * Summe der Steuer für alle Mengen
		 */
		public function getTaxAmount()
		{
			
			return wpsg_tf(($this->price_brutto * $this->menge) - ($this->price_netto * $this->menge));
			
		}
		
		public function getOneAmount($brutto_netto = WPSG_BRUTTO)
		{
		
			if ($brutto_netto == WPSG_BRUTTO) return wpsg_tf($this->price_brutto);
			else return wpsg_tf($this->price_netto);
				
		} // public function getOneAmount($brutto_netto = WPSG_BRUTTO)
		
		public function getAmount($brutto_netto = WPSG_BRUTTO)
		{
			
			if ($brutto_netto == WPSG_BRUTTO) return wpsg_tf($this->price_brutto * $this->menge);
			else return wpsg_tf($this->price_netto * $this->menge);
			
		} // public function getAmount()
		
		public function getNumber()
		{
			
			return $this->number;
			
		} // public function getNumber()
		
		public function getProduct()
		{
			
			return $this->shop->cache->loadProductObject($this->getProductId());
			
		} // public function getProduct()
		
		public function getCount()
		{
			
			return $this->menge;
			
		} // public function getCount()
		 
		public function getProductIndex()
		{
			
			return $this->product_index;
			
		}

        public function getProductName()
        {

            $strName = $this->getProduct()->getProductName();

            if ($this->shop->hasMod('wpsg_mod_productvariants') && preg_match('/pv_(.*)/', $this->getProductKey()))
            {

                $vari = $this->shop->callMod('wpsg_mod_productvariants', 'getVariantenInfoArray', array($this->getProductKey()));

                if (wpsg_isSizedString($vari['key'])) $strName .= ' / '.$vari['key'];

            }

            return $strName;

        }
        
        public function getPrice($tax_view = WPSG_NETTO) {
		    
		    if ($tax_view === WPSG_BRUTTO) {
		        
		        return $this->price_brutto;
		        
            } else {
		        
		        return $this->price_netto;
		        
            }
		    
        }

		public function getProductKey()
		{
			
			$var_key = $this->mod_vp_varkey;
			
			if (wpsg_isSizedString($var_key))
			{
				
				return $this->mod_vp_varkey;
				
			}
			else
			{
				
				return $this->productkey;
				
			}
			
		} // public function getProductKey()
		
		public function getProductId()
		{

			$mod_vp_varkey = $this->mod_vp_varkey;
			$productkey = $this->productkey;

			if (wpsg_isSizedString($mod_vp_varkey))
			{
				
				return $this->shop->getProduktID($mod_vp_varkey);
				
			}
			else if (wpsg_isSizedString($productkey))
			{
				
				return $this->shop->getProduktID($productkey);
				
			}
			else
			{
				
				return $this->p_id;
				
			}
			
		} // public function getProductId()
		
	} // class wpsg_order_product extends wpsg_model

?>