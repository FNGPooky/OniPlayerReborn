<?
use std, gui, framework, app, jurl;

class AnimeVostPlugin
{
	const Name = "AnimeVost";
	const Description = "Источник аниме AnimeVost [Озвучка]";
	const Version = "0.0.2";
	const Type = "Парсер";
	const Domain = "a98.agorov.org";
	
	static function info(){
		return [
			'name'=>self::Name,
			'desc'=>self::Description,
			'ver'=>self::Version,
			'type'=>self::Type
		];
	}
	
	static function init(){
		pSource::add(self::Name . "Plugin");
		
		return new MPlugin(self::info());
	}
	
	static function search(string $query, int $episode=1, int $type=3){
		if($type != 3){
			return pSource::set_error(self::Name, 'Отсутствует этот тип перевода');
		}
		
		$animes = ParserClass::curl_match("https://".self::Domain."/", '<div class="shortstoryHead">\s*<h2>\s*<a href="(.*?)">(.*?) \/.*?<\/a>', [], false, "do=search&subaction=search&story=".urlencode(ParserClass::clear_query($query)));
		
		if (!empty($animes)) {
			foreach ($animes as $id=>$anime) {
				if (ParserClass::clear_query($anime[2]) == ParserClass::clear_query($query)) {
					$main_id = $id;
					break;
				}
			}
			
			if (isset($animes[$main_id][1])) {
				$episodes = ParserClass::curl_match($animes[$main_id][1], 'var data = \{(.*?)\};')[0][1];
			
				if (substr($episodes, -1) == ",") {
					$episodes = json_decode("{".substr($episodes, 0, -1)."}", true);
				} else {
					$episodes = json_decode("{".$episodes."}", true);
				}
				
				if (isset($episodes["$episode серия"])) {
					pSource::add_dub(self::Name, self::Name, "https://ram.trn.su/720/".$episodes["$episode серия"].".mp4", self::Name." [720p?]");
					pSource::set_ready(self::Name);
				} else {
					return pSource::set_error(self::Name, 'Отсутствует эпизод');
				}
			} else {
				return pSource::set_error(self::Name, 'Ошибка парсинга');
			}
		} else {
			return pSource::set_error(self::Name, 'Аниме не найдено');
		}
	}
}
