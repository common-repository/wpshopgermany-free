<?php

    namespace wpsg;

    /**
     * Neue Klasse zur Berechnung des Warenkorbes
     * Soll die alte basket Klasse ersetzen
     * 
     */
    class wpsg_calculation {

        /**
         * @var wpsg_db
         */
        private $db = null;

        /**
         * @var \wpsg_ShopController
         */
        private $shop = null;

        /** @var array */
        private $arCountry = null;
        
        /** @var array */
        private $arCalculation = null;
        
        /** @var array  */
    	private $arCalculationRow = null;
            
		/** @var bool Ist bei alten rekonstriuerten Berechnungen true */
    	public $restored = false;
    			
    	private $dWeight = 0;
    	
    	private $tax_mode = '2';
	
    	private static $functionscache = [];
    	
		const TAXMODE_SMALLBUSINESS = '1';
    	const TAXMODE_B2C = '2';
    	const TAXMODE_B2B = '3';
	
		/**
		 * wpsg_calculation constructor.
		 */
        public function __construct() {
            
            $this->db = &$GLOBALS['wpsg_db'];
            $this->shop = &$GLOBALS['wpsg_sc'];
                        
            $this->arCalculationRow = [];
            $this->arCountry = [];
            
        }        
        
        public function setTaxMode($tax_mode) {
        	
        	$this->tax_mode = $tax_mode;
	
			$this->arCalculation = null;
        	
		}
        
		public function getTaxMode() {
        	
        	return $this->tax_mode; 
        	
		}
		
        /** 
         * Setzt das Standardland für die Steuerberechnung wenn im $tax_key kein Land definiert ist 
         */
        public function addCountry($country_id, $tax_mode, $tax_a, $tax_b, $tax_c, $tax_d, $default = true) {
            
        	if (!wpsg_isSizedInt($country_id)) $country_id = $this->shop->getDefaultCountry(true);
        	
        	if (array_key_exists($country_id, $this->arCountry)) return; 
        	
            $this->arCountry[$country_id] = [
            	'id' => $country_id,
            	'tax_mode' => $tax_mode,
				'default' => $default,
            	'a_'.$country_id => $tax_a,
				'b_'.$country_id => $tax_b,
				'c_'.$country_id => $tax_c,
				'd_'.$country_id => $tax_d,
			];
	
			$this->arCalculation = null;
            
        }
        
        public function getTargetCountry() {
	
			if (sizeof($this->arCountry) === 1) return array_values($this->arCountry)[0];
        	
			foreach ($this->arCountry as $country_id => $c) {
		
				if ($c['default'] !== true) return $c;
		
			}
	
			throw new \Exception(__('Kein Rechnungslaud für die Berechnung gesetzt.', 'wpsg'));
        	
		}
        
        public function getTargetCountryID() {
        	
        	if (sizeof($this->arCountry) === 1) return array_keys($this->arCountry)[0];
        	
			foreach ($this->arCountry as $country_id => $c) {
		
				if ($c['default'] !== true) return $country_id;
		
			}
			
			throw new \Exception(__('Kein Rechnungslaud für die Berechnung gesetzt.', 'wpsg'));
        	
		}
        
		public function getDefaultCountry() {
			
			foreach ($this->arCountry as $country_id => $c) {
				
				if ($c['default'] === true) return $c;
				
			}
			
			throw new \Exception(__('Kein Standardland für die Berechnung gesetzt.', 'wpsg'));
        	
		}
		
        public function getDefaultCountryID() {
        	
        	foreach ($this->arCountry as $country_id => $c) {
        		
        		if ($c['default'] === true) return $country_id;
        		
			}
        	
			throw new \Exception(__('Kein Standardland für die Berechnung gesetzt.', 'wpsg'));
			
		}
        
        public function removeVoucher($order_voucher_id) {
        	
        	foreach ($this->arCalculationRow as $k => $cr) {
        		
        		if (isset($cr['data']['order_voucher_id']) && $cr['data']['order_voucher_id'] == $order_voucher_id) {
        			
        			unset($this->arCalculationRow[$k]);
        			
				}
        		
			}
	
			$this->arCalculation = null;
        	
		}
	
		/**
		 * @param $set
		 * @param $bruttonetto
		 * @param $tax_key
		 * @param int $amount
		 * @param bool $code
		 * @param bool $id
		 * @param int $order_voucher_id
		 */
		public function addVoucher($set, $bruttonetto, $tax_key, $amount = 1, $code = false, $id = false, $order_voucher_id = 0) {
			
			$tax_key = $this->normalizeTaxKey($tax_key);
			
			if (strpos($set, '-') === false) $set = '-'.$set; 
			
			$this->arCalculationRow['voucher_'.$id.'-'.$order_voucher_id] = [
				'type' => 'voucher',
				'amount' => $amount,
				'set' => $set,
				'tax_key' => $tax_key,
				'bruttonetto' => $bruttonetto,
				'data' => [
					'code' => $code,
					'id' => $id,
					'order_voucher_id' => $order_voucher_id
				]
			];
			
			$this->arCalculation = null;
			
		}
		
		public function addCoupon($set, $bruttonetto, $tax_key, $amount = 1, $code = false, $id = false, $order_voucher_id = 0) {
			
			$tax_key = $this->normalizeTaxKey($tax_key);
			
			if (strpos($set, '-') === false) $set = '-'.$set;
			
			$this->arCalculationRow['coupon_'.$id.'-'.$order_voucher_id] = [
				'type' => 'coupon',
				'amount' => $amount,
				'set' => $set,
				'tax_key' => $tax_key,
				'bruttonetto' => $bruttonetto,				
				'data' => [
					'code' => $code,
					'id' => $id,
					'order_voucher_id' => $order_voucher_id
				]
			];
			
			$this->arCalculation = null;
			
		}
        
        public function removeDiscount() {
	
			foreach ($this->arCalculationRow as $k => $cr) {
		
				if ($cr['type'] === 'discount') {
			
					unset($this->arCalculationRow[$k]);
			
				}
		
			}
	
			$this->arCalculation = null;
            
        }
        
        public function addDiscount($set, $bruttonetto, $tax_key, $amount = 1) {
	 
			$tax_key = $this->normalizeTaxKey($tax_key);
	
			if (strpos($set, '-') === false) $set = '-'.$set;
			
			$this->arCalculationRow['discount'] = [
				'type' => 'discount',
				'amount' => $amount,
				'set' => $set,
				'tax_key' => $tax_key,
				'bruttonetto' => $bruttonetto,
				'data' => [ ]
			];
	
			$this->arCalculation = null;
	                    
        }
        
        public function addProduct($set, $bruttonetto, $tax_key, $amount, $product_key, $product_index = false, $order_product_id = false, $eu = false, $ses_data = false) {
	
			$targetCountry = false; 
			
			if ($eu === true) {
				 
				/*
				if ($this->getTaxMode() === self::TAXMODE_B2B && $this->arCountry[$this->getTargetCountryID()]['tax_mode'] == '2') {
					
					$tax_key = 'e';
					
				} else if ($this->arCountry[$this->getTargetCountryID()]['tax_mode'] == '1') {
					
					$tax_key = 'e';
					
				} else {
					
					$country_id = $this->getTargetCountryID();
					
					$tax_key = $this->normalizeTaxKey($tax_key, $country_id);
					
				}
				*/
				
				if ($this->getTaxMode() === self::TAXMODE_B2C) {
					
					$targetCountry = true;
					$country_id = $this->getTargetCountryID(); 					
					
				} else {
					
					$country_id = $this->getDefaultCountryID();
										
				}
				
				$tax_key = $this->normalizeTaxKey($tax_key, $country_id);
				 
			} else {
				
				$tax_key = $this->normalizeTaxKey($tax_key, $this->getDefaultCountryID());
				
			} 
			
            if ($product_index === false) $product_index = $this->getMaxProductIndex() + 1;
            
            if (wpsg_isSizedInt($order_product_id)) {
            
            	foreach ($this->arCalculationRow as $k => $item) {
            	
            		if ($item['type'] === 'product' && $item['data']['order_product_id'] == $order_product_id) unset($this->arCalculationRow[$k]);
            	
				}
				
			}
            
            $p = [
            	'type' => 'product',
				'amount' => $amount,
				'set' => $set,
				'tax_key' => $tax_key,
				'bruttonetto' => $bruttonetto,
				'data' => [
					'product_id' => $this->shop->getProduktID($product_key),
					'product_key' => $product_key,
					'product_index' => $product_index,
					'order_product_id' => $order_product_id,
					'eu' => $eu,
					'targetCountry' => $targetCountry
				]
			];
	
			$this->shop->callMods('calculation_addProduct',[&$p, $ses_data]);
	
			$this->arCalculationRow[] = $p;
	 
			$this->arCalculation = null;
            
        }
        
        public function removeProduct($order_product_id) {
            
            foreach ($this->arCalculationRow as $k => $cr) {
                
                if ($cr['type'] === 'product' && $cr['data']['order_product_id'] == $order_product_id) unset($this->arCalculationRow[$k]);
                
            }
            
			$this->arCalculation = null;            
            
        }
        
        /**
         * Setzt die Versandkosten
         */
        public function addShipping($set, $bruttonetto, $tax_key, $shipping_key) {
 
            $tax_key = $this->normalizeTaxKey($tax_key);
                                
            $this->arCalculationRow['shipping'] = [
            	'type' => 'shipping',
				'amount' => 1,
            	'set' => $set,
				'bruttonetto' => $bruttonetto,
                'tax_key' => $tax_key,
				'data' => [
					'shipping_key' => $shipping_key
				]
            ];
		
			$this->arCalculation = null;
			
        }

        /**
         * Setzt die Zahlungskosten
         */
        public function addPayment($set, $bruttonetto, $tax_key, $payment_key) {
	
			$tax_key = $this->normalizeTaxKey($tax_key);
 
			$this->arCalculationRow['payment'] = [
				'type' => 'payment',
				'amount' => 1,
				'set' => $set,
				'bruttonetto' => $bruttonetto,
				'tax_key' => $tax_key,
				'data' => [
					'payment_key' => $payment_key
				]
			];
	
			$this->arCalculation = null;
            
        }

        public function getCalculationArray($force_rebuild = false) {

            if ($this->arCountry === null) throw new \Exception(__('Warenkorb kann nicht ohne ein gesetztes Land berechnet werden.', 'wpsg'));
            
            if ($force_rebuild === true || is_null($this->arCalculation)) {

            	$this->arCalculation = [
            		'sum' => [
            			'product_netto' => 0,
						'product_brutto' => 0,
						'product_tax' => 0,
						'productsum_netto' => 0,
						'productsum_brutto' => 0,
						'payment_netto' => 0,
						'payment_brutto' => 0,
						'shipping_netto' => 0,
						'shipping_brutto' => 0,
						'discount_netto' => 0,
						'discount_brutto' => 0,
						'netto' => 0,
						'brutto' => 0,
						'tax' => 0,
						'amount' => 0
					],
					'tax' => [],
					'product' => [],
					'voucher' => [],
					'coupon' => []
            	];
            	
            	$arTypeOrder = ['product', 'voucher', 'shipping', 'payment', 'discount'];
            	            	
            	uasort($this->arCalculationRow, function($a, $b) use ($arTypeOrder) {
            		
            		return strcmp(array_search($a['type'], $arTypeOrder),array_search($b['type'], $arTypeOrder));
            		
				});
            	
            	//wpsg_debug($this->arCalculationRow);
				
                foreach ($this->arCalculationRow as $cr) {
                	             
                	if ($cr['type'] === 'coupon') continue;
                	 
                	$this->addTax($cr['tax_key']);
                	$this->calculateTaxProportionally($cr['bruttonetto']);
							
                	if (!is_array($cr['set'])) $cr['set'] = [$cr['set']];
                	
                	$brutto = 0;
                	$netto = 0;
                	
                	foreach ($cr['set'] as $set) {
		
						$set_brutto = 0;
						$set_brutto = 0;
                								
						if (strpos($set, '|') !== false) {
							
							/*
							list($typ, $set) = explode('-', $set);
							
							$arKosten = explode('|', $set);
							$arKosten = array_reverse($arKosten);
			
							if ($typ === 'w') $value = (($this->shop->getBackendTaxview() === WPSG_NETTO)?$this->arCalculation['sum']['productsum_netto']:$this->arCalculation['sum']['productsum_brutto']);
							else if ($typ === 's') $value = $this->arCalculation['sum']['amount']; // Menge
							else if ($typ === 'g') $value = $this->dWeight; // Gewicht
							else throw new \Exception(wpsg_translate(__('Typ (#1#) für Kostenschlüssel nicht definiert.', 'wpsg'), $typ));
							
							foreach ($arKosten as $k) {
				
								$arP = explode(":", $k);
				
								if (sizeof($arP) == 1) $kosten = $arP[0];
								else if (wpsg_tf($arP[0]) <= $value) $kosten = $arP[1];
				
							}
							
							$kosten = wpsg_tf($kosten);
							*/
				
							$kosten = $this->calculateCostKey($set);
							
							if ($this->shop->getBackendTaxview() === WPSG_NETTO) {
								
								list($set_netto, $set_brutto) = $this->calculateTaxPart(WPSG_NETTO, $kosten, $cr['tax_key']);
								
							} else {
								
								list($set_netto, $set_brutto) = $this->calculateTaxPart(WPSG_BRUTTO, $kosten, $cr['tax_key']);
								
							}
							
						} else if (strpos($set, '%') !== false) {
							
							// Prozentualer Wert
							$set_netto = $this->arCalculation['sum']['netto'] / 100 * wpsg_tf($set);
							$set_brutto = $this->arCalculation['sum']['brutto'] / 100 * wpsg_tf($set);
							
							if ($this->shop->getBackendTaxview() === WPSG_NETTO) {
								
								list($set_netto, $set_brutto) = $this->calculateTaxPart(WPSG_NETTO, $set_netto, $cr['tax_key']);
								
							} else {
								
								list($set_netto, $set_brutto) = $this->calculateTaxPart(WPSG_BRUTTO, $set_brutto, $cr['tax_key']);
								
							}
							
						} else {
							 
							if (preg_match('/^(.+)\-(.*)$/', $set)) {
								
								list($typ, $set) = explode('-', $set);
														
							}
							
							// Netto / Brutto berechnen
							if ($cr['bruttonetto'] === WPSG_NETTO) {
																
								$set_netto = wpsg_tf($set);
								
								// Kleiner 0 prüfen
								//if (($netto + $set_netto) < 0) $set_netto = -1 * $brutto;
								
								list($set_netto, $set_brutto) = $this->calculateTaxPart(WPSG_NETTO, $set_netto, $cr['tax_key']);
													
							} else {
																					
								if (wpsg_isTrue($cr['data']['targetCountry'])) {
								
									$tax_key_part = preg_replace('/\_(.*)/', '', $cr['tax_key']);
									
									$tax_default = $this->arCountry[$this->getDefaultCountryID()][$tax_key_part.'_'.$this->getDefaultCountryID()];
									$tax_target = $this->arCountry[$this->getTargetCountryID()][$tax_key_part.'_'.$this->getTargetCountryID()];
									
									$set_brutto = wpsg_calculatePreis(
										wpsg_calculatePreis(wpsg_tf($set), WPSG_NETTO, $tax_default),
										WPSG_BRUTTO,
										$tax_target
									);
									
								} else {
									
									$set_brutto = wpsg_tf($set);
									
								}
								 								
								//wpsg_Debug($brutto.":".$set_brutto);
								// Kleiner 0 prüfen
								if (($this->arCalculation['sum']['brutto'] + $set_brutto) < 0) $set_brutto = -1 * $this->arCalculation['sum']['brutto'];
																
								list($set_netto, $set_brutto) = $this->calculateTaxPart(WPSG_BRUTTO, $set_brutto, $cr['tax_key']);
								 
								//wpsg_debug('2: '.$set_netto.":".$set_brutto);
							}
							 																									
						}
						
						$netto += $set_netto;
						$brutto += $set_brutto;
														
					}
						 
					$cr['set'] = array_values($cr['set'])[0];
						
					//$country_id = preg_replace('/(.*)\_/', '',$cr['tax_key']);
					$country_id = $this->getTargetCountry()['id'];
	
					if ($this->arCountry[$country_id]['tax_mode'] == '1') {
	
						// Land ist auf "Keine Steuer" gestellt
						$brutto = $netto; 
					
					} else {
																	
						if ($this->tax_mode === self::TAXMODE_SMALLBUSINESS) {
						
							// Kleinunternehmer								
							$brutto = $netto; 
							
						} else if ($this->tax_mode === self::TAXMODE_B2B) {
							
							// Firmenkunde // keine MwSt. bei USt.IdNr. = 2
							if ($this->arCountry[$this->getTargetCountryID()]['tax_mode'] == '2') {
								
								$brutto = $netto;
								
							}
						
						}
						
					}
					
					if ($this->shop->get_option('wpsg_noroundamount') === '1') {
										
                		// Standar, bis 19.11.2019 / 4.1.7
						$netto_single = $netto;
						$brutto_single = $brutto;
					
						$netto *= $cr['amount'];
						$brutto *= $cr['amount'];
						
					} else {
	
						$netto_single = round($netto, 2);
						$brutto_single = round($brutto, 2);
					
						$netto = $netto_single * $cr['amount'];
						$brutto = $brutto_single * $cr['amount'];
						
					}
						
					$this->arCalculation['tax'][$cr['tax_key']]['netto'] += $netto;
					$this->arCalculation['tax'][$cr['tax_key']]['brutto'] += $brutto;
					
					$tax = $brutto - $netto;
					       					
					$this->arCalculation[$cr['type']][] = $cr['data'] + [
						'type' => $cr['type'],
						'netto' => $netto,
						'brutto' => $brutto,
						'netto_single' => $netto_single,
						'brutto_single' => $brutto_single,
						'tax' => $tax,
						'amount' => $cr['amount'],
						'tax_key' => $cr['tax_key'],
						'bruttonetto' => $cr['bruttonetto'],
						'set' => $cr['set']
					];
					
					wpsg_addSet($this->arCalculation['sum'][$cr['type'].'_netto'],$netto);
					wpsg_addSet($this->arCalculation['sum'][$cr['type'].'_brutto'],$brutto);
					wpsg_addSet($this->arCalculation['sum'][$cr['type'].'_tax'],$tax);
					
					if ($this->arCalculation['sum']['netto'] + $netto < 0) {
						
						$netto = -1 * $this->arCalculation['sum']['netto'];
						$brutto = -1 * $this->arCalculation['sum']['brutto'];
						$tax = -1 * $this->arCalculation['sum']['tax'];
						
					}
					
					$this->arCalculation['sum']['netto'] += $netto;
					$this->arCalculation['sum']['brutto'] += $brutto;
					$this->arCalculation['sum']['tax'] += $tax;
						
					if (in_array($cr['type'], ['product', 'voucher'])) {
												
						$this->arCalculation['sum']['productsum_netto'] += $netto;
						$this->arCalculation['sum']['productsum_brutto'] += $brutto;
						
					}
					
					if ($cr['type'] === 'product') {
						
						$this->arCalculation['sum']['amount'] += $cr['amount'];
						
					}
		
				}
				
				$this->arCalculation['sum']['topay_netto'] = $this->arCalculation['sum']['netto'];
				$this->arCalculation['sum']['topay_brutto'] = $this->arCalculation['sum']['brutto'];
					
				foreach ($this->arCalculationRow as $cr) {
                	
					$set = $cr['set'];
					
                	if ($cr['type'] === 'coupon') {
		
						// Netto / Brutto berechnen
						if ($cr['bruttonetto'] === WPSG_NETTO) {
			
							$set_netto = wpsg_tf($set);
			 							
							list($set_netto, $set_brutto) = $this->calculateTaxPart(WPSG_NETTO, $set_netto, $cr['tax_key']);
			
						} else {
			
							if (wpsg_isTrue($cr['data']['targetCountry'])) {
				
								$tax_key_part = preg_replace('/\_(.*)/', '', $cr['tax_key']);
				
								$tax_default = $this->arCountry[$this->getDefaultCountryID()][$tax_key_part.'_'.$this->getDefaultCountryID()];
								$tax_target = $this->arCountry[$this->getTargetCountryID()][$tax_key_part.'_'.$this->getTargetCountryID()];
				
								$set_brutto = wpsg_calculatePreis(
									wpsg_calculatePreis(wpsg_tf($set), WPSG_NETTO, $tax_default),
									WPSG_BRUTTO,
									$tax_target
								);
				
							} else {
				
								$set_brutto = wpsg_tf($set);
				
							}
							
							if (($this->arCalculation['sum']['brutto'] + $set_brutto) < 0) $set_brutto = -1 * $this->arCalculation['sum']['brutto'];
							 			
							list($set_netto, $set_brutto) = $this->calculateTaxPart(WPSG_BRUTTO, $set_brutto, $cr['tax_key'], true);
											
							if ($this->arCalculation['sum']['topay_netto'] + $set_netto < 0) {
								
								$set_netto = $this->arCalculation['sum']['topay_netto'];
								$this->arCalculation['sum']['topay_netto'] = 0;
								
							} else $this->arCalculation['sum']['topay_netto'] += $set_netto;
							
							if ($this->arCalculation['sum']['topay_brutto'] + $set_brutto < 0) {
								
								$set_brutto = $this->arCalculation['sum']['topay_brutto'];
								$this->arCalculation['sum']['topay_brutto'] = 0;
								
							} else $this->arCalculation['sum']['topay_brutto'] += $set_brutto;
							 							
						}
		
						$tax = $set_brutto - $set_netto;
		
						$this->arCalculation[$cr['type']][] = $cr['data'] + [
							'type' => $cr['type'],
							'netto' => $set_netto,
							'brutto' => $set_brutto,
							'netto_single' => $set_netto,
							'brutto_single' => $set_brutto,
							'tax' => $tax,
							'amount' => 1,
							'tax_key' => $cr['tax_key'],
							'bruttonetto' => $cr['bruttonetto'],
							'set' => $cr['set']
						];
						
					}
                	
				}
	 
				foreach ($this->arCalculation['tax'] as $tax_key => $tax) {
		
					$this->arCalculation['tax'][$tax_key]['sum'] = $tax['brutto'] - $tax['netto'];
		
				}
                 				
				//die(wpsg_debug($this->arCalculation));
                
            }
             
            return $this->arCalculation;

        }
         
        /**
         * Speichert die Berechnung in eine Bestellung
         * 
         * @param bool $id
         */
        public function toDB($id = false, $db_data = [], $finish_order = false) {
	
			$id = $this->db->fetchOne("SELECT `id` FROM `".WPSG_TBL_ORDER."` WHERE `id` = '".wpsg_q($id)."' ");
        	if (!wpsg_isSizedInt($id)) $id = false;
        	
            $arCalculation = $this->getCalculationArray();
             
            $db_data['price_gesamt_netto'] = wpsg_q($arCalculation['sum']['netto']);
            $db_data['price_gesamt_brutto'] = wpsg_q($arCalculation['sum']['brutto']);
	
			$db_data['topay_netto'] = wpsg_q($arCalculation['sum']['topay_netto']);
			$db_data['topay_brutto'] = wpsg_q($arCalculation['sum']['topay_brutto']);
                        
            if ($this->shop->getFrontendTaxview() === WPSG_NETTO) $display = 'netto';
            else  $display = 'brutto';
	
			$db_data['price_gesamt'] = wpsg_q($arCalculation['sum'][$display]);
			$db_data['topay'] = wpsg_q($arCalculation['sum']['topay_'.$display]);
	
			// Shipping leeren
			$db_data['shipping_set'] = '';
			$db_data['shipping_key'] = '';
			$db_data['shipping_bruttonetto'] = '';
			$db_data['shipping_tax_key'] = '';
			$db_data['type_shipping'] = '';
			$db_data['price_shipping'] = '';
			$db_data['price_shipping_netto'] = '';
			$db_data['price_shipping_brutto'] = '';
			$db_data['mwst_shipping'] = '';
	
			// Payment leeren
			$db_data['payment_set'] = '';
			$db_data['payment_key'] = '';
			$db_data['payment_bruttonetto'] = '';
			$db_data['payment_tax_key'] = '';
			$db_data['type_payment'] = '';
			$db_data['price_payment'] = '';
			$db_data['price_payment_netto'] = '';
			$db_data['price_payment_brutto'] = '';
			$db_data['mwst_payment'] = '';
		
			// Gutschein leeren
			$db_data['voucher_tax_key'] = '';
			$db_data['price_gs'] = '';
			$db_data['price_gs_netto'] = '';
			$db_data['price_gs_brutto'] = '';
			$db_data['gs_set'] = '';
			$db_data['gs_tax_key'] = '';
			$db_data['voucher_bruttonetto'] = '';
			$db_data['gs_id'] = '';
			$db_data['gs_code'] = '';
			
			// Rabatt leeren
			$db_data['discount_set'] = '';
			$db_data['discount_bruttonetto'] = '';
			$db_data['discount_tax_key'] = '';
			$db_data['price_rabatt'] = '';
			$db_data['price_rabatt_netto'] = '';
			$db_data['price_rabatt_brutto'] = '';
			
			$db_data['calculation'] = '1';
			$db_data['tax_mode'] = $this->getTaxMode();
			
            foreach ($this->arCalculationRow as $cr) {
            	
            	if ($cr['type'] === 'discount') {
		
					$db_data['discount_set'] = wpsg_q($cr['set']);
					$db_data['discount_bruttonetto'] = wpsg_q($cr['bruttonetto']);
					$db_data['discount_tax_key'] = wpsg_q($this->clearTaxKey($cr['tax_key']));
					$db_data['price_rabatt'] = wpsg_q($arCalculation['sum']['discount'.$display]);
					$db_data['price_rabatt_netto'] = wpsg_q($arCalculation['sum']['discount_netto']);
					$db_data['price_rabatt_brutto'] = wpsg_q($arCalculation['sum']['discount_brutto']);
					            		
				} else if ($cr['type'] === 'shipping') {
            		
            		$db_data['shipping_set'] = wpsg_q($cr['set']);
					$db_data['shipping_key'] = wpsg_q($cr['data']['shipping_key']);
					$db_data['shipping_bruttonetto'] = wpsg_q($cr['bruttonetto']);
					$db_data['shipping_tax_key'] = wpsg_q($this->clearTaxKey($cr['tax_key']));
					$db_data['type_shipping'] = wpsg_q($cr['data']['shipping_key']);
					$db_data['price_shipping'] = wpsg_q($arCalculation['sum']['shipping_'.$display]);
					$db_data['price_shipping_netto'] = wpsg_q($arCalculation['sum']['shipping_netto']);
					$db_data['price_shipping_brutto'] = wpsg_q($arCalculation['sum']['shipping_brutto']);
					$db_data['mwst_shipping'] = wpsg_q($arCalculation['sum']['shipping_brutto'] - $arCalculation['sum']['shipping_netto']);
            		
				} else if ($cr['type'] === 'payment') {
		
					$db_data['payment_set'] = wpsg_q($cr['set']);
					$db_data['payment_key'] = wpsg_q($cr['data']['payment_key']);
					$db_data['payment_bruttonetto'] = wpsg_q($cr['bruttonetto']);
					$db_data['payment_tax_key'] = wpsg_q($this->clearTaxKey($cr['tax_key']));
					$db_data['type_payment'] = wpsg_q($cr['data']['payment_key']);
					$db_data['price_payment'] = wpsg_q($arCalculation['sum']['payment_'.$display]);
					$db_data['price_payment_netto'] = wpsg_q($arCalculation['sum']['payment_netto']);
					$db_data['price_payment_brutto'] = wpsg_q($arCalculation['sum']['payment_brutto']);
					$db_data['mwst_payment'] = wpsg_q($arCalculation['sum']['payment_brutto'] - $arCalculation['sum']['payment_netto']);
		 					
				}
				             	
			}
            
			$db_data['be_bruttonetto'] = wpsg_q($this->shop->getBackendTaxview());
            $db_data['fe_bruttonetto'] = wpsg_q($this->shop->getFrontendTaxview());
	
            $oDefaultCountry = $this->shop->getDefaultCountry();
            
			$db_data['shop_country_id'] = wpsg_q($oDefaultCountry->id);
			$db_data['shop_country_tax'] = wpsg_q($oDefaultCountry->mwst);
			$db_data['shop_country_tax_a'] = wpsg_q(wpsg_tf($oDefaultCountry->mwst_a));
			$db_data['shop_country_tax_b'] = wpsg_q(wpsg_tf($oDefaultCountry->mwst_b));
			$db_data['shop_country_tax_c'] = wpsg_q(wpsg_tf($oDefaultCountry->mwst_c));
			$db_data['shop_country_tax_d'] = wpsg_q(wpsg_tf($oDefaultCountry->mwst_d));
				
			$oTargetCountry = \wpsg_country::getInstance($this->getTargetCountryID());
			
			$db_data['target_country_id'] = wpsg_q($oTargetCountry->id);
			$db_data['target_country_tax'] = wpsg_q($oTargetCountry->mwst);
			$db_data['target_country_tax_a'] = wpsg_q(wpsg_tf($oTargetCountry->mwst_a));
			$db_data['target_country_tax_b'] = wpsg_q(wpsg_tf($oTargetCountry->mwst_b));
			$db_data['target_country_tax_c'] = wpsg_q(wpsg_tf($oTargetCountry->mwst_c));
			$db_data['target_country_tax_d'] = wpsg_q(wpsg_tf($oTargetCountry->mwst_d));
			
			// Die Hooks verwenden Daten aus $_SESSION, deshalb nur beim Speichern im Frontend
			if (!is_admin()) {
			
				$this->shop->callMods('calculation_saveOrder', [&$this, $arCalculation, &$db_data, $finish_order]);
				
			}
			
			if ($finish_order) {
				
				$db_data['cdate'] = 'NOW()';
				
			}
			
            if (wpsg_isSizedInt($id)) {
                
                $this->db->UpdateQuery(WPSG_TBL_ORDER, $db_data, " `id` = '".wpsg_q($id)."' ");
                
            }  else {
                 
                $id = $this->db->ImportQuery(WPSG_TBL_ORDER, $db_data);
                
                // TODO: Land zuweisen und speichern
                
            }
	                         
            $arOrderProductID = [-1];
            
			if ($this->shop->hasMod('wpsg_mod_gutschein')) {
			
				// Gutscheine speichern		 
				$this->db->Query("DELETE FROM `".WPSG_TBL_ORDER_VOUCHER."` WHERE `order_id` = '".wpsg_q($id)."' ");
				
				foreach ($arCalculation['voucher'] as $v) {
				
					$this->db->ImportQuery(WPSG_TBL_ORDER_VOUCHER, [
						'create_time' => "NOW()",
						'order_id' => wpsg_q($id),
						'voucher_id' => wpsg_q($v['id']),
						'set_value' => wpsg_q($v['set']), 
						'sum_netto' => wpsg_q($v['netto']),
						'sum_brutto' => wpsg_q($v['brutto']),
						'tax_key' => wpsg_q($v['tax_key']),
						'bruttonetto' => wpsg_q($v['bruttonetto']),
						'code' => wpsg_q($v['code']),
						'coupon' => '0'
					]);
	
				}
				
				foreach ($arCalculation['coupon'] as $c) {
					
					$this->db->ImportQuery(WPSG_TBL_ORDER_VOUCHER, [
						'create_time' => "NOW()",
						'order_id' => wpsg_q($id),
						'voucher_id' => wpsg_q($c['id']),
						'set_value' => wpsg_q($c['set']),
						'sum_netto' => wpsg_q($c['netto']),
						'sum_brutto' => wpsg_q($c['brutto']),
						'tax_key' => wpsg_q($c['tax_key']),
						'bruttonetto' => wpsg_q($c['bruttonetto']),
						'code' => wpsg_q($c['code']),
						'coupon' => '1'
					]);
					
				}
				
			}
						
            // Produkte speichern
			foreach ($arCalculation['product'] as $p) {
                 
				$db_data = [
					'o_id' => wpsg_q($id),
					'p_id' => wpsg_q($p['product_id']),										
					'productkey' => wpsg_q($p['product_key']),
					'product_index' => wpsg_q($p['product_index']),
					'menge' => wpsg_q($p['amount']),
					'price_netto' => wpsg_q($p['netto_single']),
					'price_brutto' => wpsg_q($p['brutto_single']),
					'price' => wpsg_q($p[$display]),
					'mwst_value' => wpsg_q($p['brutto_single'] - $p['netto_single']),
					'mwst_key' => wpsg_q($p['tax_key']),
					'product_set' => wpsg_q($p['set']),
					'product_bruttonetto' => wpsg_q($p['bruttonetto']),
					'euleistungsortregel' => wpsg_q($p['eu'])
				];
				
				if (!wpsg_isSizedInt($p['order_product_id']) && $p['product_index'] !== false) {
					
					$p['order_product_id'] = $this->db->fetchOne("SELECT `id` FROM `".WPSG_TBL_ORDERPRODUCT."` WHERE `o_id` = '".wpsg_q($id)."' AND `p_id` = '".wpsg_q($p['product_id'])."' AND `productkey` = '".wpsg_q($p['product_key'])."' AND `product_index` = '".wpsg_q($p['product_index'])."' ");
					
				}
				
				// Die Hooks verwenden Daten aus $_SESSION, deshalb nur beim Speichern im Frontend
				if (!is_admin()) {
				
					$this->shop->callMods('calculation_saveProduct', [&$this, $p, &$db_data, $finish_order]);
					
				}
				
				if (wpsg_isSizedInt($p['order_product_id'])) {
					
					$arOrderProductID[] = $p['order_product_id'];
			
					$this->db->UpdateQuery(WPSG_TBL_ORDERPRODUCT, $db_data, " `id` = '".wpsg_q($p['order_product_id'])."' ");
					
				} else {
			
					$arOrderProductID[] = $this->db->ImportQuery(WPSG_TBL_ORDERPRODUCT, $db_data);
					
				}
			
				
            }
             
            $this->db->Query("DELETE FROM `".WPSG_TBL_ORDERPRODUCT."` WHERE `o_id` = '".wpsg_q($id)."' AND `id` NOT IN (".wpsg_q(implode(',', $arOrderProductID)).") ");
            	
			$this->arCalculation = null;
                
            return $id;
            
        } // public function toDB($id = false)
	
		/**
		 * Lädt eine alte Bestellung, die mit der alten wpsg_basket Klasse berechnet wurde
		 * 
		 * @param $db_order
		 * @param $db_products
		 */
		public function fromDBFallback($db_order, $db_products) {
									
			// Länder
			$oDefaultCountry = $this->shop->getDefaultCountry();
			
			$this->addCountry($oDefaultCountry->id, $oDefaultCountry->mwst, $oDefaultCountry->mwst_a,$oDefaultCountry->mwst_b, $oDefaultCountry->mwst_c, $oDefaultCountry->mwst_d, true);
			
			if (wpsg_isSizedInt($db_order['adress_id'])) {
				
				$country_id = $this->db->fetchOne("
					SELECT
						A.`land`
					FROM
						`".WPSG_TBL_ADRESS."` AS A
					WHERE
						A.`id` = '".wpsg_q($db_order['adress_id'])."'
				");
				
				$oInvoiceCountry = \wpsg_country::getInstance($country_id);
				 
				if (!$oInvoiceCountry->isLoaded()) throw new \Exception(wpsg_translate(__('Land mit der ID #1# existiert nicht mehr.', 'wpsg'), $country_id));
				
				$this->addCountry($oInvoiceCountry->id, $oInvoiceCountry->mwst, $oInvoiceCountry->mwst_a,$oInvoiceCountry->mwst_b, $oInvoiceCountry->mwst_c, $oInvoiceCountry->mwst_d, false);
				 
			} else {
				
				$oInvoiceCountry = $oDefaultCountry;
				
			}
		 
			if (isset($this->shop->arShipping[$db_order['type_shipping']])) {
				
				$shipping = $this->shop->arShipping[$db_order['type_shipping']];
					
				$this->addShipping($shipping['price'],$this->shop->getBackendTaxview(),$shipping['mwst_key'],$db_order['type_shipping']);
				
			} else {
				
				$this->addShipping($db_order['shipping_set'],$db_order['shipping_bruttonetto'],$db_order['shipping_tax_key'],$db_order['type_shipping']);
				
			}
			
			if (isset($this->shop->arPayment[$db_order['type_payment']])) {
				
				$payment = $this->shop->arPayment[$db_order['type_payment']];
		 
				$this->addPayment($payment['price'],$this->shop->getBackendTaxview(),$payment['mwst'],$db_order['type_payment']);
				
			} else {
				
				$this->addPayment($db_order['payment_set'],$db_order['payment_bruttonetto'],$db_order['payment_tax_key'],$db_order['type_payment']);
				
			}
			
			// Produkte
			foreach ($db_products as $db_p) {
				
				if (!wpsg_isSizedString($db_p['productkey'])) $db_p['productkey'] = $db_p['p_id'];
				
				$product_data = $this->db->fetchRow("SELECT `euleistungsortregel` FROM `".WPSG_TBL_PRODUCTS."` WHERE `id` = '".wpsg_q($db_p['p_id'])."' ");
				
				if ($this->shop->getBackendTaxview() === WPSG_BRUTTO) $product_set = $db_p['price_brutto'];
				else $product_set = $db_p['price_netto'];
				
				$tax_key = $db_p['mwst_key'];
				
				// c_1
				if ($product_data['euleistungsortregel'] === '1') $eu = true;
				$eu = false;
							
				$this->addProduct($product_set, $this->shop->getBackendTaxview(), $tax_key, $db_p['menge'],$db_p['productkey'], $db_p['product_index'], $db_p['id'], $eu);
				
			}
			
			// Rabatte
			if (wpsg_isSizedString($db_order['discount_set'])) {
				
				$this->addDiscount($db_order['discount_set'], $db_order['discount_bruttonetto'], $db_order['discount_tax_key']);
				
			}
			
			// Gutschein
			if (wpsg_isSizedString($db_order['gs_set'])) {
				
				$this->addVoucher($db_order['gs_set'], $db_order['voucher_bruttonetto'], $db_order['gs_tax_key'], 1, $db_order['gs_code'],$db_order['gs_id']);
				
			}
			
			$this->restored = true;
			
		} // public function fromDBFallback($db_order, $db_products) {
		
        public function fromDB($id) {
            
			if (!wpsg_isSizedInt($id)) return;
			
            $db_order = $this->db->fetchRow("SELECT O.*, A.`land` FROM `".WPSG_TBL_ORDER."` AS O LEFT JOIN `".WPSG_TBL_ADRESS."` AS A ON (O.`adress_id` = A.`id`) WHERE O.`id` = '".wpsg_q($id)."' ");
            $db_products = $this->db->fetchAssoc("SELECT * FROM `".WPSG_TBL_ORDERPRODUCT."` WHERE `o_id` = '".wpsg_q($id)."' ");
            
            if (!wpsg_isSizedInt($db_order['calculation'])) {
            	
            	return $this->fromDBFallback($db_order, $db_products);
            	
			}
	
			if ($this->shop->hasMod('wpsg_mod_weight')) $this->dWeight = wpsg_tf($db_order['weight']);
            
			$this->setTaxMode($db_order['tax_mode']);
 
            $this->addCountry($db_order['shop_country_id'], $db_order['shop_country_tax'], $db_order['shop_country_tax_a'],$db_order['shop_country_tax_b'], $db_order['shop_country_tax_c'], $db_order['shop_country_tax_d'], true);
			$this->addCountry($db_order['target_country_id'], $db_order['target_country_tax'], $db_order['target_country_tax_a'],$db_order['target_country_tax_b'], $db_order['target_country_tax_c'], $db_order['target_country_tax_d'], false);
			
			// Versand- und Zahlungsart
            $this->addShipping($db_order['shipping_set'],$db_order['shipping_bruttonetto'],$db_order['shipping_tax_key'],$db_order['shipping_key']);
            $this->addPayment($db_order['payment_set'], $db_order['payment_bruttonetto'], $db_order['payment_tax_key'],$db_order['payment_key']);
            
            // Produkte
            foreach ($db_products as $db_p) {
            	
                $this->addProduct($db_p['product_set'], $db_p['product_bruttonetto'], $db_p['mwst_key'], $db_p['menge'],$db_p['productkey'], $db_p['product_index'], $db_p['id'], (($db_p['euleistungsortregel'] === '1')?true:false));
                
            }
            
            // Rabatte
            if (wpsg_isSizedString($db_order['discount_set'])) {
                
                $this->addDiscount($db_order['discount_set'], $db_order['discount_bruttonetto'], $db_order['discount_tax_key']);
                
            }
            
            // Gutschein
			if (wpsg_isSizedString($db_order['gs_set'])) {
            	
            	$this->addVoucher($db_order['gs_set'], $db_order['voucher_bruttonetto'], $db_order['gs_tax_key'], 1, $db_order['gs_code'],$db_order['gs_id']);
            	
			}            
            			
			if ($this->shop->hasMod('wpsg_mod_gutschein')) {
            	
            	$arVoucher = $this->db->fetchAssoc("SELECT * FROM `".WPSG_TBL_ORDER_VOUCHER."` WHERE `order_id` = '".wpsg_q($id)."' ORDER BY `id` ASC ");
            	 
            	foreach ($arVoucher as $v) {
						
            		if ($v['coupon'] === '1') {
            			
            			$this->addCoupon($v['set_value'], $v['bruttonetto'], $v['tax_key'], 1, $v['code'], $v['voucher_id'], $v['id']);
            			
					} else {
            			
            			$this->addVoucher($v['set_value'], $v['bruttonetto'], $v['tax_key'], 1, $v['code'], $v['voucher_id'], $v['id']);
            			
					}
            		
				}
            	
			}
			
        } // public function fromDB($id)
        
		public function fromSession() {
			
			$ses = (isset($_SESSION['wpsg'])?$_SESSION['wpsg']:[]);
			 
			if ($this->shop->hasMod('wpsg_mod_weight')) $this->dWeight = wpsg_tf($this->shop->callMod('wpsg_mod_weight', 'getSessionBasketWeight'));
			
			$oDefaultCountry = $this->shop->getDefaultCountry();			
			$this->addCountry($oDefaultCountry->id, $oDefaultCountry->mwst, $oDefaultCountry->mwst_a, $oDefaultCountry->mwst_b, $oDefaultCountry->mwst_c, $oDefaultCountry->mwst_d,true);
			
			if ($ses['checkout']['land'] != $oDefaultCountry->id) {
				
				$oInvoiceCountry = \wpsg_country::getInstance($ses['checkout']['land']);
				$this->addCountry($oInvoiceCountry->id, $oInvoiceCountry->mwst, $oInvoiceCountry->mwst_a, $oInvoiceCountry->mwst_b, $oInvoiceCountry->mwst_c, $oInvoiceCountry->mwst_d,false);
				
			}
			
			if (wpsg_isSizedArray($ses['basket'])) {
				
				foreach ($ses['basket'] as $product_index => $p) {
					 
					$oProduct = \wpsg_product::getInstance($p['id']);
										
					$this->addProduct(
						$oProduct->getPrice($p['id'], $this->shop->getBackendTaxview()), 
						$this->shop->getBackendTaxview(), 
						$oProduct->mwst_key, 
						$p['menge'], 
						$p['id'], 
						$product_index, 
						false, 
						(($oProduct->euleistungsortregel === '1')?true:false),
						$p
					);
										
				}
												
			}
			
			$this->shop->callMods('calculation_fromSession',[&$this, true, false]);
			 
			// Payment
			if (isset($_SESSION['wpsg']['checkout']['payment']) && array_key_exists($_SESSION['wpsg']['checkout']['payment'], $this->shop->arPayment)) {
				
				$payment = $this->shop->arPayment[$_SESSION['wpsg']['checkout']['payment']];
												
				if (wpsg_isSizedArray($payment)) $this->addPayment($payment['price'], $this->shop->getBackendTaxview(), @$payment['tax_key'], $_SESSION['wpsg']['checkout']['payment']);
				
			}
			
			// Shipping 
			if (isset($_SESSION['wpsg']['checkout']['shipping']) && array_key_exists($_SESSION['wpsg']['checkout']['shipping'], $this->shop->arShipping)) {
				
				$shipping = $this->shop->arShipping[$_SESSION['wpsg']['checkout']['shipping']];
				
				if (wpsg_isSizedArray($shipping['sub'])) {
					 
					$price = [];
					
					foreach ($shipping['sub'] as $sub_shipping) {
						
						$price[] = $sub_shipping['price'];
						
					}
					
				} else $price = $shipping['price'];
				
				if (wpsg_isSizedArray($shipping)) $this->addShipping($price, $this->shop->getBackendTaxview(), $shipping['tax_key'], $_SESSION['wpsg']['checkout']['shipping']);
				
			}
				
			$this->shop->callMods('calculation_fromSession',[&$this, true, true]);			
			 
			// Besteuerung
			if ($this->shop->get_option('wpsg_kleinunternehmer') === '1') {
				
				$this->setTaxMode(self::TAXMODE_SMALLBUSINESS);
				
			} else if ($this->getTargetCountry()['tax_mode'] === '2' && wpsg_isSizedString($_SESSION['wpsg']['checkout']['ustidnr'])) {
				
				$this->setTaxMode(self::TAXMODE_B2B);
				
			} else {
				
				$this->setTaxMode(self::TAXMODE_B2C);
				
			}
			
		}
		
        public function getMaxProductIndex() {
            
            $max_index = 0;
            
            foreach ($this->arCalculationRow as $cr) {
                
            	if ($cr['type'] === 'product') $max_index = max($max_index, $cr['data']['product_index']);
                
            }
            
            return $max_index;
            
        }
	
		/**
		 * Berechnet einen Kostenschlüssel
		 * benötigt eine geladene Kalkulation
		 * @param $set
		 * @return float|string
		 * @throws \Exception
		 */
        public function calculateCostKey($set) {
	
			list($typ, $set) = explode('-', $set);
	
			$arKosten = explode('|', $set);
			$arKosten = array_reverse($arKosten);
	
			if ($typ === 'w') $value = (($this->shop->getBackendTaxview() === WPSG_NETTO)?$this->arCalculation['sum']['productsum_netto']:$this->arCalculation['sum']['productsum_brutto']);
			else if ($typ === 's') $value = $this->arCalculation['sum']['amount']; // Menge
			else if ($typ === 'g') $value = $this->dWeight; // Gewicht
			else throw new \Exception(wpsg_translate(__('Typ (#1#) für Kostenschlüssel nicht definiert.', 'wpsg'), $typ));
	
			foreach ($arKosten as $k) {
		
				$arP = explode(":", $k);
		
				if (sizeof($arP) == 1) $kosten = $arP[0];
				else if (wpsg_tf($arP[0]) <= $value) $kosten = $arP[1];
		
			}
	
			$kosten = wpsg_tf($kosten);
        	
        	return $kosten;
			
		}
        
        /* Private */
		
		/**
		 * Ermittelt die Verteilung der Steuersätze
		 */
		private function calculateTaxProportionally($bruttonetto) {
			
			if ($bruttonetto === WPSG_NETTO) $bruttonetto = 'netto';
			else $bruttonetto = 'brutto';
	 
			$sum = $this->arCalculation['sum'][$bruttonetto]; 
			
			// Den Anteiligen Netto Betrag beachte ich nicht bei der Anteilermittlung
			//$sum -= $this->arCalculation['tax'][0][$bruttonetto]; 
			
			foreach ($this->arCalculation['tax'] as $tax_key => $tax) {
				
				if ($tax_key == '0') {
					
					$this->arCalculation['tax'][$tax_key]['part'] = 0;
					
				} else {
					
					if ($sum <= 0) $this->arCalculation['tax'][$tax_key]['part'] = 0;
					else $this->arCalculation['tax'][$tax_key]['part'] = $tax[$bruttonetto] / $sum;
					
				}
				
			}
			
		}
		
		/**
		 * Berechnet den Netto Wert zu einem Bruttowert
		 * 
		 * @param $brutto
		 * @param $tax_key
		 */
        private function calculateTaxPart($bruttonetto, $val, $tax_key, $noTax = false) {
	        	
        	if (!is_numeric($val)) $val = 0;
        	
        	if ($tax_key == '0') { // 0, bedeutet hier anteilig
		
				$ret_netto = 0;
				$ret_brutto = 0;
				
				foreach ($this->arCalculation['tax'] as $tax_key2 => $tax) {
			
					if ($tax_key2 != '0') {
				 
						if ($bruttonetto === WPSG_NETTO) {
							
							$netto = $val * $tax['part'];
							$brutto =  $netto * (1 + $this->arCalculation['tax'][$tax_key2]['tax_value'] / 100);
														
						} else {
							
							$brutto = $val * $tax['part'];
							$netto = $brutto / (1 + $this->arCalculation['tax'][$tax_key2]['tax_value'] / 100);
							
						}
						
						//wpsg_debug($sum_netto.'('.$sum_brutto.')'.":".$val);
						
						//if ($sum_netto + $netto < 0) $netto = -1 * $sum_netto;
						//if ($sum_brutto + $brutto < 0) $brutto = -1 * $sum_brutto;
						 
						$ret_netto += $netto;
						$ret_brutto += $brutto;
						
						if ($tax_key !== $tax_key2 && !$noTax) {
						
							$this->arCalculation['tax'][$tax_key2]['netto'] += $netto;
							$this->arCalculation['tax'][$tax_key2]['brutto'] += $brutto;
							
						}
												
					}
			
				}
									
				return [$ret_netto, $ret_brutto];
		
			} else {
				
				if ($bruttonetto === WPSG_NETTO) {
					
					return [$val, $val * (1 + $this->arCalculation['tax'][$tax_key]['tax_value'] / 100)];
					
				}
				else {
					
					return [$val / (1 + $this->arCalculation['tax'][$tax_key]['tax_value'] / 100), $val];
					
				}
		
			}
			
		} 

        /**
         * Im Array sollen die Tax Schlüssel immer mit Land gespeichert sein
		 * Erzwingt auch 0% Mwst
         */
        private function normalizeTaxKey($tax_key, $country_id = false, $defaultCountryID = true) {
	
            if ($tax_key == '') $tax_key = '0';
            
            if ($tax_key != '0' && !preg_match('/\_\d+$/', $tax_key)) {
            	
            	if ($country_id === false) {
            		
            		if ($defaultCountryID === true) $country_id = $this->getDefaultCountryID();
            		else $country_id = $this->getTargetCountryID();
            		
				}
            	
            	return $tax_key.'_'.$country_id;
            					
			} else return $tax_key;
            
        }
	
		/**
		 * Entfernt den Länderschlüssel aus dem Steuerschlüssel
		 * @param $tax_key
		 */
        private function clearTaxKey($tax_key) {
        	
        	if (!preg_match('/\_\d+$/', $tax_key)) return $tax_key;
        	
        	return explode('_', $tax_key)[0];
        	
		}
	
		/**
		 * Gibt einen Array zurück, in dem die beteiligten Steuersätze mit Länderkürzel definiert sind
		 */
		public function getTaxLabelArray($short = false) {
		
			$arTaxLabel = [];
			$arTaxGgroup = wpsg_tax_groups(false, true);
			$arCalculation = $this->getCalculationArray();
			
			$country_default_id = false;
			
			foreach ($this->arCountry as $country_id => $country) {
				
				if ($country['default'] === true) $country_default_id = $country_id;
				
			}
			
			if (!wpsg_isSizedInt($country_default_id)) throw new \Exception(__('Standardland konnte in Berechnung nicht ermittelt werden.'));
			
			foreach ($arCalculation['tax'] as $tax) {
			
				if (preg_match('/_\d+/', $tax['key'])) {
				
					$arTaxKey = explode('_', $tax['key']);
					$tax_key = $arTaxKey[0];
				
					$country_id = $arTaxKey[1];
									
				} else {
				
					$tax_key = $tax['key'];
					
					$country_id = $country_default_id;
				
				}
								
				$oCountry = \wpsg_country::getInstance($country_id);
			
				$tax_value = (($tax['key'] === '0')?0:$this->arCountry[$country_id][$tax['key']]);
				
				if ($oCountry->isLoaded()) {
									
					$kuerzel = $oCountry->kuerzel;
										
				} else {
					
					$kuerzel = '';
					
				}
				
				if ($short === true) {
					
					if (!in_array($tax_key, ['0', 'e'])) $arTaxLabel[$tax['key']] = wpsg_ff(wpsg_tf($tax_value), '%');
					else if ($tax_key === 'e') $arTaxLabel[$tax_key] = '0';
					else $arTaxLabel[$tax['key']] = 'anteilig';
					
				} else {
					
					if (!in_array($tax_key, ['0', 'e'])) $arTaxLabel[$tax['key']] = $arTaxGgroup[$tax_key].' ('.wpsg_ff(wpsg_tf($tax_value), '%').' / '.$kuerzel.')';
					else $arTaxLabel[$tax['key']] = $arTaxGgroup[$tax_key];
					
				}
				
			}
			
			return $arTaxLabel;
		
		}

		public function getSum($bruttonetto = WPSG_NETTO) {
			
			$arCalculation = $this->getCalculationArray();
			
			if ($bruttonetto === WPSG_BRUTTO) return $arCalculation['sum']['brutto'];
			else return $arCalculation['sum']['netto'];
			
		}
		
		private function addTax($tax_key) {
	
			if (!isset($this->arCalculation['tax'][$tax_key])) {
				
				$arTaxKey = explode('_', $tax_key);
								
				$this->arCalculation['tax'][$tax_key] = [
					'key' => $tax_key,
					'tax_value' => (($tax_key !== '0')?$this->arCountry[$arTaxKey[1]][$tax_key]:0),
					'netto' => 0,
					'brutto' => 0
				];
				
			} 
            
        }
                
        /* Static */
	
		/**
		 * return \wpsg\wpsg_calculation
		 */
		public static function getSessionCalculation($no_cache = false) {
			
			$cache_key = __FUNCTION__.implode('|', \func_get_args());
			
			if (!isset(self::$functionscache[$cache_key]) || $no_cache) {
			
				$oCalculation = new wpsg_calculation();
				$oCalculation->fromSession();
				
				self::$functionscache[$cache_key] = $oCalculation;
				
			}
			
			return self::$functionscache[$cache_key];
				
		}
	        
    }
         
         