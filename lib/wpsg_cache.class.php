<?php

	/**
	 * Diese Klasse soll Anfragen Cachen, benötigt zum Beispiel mehrere Module die Datenbankdaten einer Bestellung so sollen unnötige Datenbankanfragne verhindert werden 
	 */
	class wpsg_cache 
	{

		var $_db; // Das Datenbankobjekt
		
		var $_arOrder; // Array mit den geladenen Bestellungen
		var $_arOrderObjects; // Array mit den geladenene Bestellobjekten
		var $_arBasket; // Array mit initiierten Warenkörben, Index ist die Bestellung
		var $_arKunden; // Array mit den geladenen Kunden
		var $_arCustomerObjects; // Array mit geladenene Kundenobjekten
		var $_arProducts; // Array mit den geladenen Produktdaten aus der DB 
		var $_arProductObjects; // Array mit geladenen Produktobjekten
		var $_arMwSt; // Array mit den MwSt. Sätzen
		var $_BasketArray; // Array mit dem Warenkorb der aktuellen Session als Array
		var $_arCountry; // Array mit den Datensätzen aus der Ländertabelle
		
		var $_arMwStDB;
		
		public function __construct($db)
		{
			
			$this->_db = $db;
			
			$this->_arOrder = array();
			$this->_arBasket = array();
			$this->_arKunden = array();
			$this->_arCustomerObjects = array();
			$this->_arProducts = array(); 
			$this->_arProductObjects = array();
			$this->_arMwSt = array();
			$this->_arMwStDB = false;
			$this->_BasketArray = false;
			$this->_arOrderObjects = array();
			$this->_arCountry = [];
			
		} // public function __construct()
		
		/**
		 * Gibt einen Länderdatensatz aus der Datenbank zurück
		 * Wenn als $country_id 0 übergeben wird, werden alle Länder zurückgegeben
		 * @param int $country_id
		 */
		public function getCountry($country_id = 0) {
			
			if (!array_key_exists($country_id, $this->_arCountry)) {
				
				if ($country_id === 0) {
				
					$this->_arCountry[$country_id] = $this->_db->fetchAssoc("SELECT * FROM `".WPSG_TBL_LAND."` ", "id");
						
				} else {
					
					$this->_arCountry[$country_id] = $this->_db->fetchAssoc("SELECT * FROM `".WPSG_TBL_LAND."` WHERE `id` = '".wpsg_q($country_id)."' ", "id");
					
				}
								
			}
			
			return $this->_arCountry[$country_id];
			
		}
		
		/**
		 * Lädt die reinen Bestelldaten aus der Datenbank und gibt sie zurück
		 */
		public function loadOrder($order_id, $noCache = false)
		{
			
			if (!array_key_exists($order_id, $this->_arOrder) || $noCache === true)
			{
					
				$this->_arOrder[$order_id] = $this->_db->fetchRow("
					SELECT
						*
					FROM
						`".WPSG_TBL_ORDER."` 
					WHERE
						`id` = '".wpsg_q($order_id)."'
				");
				
			}
			
			return $this->_arOrder[$order_id];
			
		} // public function loadOrder($order_id)
		
		/**
		 * Lädt ein Bestellobjekt anhand der BestellID
		 * @return wpsg_order Bestellung als Objekt
		 */
		public function loadOrderObject($order_id)
		{
			
			if (!array_key_exists($order_id, $this->_arOrderObjects))
			{
				
				$this->_arOrderObjects[$order_id] =	new wpsg_order($order_id);
				$this->_arOrderObjects[$order_id]->load($order_id);
				
			}
			
			return $this->_arOrderObjects[$order_id];
			
		}
		
		public function clearOrderCache($order_id)
		{
			
			if ($order_id == false)
			{
				
				$this->_arOrder = array();
				$this->_arOrderObjects = array();
				
			}
			else
			{
			
				if (array_key_exists($order_id, $this->_arOrder)) unset($this->_arOrder[$order_id]);
				if (array_key_exists($order_id, $this->_arOrderObjects)) unset($this->_arOrderObjects[$order_id]);
				if (array_key_exists('wpsg_order_'.$order_id, wpsg_order::$_objCache)) unset(wpsg_order::$_objCache['wpsg_order_'.$order_id]);
				
			}
			
		} // public function clearOrderCache($order_id)
		 		
		public function clearKundenCache($kunde_id = false)
		{
			
			if ($kunde_id == false)
			{
				
				$this->_arKunden = array();
				$this->_arCustomerObjects = array();
				
			}
			else
			{
				
				if (array_key_exists($kunde_id, $this->_arKunden)) unset($this->_arKunden[$kunde_id]);
				if (array_key_exists($kunde_id, $this->_arCustomerObjects)) unset($this->_arCustomerObjects[$kunde_id]);
				if (array_key_exists('wpsg_customer'.$kunde_id, wpsg_customer::$_objCache)) unset(wpsg_customer::$_objCache['wpsg_customer'.$kunde_id]);
				
			}
			
		} // public function clearKundenCache($kunde_id = false)
 
		/**
		 * Lädt die reinen Produktdaten aus der Datenbank und gibt sie zurück
		 * Besser loadProduktArray aus Shop verwenden da dies Übersetzung etc. berücksichtigt
		 */
		public function loadProduct($product_id)
		{
			
			if (!array_key_exists($product_id, $this->_arProducts))
			{
					
				$this->_arProducts[$product_id] = $this->_db->fetchRow("
					SELECT
						*
					FROM
						`".WPSG_TBL_PRODUCTS."` 
					WHERE
						`id` = '".wpsg_q($product_id)."'
				");
				
				$this->_arProducts[$product_id]['product_key'] = $product_id;
				
			}
			
			return $this->_arProducts[$product_id];
			
		} // public function loadProduct($product_id)
		
		/**
		 * Lädt ein Produkt und gibt es als Objekt zurück
		 * @param \Integer $product_id
		 * @return wpsg_product Produktobjekt
		 */
		public function loadProductObject($product_id)
		{
			
			if (!array_key_exists($product_id, $this->_arProductObjects))
			{
			
				$this->_arOrderObjects[$product_id] =	new wpsg_product();
				$this->_arOrderObjects[$product_id]->load($product_id);
							
			}
				
			return $this->_arOrderObjects[$product_id];
			
		} // public function loadProductObject($product_id)
		
		/**
		 * Löscht den Object Cache eines Produktes
		 * @param string $product_id
		 */
		public function clearProductCache($product_id = false)
		{
			
			if ($product_id === false)
			{
				
				$this->_arProducts = array();
				$this->_arProductObjects = array();
				
			}
			else
			{
				
				if (array_key_exists($product_id, $this->_arProducts)) unset($this->_arProducts[$product_id]);
				if (array_key_exists($product_id, $this->_arProductObjects)) unset($this->_arProductObjects[$product_id]);
				if (array_key_exists('wpsg_product'.$product_id, wpsg_product::$_objCache)) unset(wpsg_product::$_objCache['wpsg_product'.$product_id]);
				
			}
			
		} // public function clearProductCache($product_id = false)
		
		/**
		 * Lädt die reinen Kundendaten aus der Datenbank und gibt sie zurück
		 */
		public function loadKunden($kunde_id, $noCache = false) 
		{

			if (!array_key_exists($kunde_id, $this->_arKunden) || $noCache === true)
			{
					
				$this->_arKunden[$kunde_id] = $this->_db->fetchRow("
					SELECT
						C.*,
						CA.`title`, CA.`name`, CA.`vname`, CA.`firma`, CA.`fax`, CA.`strasse`, CA.`nr`, CA.`plz`, CA.`ort`, CA.`land`, CA.`tel` 
					FROM
						`".WPSG_TBL_KU."` AS C
						 	LEFT JOIN `".WPSG_TBL_ADRESS."` AS CA ON (CA.`id` = C.`adress_id`) 
					WHERE
						C.`id` = '".wpsg_q($kunde_id)."'
				");
				
			}
			
			return $this->_arKunden[$kunde_id];
			
		} // public function loadKunden($kunde_id) 
		
		/**
		 * Lädt ein Kundenobjekt oder gibt es aus dem Cache zurück
		 * @param int $customer_id ID des Kundendatensatzes
		 */
		public function loadCustomerObject($customer_id)
		{
			
			if (!array_key_exists($customer_id, $this->_arCustomerObjects))
			{
				
				$oCustomer = new wpsg_customer();
				$oCustomer->load($customer_id);
				
				$this->_arCustomerObjects[$customer_id] = $oCustomer;
				
			}
			
			return $this->_arCustomerObjects[$customer_id];
			
		} // public function loadCustomerObject($kunde_id)
		
		/**
		 * Initiiert einen Basket und gibt ihn zurück
		 */
		public function loadBasketArray($order_id)
		{
			
			if (!array_key_exists($order_id, $this->_arBasket))
			{
				
				$basket = new wpsg_basket();
				$basket->initFromDB($order_id);
								
				$this->_arBasket[$order_id] = $basket->toArray();
				
			}
			
			return $this->_arBasket[$order_id];
			
		} // public function loadBasketArray($order_id)
		
		/**
		 * Löscht den Basket Cache der aktuellen Bestellung
		 */
		public function clearShopBasketArray()
		{
			
			$GLOBALS['wpsg_sc']->basket->initFromSession();
			$this->_BasketArray = false;
			
		} // public function clearShopBasketArray()
		
		/**
		 * Gibt die Array Repräsentation des aktuellen Shop Baskets als Array zurück
		 */
		public function getShopBasketArray()
		{
			
			if ($this->_BasketArray === false)
			{
				
				$this->_BasketArray = $GLOBALS['wpsg_sc']->basket->toArray();
				
			}
			
			return $this->_BasketArray;
			
		} // public function getShopBasketArray()
		
	} // class wpsg_cache

?>