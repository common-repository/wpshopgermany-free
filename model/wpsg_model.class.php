<?php

	/**
	 * Oberklasse aller wpsg Modelle
	 * @author Daschmi
	 */
	class wpsg_model
	{
		
		/** @var array */
		public static $_objCache = array();
		
		/** @var wpsg_db */
		var $db = false;

		/** @var wpsg_ShopController */
		var $shop = false;
		
		var $id = false;		
		var $data = false;
		
		/**
		 * Konstruktor
		 */
		public function __construct()
		{
			
			$this->shop = $GLOBALS['wpsg_sc'];
			$this->db = $GLOBALS['wpsg_db'];
			
			$this->data = array();
			
		} // public function __construct()
		
		public function getId() {
			
			return intval($this->data['id']);
			
		}
		
		/**
		 * Lädt Daten des Objekts
		 */
		public function load($id)
		{
			
			$this->id = $id;
			
		} // public function load($id)

		/**
		 * Prüft ob das Objekt korrekt geladen wurde
		 * Wenn ein Datensatz in der DB nicht existiert, wird ein leeres Dummy Objekt geladen
		 */
		public function isLoaded() 
		{
			
			return wpsg_isSizedInt($this->data['id']);
			
		} // public function isLoaded() 
		
		/**
		 * Etwas im Datenarray ablegen
		 */
		public function __get($name)
		{

			if (array_key_exists($name, (array)$this->data))
			{
				
				return $this->data[$name];
				
			}
			
			return null;
			
		} // public function __get($name)
		 
		
		/**
		 * Speichert etwas in einen Datenarry
		 */
		public function __set($name, $value)
		{
		
			$this->data[$name] = $value;
			 
		} // public function __set($name, $value)
		
		/**
		 * Gibt eine Instanz dieses Models zurück anhand des Primärschlüssels
		 * @param int $id
		 * @param bool $noCache
		 * @return static
		 */
		public static function getInstance($id, $noCache = false) {
						
			$class_name = get_called_class();
			
			if (wpsg_isSizedArray($id)) {
				
				$arReturn = [];
				
				foreach ($id as $_id) {
					
					$arReturn[$_id] = self::getInstance($_id);
					
				}
				
				return $arReturn;
				
			} else if (is_array($id)) {
				
				return [];
				
			}
			else {
				
				if (!isset(self::$_objCache[$class_name]) || !array_key_exists($id, self::$_objCache[$class_name]) || $noCache === true) {
										
					$oObject = new $class_name();
					$oObject->load($id);
					
					self::$_objCache[$class_name][$id] = $oObject;
					
				}
				
				return self::$_objCache[$class_name][$id];
				
			}
			
		} // public abstract static function getInstance($id)
		
		public static function clearCache($id = false) {
			
			$class_name = get_called_class();
			
			if ($id === false) {
				
				unset(self::$_objCache[$class_name]);
				
				self::$_objCache[$class_name] = [];
				
			}
			else {
				
				unset(self::$_objCache[$class_name][$id]);
				
			}
			
		}
				
	} // class wpsg_model
