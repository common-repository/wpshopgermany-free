<?php

    /**
     * User: Daschmi (daschmi@daschmi.de)
     * Date: 18.05.2017
     * Time: 08:44
     */

    namespace wpsg;
    
    abstract class Exceptionhandler
    {

        /**
         * Wird aufgerufen wenn eine Exception nicht im Code behandelt wird
         * @param $ex
         */
        static function exception($ex) 
        {
         
            if (get_class($ex) === "wpsg\Exception")
            {

                $typeLabel = $ex->getTypLabel();
                $arData = $ex->getData();

            }
            else 
            {

                $typeLabel = __('Allgemeiner Fehler', 'wpsg');
                $arData = Array();

            }

            $msg = $ex->getMessage();

            // Protokolleintrag anlegen
            $strLogText  = date('d.m.Y H:i:s').': '.str_pad($typeLabel, 50, ' ')."\r\n";
            $strLogText .= $msg."\r\n";

            foreach ($arData as $d)
            {

                $strLogText .= $d[0].': '.$d[1]."\r\n";

            }

            ob_start();
            $strLogText .= $ex;
            ob_end_clean();

            ob_start();
            debug_print_backtrace();
            $backtrace = ob_get_contents();
            ob_end_clean();
         
            $strLogText .= str_pad('', 120, '-')."\r\n";

            $log_file = $GLOBALS['wpsg_sc']->getStorageRoot().'exception.log';

            if (file_exists($log_file)) file_put_contents($log_file, $strLogText.file_get_contents($log_file));
            else file_put_contents($log_file, $strLogText);

            die(wpsg_debug($typeLabel.": ".$msg."\r\n".$backtrace, true));

        } // static function myCallbackMethod($ex)
        
    } // abstract class exceptionhandler
    