<?

use std, framework, app, jurl;

class AniLibriaPlugin
{
	const Name = 'AniLibria';
	const Description = 'Источник аниме AniLibria [Озвучка]';
	const Version = '0.0.2';
	const Type = 'Парсер';
	
	static function info(){
		return [
			'name'=>self::Name,
			'desc'=>self::Description,
			'ver'=>self::Version,
			'type'=>self::Type
		];
	}
	
	static function init(){
		pSource::add('AniLibriaPlugin');
		
		return new MPlugin(self::info());
	}
	
	static function search(string $query, int $episode=1, int $type = 3){
		if($type != 3){
			return pSource::set_error(self::Name, 'Отсутствует этот тип перевода');
		}
		$query = ParserClass::clear_query($query);
		$animes = ParserClass::curl_match('http://online.anilibria.life/index.php?do=search', '<div class="kino-title"> <a href="(.*?)" class="kino-h"> <h2>(.*?)<\/h2>', [], false, 'do=search&subaction=search&search_start=0&result_from=0&full_search=1&all_world_seach=1&titleonly=3&sortby=news_read&story='.$query);

		if(!empty($animes)){
			foreach($animes as $id=>$anime){
				$name = trim(ParserClass::replace('(\[.*?\])', $anime[2], ''));
				if($name == $query){
					$main_id = $id;
					break;
				}
				if(str::contains($name, $query)){
					$main_id = $id;
				}
			}
			
			if(!isset($main_id)){
				$main_id = 0;
			}

			$url = ParserClass::curl_match(str_replace('//anilibria', '//online.anilibria', $animes[$main_id][1]), 'marginwidth="0" src="(.*?)"')[0][1];
			$episodes = json_decode(ParserClass::curl_match($url, 'file:\'(.*?)\',')[0][1],1);

			if(!empty($episodes)){
				foreach($episodes as $source){
					$url = $source['folder'][0]['folder'][$episode-1]['file'];
					$url = self::checkQuality($url);
					if($url == null or empty($url[0])){
						pSource::set_error(self::Name, 'Ошибка парсинга');
					}else{
						pSource::add_dub(self::Name, $source['title'], $url[0], self::Name.' [' . $url[1] .']');
						pSource::set_ready(self::Name);
					}
				}
			}else{
				return pSource::set_error(self::Name, 'Отсутствует эпизод');
			}
		}else{
			return pSource::set_error(self::Name, 'Аниме не найдено');
		}
    }
	
	static function checkQuality($url){
        $m3u8 = ParserClass::curlexec($url);
	
        if(str::contains($m3u8, '1080/index')){
            return [str::replace($url, 'index.m3u8', '/1080/index.m3u8'), '1080p'];
        }
        if(str::contains($m3u8, '720/index')){
            return [str::replace($url, 'index.m3u8', '/720/index.m3u8'), '720p'];
        }
        if(str::contains($m3u8, '480/index')){
            return [str::replace($url, 'index.m3u8', '/480/index.m3u8'), '480p'];
        }
		if(str::contains($m3u8, '360/index')){
            return [str::replace($url, 'index.m3u8', '/360/index.m3u8'), '360p'];
        }
        
        return null;
    }
}