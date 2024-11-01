<?php

	use \setasign\Fpdi\Fpdi;
	use \setasign\FpdiProtection\FpdiProtection;
	
	class wpsg_fpdf extends Fpdi
	{
		 
		var $noHeader = false;
		var $extgstates = [];
		 
		public function Text($x, $y, $text)
		{
			
			parent::Text($x, $y, $this->toIso($text));
			
		}
 	
		public function Cell($w, $h = 0, $text = '', $border = 0, $ln = 0, $align = '', $fill = 0, $link = '') 
		{
						
			return parent::Cell($w, $h, $this->toIso($text), $border, $ln, $align, $fill, $link);
			
		}
 
		public function wpsg_MultiCell($x, $y, $h, $txt, $border = 0, $align = 'L', $fill = 0, $width = 0)
		{
 
			//	MultiCell(float w , float h , string txt [, mixed border] [, string align] [, integer fill])
			parent::setXY($x, $y);
			
			// Hier kein toISO weil intern wieder Cell aufgerufen wird
			parent::MultiCell($width, $h, $txt, $border, $align, $fill); 

			$height = $this->getY() - $y;
				
			return $height;
			
		}
		
		public function wpsg_SetTextColor($strHEXCode)
		{
						
			if ($strHEXCode[0] == '#')
		        $strHEXCode = substr($strHEXCode, 1);
		
		    if (strlen($strHEXCode) == 6)
		        list($r, $g, $b) = array($strHEXCode[0].$strHEXCode[1],
		                                 $strHEXCode[2].$strHEXCode[3],
		                                 $strHEXCode[4].$strHEXCode[5]);
		    elseif (strlen($strHEXCode) == 3)
		        list($r, $g, $b) = array($strHEXCode[0].$strHEXCode[0], $strHEXCode[1].$strHEXCode[1], $strHEXCode[2].$strHEXCode[2]);
		    else
		        return false;
		
		    $r = hexdec($r); $g = hexdec($g); $b = hexdec($b);
				    			
			parent::setTextColor($r, $g, $b);
			
		} // public function wpsg_SetTextColor($strHEXCode)
		
		function Rotate($angle,$x=-1,$y=-1)
		{
			
		    if($x==-1)
		        $x=$this->x;
		    if($y==-1)
		        $y=$this->y;
		    if($this->angle!=0)
		        $this->_out('Q');
		    $this->angle=$angle;
		    if($angle!=0)
		    {
		        $angle*=M_PI/180;
		        $c=cos($angle);
		        $s=sin($angle);
		        $cx=$x*$this->k;
		        $cy=($this->h-$y)*$this->k;
		        $this->_out(sprintf('q %.5f %.5f %.5f %.5f %.2f %.2f cm 1 0 0 1 %.2f %.2f cm',$c,$s,-$s,$c,$cx,$cy,-$cx,-$cy));
		    }
		    
		}
		
		function RotatedText($x, $y, $txt, $angle, $bCenter = "0")
		{
		
			if ($bCenter == "1")
			{
				
				$h = $this->GetStringWidth($txt) / 2;	// Hypothenuse
				
				if ($angle == "" || $angle == 0) 
				{
					
					$x = $x - $h;
											
				}
				else
				{
					
					$xAK = $h * cos(deg2rad($angle)); // Ankathete
					$yGK = $h * sin(deg2rad($angle)); // Gegenkathete
											
					$x = $x - $xAK;
					$y = $y + $yGK;					
					
				}
				
			}
		
		    // Text rotated around its origin
	    	$this->Rotate($angle,$x,$y);
	    	$this->Text($x,$y, $txt);
	    	$this->Rotate(0);
	    	
		}
		
		function TextWithDirection($x, $y, $txt, $direction = 'R')
		{
		    $txt=str_replace(')', '\\)', str_replace('(', '\\(', str_replace('\\', '\\\\', $txt)));
		    if ($direction=='R')
		        $s=sprintf('BT %.2f %.2f %.2f %.2f %.2f %.2f Tm (%s) Tj ET', 1, 0, 0, 1, $x*$this->k, ($this->h-$y)*$this->k, $txt);
		    elseif ($direction=='L')
		        $s=sprintf('BT %.2f %.2f %.2f %.2f %.2f %.2f Tm (%s) Tj ET', -1, 0, 0, -1, $x*$this->k, ($this->h-$y)*$this->k, $txt);
		    elseif ($direction=='U')
		        $s=sprintf('BT %.2f %.2f %.2f %.2f %.2f %.2f Tm (%s) Tj ET', 0, 1, -1, 0, $x*$this->k, ($this->h-$y)*$this->k, $txt);
		    elseif ($direction=='D')
		        $s=sprintf('BT %.2f %.2f %.2f %.2f %.2f %.2f Tm (%s) Tj ET', 0, -1, 1, 0, $x*$this->k, ($this->h-$y)*$this->k, $txt);
		    else
		        $s=sprintf('BT %.2f %.2f Td (%s) Tj ET', $x*$this->k, ($this->h-$y)*$this->k, $txt);
		    if ($this->ColorFlag)
		        $s='q '.$this->TextColor.' '.$s.' Q';
		    $this->_out($s);
		}

		function TextWithRotation($x, $y, $txt, $txt_angle, $font_angle=0)
		{
		    $txt=str_replace(')', '\\)', str_replace('(', '\\(', str_replace('\\', '\\\\', $txt)));
		
		    $font_angle+=90+$txt_angle;
		    $txt_angle*=M_PI/180;
		    $font_angle*=M_PI/180;
		
		    $txt_dx=cos($txt_angle);
		    $txt_dy=sin($txt_angle);
		    $font_dx=cos($font_angle);
		    $font_dy=sin($font_angle);
		
		    $s=sprintf('BT %.2f %.2f %.2f %.2f %.2f %.2f Tm (%s) Tj ET',
		             $txt_dx, $txt_dy, $font_dx, $font_dy,
		             $x*$this->k, ($this->h-$y)*$this->k, $txt);
		    if ($this->ColorFlag)
		        $s='q '.$this->TextColor.' '.$s.' Q';
		    $this->_out($s);
		} 
		 
	    function AlphaPDF($orientation='P', $unit='mm', $format='A4')
	    {
	        parent::FPDF($orientation, $unit, $format);
	        $this->extgstates = array();
	    }
	
	    function SetAlpha($alpha, $bm='Normal')
	    {
	        // set alpha for stroking (CA) and non-stroking (ca) operations
	        $gs = $this->AddExtGState(array('ca'=>$alpha, 'CA'=>$alpha, 'BM'=>'/'.$bm));
	        $this->SetExtGState($gs);
	    }
	
	    function AddExtGState($parms)
	    {
	        $n = count($this->extgstates)+1;
	        $this->extgstates[$n]['parms'] = $parms;
	        return $n;
	    }
	
	    function SetExtGState($gs)
	    {
	        $this->_out(sprintf('/GS%d gs', $gs));
	    }
	
	    function _enddoc()
	    {
	        if(!empty($this->extgstates) && $this->PDFVersion<'1.4')
	            $this->PDFVersion='1.4';
	        parent::_enddoc();
	    }
	
	    function _putextgstates()
	    {
	    	
	    	$extgstates = $this->extgstates;
	    	
	    	if (is_array($extgstates)) {
	        
	    		for ($i = 1; $i <= count($this->extgstates); $i++) {
	    			
					$this->_newobj();
					$this->extgstates[$i]['n'] = $this->n;
					$this->_out('<</Type /ExtGState');
					foreach ($this->extgstates[$i]['parms'] as $k=>$v)
						$this->_out('/'.$k.' '.$v);
					$this->_out('>>');
					$this->_out('endobj');
					
				}
				
			}
				
	    }

	    function _putresourcedict()
	    {
	        parent::_putresourcedict();
	        $this->_out('/ExtGState <<');
	        foreach((array)$this->extgstates as $k=>$extgstate)
	            $this->_out('/GS'.$k.' '.$extgstate['n'].' 0 R');
	        $this->_out('>>');
	    }
	
	    function _putresources()
	    {
	    	
	        $this->_putextgstates();
	        
	        parent::_putresources();
	        
	    }
		
		private function toIso($value)
		{
			
			/* Alternative:
				$value = utf8_decode($value);
				$value = str_replace("€", chr(128), $value);
				$value = str_replace(utf8_decode("€"), chr(128), $value);
			 */
			 			
			$value = str_replace('–', '-', $value);
			$value = str_replace("€", '&euro;', $value);
			$value = utf8_decode($value);
			$value = str_replace("&euro;", chr(128), $value); 
			
			return $value;
			
		}
		
	}

?>