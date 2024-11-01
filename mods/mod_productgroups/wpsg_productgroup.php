<?php

    /**
     * Model für Zugriff auf die Produktgruppen
     */

    class wpsg_productgroup extends wpsg_model
    {

	    /**
		 * Lädt die Daten der Produktgruppe
		 */
		public function load($productgroup_id)
		{

			parent::load($productgroup_id);

			$this->data = $this->db->fetchRow("
				SELECT
					PG.*,
					(SELECT COUNT(*) FROM `".WPSG_TBL_PRODUCTS."` AS P WHERE P.`pgruppe` = PG.`id`) AS `product_count`
				FROM
					`".WPSG_TBL_PRODUCTS_GROUP."` AS PG
				WHERE
					PG.`id` = '".wpsg_q($productgroup_id)."'
			");

			if ($this->data['id'] != $productgroup_id || !wpsg_isSizedInt($productgroup_id)) return false;

			return true;

		} // public function __construct($customer_group_id)

	    public function countProducts()
	    {

		    return $this->product_count;

	    }
	
		/**
		 * @return string
		 */
	    public function getLabel() {
			
			return $this->__get('name');
			
		} 
	    
        /* Statische Funktionen */

	    public static function getProductgroupSelect($arProductFilter = [])
	    {

			if (wpsg_isSizedArray($arProductFilter))
			{

				unset($arProductFilter['limit']);
				unset($arProductFilter['page']);
				unset($arProductFilter['status']);
				unset($arProductFilter['productgroup_ids']);
				unset($arProductFilter['productcategory_ids']);

				list($strQuerySELECT, $strQueryWHERE, $strQueryJOIN, $strQueryHAVING, $strQueryORDER) = wpsg_product::getQueryParts($arProductFilter);

				$strQuery = "
					SELECT
						PG.`id`, CONCAT(PG.`name`,' (', (
							SELECT COUNT(*) FROM `".WPSG_TBL_PRODUCTS."` AS P ".$strQueryJOIN." WHERE (P.`pgruppe` = PG.`id` AND P.`deleted` = 0 ".$strQueryWHERE.")
						), ')') AS `name` ,
						(
							SELECT COUNT(*) FROM `".WPSG_TBL_PRODUCTS."` AS P ".$strQueryJOIN." WHERE (P.`pgruppe` = PG.`id` AND P.`deleted` = 0 ".$strQueryWHERE.")
					  	) AS `product_count`
					FROM
						`".WPSG_TBL_PRODUCTS_GROUP."` AS PG
					HAVING
						`product_count` > 0
					ORDER BY
						`name` ASC
				";

				return $GLOBALS['wpsg_db']->fetchAssocField($strQuery, "id", "name");

			}
			else
			{

				return $GLOBALS['wpsg_db']->fetchAssocField("
					SELECT
						PG.`id`, CONCAT(PG.`name`,' (', (
							SELECT COUNT(*) FROM `".WPSG_TBL_PRODUCTS."` AS P WHERE (P.`pgruppe` = PG.`id` AND P.`deleted` = 0)
						), ')') AS `name` ,
						(SELECT COUNT(*) FROM `".WPSG_TBL_PRODUCTS."` AS P WHERE (P.`pgruppe` = PG.`id` AND P.`deleted` = 0)) AS `product_count`
					FROM
						`".WPSG_TBL_PRODUCTS_GROUP."` AS PG
					HAVING
						`product_count` > 0
					ORDER BY
						`name` ASC
				", "id", "name");

			}

	    } // public static function getProductgroupSelect()

        /**
		 * Zählt die Bestellungen anhand des Filters
		 */
		public static function count($arFilter)
		{

			list($strQuerySELECT, $strQueryWHERE, $strQueryJOIN, $strQueryHAVING, $strQueryORDER) = self::getQueryParts($arFilter);

			$strQuery = "
				SELECT
					COUNT(*)
				FROM
					(
						SELECT
						  	DISTINCT PG.`id`
						FROM
							`".WPSG_TBL_PRODUCTS_GROUP."` AS PG
							".$strQueryJOIN."
						WHERE
							1
							".$strQueryWHERE."
						HAVING
							1
							".$strQueryHAVING."
					) AS innerSelect
			";

			return $GLOBALS['wpsg_db']->fetchOne($strQuery);

		} // public static function count($arFilter)

		public static function find($arFilter = array())
		{

			list($strQuerySELECT, $strQueryWHERE, $strQueryJOIN, $strQueryHAVING, $strQueryORDER) = self::getQueryParts($arFilter);

			$strLimit = "";

			if (wpsg_isSizedArray($arFilter['limit'])) $strLimit = "LIMIT ".wpsg_q($arFilter['limit'][0]).", ".wpsg_q($arFilter['limit'][1]);

			$strQuery = "
				SELECT
					PG .`id`
					".$strQuerySELECT."
				FROM
					`".WPSG_TBL_PRODUCTS_GROUP."` AS PG
				WHERE
					1
					".$strQueryWHERE."
				HAVING
					1
					".$strQueryHAVING."
				ORDER BY
					".$strQueryORDER."
				".$strLimit."
			";

			$arCustomerID = $GLOBALS['wpsg_db']->fetchAssocField($strQuery);
			$arReturn = array();

			foreach ($arCustomerID as $customer_id)
			{

				$arReturn[$customer_id] = self::getInstance($customer_id);

			}

			return $arReturn;

		} // public function find($arQuery = array())

		public static function getQueryParts($arFilter = array())
		{

			$strQuerySELECT = "";
			$strQueryWHERE = "";
			$strQueryJOIN = "";
			$strQueryHAVING = "";

			if (wpsg_isSizedString($arFilter['order'], 'name')) { $strQueryORDER = " PG.`name` "; }
			else if (wpsg_isSizedString($arFilter['order'], 'template_file')) { $strQueryORDER = " PG.`template_file` "; }
			else if (wpsg_isSizedString($arFilter['order'], 'product_count')) {

				$strQuerySELECT .= ", (SELECT COUNT(*) FROM `".WPSG_TBL_PRODUCTS."` AS P WHERE P.`pgruppe` = PG.`id`) AS `product_count`) ";
				$strQueryORDER = " `product_count` ";

			}
			else $strQueryORDER = " PG.`id` ";

			// Richtung
			if (wpsg_isSizedString($arFilter['ascdesc'], "DESC")) $strQueryORDER .= " DESC ";
			else $strQueryORDER .= " ASC ";

			return array($strQuerySELECT, $strQueryWHERE, $strQueryJOIN, $strQueryHAVING, $strQueryORDER);

		} // public function getQueryParts($arFilter = array())

    }