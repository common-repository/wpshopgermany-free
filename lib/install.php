<?php

	require_once(WPSG_PATH_WP.'/wp-admin/includes/upgrade.php');

	/**
	 * Meta Tabelle
	 * Gedacht um zukünftig erweiterte Daten zu Bestellungen, Produkten etc. zu speichern
	 */
	$sql = "CREATE TABLE ".WPSG_TBL_META." (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		meta_table varchar(255) NOT NULL,
		target_id int(9) NOT NULL,		
		meta_key varchar(255) NOT NULL,		
		meta_value longtext NOT NULL,
		PRIMARY KEY  (id),
		KEY target_id (target_id),
		KEY meta_key (meta_key),		
		KEY meta_table (meta_table)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;";	
	
	dbDelta($sql);
	
	/**
	 * Produkttabelle
	 */
	$sql = "CREATE TABLE ".WPSG_TBL_PRODUCTS." (
	  	id mediumint(9) NOT NULL AUTO_INCREMENT,		
	  	cdate datetime NOT NULL,
	  	disabled INT(11) NOT NULL,
	  	partikel INT(11) NOT NULL,
	  	name VARCHAR(255) NOT NULL,	
	  	detailname VARCHAR(500) NOT NULL,
		shortdesc VARCHAR(1500) NOT NULL,
	  	anr VARCHAR(100) NOT NULL,
	  	typ VARCHAR(100) NOT NULL, 
	  	preis DOUBLE(10,2) NOT NULL,
		oldprice DOUBLE(10,2) NOT NULL,
		mwst_key VARCHAR(1) NOT NULL,
	  	beschreibung longtext NOT NULL,
		longdescription longtext NOT NULL,
		moreinfos longtext NOT NULL,
        moreinfos2 longtext NOT NULL,
	  	pgruppe INT(11) NOT NULL,
	  	ptemplate_file VARCHAR(255) NOT NULL,
	  	deleted INT(1) NOT NULL,
	  	lang_parent INT(11) NOT NULL,
	  	lang_code VARCHAR(11) NOT NULL COMMENT 'Der Language Code der Übersetzung',
	  	rabatt VARCHAR(255) NOT NULL,	  	
	  	posturl VARCHAR(500) NOT NULL,
	  	posturl_verkauf INT(1) NOT NULL,
	  	posturl_bezahlung INT(1) NOT NULL,
	  	produktvars VARCHAR(255) NOT NULL,
		allowedpayments VARCHAR(255) NOT NULL,
		allowedshipping VARCHAR(255) NOT NULL,
		euleistungsortregel INT(1) NOT NULL,
		basket_multiple INT(1) NOT NULL, 
		rating INT(1) NOT NULL,
	  	postids VARCHAR(255) NOT NULL,	  	 
	  	PRIMARY KEY  (id),
	  	KEY lang_parent (lang_parent)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;";
	
	$b = dbDelta($sql);

	/**
	 * Adresstabelle
	 */
	
	$sql = "CREATE TABLE ".WPSG_TBL_ADRESS." (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		cdate datetime NOT NULL,
		title VARCHAR(50) NOT NULL,
		name VARCHAR(255) NOT NULL,	   			
   		vname VARCHAR(255) NOT NULL,
   		firma VARCHAR(255) NOT NULL,
   		fax VARCHAR(255) NOT NULL,
   		strasse VARCHAR(255) NOT NULL,
   		nr VARCHAR(255) NOT NULL COMMENT 'Hausnummer Optional / Siehe Kundeneinstellungen',
   		plz VARCHAR(255) NOT NULL,
   		ort VARCHAR(255) NOT NULL,
   		land VARCHAR(100) NOT NULL,
   		tel VARCHAR(100) NOT NULL, 		
   		PRIMARY KEY  (id)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;";

	dbDelta($sql);
 	
	/**
	 * Kundentabelle ALT
	 */
	$sql = "CREATE TABLE ".WPSG_TBL_KU." (
   		id int(25) NOT NULL AUTO_INCREMENT,
   		knr VARCHAR(255) NOT NULL,
   		paypal_payer_id VARCHAR(255) NOT NULL,   		
   		geb DATE NOT NULL,
   		email VARCHAR(500) NOT NULL COMMENT 'E-Mail Adresse des Kunden',
   		ustidnr VARCHAR(100) NOT NULL,   		
   		custom TEXT NOT NULL,
   		adress_id INT(11) NOT NULL COMMENT 'Link zu WPSG_TBL_ADRESS (Kundenadresse)',
   		deleted INT(11) NOT NULL COMMENT 'Markierung für gelöschte Kunden',
   		invisible INT(1) DEFAULT 0 NOT NULL COMMENT '1=unvollständige Bestellung',
		KEY adress_id (adress_id),
   		PRIMARY KEY  (id)
   	) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;";
   	
   	dbDelta($sql);
	
	/**
	 * Tabelle für die Bestellungen
	 */
	$sql = "CREATE TABLE ".WPSG_TBL_ORDER." (
   		id int(25) NOT NULL AUTO_INCREMENT,
   		onr VARCHAR(255) NOT NULL, 
   		cdate datetime NOT NULL,
   		k_id mediumint(9) NOT NULL, 
   		ip VARCHAR(15) NOT NULL,
   		useragent VARCHAR(255) NOT NULL,
   		comment TEXT NOT NULL,
   		price_gesamt DOUBLE(10,4) NOT NULL,
		price_gesamt_netto DOUBLE(10,4) NOT NULL,
		price_gesamt_brutto DOUBLE(10,4) NOT NULL,   	 
		price_frontend INT(1) NOT NULL,   		
		topay DOUBLE(10,4) NOT NULL COMMENT '',   		
		topay_netto DOUBLE(10,4) NOT NULL COMMENT 'Zahlbetrag Netto',
		topay_brutto DOUBLE(10,4) NOT NULL COMMENT 'Zahlbetrag Brutto', 
	  	bname VARCHAR(500) NOT NULL,
		bblz VARCHAR(500) NOT NULL,
		binhaber VARCHAR(500) NOT NULL,
		payed_date datetime NOT NULL,
		bnr VARCHAR(500) NOT NULL,
		bvars TEXT NOT NULL,
		pvars text NOT NULL,
   		status int(5) NOT NULL,
   		transaction varchar(255) NOT NULL,   			
   		dp_cron_planed datetime NOT NULL,
   		dp_cron_done datetime NOT NULL,   		 
   		kleinunternehmer INT(1) NOT NULL COMMENT 'DEPRECATED Durch taxmode ersetzt',
   		custom_data MEDIUMBLOB NOT NULL,
   		admincomment TEXT NOT NULL,
   		language VARCHAR(10) NOT NULL,
   		adress_id INT(11) NOT NULL COMMENT 'Link zu WPSG_TBL_ADRESS (Rechnungsadresse)',
   		shipping_adress_id INT(11) NOT NULL COMMENT 'Link zu WPSG_TBL_ADRESS (Lieferanschrift)',   		   		
   		be_bruttonetto INT(1) NOT NULL COMMENT 'Preisanzeige Backend',
   		fe_bruttonetto INT(1) NOT NULL COMMENT 'Preisanzeige Frontend',   		   		
   		shipping_set VARCHAR(255) NOT NULL COMMENT 'Versandkosten evtl. mit %',
   		shipping_key VARCHAR(255) NOT NULL COMMENT 'Ausgewählte Versandart',
   		shipping_bruttonetto INT(1) NOT NULL COMMENT 'Versandkosten in Brutto/Netto',
   		shipping_tax_key VARCHAR(10) COMMENT 'Steuerschlüssel der Versandart',   		
   		type_shipping VARCHAR(255) COMMENT 'DEPRECATED durch shipping_key ersetzt',
   		price_shipping DOUBLE(10,4) COMMENT 'DEPRECATED sollte nicht verwendet werden',
		price_shipping_netto DOUBLE(10,4) COMMENT 'DEPRECATED sollte nicht verwendet werden',
		price_shipping_brutto DOUBLE(10,4) COMMENT 'DEPRECATED sollte nicht verwendet werden',
		mwst_shipping DOUBLE(10,4) COMMENT 'DEPRECATED durch shipphig_tax ersetzt',	   		
   		payment_set VARCHAR(255) COMMENT 'Zahlungsarten evtl. mit %',
   		payment_key VARCHAR(255) COMMENT 'Ausgewählte Zahlungsart',
   		payment_bruttonetto INT(1) COMMENT 'Zahlungskoten in Brutto/Netto',
   		payment_tax_key VARCHAR(10) COMMENT 'Steuerschlüssel der Zahlungsart',   		
   		type_payment VARCHAR(255) COMMENT 'DEPRECATED durch payment_key ersetzt',
   		price_payment DOUBLE(10,4) COMMENT 'DEPRECATED sollte nicht verwendet werden',
		price_payment_netto DOUBLE(10,4) COMMENT 'DEPRECATED sollte nicht verwendet werden',
		price_payment_brutto DOUBLE(10,4) COMMENT 'DEPRECATED sollte nicht verwendet werden',
   		mwst_payment DOUBLE(10,4) COMMENT 'DEPRECATED durch payment_tax ersetzt',   		
   		discount_set VARCHAR(255) COMMENT 'Rabattwert aus Backend',
   		discount_bruttonetto INT(1) COMMENT 'Brutto/Netto Rabatt',
   		discount_tax_key VARCHAR(10) COMMENT 'Steuerschlüssel Rabatt',   		
   		price_rabatt DOUBLE(10,4) NOT NULL COMMENT 'DEPRECATED',
   		price_rabatt_netto DOUBLE(10,4) NOT NULL COMMENT 'DEPRECATED',
   		price_rabatt_brutto DOUBLE(10,4) NOT NULL COMMENT 'DEPRECATED',   		
   		voucher_tax_key VARCHAR(10) COMMENT 'Steuerschlüssel Gutschein',   		
   		price_gs DOUBLE(10,4) NOT NULL COMMENT 'DEPRECATED sollte nicht verwendet werden',
		price_gs_netto DOUBLE(10,4) NOT NULL COMMENT 'DEPRECATED sollte nicht verwendet werden',
		price_gs_brutto DOUBLE(10,4) NOT NULL COMMENT 'DEPRECATED sollte nicht verwendet werden',
   		gs_set VARCHAR(100) NOT NULL COMMENT 'Eventuell in % (Wert des Gutscheins)',
   		gs_tax_key VARCHAR(10) NOT NULL COMMENT 'Steuersatz des Gutscheins',
   		voucher_bruttonetto INT(1) COMMENT 'Brutto/Netto Gutschein',
   		gs_id INT(11) NOT NULL,
   		gs_code VARCHAR(1000) NOT NULL COMMENT 'Der Gutscheincode',   		   		   		
   		shop_country_id INT(11) COMMENT 'ID des Landes vom Shop',
   		shop_country_tax INT(1) COMMENT 'MwSt. Grundlage',
   		shop_country_tax_a DOUBLE(10,4) COMMENT 'Steuersatz A',
   		shop_country_tax_b DOUBLE(10,4) COMMENT 'Steuersatz B',
   		shop_country_tax_c DOUBLE(10,4) COMMENT 'Steuersatz C',
   		shop_country_tax_d DOUBLE(10,4) COMMENT 'Steuersatz D',   		
   		target_country_id INT(11) COMMENT 'ID des Landes vom Zie',
   		target_country_tax INT(1) COMMENT 'MwSt. Grundlage',
   		target_country_tax_a DOUBLE(10,4) COMMENT 'Steuersatz A',
   		target_country_tax_b DOUBLE(10,4) COMMENT 'Steuersatz B',
   		target_country_tax_c DOUBLE(10,4) COMMENT 'Steuersatz C',
   		target_country_tax_d DOUBLE(10,4) COMMENT 'Steuersatz D',
   		calculation INT(1) COMMENT '1 wenn die Bestellung mit der neuen Calculation Klasse berechnet wurde',
   		tax_mode INT(1) COMMENT 'Art der Besteuerung Kleinunternehmer/1, Endkunden/2, Firmenkunden/3',
   		secret VARCHAR(255) COMMENT 'Zufallszahl',
   		KEY adress_id (adress_id),
   		KEY shipping_adress_id (shipping_adress_id),
   		KEY k_id (k_id),
   		KEY gs_id (gs_id),
   		PRIMARY KEY  (id)
   	) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;";
	 	 
	dbDelta($sql);
		
	/**
	 * Tabelle für die bestellten Produkte
	 */
	$sql = "CREATE TABLE ".WPSG_TBL_ORDERPRODUCT." (
   		id mediumint(9) NOT NULL AUTO_INCREMENT,
   		o_id int(25) NOT NULL,
   		p_id int(25) NOT NULL,   		
   		product_set VARCHAR(255) COMMENT 'Preis des Produktes im Backend',
   		product_bruttonetto INT(1) COMMENT 'Preiseinstellung Brutto/Netto',   		   		
		productkey varchar(255) NOT NULL,
		product_index int(11) NOT NULL,
   		menge mediumint(9) NOT NULL,
   		price DOUBLE(10,4) NOT NULL, 
		price_netto DOUBLE(10,4) NOT NULL,
		price_brutto DOUBLE(10,4) NOT NULL,
		mwst_key varchar(1) NOT NULL,   		
   		mwst_value double(4,2) NOT NULL,
   		mod_downloadprodukt_counter int(11) NOT NULL,
   		mod_vp_varkey VARCHAR( 255 ) NOT NULL, 
		allowedpayments VARCHAR( 255 ) NOT NULL, 
		allowedshipping VARCHAR( 255 ) NOT NULL,
		euleistungsortregel INT(1) NOT NULL,
		PRIMARY KEY  (id),
   		KEY o_id (o_id),
   		KEY p_id (p_id)
   	) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;";
   	
   	dbDelta($sql);
	
	/**
	 * Tabelle für die Versandzonen
	 */
	$sql = "CREATE TABLE ".WPSG_TBL_VZ." (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		name VARCHAR(255) NOT NULL,
		innereu int(1) NOT NULL,
		param TEXT NOT NULL,
		PRIMARY KEY  (id)		
	) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;";
	
	dbDelta($sql);
	
	/**
	 * Tabelle für die Länder
	 */	
	$sql = "CREATE TABLE ".WPSG_TBL_LAND." (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		name VARCHAR(255) NOT NULL,
		kuerzel VARCHAR(10) NOT NULL,
		vz mediumint(9) NOT NULL,
		mwst mediumint(9) NOT NULL,
		mwst_a DOUBLE(10,4) NULL,
		mwst_b DOUBLE(10,4) NULL,
		mwst_c DOUBLE(10,4) NULL,
		mwst_d DOUBLE(10,4) NULL,
		PRIMARY KEY  (id),
		KEY vz (vz),
		KEY mwst (mwst)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;";
	
	dbDelta($sql);
	
	/**
	 * Tabelle für das Bestellprotokoll
	 */
	$sql = "CREATE TABLE ".WPSG_TBL_OL." (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		o_id mediumint(9) NOT NULL,
		cdate datetime NOT NULL,
		title VARCHAR(500) NOT NULL,
		mailtext TEXT,
		PRIMARY KEY  (id),
		KEY o_id (o_id)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;";
	
	dbDelta($sql);

	include WPSG_PATH.'/lib/update.php';
	
?>