<?php

	/**
	 * Klasse, die die Funktionen Kapselt die die News betreffen die im Backend angezeigt werden
	 * @author 11.08.2015 Daschmi (daniel@maennchen1.de)
	 *
	 */
	class wpsg_news
	{
		
		/** URL für den Feed */
		private static $rss_url = 'https://wpshopgermany.maennchen1.de/feed/';
		
		/** Limit für die News */
		private static $nLimit = 5;
		
		/** Sekunden für den Refresh der News */
		private static $nRefreshDelay = 86400;
		
		/**
		 * Gibt die News entweder aus dem Cache zurück oder lädt sie nach
		 */
		public static function getLatestNews()
		{
			
			$last_refresh = $GLOBALS['wpsg_sc']->get_option('wpsg_news_cache_refresh');
			$last_news = $GLOBALS['wpsg_sc']->get_option('wpsg_news_cache');
			
			if (!wpsg_isSizedInt($last_refresh) || !wpsg_isSizedArray($last_news) || ($last_refresh + self::$nRefreshDelay) <= time()) {
				 
				return self::getLatestNewsFromRSS();
				
			}
			else
			{
			
				return $last_news;
				
			}
			
		} // public static function getLatestNews()
		
		/**
		 * Liest den RSS Feed und gibt den Array mit den News zurück
		 * Speichert die News gleichzeitig in den Cache und aktualisiert das Refresh Datum
		 */
		public static function getLatestNewsFromRSS() {
			
			$xml = new SimpleXMLElement($GLOBALS['wpsg_sc']->get_url_content(self::$rss_url));
			
			$result = $xml->xpath('//item');
			
			$count = 0; $arReturn = array();
			
			foreach ($result as $node) {
				
				$count ++;
				
				$arReturn[] = array(
					'id' => strval($node->guid[0]),
					'title' => strval($node->title[0]),
					'url' => strval($node->link[0]),
					'date' => strtotime(strval($node->pubDate[0])),
					'teaser' => strval($node->description[0])						
				);
								
				if ($count >= self::$nLimit) break;
								
			}
			
			// News im Cache ablegen
			$GLOBALS['wpsg_sc']->update_option('wpsg_news_cache', $arReturn, false, false, WPSG_SANITIZE_NONE);
			$GLOBALS['wpsg_sc']->update_option('wpsg_news_cache_refresh', time(), false, false, WPSG_SANITIZE_NONE);
									
			return $arReturn;
			
		} // private static function getLatestNewsFromRSS()
		
		/**
		 * Prüft ob die News bereits gelesen wurde
		 * 
		 * @param String $news_id
		 * @return Boolean (true wenn gelesen) 
		 */
		public static function isRead($news_id)
		{
			
			/* $arNewsRead = $GLOBALS['wpsg_sc']->get_option('wpsg_news_read'); */
			$arNewsRead = get_user_meta(get_current_user_id(), 'wpsg_news_read', true);
			if (!is_array($arNewsRead)) $arNewsRead = array();
			
			return in_array($news_id, $arNewsRead);
						
		} // public static function isRead($news_id)

		/**
		 * Markiert eine News als gelesen.
		 * @param String $news_id ID der News
		 */
		public static function markRead($news_id)
		{
			
			/* $arNewsRead = $GLOBALS['wpsg_sc']->get_option('wpsg_news_read'); */
			$arNewsRead = get_user_meta(get_current_user_id(), 'wpsg_news_read', true);
			
			if (!is_array($arNewsRead)) $arNewsRead = array();
			
			if (!in_array($news_id, $arNewsRead)) $arNewsRead[] = $news_id;
			
			//$GLOBALS['wpsg_sc']->update_option('wpsg_news_read', $arNewsRead);
			update_user_meta(get_current_user_id(), 'wpsg_news_read', $arNewsRead);
			
		} // public static function markRead($news_id)
		
		/**
		 * Zählt die nicht gelesenen News
		 */
		public static function countUnreadNews()
		{
			 			
			$arNews = self::getLatestNews();
			$nUnread = 0;
			
			foreach ($arNews as $news)
			{
				
				if (!self::isRead($news['id']))
				{
					
					$nUnread ++;
					
				}
				
			} 
			
			return $nUnread;
			
		} // public static function countUnreadNews()
		
		/**
		 * Bereitet die Ausgabe für das Backend vor und ersetzt den Weiterlesen Link
		 * 
		 * @param Array $newst
		 * @return String Verarbeiteter Inhalt für die Ausgabe
		 */
		public static function prepareContent($news)
		{
			
			return str_replace(
				'Weiterlesen', 
				'<a target="_blank" onclick="setTimeout(function() { location.href = location.href; }, 1000); return true;" href="'.WPSG_URL_WP.'wp-admin/admin.php?page=wpsg-Admin&subaction=news&read='.rawurlencode($news['id']).'&noheader=1">Weiterlesen</a>', 
				strip_tags($news['teaser'])
			);
			
		} // public static function prepareContent($news_content)
		
		/**
		 * Gibt eine News anhand ihrer ID zurück (Schaut nur in den Cache)
		 * 
		 * @param String $news_id
		 * @return mixed $news
		 */
		public static function getNewsById($news_id) {
			
			$last_news = self::getLatestNews(); //$GLOBALS['wpsg_sc']->get_option('wpsg_news_cache');
			
			if (wpsg_isSizedArray($last_news)) {
			
				foreach ($last_news as $news) {
				
					if ($news['id'] == $news_id) return $news;
				
				}
				
			}
			
			return false;
			
		} // public static function getNewsById($news_id)
		
	} // class wpsg_news

?>