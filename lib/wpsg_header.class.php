<?php

	class wpsg_header
	{

        public static function startDownloadContent($filename, $content)
        {

            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename='.$filename);
            header('Connection: Keep-Alive');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');

            // Bei Download von Textdateien (Profil.json) wurde die falsche Länge übergeben und der Download damit abgeschnitten ??
            //header('Content-Length: '.mb_strlen($content));

            echo $content;
            exit;

        } // public static function startDownload($filename)
		
        public function IMG($file) {

            $filename = basename($file);
            $file_extension = strtolower(substr(strrchr($filename,"."),1));

            switch ($file_extension) {
                
                case "gif": $ctype = "image/gif"; break;
                case "png": $ctype = "image/png"; break;
                case "jpeg":
                case "jpg": $ctype = "image/jpeg"; break;
                
                default:
                    
            }

            header('Content-type: '.$ctype);
            
            readfile($file);
            exit;
		    
        }
        
		public static function PDFPlugin($file)
		{
			
			header("Cache-Control: no-cache, must-revalidate"); 
			header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
			header('Content-type: application/pdf');			
			header('Content-Disposition: inline; filename="'.basename($file).'"');
			
			readfile($file);
			
		} // public static function PDFPlugin($filename)
		
		public static function JSONData($arData)
		{
			
			header('Content-Type: application/json');
			die(json_encode($arData));
			
		} // public static function JSONData()
		
		public static function ZIP($file, $filename = false)
		{
			
			if ($filename === false) $filename = basename($file);
			
			header("Pragma: public");
			header("Expires: 0");
			header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			header("Cache-Control: public");
			header("Content-Description: File Transfer");
			header("Content-type: application/octet-stream");
			header("Content-Disposition: attachment; filename=\"".$filename."\"");
			header("Content-Transfer-Encoding: binary");
			header("Content-Length: ".filesize($file));
		
			readfile($file);
			
		}
		
	} // class wpsg_header

?>