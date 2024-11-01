<?php

/**
 * Klasse für einen Anschreiben
 * @author Daschmi
 *
 */
class wpsg_anschreiben extends wpsg_model
{
    
    /**
     * Lädt die Daten des Anschreiben
     * @see wpsg_model::load()
     */
    public function load($anschreiben_id)
    {
        
        parent::load($anschreiben_id);
        
        $this->data = $this->db->fetchRow("SELECT DN.* FROM `".WPSG_TBL_anschreiben."` AS DN WHERE DN.`id` = '".wpsg_q($anschreiben_id)."' ");
        
        if ($this->data['id'] != $anschreiben_id) throw new \wpsg\Exception(__('Konnte Anschreiben nicht laden, ungültige ID übergeben', 'wpsg'));
        
        return true;
        
    } // public function load($anschreiben_id)
    
    /**
     * Gibt true zurück wenn der Anschreiben für ungültig erklärt wurde
     * @return boolean
     */
    public function isCanceled()
    {
        
        if ($this->cancel == '1') return true;
        else return false;
        
    } // public function isCanceled()
    
    /**
     * Gibt das Datum der Lieferung als Timestamp zurück
     */
    public function getDeliveryTimestamp()
    {
        
        return strtotime($this->delivery_date);
        
    } // public function getDeliveryTimestamp()
    
    /**
     * Gibt die Produkte dieses Anschreiben zurück
     */
    public function getProducts()
    {
        
        return $this->db->fetchAssoc("
				SELECT
					P.`id`, OP.`productkey`, P.`name`
				FROM
					`".WPSG_TBL_ORDERPRODUCT."` AS OP
						LEFT JOIN `".WPSG_TBL_PRODUCTS."` AS P ON (OP.`p_id` = P.`id`)
				WHERE
					OP.`o_id` = '".wpsg_q($this->order_id)."' AND
					OP.`product_index` IN (".wpsg_q($this->product_indexes).")
			", "productkey");
        
    } // public function getProducts()
    
    /**
     * Prüft ob ein Anschreiben für das Produkt erstellt werden kann
     */
    public static function checkProductKey($product_indexes, $order_id)
    {
        
        $nExists = $GLOBALS['wpsg_db']->fetchOne("
				SELECT
					COUNT(*)
				FROM
					`".WPSG_TBL_anschreiben."`
				WHERE
					`order_id` = '".wpsg_q($order_id)."' AND
					FIND_IN_SET('".wpsg_q($product_indexes)."', `product_indexes`) AND
					`cancel` != '1'
			");
        
        if ($nExists > 0) return false;
        else return true;
        
    } // public static function checkProductKey($product_indexes, $order_id)
    
    /**
     * zeigt das Formular zum Drucken in der Bestellverwaltung
     */
    public function order_view($order_id, &$arSidebarArray)
    {
        
        $path = $this->shop->getRessourcePath();
        
        $this->shop->view['arTemplates'] = array();
        
        $arrFiles = scandir($path);
        
        foreach ($arrFiles as $file)
        {
            
            if (is_file($path.$file) && preg_match('/(.*)\.phtml/', $file) && !preg_match('/(.*)_html\.phtml/', $file))
            {
                
                $template_name = str_replace('.phtml', '', $file);
                
                $this->shop->view['arTemplates'][$file]['filename'] = $file;
                $this->shop->view['arTemplates'][$file]['name'] = ucfirst($template_name);
                
            }
            
        }
        
        $arSidebarArray[$this->id] = array(
            'title' => $this->name,
            'content' => $this->shop->render(WPSG_PATH_VIEW.'mods/mod_anschreiben/order_view.phtml', false)
        );
        
    } // public function order_view_content($order_id)
    
    /**
     * Gibt einen Array von Anschreibenen zurück, die auf den übergebenen Filter passen
     * @param array $arFilter
     */
    public static function find($arFilter = array())
    {
        
        $strQueryWHERE = "";
        $strQueryORDER = " DN.`cdate` ";
        $strQueryORDER_DIRECTION = " DESC ";
        
        if (wpsg_isSizedInt($arFilter['order_id'])) $strQueryWHERE .= " AND DN.`order_id` = '".wpsg_q($arFilter['order_id'])."' ";
        if (isset($arFilter['cancel'])) $strQueryWHERE .= " AND DN.`cancel` = '".wpsg_q($arFilter['cancel'])."' ";
        if (isset($arFilter['order'])) $strQueryORDER = wpsg_q($arFilter['order']);
        if (isset($arFilter['order_direction'])) $strQueryORDER_DIRECTION = wpsg_q($arFilter['order_direction']);
        if (wpsg_isSizedInt($arFilter['product_index'])) $strQueryWHERE .= " AND FIND_IN_SET('".wpsg_q($arFilter['product_index'])."', DN.`product_indexes`) ";
        
        $strQuery = "
				SELECT
					DN.`id`
				FROM
					`".WPSG_TBL_anschreiben."` AS DN
				WHERE
					1
					".$strQueryWHERE."
				GROUP BY
					DN.`id`
				ORDER BY
					".$strQueryORDER." ".$strQueryORDER_DIRECTION."
			";
        
        $arDNID = $GLOBALS['wpsg_db']->fetchAssocField($strQuery);
        $arReturn = array();
        
        foreach ($arDNID as $dn_id)
        {
            
            $arReturn[] = wpsg_anschreiben::getInstance($dn_id);
            
        }
        
        return $arReturn;
        
    } // public static function find($arFilter = array())
    
} // class wpsg_anschreiben extends wpsg_model

?>