<?php

    /**
     * User: Daschmi (daschmi@daschmi.de)
     * Date: 16.11.2016
     * Time: 10:31
     */

    abstract class wpsg_remoteconnection
    {
   
        /* Statische Funktionen */
        
        public static function handleConenctionString($strConnectionString, $filename, $file, $con_type = FTP_ASCII)
        {
            
            if (preg_match('/^ftp/', $strConnectionString))
            {
                
                $arPath = explode('/', $strConnectionString);
                $arConnection = explode('@', $arPath[2]);
                $arAccess = explode(':', $arConnection[0]);
                
                $host = $arConnection[1];
                $username = $arAccess[0];
                $password = $arAccess[1];
                
                $path = implode('/', array_slice($arPath, 3));
                                
                $connection = ftp_connect($host);                
                if ($connection === false) throw new \Exception(wpsg_translate(__('Konnte keine Verbindung zu #1# aufbauen. (Host)', 'wpsg'), $host));
                
                $login_result = ftp_login($connection, $username, $password);
                if ($login_result === false) throw new \Exception(wpsg_translate(__('Konnte keine Verbindung zu #1# aufbauen. (Zugangsdaten)', 'wpsg'), $host));
                
                ftp_pasv($connection, true);
                
                $trans = ftp_put($connection, $path.'/'.$filename, $file, $con_type);
                
                ftp_close($connection);
                
                return true;
                
            }
            
            return false;
            
        } // public static function handleConenctionString($connection, $file)
        
    } // class wpsg_mod_remoteconnection

?>