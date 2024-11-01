<?php

    /**
     * Integriert die Anmeldung für das Satollo Newsletter Plugin in den Shop
     * @author daniel
     */
    class wpsg_mod_nlsatolo extends wpsg_mod_basic {
    
        var $lizenz = 1;
        var $id = 600;
    
        /**
         * Costructor
         */
        public function __construct() {
    
            parent::__construct();
    
            $this->name = __('Satollo Newsletter', 'wpsg');
            $this->group = __('Sonstiges', 'wpsg');
            $this->desc = __('Ermöglicht die Anmeldung an das "<a href="http://wordpress.org/extend/plugins/newsletter/">Newsletter</a>" Plugin von Satollo.', 'wpsg');
    
        } // public function __construct()
    
        public function install() {
    
            $this->shop->checkDefault('wpsg_mod_nlsatolo_action', '0');
    
        } // public function install()		
    
        public function settings_edit() {
    
            $this->shop->view['plugin_active'] = $this->checkNewsletterPlugin();
    
            if ($this->shop->view['plugin_active'] === true) {
    
                $options_profile = NewsletterSubscription::instance()->options_lists;
    
                $arLists = array();
    
                for ($i = 1; $i <= NEWSLETTER_LIST_MAX; $i++) {
    
                    $arLists['' . $i] = '(' . $i . ') ' . $options_profile['list_' . $i];
    
                }
    
                $this->shop->view['arLists'] = $arLists;
    
            }
    
            $this->render(WPSG_PATH_VIEW.'/mods/mod_nlsatolo/settings_edit.phtml');
    
        } // public function settings_edit()
    
        public function settings_save() {
    
            $this->shop->update_option('wpsg_mod_nlsatolo_doubleoptin', $_REQUEST['wpsg_mod_nlsatolo_doubleoptin'], false, false, WPSG_SANITIZE_INT);
            $this->shop->update_option('wpsg_mod_nlsatolo_group', $_REQUEST['wpsg_mod_nlsatolo_group'], false, false, WPSG_SANITIZE_INT);
    
        } // public function settings_save()
    
        public function basket_save_done(&$o_id, &$k_id, &$oBasket) {
    
            global $newsletter;
    
            $arBasket = $oBasket->toArray();
    
            if ($arBasket['checkout']['wpsg_mod_nlsatolo'] == '1') {
    
                if (function_exists("newsletter_subscribe")) {
    
                    // Version <= 1.5.9
                    newsletter_subscribe($arBasket['checkout']['email'], $arBasket['checkout']['vname'].' '.$arBasket['checkout']['name']);
                    $this->shop->addFrontendMessage(__("Sie wurden erfolgreich in unseren Newsletter eingetragen.", "wpsg"));
    
                } else if (get_class($newsletter) == "Newsletter") {
    
                    global $newsletter;
    
                    $user = new stdClass();
    
                    $user->email = $arBasket['checkout']['email'];
                    $user->name = $arBasket['checkout']['vname'];
                    $user->surname = $arBasket['checkout']['name'];
    
                    $newsletterSubscription = NewsletterSubscription::instance();
                    $newsletterSubscription->hook_init();
    
                    if ($this->shop->get_option('wpsg_mod_nlsatolo_doubleoptin') === '1') {
    
                        $user->status = 'S';
                        $newsletter->save_user($user);
    
                        $user = $newsletterSubscription->get_user($user->email);
    
                        $newsletterSubscription->send_message('confirmation', $user);
                        $newsletterSubscription->set_user_list($user, $this->shop->get_option('wpsg_mod_nlsatolo_group'), '1');
    
                        $this->shop->addFrontendMessage(__("Sie wurden erfolgreich in unseren Newsletter eingetragen, müssen diese aber noch bestätigen. Sie haben dazu eine E-Mail erhalten.", "wpsg"));
    
                    } else {
    
                        $user->status = 'C';
                        $newsletter->save_user($user);
    
                        $user = $newsletterSubscription->get_user($user->email);
    
                        $newsletterSubscription->set_user_list($user, $this->shop->get_option('wpsg_mod_nlsatolo_group'), '1');
    
                        $this->shop->addFrontendMessage(__("Sie wurden erfolgreich in unseren Newsletter eingetragen.", "wpsg"));
    
                    }
    
                } else {
    
                    $this->shop->addFrontendError(__('Sie wurden nicht zum Newsletter angemeldet, da das Plugin "newsletter" nicht installiert ist.', 'wpsg'));
    
                }
    
            }
    
        } // public function basket_save_done(&$o_id, &$k_id, &$oBasket)
    
        public function checkout_customer_inner() {
    
            $this->shop->render(WPSG_PATH_VIEW.'/mods/mod_nlsatolo/checkout_customer_inner.phtml');
    
        } // public function checkout_inner_prebutton()
    
        public function clearSession()  {
    
            if ($this->shop->get_option('wpsg_afterorder') == '1') {
    
                unset($_SESSION['wpsg']['wpsg_mod_nlsatolo']);
    
            }
    
        } // public function clearSession()
    
        /**
         * Gibt true zurück, wenn das Satolo Newsletter Plugin installiert und aktiv ist
         */
        private function checkNewsletterPlugin() {
    
            require_once(ABSPATH.'wp-admin/includes/plugin.php');
    
            if (is_plugin_active('newsletter/plugin.php')) {
    
                if ($GLOBALS['wpng_pc']->hasActiveLicence() || $GLOBALS['wpng_pc']->getDemoDays() > 0) {
    
                    return true;
    
                }
    
            }
    
            return false;
    
        } // private function checkNewsletterPlugin()
    
    } // class wpsg_mod_nlsatolo extends wpsg_mod_basic

?>