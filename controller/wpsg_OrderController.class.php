<?php

	/**
	 * Controller für die Bestellungen
	 */
	class wpsg_OrderController extends wpsg_SystemController
	{

		/**
		 * Übernimmt die Vertreilung der Anfragen
		 */
		public function dispatch()
		{

			parent::dispatch();

			if (wpsg_isSizedString($_REQUEST['subaction'], 'productData')) {

				$this->productDataAction();
				
			} else if (wpsg_isSizedString($_REQUEST['subaction'], 'add')) {
			    
				check_admin_referer('wpsg-order-add');
			 
			    $this->addAction();
			    
            } else if (wpsg_isSizedString($_REQUEST['subaction'], 'autocomplete')) {
			    
			    $this->autocompleteAction();
			    
            } else if (wpsg_isSizedString($_REQUEST['action'], 'updateOrder')) {
			    
			    $this->updateOrderAction();
			    
            } if (wpsg_isSizedString($_REQUEST['subaction'], 'shippingData')) {
				$this->shippingDataAction();
			}
			if (wpsg_isSizedString($_REQUEST['subaction'], 'paymentData'))
			{
				$this->paymentDataAction();
			}
			if (wpsg_isSizedString($_REQUEST['subaction'], 'voucherData'))
			{
				$this->voucherDataAction();
			}
			if (wpsg_isSizedString($_REQUEST['subaction'], 'itemOrderData'))
			{
				$this->itemOrderDataAction();
			}
			if (wpsg_isSizedString($_REQUEST['subaction'], 'foldedItemsData'))
			{
				$this->foldedItemsDataAction();
			}
			if (wpsg_isSizedString($_REQUEST['subaction'], 'discountData'))
			{
				$this->discountDataAction();
			}
			if (wpsg_isSizedString($_REQUEST['action'], 'view'))
			{
				$this->viewAction();
			}
			else if (wpsg_isSizedString($_REQUEST['action'], 'switchStatus'))
			{
				$this->switchStatusAction();
			}
			else if (wpsg_isSizedString($_REQUEST['action'], 'setAdminComment'))
			{
				$this->setAdminCommentAction();
			}
			else if (wpsg_isSizedString($_REQUEST['action'], 'storno'))
			{
				$this->stornoAction();
			}
			else if (wpsg_isSizedString($_REQUEST['action'], 'delete'))
			{
				$this->deleteAction();
			}
			else if (wpsg_isSizedString($_REQUEST['action'], 'ajax'))
			{
				$this->ajaxAction();
			}
			else
			{
				$this->indexAction();
			}

		} // public function dispatch()

        public function updateOrderAction() {

			$_REQUEST['shipping_price'] = wpsg_sinput("text_field", $_REQUEST['shipping_price']);
	        $_REQUEST['payment_price'] = wpsg_sinput("text_field", $_REQUEST['payment_price']);

	        $_REQUEST['edit_id'] = wpsg_sinput("key", $_REQUEST['edit_id']);

		    $oCalculation = new \wpsg\wpsg_calculation();
		    $oCalculation->fromDB($_REQUEST['edit_id']);
		     
            $oCalculation->addShipping(
				$_REQUEST['shipping_price'],
				$this->shop->getBackendTaxview(),
                $this->shop->arShipping[$_REQUEST['shipping_key']]['mwst_key'],
				$_REQUEST['shipping_key']
            );

            $oCalculation->addPayment(
                $_REQUEST['payment_price'],
				$this->shop->getBackendTaxview(),
                $this->shop->arPayment[$_REQUEST['payment_key']]['mwst_key'],
				$_REQUEST['payment_key']
            );

            $oCalculation->toDB($_REQUEST['edit_id']);

            $this->shop->view['oCalculation'] = $oCalculation; 
            
            wpsg_header::JSONData([
                'product_table' => $this->shop->render(WPSG_PATH_VIEW.'order/product_table.phtml', false)
            ]);
            
        } // public function updateOrderAction()
        
        public function autocompleteAction() {

            $arReturn = [];
		    
		    $arCustomer = wpsg_customer::find(Array('s' => $_REQUEST['term']));		    
		    		    
		    foreach ($arCustomer as $oCustomer) {
		        
		        $arReturn[] = Array(
		            'id' => 'ID:'.$oCustomer->id.' / '.$oCustomer->getLabel().' ('.$oCustomer->email.')',
                    'value' => 'ID:'.$oCustomer->id.' / '.$oCustomer->getLabel().' ('.$oCustomer->email.')'  
                );
		        
            }
		    
            wpsg_header::JSONData($arReturn);
            
		    exit;
		    
        } // public function autocompleteAction()
        
        public function addAction() {
 
            $customer_id = false;
            
            if (!wpsg_isSizedString($_REQUEST['search_customer'])) $this->addBackendError(__('Bitte einen Kunden definieren.', 'wpsg'));
            else {

	            $_REQUEST['search_customer'] = wpsg_sinput("text_field", $_REQUEST['search_customer']);

                // ID:3 / Max Mustermann (buyer@maennchen1.de)
                preg_match_all('/^ID:(\d+?)/i', $_REQUEST['search_customer'], $m);
                
                if (wpsg_isSizedInt($m[1][0])) {
                    
                    $customer_id = $m[1][0];
                    
                } else {

                    $customer_id = $this->db->fetchOne("SELECT `id` FROM `".WPSG_TBL_KU."` WHERE `email` = '".wpsg_q($_REQUEST['search_customer'])."' ");
                                 
                    if (!wpsg_isSizedInt($customer_id)) {
                    
                        $customer_id = $this->db->ImportQuery(WPSG_TBL_KU, Array(
                            'email' => wpsg_q($_REQUEST['search_customer'])
                        ));
                        
                        $customer_nr = $this->shop->buildKNR($customer_id);
                                                
                        $adress_id = $this->db->ImportQuery(WPSG_TBL_ADRESS, [
                            'cdate' => 'NOW()',
                            'land' => wpsg_q($this->shop->getDefaultCountry()->id)
                        ]);

                        $this->db->UpdateQuery(WPSG_TBL_KU, [
                            'knr' => wpsg_q($customer_nr),
                            'adress_id' => wpsg_q($adress_id)
                        ], " `id` = '".wpsg_q($customer_id)."' ");
                        
                    }
                    
                } 
                
                $oCustomer = wpsg_customer::getInstance($customer_id);
                $oTargetCountry = wpsg_country::getInstance($oCustomer->getCountryID());
                                
            }
                        
            if (!wpsg_isSizedInt($customer_id)) $this->addBackendError(__('Es konnte kein Kunde gefunden werden.', 'wpsg'));
            
            if ($this->hasBackendError()) {
                     
                $this->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Order&search_customer='.$_REQUEST['search_customer']);
                
            } else {

                $oCustomer = wpsg_customer::getInstance($customer_id);
				$oDefaultCountry = $this->shop->getDefaultCountry();                
                								
                $order_id = $this->db->ImportQuery(WPSG_TBL_ORDER, Array(
                    'payment_key' => wpsg_q(wpsg_sinput("key", $_REQUEST['add_payment'])),
                    'payment_bruttonetto' => wpsg_q($this->shop->getBackendTaxview()),
					'payment_tax_key' => wpsg_q($this->shop->arPayment[wpsg_sinput("key", $_REQUEST['add_payment'])]['mwst_key']),
					'payment_set' => wpsg_q($this->shop->arPayment[wpsg_sinput("key", $_REQUEST['add_payment'])]['price']),
					'shipping_key' => wpsg_q(wpsg_sinput("key", $_REQUEST['add_shipping'])),
					'shipping_bruttonetto' => wpsg_q($this->shop->getBackendTaxview()),
					'shipping_tax_key' => wpsg_q($this->shop->arShipping[wpsg_sinput("key", $_REQUEST['add_shipping'])]['mwst_key']),
					'shipping_set' => wpsg_q($this->shop->arShipping[wpsg_sinput("key", $_REQUEST['add_shipping'])]['price']),
                    'price_frontend' => $this->shop->getFrontendTaxview(),
                    'cdate' => 'NOW()',
                    'adress_id' => wpsg_q($oCustomer->adress_id),
                    'k_id' => wpsg_q($customer_id),
					'calculation' => '1',
					'shop_country_id' => wpsg_q($oDefaultCountry->id),
					'shop_country_tax' => wpsg_q($oDefaultCountry->mwst),
					'shop_country_tax_a' => wpsg_q($oDefaultCountry->mwst_a),
					'shop_country_tax_b' => wpsg_q($oDefaultCountry->mwst_b),
					'shop_country_tax_c' => wpsg_q($oDefaultCountry->mwst_c),
					'shop_country_tax_d' => wpsg_q($oDefaultCountry->mwst_d),
					'target_country_id' => wpsg_q($oTargetCountry->id),
					'target_country_tax' => wpsg_q($oTargetCountry->mwst),
					'target_country_tax_a' => wpsg_q($oTargetCountry->mwst_a),
					'target_country_tax_b' => wpsg_q($oTargetCountry->mwst_b),
					'target_country_tax_c' => wpsg_q($oTargetCountry->mwst_c),
					'target_country_tax_d' => wpsg_q($oTargetCountry->mwst_d)
                ));
                                                 
                $onr = $this->shop->buildONR($order_id, $customer_id,$oCustomer->getNr());
                
                $this->db->UpdateQuery(WPSG_TBL_ORDER, Array(
                    'onr' => wpsg_q($onr) 
                ), " `id` = '".wpsg_q($order_id)."' ");
                
                $this->db->ImportQuery(WPSG_TBL_OL, Array(
                    'o_id' => wpsg_q($order_id),
                    'cdate' => 'NOW()',
                    'title' => __('Bestellung im Backend angelegt.', 'wpsg'),
                    'mailtext' => ''
                ));
                
                $this->addBackendMessage(__('Die neue Bestellung wurde vorbereitet.', 'wpsg'));
                
                $this->redirect(wp_nonce_url(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Order&action=view&edit_id='.$order_id, 'wpsg-order-edit-'.$order_id));
                                
            }
            
            exit;
		    
        } // public function addAction()
        
		/**
		 * Wird aufgerufen wenn die Produkte bearbeitet werden
		 */
		public function productDataAction()
		{

			$t1 = 0;
			$oid = 0;
			$option = $this->shop->get_option('wpsg_preisangaben');
			if (isset($_REQUEST['edit_id'])) $oid = wpsg_q($_REQUEST['edit_id']);

			// Steuersatz berechnen
			$oOrder = wpsg_order::getInstance($oid);
			$country_id = $oOrder->getCustomer()->getCountryId();
			//$country_id = 1;
			$country = wpsg_country::getInstance($country_id);

			if (isset($_REQUEST['do']) && $_REQUEST['do'] == 'add')
			{
				//url: 'wp-admin/admin.php?page=wpsg-Admin&subaction=productData&do=add&edit_id=' + this.o_id + '&p_id=' + pid + '&noheader=1',
				if (isset($_REQUEST['edit_id'])) $oid = wpsg_q($_REQUEST['edit_id']);
				if (isset($_REQUEST['p_id'])) $pid = wpsg_q($_REQUEST['p_id']);

				$arProd = $this->db->fetchRow("SELECT * FROM `".WPSG_TBL_PRODUCTS."` WHERE `id` = '".wpsg_q($pid)."'");

				$data = array('o_id' => $oid, 'p_id' => $pid, 'productkey' => $pid,
				              'menge' => 1, 'mwst_key' => $arProd['mwst_key'], 'mod_vp_varkey' => $pid,
				              'allowedpayments' => $arProd['allowedpayments'],
				              'allowedshipping' => $arProd['allowedshipping']
				);

				if ($this->shop->hasMod('wpsg_mod_weight')) $data['weight'] = $arProd['weight'];
				// Füllmenge nicht in WPSG_TBL_ORDERPRODUCT
				//if ($this->shop->hasMod('wpsg_mod_fuellmenge')) $data['fmenge'] = $arProd['fmenge'];

				$noMwSt = false;
				if (isset($_REQUEST['noMwSt']))
				{
					if (wpsg_q($_REQUEST['noMwSt']) == 1) $noMwSt = true;
					$tax_key = $arProd['mwst_key'];
					if ($noMwSt === true) $tax_value = 0; else $tax_value = $country->getTax($tax_key);
				}

				$price = $arProd['preis'];

				if ($option == WPSG_NETTO)
				{
					$netto = $price;
					$brutto = $price * ((100.0 + $tax_value) / 100.0);

				}
				else
				{
					$netto = $price / ((100.0 + $tax_value) / 100.0);
					$brutto = $price;

				}
				$diff = $brutto - $netto;

				$count = $this->db->fetchOne("SELECT COUNT(*) FROM `".WPSG_TBL_ORDERPRODUCT."` WHERE `o_id` = '".wpsg_q($oid)."' ");

				$data['product_index'] = $count;
				$data['price'] = $price;
				$data['price_netto'] = $netto;
				$data['price_brutto'] = $brutto;
				////$data['mwst_value'] = $diff;
				$data['mwst_value'] = $tax_value;

				$op_id = $this->db->ImportQuery(WPSG_TBL_ORDERPRODUCT, $data);
				//$this->db->UpdateQuery(WPSG_TBL_ORDER, $ogs, "`id` = '".$oid."'");

			}
			else if (isset($_REQUEST['do']) && $_REQUEST['do'] == 'remove')
			{
				// Order-Produkt löschen
				//url: 'wp-admin/admin.php?page=wpsg-Admin&subaction=productData&do=remove&edit_id=' + o_id + '&p_id=' + op_id + '&noheader=1',
				if (isset($_REQUEST['edit_id'])) $oid = wpsg_q($_REQUEST['edit_id']);
				if (isset($_REQUEST['op_id'])) $opid = wpsg_q($_REQUEST['op_id']);

				$this->db->Query("DELETE FROM `".WPSG_TBL_ORDERPRODUCT."` WHERE `id` = '".wpsg_q($opid)."' ");

			}
			else if (isset($_REQUEST['do']) && $_REQUEST['do'] == 'change')
			{
				//url1 = 'wp-admin/admin.php?page=wpsg-Admin&subaction=productData&do=change&edit_id=' + this.o_id + '&op_id=' + this.op_id + '&p_id=' + pid + '&noheader=1';
				if (isset($_REQUEST['edit_id'])) $oid = wpsg_q($_REQUEST['edit_id']);
				if (isset($_REQUEST['p_id'])) $pid = wpsg_q($_REQUEST['p_id']);
				if (isset($_REQUEST['op_id'])) $opid = wpsg_q($_REQUEST['op_id']);

				$arProd = $this->db->fetchRow("SELECT * FROM `".WPSG_TBL_PRODUCTS."` WHERE `id` = '".wpsg_q($pid)."'");

				//$data = array('o_id' => $oid, 'p_id' => $pid, 'productkey' => $pid,
				//		'mod_vp_varkey' => $pid,
				//		'allowedpayments' => $arProd['allowedpayments'],
				//		'allowedshipping' => $arProd['allowedshipping']
				//);

				if ($this->shop->hasMod('wpsg_mod_weight')) $data['weight'] = $arProd['weight'];
				// Füllmenge nicht in WPSG_TBL_ORDERPRODUCT
				//if ($this->shop->hasMod('wpsg_mod_fuellmenge')) $data['fmenge'] = $arProd['fmenge'];

				$pval = abs(wpsg_tf($_REQUEST['p_val']));
				$pme = abs(wpsg_tf($_REQUEST['p_me']));

				$noMwSt = false;
				if (isset($_REQUEST['noMwSt']))
				{
					if (wpsg_q($_REQUEST['noMwSt']) == 1) $noMwSt = true;
					//$tax_key = $arProd['mwst_key'];
					$tax_key = wpsg_q($_REQUEST['p_mwst']);
					if ($noMwSt === true) $tax_value = 0; else $tax_value = $country->getTax($tax_key);
				}

				$price = $pval;

				if ($option == WPSG_NETTO)
				{
					$netto = $price;
					$brutto = $price * ((100.0 + $tax_value) / 100.0);

				}
				else
				{
					$netto = $price / ((100.0 + $tax_value) / 100.0);
					$brutto = $price;

				}
				$diff = $brutto - $netto;

				$data['mwst_key'] = wpsg_q($_REQUEST['p_mwst']);
				$data['menge'] = $pme;
				$data['price'] = $price;
				$data['price_netto'] = $netto;
				$data['price_brutto'] = $brutto;
				////$data['mwst_value'] = $diff;
				$data['mwst_value'] = $tax_value;

				$this->db->UpdateQuery(WPSG_TBL_ORDERPRODUCT, $data, "`id` = '".$opid."'");

			}

			$this->correctPrice($oid);

			// Order-Daten sammeln und Tabelle neu generieren
			$basket = new wpsg_basket();
			$basket->initFromDB($oid, true);
			$this->shop->view['basket'] = $basket->toArray(true);

			$this->shop->view['colspan'] = 3;
			if ($this->shop->get_option('wpsg_showMwstAlways') == '1' || sizeof($this->shop->view['basket']['mwst']) > 1) $this->shop->view['colspan'] ++;

			$this->shop->render(WPSG_PATH_VIEW.'/order/view_orderdata.phtml');

			exit;

		} // public function productDataAction()

		public function correctPrice($oid)
		{

			// Steuersatz berechnen
			$oOrder = wpsg_order::getInstance($oid);
			$country_id = $oOrder->getCustomer()->getCountryId();
			//$country_id = 1;
			$country = wpsg_country::getInstance($country_id);

			// Tabelle Order die Preise korrigieren
			$arOrderProd = $this->db->fetchAssoc("SELECT * FROM `".WPSG_TBL_ORDERPRODUCT."` WHERE `o_id` = '".wpsg_q($oid)."'");

			$data = array();
			$data['price_gesamt'] = 0;
			$data['price_gesamt_netto'] = 0;
			$data['price_gesamt_brutto'] = 0;

			foreach ($arOrderProd as $p)
			{

				$tax_key = $p['mwst_key'];
				//if ($noMwSt === true) $tax_value = 0; else $tax_value = $country->getTax($tax_key);
				$tax_value = $country->getTax($tax_key);

				if ($option == WPSG_NETTO)
				{
					$price = $p['menge'] * $p['price_netto'];
					$data['price_gesamt'] += $price;
					$data['price_gesamt_netto'] += $price;
					$data['price_gesamt_brutto'] += $price * ((100.0 + $tax_value) / 100.0);

				}
				else
				{
					$price = $p['menge'] * $p['price_brutto'];
					$data['price_gesamt'] += $price;
					$data['price_gesamt_netto'] += $price / ((100.0 + $tax_value) / 100.0);
					$data['price_gesamt_brutto'] += $price;

				}
			}

			// Zusatzkosten (Gutschein, Rabatt, Versand, Zahlung) zusammenfassen
			$p = $this->db->fetchRow("SELECT * FROM `".WPSG_TBL_ORDER."` WHERE `id` = '".wpsg_q($oid)."'");
			$zusatz = $p['price_gs'] * -1;
			$zusatz_netto = $p['price_gs_netto'] * -1;
			$zusatz_brutto = $p['price_gs_brutto'] * -1;
			$zusatz -= $p['price_rabatt'];
			$zusatz_netto -= $p['price_rabatt_netto'];
			$zusatz_brutto -= $p['price_rabatt_brutto'];
			$zusatz += $p['price_shipping'];
			$zusatz_netto += $p['price_shipping_netto'];
			$zusatz_brutto += $p['price_shipping_brutto'];
			$zusatz += $p['price_payment'];
			$zusatz_netto += $p['price_payment_netto'];
			$zusatz_brutto += $p['price_payment_brutto'];

			$data['price_gesamt'] += $zusatz;
			$data['price_gesamt_netto'] += $zusatz_netto;
			$data['price_gesamt_brutto'] += $zusatz_brutto;

			$this->db->UpdateQuery(WPSG_TBL_ORDER, $data, "`id` = '".$oid."'");

		}

		/**
		 * Wird aufgerufen, wenn die Zahlungsart verändert wird
		 */
		public function paymentDataAction()
		{
			$pid = 0;
			$oid = 0;
			$pval = 0.0;
			$option = $this->shop->get_option('wpsg_preisangaben');
			if (isset($_REQUEST['edit_id'])) $oid = wpsg_q($_REQUEST['edit_id']);
			if (isset($_REQUEST['p_id'])) $pid = wpsg_q($_REQUEST['p_id']);
			if (isset($_REQUEST['p_value'])) $pval = wpsg_tf($_REQUEST['p_value']);
			if (isset($_REQUEST['do']) && $_REQUEST['do'] == 'change')
			{
				//'wp-admin/admin.php?page=wpsg-Admin&subaction=paymentData&do=change&edit_id=12&p_id=34&p_value=56&noheader=1';

				$arTaxKey = explode('_', wpsg_q($_REQUEST['mwst']));
				$country_id = $arTaxKey[1];
				$country = wpsg_country::getInstance($country_id);
				$tax_key = 'c';
				$noMwSt = false;
				if (wpsg_q($_REQUEST['noMwSt']) == 1) $noMwSt = true;
				if ($noMwSt === true) $tax_value = 0; else $tax_value = $country->getTax($tax_key);
				//if (wpsg_q($_REQUEST['price_frontend']) == WPSG_NETTO)
				/*
				 if ($this->shop->get_option('wpsg_preisangaben') == WPSG_NETTO)
				 {
				 $valb = wpsg_calculatePreis($pval, WPSG_BRUTTO, $tax_value);
				 $valn = $pval;
				 }
				 else
				 {
				 $valn = wpsg_calculatePreis($pval, WPSG_NETTO, $tax_value);
				 $valb = $pval;
				 }

				 $data = array('price_payment' => $pval, 'price_payment_brutto' => $valb, 'price_payment_netto' => $valn,
				 'type_payment' => $pid);
				 */
				if ($option == WPSG_NETTO)
				{
					$data = array('price_payment' => $pval, 'price_payment_netto' => $pval,	'type_payment' => $pid);
				}
				else
				{
					$data = array('price_payment' => $pval, 'price_payment_brutto' => $pval, 'type_payment' => $pid);
				}
				$this->db->UpdateQuery(WPSG_TBL_ORDER, $data, "`id` = '".$oid."'");

			}

			// Order-Daten sammeln und Tabelle neu generieren
			$basket = new wpsg_basket();
			$basket->initFromDB($oid, true);
			$this->shop->view['basket'] = $basket->toArray(true);

			//$pval = 7.9646;
			// Aufruf in wpsg_basket mit Wert = 7.9646 ergibt 1.0354
			//$taxp = $this->addMwSt($arReturn, $arReturn['sum']['preis_payment_netto']);
			//$taxp = $bc->addMwSt($this->shop->view['basket'], $pval);
			//$taxp = abs($taxp);

			if ($option == WPSG_NETTO)
			{
				$data = array('price_payment_brutto' => $this->shop->view['basket']['sum']['preis_payment_brutto']);
			}
			else
			{
				$data = array('price_payment_netto' => $this->shop->view['basket']['sum']['preis_payment_netto']);
			}
			$this->db->UpdateQuery(WPSG_TBL_ORDER, $data, "`id` = '".$oid."'");

			$this->correctPrice($oid);

			$this->shop->view['colspan'] = 3;
			if ($this->shop->get_option('wpsg_showMwstAlways') == '1' || sizeof($this->shop->view['basket']['mwst']) > 1) $this->shop->view['colspan'] ++;

			$this->shop->render(WPSG_PATH_VIEW.'/order/view_orderdata.phtml');

			exit;

		}	// public function paymentDataAction()

		/**
		 * Wird aufgerufen, wenn die Versandart verändert wird
		 */
		public function shippingDataAction()
		{
			$sid = 0;
			$oid = 0;
			$sval = 0.0;
			$option = $this->shop->get_option('wpsg_preisangaben');
			if (isset($_REQUEST['edit_id'])) $oid = wpsg_q($_REQUEST['edit_id']);
			if (isset($_REQUEST['s_id'])) $sid = wpsg_q($_REQUEST['s_id']);
			if (isset($_REQUEST['s_value'])) $sval = wpsg_tf($_REQUEST['s_value']);
			if (isset($_REQUEST['do']) && $_REQUEST['do'] == 'change')
			{
				//'wp-admin/admin.php?page=wpsg-Admin&subaction=shippingData&do=change&edit_id=12&p_id=34&p_value=56&noheader=1';

				$arTaxKey = explode('_', wpsg_q($_REQUEST['mwst']));
				$country_id = $arTaxKey[1];
				$country = wpsg_country::getInstance($country_id);
				$tax_key = 'c';
				$noMwSt = false;
				if (wpsg_q($_REQUEST['noMwSt']) == 1) $noMwSt = true;
				if ($noMwSt === true) $tax_value = 0; else $tax_value = $country->getTax($tax_key);
				//if (wpsg_q($_REQUEST['price_frontend']) == WPSG_NETTO)
				if ($option == WPSG_NETTO)
				{
					$data = array('price_shipping' => $sval, 'price_shipping_netto' => $sval, 'type_shipping' => $sid);
				}
				else
				{
					$data = array('price_shipping' => $sval, 'price_shipping_brutto' => $sval, 'type_shipping' => $sid);
				}
				$this->db->UpdateQuery(WPSG_TBL_ORDER, $data, "`id` = '".$oid."'");

			}

			// Order-Daten sammeln und Tabelle neu generieren
			$basket = new wpsg_basket();
			$basket->initFromDB($oid, true);
			$this->shop->view['basket'] = $basket->toArray(true);

			if ($option == WPSG_NETTO)
			{
				$data = array('price_shipping_brutto' => $this->shop->view['basket']['sum']['preis_shipping_brutto']);
			}
			else
			{
				$data = array('price_shipping_netto' => $this->shop->view['basket']['sum']['preis_shipping_netto']);
			}
			$this->db->UpdateQuery(WPSG_TBL_ORDER, $data, "`id` = '".$oid."'");

			$this->correctPrice($oid);

			$this->shop->view['colspan'] = 3;
			if ($this->shop->get_option('wpsg_showMwstAlways') == '1' || sizeof($this->shop->view['basket']['mwst']) > 1) $this->shop->view['colspan'] ++;

			$this->shop->render(WPSG_PATH_VIEW.'/order/view_orderdata.phtml');

			exit;

		}	// public function shippingDataAction()

		/**
		 * Wird aufgerufen, wenn die Gutscheine verwaltet werden sollen
		 */
		public function voucherDataAction()
		{
			$t1 = 0;
			$oid = 0;
			$option = $this->shop->get_option('wpsg_preisangaben');
			if (isset($_REQUEST['edit_id'])) $oid = wpsg_q($_REQUEST['edit_id']);

			if (isset($_REQUEST['do']) && $_REQUEST['do'] == 'add')
			{
				if (isset($_REQUEST['edit_id'])) $oid = wpsg_q($_REQUEST['edit_id']);
				//wp-admin/admin.php?page=wpsg-Admin&subaction=voucherData&do=add&oid=34&noheader=1
				$dt = new DateTime();
				$dt1 = $dt->format('Y-m-d H:i:s');
				$dt = date_modify($dt, '+1 year');
				$dt2 = $dt->format('Y-m-d H:i:s');
				$gs = array('value' => '10.00', 'calc_typ' => 'w', 'code' => 'Gutschein', 'o_id' => $oid,
				            'cdate' => $dt1, 'start_date' => $dt1, 'end_date' => $dt2);
				$gs_id = $this->db->ImportQuery(WPSG_TBL_GUTSCHEIN, $gs);
				$sval = 10.0;
				if ($option == WPSG_NETTO)
				{
					$data = array('price_gs' => $sval, 'price_gs_netto' => $sval);
				}
				else
				{
					$data = array('price_gs' => $sval, 'price_gs_brutto' => $sval);
				}

				$this->db->UpdateQuery(WPSG_TBL_ORDER, $data, "`id` = '".$oid."'");
				$_REQUEST['gs_id'] = $gs_id;

			}
			else if (isset($_REQUEST['do']) && $_REQUEST['do'] == 'remove')
			{
				// Gutschein löschen
				$this->db->Query("DELETE FROM `".WPSG_TBL_GUTSCHEIN."` WHERE `id` = '".wpsg_q($_REQUEST['gs_id'])."'");
				$ogs = array('gs_id' => 0, 'price_gs' => 0, 'price_gs_netto' => 0, 'price_gs_brutto' => 0);
				$this->db->UpdateQuery(WPSG_TBL_ORDER, $ogs, "`id` = '".$oid."'");
				$_REQUEST['gs_id'] = 0;

			}
			else if (isset($_REQUEST['do']) && $_REQUEST['do'] == 'editname')
			{
				//wp-admin/admin.php?page=wpsg-Admin&subaction=voucherData&do=editname&oid=34&noheader=1
				//submitdata: { field: 'code', gs_id: 92 }
				$data = array(wpsg_q($_REQUEST['field']) => wpsg_q($_REQUEST['value']));

				$this->db->UpdateQuery(WPSG_TBL_GUTSCHEIN, $data, "`id` = '".wpsg_q($_REQUEST['gs_id'])."'");
			}
			else if (isset($_REQUEST['do']) && $_REQUEST['do'] == 'editvalue')
			{
				//wp-admin/admin.php?page=wpsg-Admin&subaction=voucherData&do=editname&oid=34&noheader=1
				//submitdata: { field: 'code', gs_id: 92 }
				$sval = abs(wpsg_tf($_REQUEST['value']));
				/*
				 $arTaxKey = explode('_', wpsg_q($_REQUEST['mwst']));
				 $country_id = $arTaxKey[1];
				 $country = wpsg_country::getInstance($country_id);
				 $tax_key = 'c';
				 $noMwSt = false;
				 if (wpsg_q($_REQUEST['noMwSt']) == 1) $noMwSt = true;
				 if ($noMwSt === true) $tax_value = 0; else $tax_value = $country->getTax($tax_key);
				 //if (wpsg_q($_REQUEST['price_frontend']) == WPSG_NETTO)
				 */
				if ($option == WPSG_NETTO)
				{
					$data = array('price_gs' => $sval, 'price_gs_netto' => $sval);
				}
				else
				{
					$data = array('price_gs' => $sval, 'price_gs_brutto' => $sval);
				}

				$this->db->UpdateQuery(WPSG_TBL_ORDER, $data, "`id` = '".wpsg_q($_REQUEST['edit_id'])."'");

			}

			// Order-Daten sammeln und Tabelle neu generieren
			$basket = new wpsg_basket();
			$basket->initFromDB($oid, true);
			$this->shop->view['basket'] = $basket->toArray(true);

			//if (isset($_REQUEST['do']) && $_REQUEST['do'] == 'editvalue')
			{
				if ($option == WPSG_NETTO)
				{
					$data = array('price_gs_brutto' => $this->shop->view['basket']['sum']['gs_brutto']);
				}
				else
				{
					$data = array('price_gs_netto' => $this->shop->view['basket']['sum']['gs_netto']);
				}
				$this->db->UpdateQuery(WPSG_TBL_ORDER, $data, "`id` = '".wpsg_q($_REQUEST['edit_id'])."'");
			}

			$this->correctPrice($oid);

			$this->shop->view['colspan'] = 3;
			if ($this->shop->get_option('wpsg_showMwstAlways') == '1' || sizeof($this->shop->view['basket']['mwst']) > 1) $this->shop->view['colspan'] ++;

			$this->shop->render(WPSG_PATH_VIEW.'/order/view_orderdata.phtml');

			exit;

		} // public function voucherDataAction()

		/*
		 * Wird aufgerufen, wenn Elemente der Bestellansicht in der Bestellverwaltung im alternativen Design verschoben werden
		 */
		public function itemOrderDataAction()
		{

			if(isset($_REQUEST['order'])  && wpsg_isSizedArray($_REQUEST['order'])) $order = $_REQUEST['order'];
			else $order = array();

			if(isset($_REQUEST['orderPosition'])  && wpsg_isSizedString($_REQUEST['orderPosition'])) $orderPos = $_REQUEST['orderPosition'];
			else $orderPos = "";

			$currentOrderLeft = $this->shop->get_option('wpsg_backendui_orderdetail_itemorder_left');
			$currentOrderRight = $this->shop->get_option('wpsg_backendui_orderdetail_itemorder_right');

			if($orderPos === "left" && $currentOrderLeft !== $order)
				$this->shop->update_option('wpsg_backendui_orderdetail_itemorder_left', $order);

			if($orderPos === "right" && $currentOrderRight !== $order)
				$this->shop->update_option('wpsg_backendui_orderdetail_itemorder_right', $order);

		} // public function itemOrderDataAction

		/*
		 * Wird aufgerufen, wenn Elemente der Bestellansicht in der Bestellverwaltung im alternativen Design eingeklappt werdeb
		 */
		public function foldedItemsDataAction()
		{

			if(isset($_REQUEST['folded_items'])) $items = $_REQUEST['folded_items'];
			else $items = array();

			$currentFoldedItems = $this->shop->get_option('wpsg_backendui_orderdetail_foldedpanels');

			if($currentFoldedItems !== $items)
				$this->shop->update_option('wpsg_backendui_orderdetail_foldedpanels', $items);

		} // public function itemOrderDataAction

		/**
		 * Wird aufgerufen, wenn die Rabatte verwaltet werden sollen
		 */
		public function discountDataAction()
		{
			$t1 = 0;
			$oid = 0;
			$option = $this->shop->get_option('wpsg_preisangaben');
			if (isset($_REQUEST['edit_id'])) $oid = wpsg_q($_REQUEST['edit_id']);

			if (isset($_REQUEST['do']) && $_REQUEST['do'] == 'add')
			{
				if (isset($_REQUEST['edit_id'])) $oid = wpsg_q($_REQUEST['edit_id']);

				$sval = 10.0;
				if ($option == WPSG_NETTO)
				{
					$data = array('price_rabatt' => $sval, 'price_rabatt_netto' => $sval);
				}
				else
				{
					$data = array('price_rabatt' => $sval, 'price_rabatt_brutto' => $sval);
				}

				$this->db->UpdateQuery(WPSG_TBL_ORDER, $data, "`id` = '".$oid."'");

			}
			else if (isset($_REQUEST['do']) && $_REQUEST['do'] == 'remove')
			{
				// Rabatt löschen
				$ogs = array('price_rabatt' => 0, 'price_rabatt_netto' => 0, 'price_rabatt_brutto' => 0);
				$this->db->UpdateQuery(WPSG_TBL_ORDER, $ogs, "`id` = '".$oid."'");

			}
			else if (isset($_REQUEST['do']) && $_REQUEST['do'] == 'editname')
			{
				//submitdata: { field: 'code', gs_id: 92 }
				$data = array(wpsg_q($_REQUEST['field']) => wpsg_q($_REQUEST['value']));

				//$this->db->UpdateQuery(WPSG_TBL_GUTSCHEIN, $data, "`id` = '".wpsg_q($_REQUEST['gs_id'])."'");
			}
			else if (isset($_REQUEST['do']) && $_REQUEST['do'] == 'editvalue')
			{
				$sval = abs(wpsg_tf($_REQUEST['value']));

				$arTaxKey = explode('_', wpsg_q($_REQUEST['mwst']));
				$country_id = $arTaxKey[1];
				$country = wpsg_country::getInstance($country_id);
				$tax_key = $arTaxKey[0];
				$noMwSt = false;
				if (wpsg_q($_REQUEST['noMwSt']) == 1) $noMwSt = true;
				if ($noMwSt === true) $tax_value = 0; else $tax_value = $country->getTax($tax_key);
				//if (wpsg_q($_REQUEST['price_frontend']) == WPSG_NETTO)
				if ($option == WPSG_NETTO)
				{
					$data = array('price_rabatt' => $sval, 'price_rabatt_netto' => $sval);
				}
				else
				{
					$data = array('price_rabatt' => $sval, 'price_rabatt_brutto' => $sval);
				}

				$this->db->UpdateQuery(WPSG_TBL_ORDER, $data, "`id` = '".wpsg_q($_REQUEST['edit_id'])."'");
			}

			// Order-Daten sammeln und Tabelle neu generieren
			$basket = new wpsg_basket();
			$basket->initFromDB($oid, true);
			$this->shop->view['basket'] = $basket->toArray(true);

			//if (isset($_REQUEST['do']) && $_REQUEST['do'] == 'editvalue')
			{
				if ($option == WPSG_NETTO)
				{
					$data = array('price_rabatt_brutto' => $this->shop->view['basket']['sum']['preis_rabatt_brutto']);
				}
				else
				{
					$data = array('price_rabatt_netto' => $this->shop->view['basket']['sum']['preis_rabatt_netto']);
				}
				$this->db->UpdateQuery(WPSG_TBL_ORDER, $data, "`id` = '".wpsg_q($_REQUEST['edit_id'])."'");
			}

			$this->correctPrice($oid);

			$this->shop->view['colspan'] = 3;
			if ($this->shop->get_option('wpsg_showMwstAlways') == '1' || sizeof($this->shop->view['basket']['mwst']) > 1) $this->shop->view['colspan'] ++;

			$this->shop->render(WPSG_PATH_VIEW.'/order/view_orderdata.phtml');

			exit;

		} // public function discountDataAction()

		/**
		 * Setzt den Admin Kommentar
		 */
		public function setAdminCommentAction()
		{

			$_REQUEST['value'] = wpsg_sinput("text_field", $_REQUEST['value']);

			$this->db->UpdateQuery(WPSG_TBL_ORDER, array(
				'admincomment' => wpsg_q($_REQUEST['value'])
			), "`id` = '".wpsg_q($_REQUEST['edit_id'])."'");

			die($_REQUEST['value']);

		} // public function setAdminCommentAction()

		/**
		 * Nimmt Ajax Anfragen innerhalb der Bestellverwaltung entgegen
		 */
		public function ajaxAction()
		{

			if (isset($_REQUEST['mod']))
			{

				if (isset($_REQUEST['edit_id']))
				{

					$this->shop->view['data'] = $this->db->fetchRow("
						SELECT
							O.*
						FROM
							`".WPSG_TBL_ORDER."` AS O
						WHERE
							O.`id` = '".wpsg_q($_REQUEST['edit_id'])."'					
					");

					$this->shop->view['data']['shipping_land'] = $this->db->fetchRow("SELECT * FROM `".WPSG_TBL_LAND."` WHERE `id` = '".wpsg_q($this->shop->view['data']['shipping_land'])."'");

					$temp = $this->db->fetchRow("
						SELECT
							A.*
						FROM
							`".WPSG_TBL_ADRESS."` AS A
						WHERE
							A.`id` = '".wpsg_q($this->shop->view['data']['shipping_adress_id'])."'
					");

					$this->shop->view['data']['shipping_land'] = $this->db->fetchRow("SELECT * FROM `".WPSG_TBL_LAND."` WHERE `id` = '".wpsg_q($temp['land'])."'");

					$this->shop->view['kunde'] = $this->db->fetchRow("
						SELECT
							K.*
						FROM
							`".WPSG_TBL_KU."` AS K
						WHERE
							K.`id` = '".wpsg_q($this->shop->view['data']['k_id'])."'
					");

					$this->shop->view['kunde']['land'] = $this->db->fetchRow("SELECT * FROM `".WPSG_TBL_LAND."` WHERE `id` = '".wpsg_q($this->shop->view['kunde']['land'])."'");

					$temp = $this->db->fetchRow("
						SELECT
							A.*
						FROM
							`".WPSG_TBL_ADRESS."` AS A
						WHERE
							A.`id` = '".wpsg_q($this->shop->view['data']['adress_id'])."'
					");

					$this->shop->view['kunde']['land'] = $this->db->fetchRow("SELECT * FROM `".WPSG_TBL_LAND."` WHERE `id` = '".wpsg_q($temp['land'])."'");

				}

				$this->shop->callMod($_REQUEST['mod'], 'order_ajax');

			}
			else if (wpsg_isSizedString($_REQUEST['wpsg_action'], 'saveInvoiceAdress'))
			{

				parse_str($_REQUEST['form_data'], $form_data);
 
				if (wpsg_isSizedInt($form_data['dialog_all']))
				{

					// Alle Bestellungen des Kunden anpassen
					$customer_id = $this->db->fetchOne("SELECT `k_id` FROM `".WPSG_TBL_ORDER."` WHERE `id` = '".wpsg_q($_REQUEST['edit_id'])."' ");
					$arOrder = $this->db->fetchAssocField("SELECT `id` FROM `".WPSG_TBL_ORDER."` WHERE `k_id` = '".wpsg_q($customer_id)."' ");

				}
				else
				{

					$arOrder = array($_REQUEST['edit_id']);

				}

				$adress_data = array(
					'firma' => wpsg_q($form_data['dialog_firma']),
					'title' => wpsg_q($form_data['dialog_title']),
					'vname' => wpsg_q($form_data['dialog_vname']),
					'name' => wpsg_q($form_data['dialog_name']),
					'fax' => wpsg_q($form_data['dialog_fax']),
					'tel' => wpsg_q($form_data['dialog_tel']),
					'strasse' => wpsg_q($form_data['dialog_strasse']),
					'nr' => wpsg_q($form_data['dialog_nr']),
					'plz' => wpsg_q($form_data['dialog_plz']),
					'ort' => wpsg_q($form_data['dialog_ort']),
					'land' => wpsg_q($form_data['dialog_land'])
				);

				foreach ($arOrder as $order_id)
				{

					// Daten in der Bestellung aktualisieren
					/** @var wpsg_order $oOrder */
					$oOrder = wpsg_order::getInstance($order_id);
					$oOrder->updateAdress($adress_data);

					// Daten im Kunden aktualisieren?
					if (wpsg_isSizedInt($form_data['dialog_customer'])) {

						$oCustomer = $oOrder->getCustomer();
						$oCustomer->updateAdress($adress_data);
					
					}

					if (wpsg_isSizedInt($form_data['dialog_shipping'])) {
						
						$oOrder->updateShippingAdress($adress_data);

					} 
					
					if ($order_id == $_REQUEST['edit_id']) {
						
						$this->db->UpdateQuery(WPSG_TBL_KU, [
							'ustidnr' => wpsg_q($form_data['dialog_ustidnr'])
						], " `id` = '".wpsg_q($oOrder->getCustomer()->id)."' ");
						
					}
					
				}
				
				die("1");

			}
			else if (wpsg_isSizedString($_REQUEST['wpsg_action'], 'debug_urlpay'))
			{

				$oOrder = wpsg_order::getInstance($_REQUEST['edit_id']);
				$arOrderProducts = $oOrder->getOrderProducts();

				foreach ($arOrderProducts as $oOrderProduct)
				{

					$oProduct = $oOrderProduct->getProduct();

					$this->shop->notifyURL($oProduct->posturl, $oOrderProduct->getProductKey(), $oOrderProduct->getCount(), $oOrder->id, 1, false, array(
						'product_index' => $oOrderProduct->getProductIndex()
					));

				}

				$this->shop->addBackendMessage(__('URL Benachrichtigung (Bezahlung) wurde für die Produkte der Bestellung simuliert.'));
				$this->shop->redirect(
					wpsg_admin_url('Order', 'view', ['edit_id' => $_REQUEST['edit_id']])					
				);

			}
			else if (wpsg_isSizedString($_REQUEST['wpsg_action'], 'debug_urlbuy'))
			{

				$oOrder = wpsg_order::getInstance($_REQUEST['edit_id']);
				$arOrderProducts = $oOrder->getOrderProducts();

				foreach ($arOrderProducts as $oOrderProduct)
				{

					$oProduct = $oOrderProduct->getProduct();

					$this->shop->notifyURL($oProduct->posturl, $oOrderProduct->getProductKey(), $oOrderProduct->getCount(), $oOrder->id, 0, false, array(
						'product_index' => $oOrderProduct->getProductIndex()
					));

				}

				$this->shop->addBackendMessage(__('URL Benachrichtigung (Kauf) wurde für die Produkte der Bestellung simuliert.'));
				$this->shop->redirect(
					wpsg_admin_url('Order', 'view', ['edit_id' => $_REQUEST['edit_id']])
				);

			}
			else if (wpsg_isSizedString($_REQUEST['wpsg_action'], 'debug_customermail'))
			{

				$this->shop->basket->initFromDB($_REQUEST['edit_id']);
				$arBasket = $this->shop->basket->toArray();

				$this->shop->basket->sendOrderSaveMails($_REQUEST['edit_id'], $arBasket, true, false, true);

				$this->shop->addBackendMessage(wpsg_translate(__('Kundenmail wurde erfolgreich an #1# simuliert.', 'wpsg'), $this->shop->get_option('wpsg_adminmail_empfaenger')));
				$this->shop->redirect(
					wpsg_admin_url('Order', 'view', ['edit_id' => $_REQUEST['edit_id']])
				);

			}
			else if (wpsg_isSizedString($_REQUEST['wpsg_action'], 'debug_adminmail'))
			{

				$this->shop->basket->initFromDB($_REQUEST['edit_id']);
				$arBasket = $this->shop->basket->toArray();

				$this->shop->basket->sendOrderSaveMails($_REQUEST['edit_id'], $arBasket, false, true, true);

				$this->shop->addBackendMessage(wpsg_translate(__('Adminmail wurde erfolgreich an #1# simuliert.', 'wpsg'), $this->shop->get_option('wpsg_adminmail_empfaenger')));
				$this->shop->redirect(
					wpsg_admin_url('Order', 'view', ['edit_id' => $_REQUEST['edit_id']])
				);

			}

		} // public function ajaxAction()

		/**
		 * Ändert den Status einer Bestellung und leitet zur Bestellung zurück
		 */
		public function switchStatusAction()
		{

			if (!array_key_exists($_REQUEST['status'], $this->shop->arStatus))
			{

				$this->shop->addBackendError(__('Status konnte nicht gesetzt werden!', 'wpsg'));

			}
			else
			{

				$bOK = $this->shop->setOrderStatus(
					wpsg_sinput("key", $_REQUEST['edit_id']),
					wpsg_sinput("key", $_REQUEST['status']),
					(($_REQUEST['sendMail'] == '1')?true:false)
				);

				if ($bOK)
					$this->shop->addBackendMessage(__('Status wurde erfolgreich geändert!', 'wpsg'));
				else
					$this->shop->addBackendError(__('Status wurde nicht geändert, da unverändert.', 'wpsg'));

			}

			$this->redirect(wpsg_admin_url('Order', 'view', ['edit_id' => $_REQUEST['edit_id']]));

		} // public function switchStatusAction()

		/**
		 * Wird beim stornieren einer einzelnen Bestellung aufgerufen
		 */
		public function stornoAction() {
			
			wpsg_checkNounce('Order', 'storno', ['edit_id' => wpsg_getInt($_REQUEST['edit_id'])]);

			$this->shop->setOrderStatus($_REQUEST['edit_id'], 500, true);
			$this->shop->addBackendMessage(__('Bestellung wurde storniert und Kunde benachrichtigt.', 'wpsg'));

			$this->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Order');

		} // public function stornoAction()

		/**
		 * Zeigt eine Bestellung an
		 */
		public function viewAction() {
									
			if (wpsg_isSizedString($_REQUEST['subaction'], 'updateCalculation')) {

				$_REQUEST['edit_id'] = wpsg_sinput("key", $_REQUEST['edit_id']);
				$_REQUEST['tax_mode'] = wpsg_sinput("key", $_REQUEST['tax_mode']);

				$oCalculation = new \wpsg\wpsg_calculation();
				
				$oCalculation->fromDB($_REQUEST['edit_id']);				
				$oCalculation->setTaxMode($_REQUEST['tax_mode']);								
				$oCalculation->toDB($_REQUEST['edit_id']);
				
				// Umweg ist hier notwendig, da sonst beim ändern der Besteuerung auch die Produkte neu geladen werden müssten
				$oCalculation = new \wpsg\wpsg_calculation();
				$oCalculation->fromDB($_REQUEST['edit_id']);
				
				$this->shop->view['oCalculation'] = $oCalculation;
				
				wpsg_header::JSONData([
					'product_table' => $this->shop->render(WPSG_PATH_VIEW.'order/product_table.phtml', false)
				]);
				
			} else if (wpsg_isSizedString($_REQUEST['subaction'], 'removeProduct')) {
				
				wpsg_checkNounce('Order', 'view', ['subaction' => 'removeProduct', 'edit_id' => $_REQUEST['edit_id']]);
				
                $oCalculation = new \wpsg\wpsg_calculation();
                $oCalculation->fromDB($_REQUEST['edit_id']);

                $oCalculation->removeProduct($_REQUEST['order_product_id']);

                $oCalculation->toDB($_REQUEST['edit_id']);

                $this->shop->view['oCalculation'] = $oCalculation;

                wpsg_header::JSONData([
                    'product_table' => $this->shop->render(WPSG_PATH_VIEW.'order/product_table.phtml', false)
                ]);

			} else if (wpsg_isSizedString($_REQUEST['subaction'], 'sendMail')) {
				
				wpsg_checkNounce('Order', 'view', ['subaction' => 'sendMail', 'do' => 'customer', 'edit_id' => $_REQUEST['edit_id']]);
				
		    	$oBasket = new wpsg_basket();
		    	$oBasket->initFromDB($_REQUEST['edit_id']);
		    	
		    	//die(wpsg_debug($oBasket->toArray()));
		    	
		    	$oBasket->sendOrderSaveMails($_REQUEST['edit_id'], $oBasket->toArray(), true, false, false);
		    						
		    	die(__('Mail wurde erfolgreich versendet.', 'wpsg'));
                
			} else if (wpsg_isSizedString($_REQUEST['subaction'], 'editVoucher')) {
			
		    	/** @var \wpsg\wpsg_calculation */
				$this->shop->view['oCalculation'] = new \wpsg\wpsg_calculation();
				$this->shop->view['oCalculation']->fromDb($_REQUEST['edit_id']);
			
				if (isset($_REQUEST['do'])) {
					
					if ($_REQUEST['do'] === 'remove') {
				 
						$this->shop->view['oCalculation']->removeVoucher($_REQUEST['order_voucher_id']);
						 
					} else if ($_REQUEST['do'] === 'submit') {
						
						$this->shop->view['oCalculation']->removeVoucher($_REQUEST['order_voucher_id']);
						
						if (wpsg_tf($_REQUEST['be_voucher_amount']) > 0) {
							
							if (wpsg_isSizedInt($_REQUEST['be_voucher_coupon'])) {
								
								$this->shop->view['oCalculation']->addCoupon($_REQUEST['be_voucher_amount'], $this->shop->getBackendTaxview(), '0', 1, $_REQUEST['be_voucher_code'], $_REQUEST['be_voucher_id'], $_REQUEST['order_voucher_id']);
								
							} else {
								
								$this->shop->view['oCalculation']->addVoucher($_REQUEST['be_voucher_amount'], $this->shop->getBackendTaxview(), '0', 1, $_REQUEST['be_voucher_code'], $_REQUEST['be_voucher_id'], $_REQUEST['order_voucher_id']);
								
							}
																					
						}
						 						
					} else if ($_REQUEST['do'] === 'search') {
						
						$arVoucher = $this->db->fetchAssoc("
							SELECT
								V.`id`, V.`value` AS `gs_value`, V.`code`, V.`calc_typ`, V.`id`, V.`coupon`,
								CONCAT(V.`code`) AS `value`
							FROM
								`".WPSG_TBL_GUTSCHEIN."` AS V
							WHERE
								V.`code` LIKE '%".wpsg_q($_REQUEST['term'])."%' 
							ORDER BY
								V.`code` ASC 
						");
						
						foreach ($arVoucher as $k => $v) {
							
							$oVoucher = wpsg_voucher::getInstance($v['id']);
							
							if (!$oVoucher->isUsabel()) unset($arVoucher[$k]);
							else {
							
								if ($v['calc_type'] === 'p') $arVoucher[$k]['gs_value'] = wpsg_ff($arVoucher[$k]['gs_value'], '%');
								else $arVoucher[$k]['gs_value'] = wpsg_ff($oVoucher->getFreeAmount(), $this->shop->get_option('wpsg_currency'));
								
							}
							
						}
						
						wpsg_header::JSONData($arVoucher);
						
					}
					
					$this->shop->view['oCalculation']->toDB($_REQUEST['edit_id']);
					
					$this->shop->view['oCalculation'] = new \wpsg\wpsg_calculation();
					$this->shop->view['oCalculation']->fromDB($_REQUEST['edit_id']); // Muss ich machen, damit ich die order_voucher_id habe
					 					
					wpsg_header::JSONData([
						'product_table' => $this->shop->render(WPSG_PATH_VIEW.'order/product_table.phtml', false)
					]);
					
				}
				
				$this->shop->render(WPSG_PATH_VIEW.'/order/backendEdit/addVoucher.phtml');
			
				exit;
                
            } else if (wpsg_isSizedString($_REQUEST['subaction'], 'editDiscount')) {

		    	/** @var \wpsg\wpsg_calculation */
		        $oCalculation = new \wpsg\wpsg_calculation();
                $oCalculation->fromDb($_REQUEST['edit_id']);
			
				$this->shop->view['oCalculation'] = $oCalculation;
                
                if (wpsg_isSizedString($_REQUEST['do'], 'submit')) {
	
					$oCalculation->addDiscount($_REQUEST['be_discount_amount'], $this->shop->getBackendTaxview(), '0',1);
                    $oCalculation->toDB($_REQUEST['edit_id']);
	 
                    wpsg_header::JSONData([
                        'product_table' => $this->shop->render(WPSG_PATH_VIEW.'order/product_table.phtml', false)
                    ]);
                    
                } else if (wpsg_isSizedString($_REQUEST['do'], 'remove')) {
	
					$oCalculation->removeDiscount();
					$oCalculation->toDB($_REQUEST['edit_id']);
	 
					wpsg_header::JSONData([
						'product_table' => $this->shop->render(WPSG_PATH_VIEW.'order/product_table.phtml', false)
					]);
					
				}
                
                $this->shop->render(WPSG_PATH_VIEW.'/order/backendEdit/addDiscount.phtml');

                exit;
		        
            } else if (wpsg_isSizedString($_REQUEST['subaction'], 'addProduct')) {
				
		        if (wpsg_isSizedString($_REQUEST['do'], 'search')) {
			
		        	wpsg_checkNounce('Order', 'view', ['subaction' => 'addProduct', 'edit_id' => $_REQUEST['edit_id'], 'do' => 'search']);
		        	
                    $arReturn = [];

                    $arProduct = wpsg_product::find(Array('s' => $_REQUEST['term']));

                    /** @var wpsg_product $oProduct */
                    foreach ($arProduct as $oProduct) {

                        $arReturn[] = Array(
                            'id' => $oProduct->id,
                            'value' => $oProduct->getProductName()
                        );

                    }

                    wpsg_header::JSONData($arReturn);

                    exit;
                    
				} else if (wpsg_isSizedString($_REQUEST['do'], 'updatePrice')) {
			
		        	parse_str($_REQUEST['form_data'], $form_data);
		        	
					$product_key = $_REQUEST['product_id'];					
					$this->shop->callMods('getProductKeyFromRequest',[&$product_key, $_REQUEST['product_id'], $form_data]);
					
					$arProduct = $this->shop->loadProduktArray($_REQUEST['product_id'],['product_key' => $product_key]);
					 
					if ($this->shop->getBackendTaxview() === WPSG_BRUTTO) echo wpsg_ff($arProduct['preis'], 'EUR');
					else echo wpsg_ff($arProduct['preis_netto'], 'EUR');
					
					exit;
		        	
                } else if (wpsg_isSizedString($_REQUEST['do'], 'product')) {
			
					wpsg_checkNounce('Order', 'view', ['subaction' => 'addProduct', 'do' => 'product', 'edit_id' => $_REQUEST['edit_id']]);
		        	
		            if (wpsg_isSizedInt($_REQUEST['product_id'])) {

                        $this->shop->view['oProduct'] = wpsg_product::getInstance($_REQUEST['product_id']);
		                
                    }
		            
		            if (wpsg_isSizedInt($_REQUEST['order_product_id'])) {
		                
		                $this->shop->view['oOrderProduct'] = wpsg_order_product::getInstance($_REQUEST['order_product_id']);

                        if (!wpsg_isSizedInt($_REQUEST['product_id'])) $this->shop->view['oProduct'] = $this->shop->view['oOrderProduct']->getProduct();
		                
                    } else {
		             
		                $this->shop->view['oOrderProduct'] = false;
		                
                    }
		            
                    $this->shop->render(WPSG_PATH_VIEW.'/order/backendEdit/addProduct_product_selected.phtml');

                    exit;
		            
                } else if (wpsg_isSizedString($_REQUEST['do'], 'submit')) {
			
					wpsg_checkNounce('Order', 'view', ['subaction' => 'addProduct', 'do' => 'submit', 'edit_id' => $_REQUEST['edit_id']]);
		        	
                    $oCalculation = new \wpsg\wpsg_calculation();
                    $oCalculation->fromDB($_REQUEST['edit_id']);

                    $order_product_id = ((wpsg_isSizedInt($_REQUEST['order_product_id']))?$_REQUEST['order_product_id']:false);
		            $oProduct = wpsg_product::getInstance($_REQUEST['product_id']);
					
		            $product_key = $_REQUEST['product_id'];					
		            $this->shop->callMods('getProductKeyFromRequest',[&$product_key, $_REQUEST['product_id'], $_REQUEST]);
		            
		            if ($_REQUEST['add_eu'] === '1') $eu = true;
		            else $eu = false;
			
					$product_index = false;
		            if (wpsg_isSizedInt($order_product_id)) $product_index = $this->db->fetchOne("SELECT `product_index` FROM `".WPSG_TBL_ORDERPRODUCT."` WHERE `id` = '".wpsg_q($order_product_id)."' ");
		            
					$oCalculation->addProduct($_REQUEST['add_price'], $this->shop->getBackendTaxview(), $oProduct->mwst_key, $_REQUEST['add_amount'], $product_key, $product_index, $order_product_id, $eu);
			
					$oCalculation->toDB($_REQUEST['edit_id']);
					
					$oCalculation = new \wpsg\wpsg_calculation();
					$oCalculation->fromDB($_REQUEST['edit_id']);
			
					$this->shop->view['oCalculation'] = $oCalculation;
			
					wpsg_header::JSONData([
						'product_table' => $this->shop->render(WPSG_PATH_VIEW.'order/product_table.phtml', false)
					]);
					 		            
                } else {
			 
					wpsg_checkNounce('Order', 'view', ['subaction' => 'addProduct', 'edit_id' => $_REQUEST['edit_id']]);
		        	
				}
		        
                $this->shop->render(WPSG_PATH_VIEW.'/order/backendEdit/addProduct.phtml');

                exit;
		        
            } else {
				
				if (!(wpsg_isSizedString($_REQUEST['subaction'], 'editPayShipping'))) {
				
					wpsg_checkNounce('Order', 'view', ['edit_id' => wpsg_getInt($_REQUEST['edit_id'])]);
					
				}
				
			}
		   
			$this->shop->view['data'] = $this->db->fetchRow("
				SELECT
					K.*, O.*, CA.*,
					O.`id` AS id,
					O.`comment` AS `order_comment`,
					O.`status` AS `status`,
					K.`id` AS k_id,
					L.`kuerzel` AS `land_krzl`,
					L.`name` AS `land_name`
				FROM
					`".WPSG_TBL_ORDER."` AS O 
						LEFT JOIN `".WPSG_TBL_KU."` AS K ON (O.`k_id` = K.`id`)
						LEFT JOIN `".WPSG_TBL_ADRESS."` AS CA ON (O.`adress_id` = CA.`id`)
						LEFT JOIN `".WPSG_TBL_LAND."` AS L ON (CA.`land` = L.`id`)
				WHERE
					O.`id` = '".wpsg_q($_REQUEST['edit_id'])."'	
			");

			$this->shop->view['oOrder'] = wpsg_order::getInstance($_REQUEST['edit_id']);

			$basket = new wpsg_basket();
			$basket->initFromDB($this->shop->view['data']['id'], true);
			$this->shop->view['basket'] = $basket->toArray(true);

			$this->shop->view['arPayment'] = $this->shop->arPayment;
			$this->shop->view['arShipping'] = $this->shop->arShipping;
			
			$oCalculation = new \wpsg\wpsg_calculation();
			$oCalculation->fromDB($this->shop->view['data']['id']);
			
			$this->shop->view['oCalculation'] = $oCalculation;
			
            if (wpsg_isSizedString($_REQUEST['subaction'], 'editPayShipping')) {
	
				wpsg_checkNounce('Order', 'view', ['subaction' => 'editPayShipping', 'edit_id' => $_REQUEST['edit_id']]);
            	
                $this->shop->render(WPSG_PATH_VIEW.'/order/backendEdit/editPayShipping.phtml');

                exit;

            }
            
            $oCalculation = new \wpsg\wpsg_calculation();
            $oCalculation->fromDB($this->shop->view['data']['id']);

            $this->shop->view['oCalculation'] = $oCalculation;

            $this->shop->view['country'] = $this->db->fetchRow("
				SELECT
					C.`id`, C.`name`, C.`kuerzel`,
					C.`id` AS `select_key`,
					C.`name` AS `select_value`
				FROM
					`".WPSG_TBL_LAND."` AS C
				WHERE
					C.`id` = '".$this->shop->view['data']['land']."'
			");

			$this->shop->view['arCountry'] = $this->db->fetchAssocField("SELECT `id`, `name` FROM `".WPSG_TBL_LAND."` WHERE 1 ", "id", "name");

			$this->shop->view['pflicht'] = $this->shop->loadPflichtFeldDaten();
			$this->shop->view['arTitles'] = explode('|', $this->shop->view['pflicht']['anrede_auswahl']);

			$this->shop->view['shipping_country'] = $this->db->fetchRow("
				SELECT
					C.`id`, C.`name`, C.`kuerzel`
				FROM
					`".WPSG_TBL_LAND."` AS C
				WHERE
					C.`id` = '".$this->shop->view['data']['shipping_land']."'
			");

			$arAdr = $this->db->fetchRow("
						SELECT
							`title`,
							`name`,
							`vname`,
							`firma`,
							`strasse`,
							`plz`,
							`ort`,
							`land`
						FROM
							`".WPSG_TBL_ADRESS."`
						WHERE
							`id` = '".wpsg_q($this->shop->view['data']['adress_id'])."'
					");
			
            if (!is_array($arAdr)) $arAdr = [];
            
			$this->shop->view['data'] = array_merge($this->shop->view['data'], $arAdr);

			if ($this->shop->hasMod('wpsg_mod_orderupload'))
			{
				$this->shop->callMod('wpsg_mod_orderupload', 'order_view_sidebar', array(&$_REQUEST['edit_id']));
			}
            
			$this->shop->view['arSubAction'] = array(
				'general' => array(
					'title' => __('Allgemein', 'wpsg'),
					'content' => $this->shop->render(WPSG_PATH_VIEW.'/order/view_general.phtml', false)
				),
				'orderdata' => array(
					'title' => __('Bestelldaten', 'wpsg'),
					'content' => $this->shop->render(WPSG_PATH_VIEW.'/order/view_orderdata.phtml', false)
				),
				'customerdata' => array(
					'title' => __('Kundendaten', 'wpsg'),
					'content' => $this->shop->render(WPSG_PATH_VIEW.'/order/view_customerdata.phtml', false)
				),
				'shippay' => array(
					'title' => __('Versand-/Zahlungsart', 'wpsg'),
					'content' => $this->shop->render(WPSG_PATH_VIEW.'/order/view_shippay.phtml', false)
				)
			);

			$this->shop->callMods('order_view', array($_REQUEST['edit_id'], &$this->shop->view['arSubAction']));

			$this->shop->view['arSubAction']['orderlog'] = array(
				'title' => __('Bestellprotokoll', 'wpsg'),
				'content' => $this->shop->render(WPSG_PATH_VIEW.'/order/view_orderlog.phtml', false)
			);

			if (wpsg_isSizedInt($this->get_option('wpsg_debugModus')))
			{

				$this->shop->view['arSubAction']['dev'] = array(
					'title' => __('Entwickleroptionen', 'wpsg'),
					'content' => $this->shop->render(WPSG_PATH_VIEW.'/order/view_dev.phtml', false)
				);

			}

			if($this->get_option('wpsg_alternativeOrderDesign') == true)
			{

				$this->shop->render(WPSG_PATH_VIEW.'/order/view_alternativeDesign.phtml');

			}
			else
			{

				$this->shop->render(WPSG_PATH_VIEW.'/order/view.phtml');

			}

		} // public function viewAction()

		/**
		 * Übernimmt das löschen von Bestellungen
		 */
		public function deleteAction() {
			
			wpsg_checkNounce('Order', 'delete', ['edit_id' => wpsg_getInt($_REQUEST['edit_id'])]);

			if (!isset($_REQUEST['edit_id'])) {

				$this->shop->addBackendError(__('Keine Bestellnummer übergeben.', 'wpsg'));
				$this->shop->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Order');

			}

			$oOrder = wpsg_order::getInstance($_REQUEST['edit_id']);
			$oOrder->delete();

			$this->shop->addBackendMessage(__('Bestellung erfolgreich gelöscht.', 'wpsg'));
			$this->shop->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Order');

		} // public function deleteAction()

		public function indexAction() {

			if (wpsg_isSizedString($_REQUEST['do'], 'writeMultiRechnung') || wpsg_isSizedString($_REQUEST['wpsg_action'], 'showRechnung'))
			{

				// Mehrere Rechnungen schreiben/anzeigen
				if (isset($_REQUEST['ids'])) $IDs = explode("_", $_REQUEST['ids']);
				else $IDs = array_keys($_REQUEST['wpsg_multido']);

				// Hier die Rechnungen noch einmal nach CDATE sortieren
				$IDs = $this->db->fetchAssocField("SELECT `id` FROM `".WPSG_TBL_ORDER."` WHERE `id` IN (".wpsg_q(implode(',', $IDs)).") ORDER BY `cdate` ASC");
				
				require_once WPSG_PATH_LIB.'FPDF_1.81/fpdf.php';
				require_once WPSG_PATH_LIB.'FPDI_2.2.0/autoload.php';

				if (sizeof($IDs) > 0)
				{

					$pdf = new FPDI();

					foreach ($IDs as $o_id)
					{

						$rnr_db = $this->db->fetchRow("SELECT * FROM `".WPSG_TBL_RECHNUNGEN."` WHERE `rnr` != '' AND `storno` = '0000-00-00 00:00:00' AND `o_id` = '".wpsg_q($o_id)."' ORDER BY `id` DESC");
						$rnr = $rnr_db['rnr'];

						$path = $this->shop->callMod('wpsg_mod_rechnungen', 'getFilePath', array($o_id));

						$rnr_file = false;
						if (file_exists($path.'/R'.$rnr.'.pdf')) $rnr_file = $path.'/R'.$rnr.'.pdf';
						else if (file_exists($path.'/R'.$rnr_db['id'].'.pdf')) $rnr_file = $path.'/R'.$rnr_db['id'].'.pdf';
						else if (file_exists($path.'/'.$rnr_db['id'].'.pdf')) $rnr_file = $path.'/'.$rnr_db['id'].'.pdf';
						else if (file_exists($path.'/'.$rnr_db['rnr'].'.pdf')) $rnr_file = $path.'/'.$rnr_db['rnr'].'.pdf';

						if (file_exists($rnr_file))
						{

							$pagecount = $pdf->setSourceFile($rnr_file);

							for ($i = 1; $i <= $pagecount; $i++)
							{

								$tplidx = $pdf->ImportPage($i);
								$pdf->AddPage();
								$pdf->useTemplate($tplidx);

							}

						}

					}

					ob_end_clean();

					$pdf->Output('rechnungen.pdf', 'I');

					exit;

				}
				else
				{

					die(__('Keine Rechnungen gewählt!', 'wpsg'));

				}

			}
			else if (isset($_REQUEST['wpsg_order_doaction']))
			{

				if ($_REQUEST['wpsg_action'] == '-1') $this->addBackendError(__('Bitte eine Aktion wählen!', 'wpsg'));
				else if (!isset($_REQUEST['wpsg_multido']) || !is_array($_REQUEST['wpsg_multido'])) $this->addBackendError(__('Bitte mindestens eine Bestellung auswählen!', 'wpsg'));
				else
				{

					if ($_REQUEST['wpsg_action'] == 'multiDelete')
					{

						$arOrderID = $this->db->fetchAssocField("SELECT `id` FROM `".WPSG_TBL_ORDER."` WHERE `id` IN (".wpsg_q(implode(',', array_keys($_REQUEST['wpsg_multido']))).") ");
						$arOrder = wpsg_order::getInstance($arOrderID);

						foreach ($arOrder as $oOrder) { $oOrder->delete(); }

						$this->shop->addBackendError(wpsg_translate(__('#1# Bestellung(en) gelöscht.', 'wpsg'), sizeof($arOrder)));

					}
					else if ($_REQUEST['wpsg_action'] == 'writeRechnung')
					{

						$nWriteRechnung = 0;

						// E-Mail an Kunden senden
						$_REQUEST['wpsg_rechnungen_sendmail'] = '1';

						// Rechnungsdatum
						$_REQUEST['wpsg_rechnungen_datum'] = date('d.m.Y');

						// Status setzen
						$_REQUEST['wpsg_rechnungen_status'] = '1';

						// Neuer Status
						$_REQUEST['wpsg_rechnungen_status_neu'] = '110';

						// Fußtext
						if ($this->shop->get_option('wpsg_rechnungen_foottext_standard') !== false)
						{

							$wpsg_rechnungen_footer = $this->get_option("wpsg_rechnungen_footer");
							if (!is_array($wpsg_rechnungen_footer)) $wpsg_rechnungen_footer = unserialize($this->get_option("wpsg_rechnungen_footer"));
							if (!is_array($wpsg_rechnungen_footer)) $wpsg_rechnungen_footer = Array();

							$_REQUEST['wpsg_rechnungen_fusstext'] = wpsg_getStr($wpsg_rechnungen_footer[$this->shop->get_option('wpsg_rechnungen_foottext_standard')][1]);

						}
						else
						{

							$_REQUEST['wpsg_rechnungen_fusstext'] = '';

						}

						// URL Benachrichtigung
						$_REQUEST['wpsg_rechnungen_url'] = '0';

						// Fälligkeit anzeigen
						$_REQUEST['wpsg_rechnungen_faelligkeit'] = '1';

						$arIDs = array();

						// Sortieren nach CDDATE
						$arIDs_write = $this->db->fetchAssocField("SELECT `id` FROM `".WPSG_TBL_ORDER."` WHERE `id` IN (".wpsg_q(implode(',', array_keys($_REQUEST['wpsg_multido']))).") ORDER BY `cdate` ASC");

						// Rechnung für mehrere Bestellungen schreiben
						foreach ($arIDs_write as $k)
						{

							$bRechnungExists = $this->db->fetchOne("SELECT COUNT(*) FROM `".WPSG_TBL_RECHNUNGEN."` WHERE `rnr` != '' AND `o_id` = '".wpsg_q($k)."' AND `storno` = '0000-00-00 00:00:00'");

							// E-Mail aus Bestellung
							$_REQUEST['wpsg_rechnungen_email'] = $this->db->fetchOne("SELECT K.`email` FROM `".WPSG_TBL_ORDER."` AS O LEFT JOIN `".WPSG_TBL_KU."` AS K ON (O.`k_id` = K.`id`) WHERE O.`id` = '".wpsg_q($k)."'");

							if ($bRechnungExists <= 0)
							{

								try
								{

									$this->shop->callMod('wpsg_mod_rechnungen', 'writeRechnung', array($k, false, false));
									$arIDs[] = $k;
									$nWriteRechnung ++;

								}
								catch (Exception $e)
								{

									die($e->getMessage());

								}

							}

						}

						if ($nWriteRechnung <= 0)
						{

							$this->addBackendError(__('Für die gewählten Bestellungen konnten keine Rechnungen geschrieben werden, bestehende Rechnungen müssen erst storniert werden.', 'wpsg'));

						}
						else
						{

							$this->addBackendMessage(
								'nohspc_'.
								wpsg_translate(__('#1# Rechnungen geschrieben', 'wpsg'), $nWriteRechnung).
								' <a href="'.WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Order&noheader=1&do=writeMultiRechnung&ids='.implode('_', $arIDs).'" target="_new">'.__('Download', 'wpsg').'</a>'
							);

						}

					}
					else if (is_numeric($_REQUEST['wpsg_action']))
					{

						// Status setzen
						foreach ($_REQUEST['wpsg_multido'] as $k => $v)
						{

							$this->shop->setOrderStatus($k, $_REQUEST['wpsg_action'], false);

						}

						$this->addBackendMessage(wpsg_translate(__('Status von #1# Bestellungen aktualisiert (Kunden wurden nicht benachrichtigt)', 'wpsg'), sizeof($_REQUEST['wpsg_multido'])));

					}

				}

				$this->redirect(WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Order');

			}
			
			if (isset($_REQUEST['submit-button'])) check_admin_referer('wpsg-order-search');

			$nPerPage = $this->get_option('wpsg_order_perpage');
			if ($nPerPage <= 0) $nPerPage = 10;

			$this->shop->view['hasFilter'] = false;
			$this->shop->view['pages'] = 0;
			$this->shop->view['arStatus'] = array();
			$this->shop->view['arFilter'] = array(
				'order' => 'cdate',
				'ascdesc' => 'DESC',
				'status' => '0',
				'page' => '1'
			);

			$this->shop->view['tabs'] = array('left' => array(), 'right' => array());

			$this->shop->callMods('order_index_tab', array(&$this->shop->view['tabs']));

			if (wpsg_isSizedArray($_REQUEST['filter']))
			{

				$_REQUEST['filter']['s'] = wpsg_sinput("text_field", $_REQUEST['filter']['s']);
				$_REQUEST['filter']['k_id'] = wpsg_xss($_REQUEST['filter']['k_id']);
				$_REQUEST['filter']['cdate_m'] = wpsg_sinput("key", $_REQUEST['filter']['cdate_m']);
				$_REQUEST['filter']['cdate_y'] = wpsg_sinput("key", $_REQUEST['filter']['cdate_y']);
				$_REQUEST['filter']['invoicedate_m'] = wpsg_sinput("key", $_REQUEST['filter']['invoicedate_m']);
				$_REQUEST['filter']['invoicedate_y'] = wpsg_sinput("key", $_REQUEST['filter']['invoicedate_y']);

				$this->shop->view['arFilter'] = $_REQUEST['filter'];

			}
			else if (wpsg_isSizedArray($_SESSION['wpsg']['backend']['order']['arFilter']))
			{

				$this->shop->view['arFilter'] = $_SESSION['wpsg']['backend']['order']['arFilter'];

			}

			if (!isset($this->shop->view['arFilter']['status'])) $this->shop->view['arFilter']['status'] = '0';

			// Filter gesetzt?
			foreach (Array('s', 'k_id', 'cdate_m', 'cdate_y', 'invoicedate_m', 'invoicedate_y') as $field)
			{

				if (wpsg_isSizedString($this->shop->view['arFilter'][$field]) && $this->shop->view['arFilter'][$field] != '-1') { $this->shop->view['hasFilter'] = true; break; }

			}


			foreach ($this->shop->arStatus as $status_key => $status_label)
			{

				if ($this->shop->get_option('wpsg_showincompleteorder') != '1' && $status_key == wpsg_ShopController::STATUS_UNVOLLSTAENDIG) continue;

				$arFilterState = $this->shop->view['arFilter'];
				$arFilterState['status'] = $status_key;

				$count = wpsg_order::count($arFilterState);

				if (wpsg_isSizedInt($count)) $this->shop->view['arStatus'][$status_key] = array('label' => $status_label, 'count' => $count);

			}

			$arFilterState = $this->shop->view['arFilter']; unset($arFilterState['status']);
			$arFilterState['NOTstatus'] = wpsg_ShopController::STATUS_UNVOLLSTAENDIG;

			$this->shop->view['arStatus'] = wpsg_array_merge(array('-1' => array('label' => __('Alle', 'wpsg'), 'count' => wpsg_Order::count($arFilterState))), $this->shop->view['arStatus']);

			if ($this->shop->view['arFilter']['status'] == '-1') $this->shop->view['arFilter']['NOTstatus'] = wpsg_ShopController::STATUS_UNVOLLSTAENDIG;

			$this->shop->view['countAll'] = wpsg_order::count($this->shop->view['arFilter']);

			if (wpsg_isSizedInt($_REQUEST['seite'])) $this->shop->view['arFilter']['page'] = $_REQUEST['seite'];

			$this->shop->view['pages'] = ceil($this->shop->view['countAll'] / $nPerPage);
			if (!isset($this->shop->view['arFilter']['page']) || $this->shop->view['arFilter']['page'] <= 0 || $this->shop->view['arFilter']['page'] > $this->shop->view['pages']) $this->shop->view['arFilter']['page'] = 1;

			$this->shop->view['arFilter']['limit'] = array(($this->shop->view['arFilter']['page'] - 1) * $nPerPage, $nPerPage);

			// Filter speichern
			$_SESSION['wpsg']['backend']['order']['arFilter'] = $this->shop->view['arFilter'];

			$this->shop->view['arData'] = wpsg_order::find($this->shop->view['arFilter']);

			$this->shop->view['cdate_years'] = $this->db->fetchAssocField("SELECT DISTINCT DATE_FORMAT(`cdate`, '%Y') FROM `".WPSG_TBL_ORDER."` ORDER BY `cdate` ASC ");
			if ($this->shop->hasMod('wpsg_mod_rechnungen')) $this->shop->view['invoicedate_years'] = $this->db->fetchAssocField("SELECT DISTINCT DATE_FORMAT(`datum`, '%Y') FROM `".WPSG_TBL_RECHNUNGEN."` WHERE `storno` = '0000-00-00' AND `gnr` = '' ORDER BY `datum` ASC ");

			$this->shop->render(WPSG_PATH_VIEW.'/order/index.phtml');

		} // public function indexAction()

	} // class wpsg_OrderController extends wpsg_SystemController

?>