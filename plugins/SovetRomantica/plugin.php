<?

use std, framework, app, jurl;

class SovetRomanticaPlugin
{
	const Name = 'SovetRomantica';
	const Description = 'Источник аниме SovetRomantica [Озвучка\Субтитры]';
	const Version = '0.0.2';
	const Type = 'Парсер';

	static $cookie;
	static function info(){
		return [
			'name'=>self::Name,
			'description'=>self::Description,
			'version'=>self::Version,
			'type'=>self::Type
		];
	}

	static function init(){
		pSource::add(self::Name . "Plugin");
		pParser::add('SovetRomantica', array("SovetRomanticaPlugin", "parse"));

		self::loadCookie();

		return self::info();
	}

	static function loadCookie(){
		uiLater(function(){
			$browser = pObjControl::createPhantomBrowser();
			$browser[1]->engine->watchState(function($sender, $old, $new) use($browser){
				if($new == 'SUCCEEDED'){
					$cookie = $sender->executeScript("document.cookie");
					if(!empty($cookie)){
						self::$cookie = $cookie;
						pObjControl::freePhantomBrowser($browser[0]);
						unset($browser);
					}
				}
			});
			$browser[1]->engine->load('https://sovetromantica.com');
		});
	}

	static function search(string $query, int $episode=1, int $type=3){
		if($type == 1){
			return pSource::set_error(self::Name, 'Отсутствует этот тип перевода');
		}

		$animes = json_decode(ParserClass::curlexec('https://service.sovetromantica.com/v1/animesearch?anime_name='.urlencode($query)), true);

		if(!empty($animes)){
			foreach($animes as $id=>$anime){
				if($anime['anime_name_russian'] == $query){
					$main_id = $id;
					break;
				}
			}
			if(!isset($anime_id)){
				$main_id = 0;
			}
			$episodes = json_decode(ParserClass::curlexec('https://service.sovetromantica.com/v1/anime/'.$animes[$main_id]['anime_id'].'/episodes'), true);

			foreach ($episodes as $id => $ep) {
				if ($ep["episode_count"] == $episode) {
					$ep_id = $id;
				}
			}

			if(isset($episodes[$ep_id]['embed'])){
				$url = str_replace("sovetromantica.com", "ani.wtf", $episodes[$ep_id]['embed']);
				$url = self::parse(explode('-', $url)[0] . '-' . ($type == 3 ? 'dubbed' : 'subtitles'), true);

				if(!empty($url)){
					pSource::add_dub(self::Name, self::Name, $url[0], self::Name.' [' . $url[1] .']');
				}else{
					return pSource::set_error(self::Name, 'Ошибка парсинга');
				}
			}else{
				return pSource::set_error(self::Name, 'Отсутствует эпизод');
			}
			pSource::set_ready(self::Name);
		}else{
			return pSource::set_error(self::Name, 'Аниме не найдено');
		}
	}

	static function parse($url, bool $returnQuality = false){
		$url = ParserClass::curl_match($url, '"file":"(.*?)"', ['Cookie: ' .self::$cookie])[0][1];

		if(empty($url) == false){
			$url = self::checkQuality($url);
			
			return $returnQuality ? $url : $url[0];
		}
	}

	static function checkQuality($url){
		$m3u8 = ParserClass::curlexec($url);

		if(str::contains($m3u8, '1080p')){
			return [str::replace($url, '.m3u8', '_1080p.m3u8'), '1080p'];
		}
		if(str::contains($m3u8, '720p')){
			return [str::replace($url, '.m3u8', '_720p.m3u8'), '720p'];
		}
		if(str::contains($m3u8, '480p')){
			return [str::replace($url, '.m3u8', '_480p.m3u8'), '480p'];
		}
		
		return null;
	}
}
