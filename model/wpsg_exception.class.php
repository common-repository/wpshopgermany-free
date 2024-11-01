<?php

    /**
     * User: Daschmi (daschmi@daschmi.de)
     * Date: 18.05.2017
     * Time: 08:57
     */

    namespace wpsg;
 
    class Exception extends \Exception
    {
        
        const TYP_UNEXPECTED = 0;
        const TYP_DB = 1;
         
        private $typ = null;
        private $data = null;
        
        public function __construct($message, $typ = null, $arData = array(), $code = 0, Exception $previous = null)
        {
                        
            parent::__construct($message, $code, $previous);
            
            if ($typ === null) $typ = self::TYP_UNEXPECTED;
            
            $this->typ = $typ;
            $this->data = $arData;
            
        } // public function __construct($message, $code = 0, Exception $previous = null)

        public function getData()
        {

            return $this->data;

        }

        public function getTypLabel() 
        {
        
            switch ($this->typ)
            {
                
                case 0: return __('Unerwartetes Programmverhalten', 'wpsg'); break;
                case 1: return __('Datenbankfehler', 'wpsg'); break;
                
                default: return __('Ungekannter Fehlertyp', 'wpsg'); break;
                
            } // switch ($this->_typ)
            
        } // public function getTypLabel()
		
		public static function getMethodNotFoundException() {
        	
        	return new \Exception('Funktion existiert nicht');
        	
		}
		
		public static function getSanitizeException() {
        	
        	return new \Exception('Parameterfehler');
        	
		}
		
		public static function getInvalidValueException() {
        	
        	return new \Exception('Aufrufsfehler');
        	
		} 
        
    } // class exception extends \Exception