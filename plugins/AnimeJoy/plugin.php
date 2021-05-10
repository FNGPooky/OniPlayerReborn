<?

use std, gui, framework, app, jurl;

class AnimeJoyPlugin
{
	const Name = 'AnimeJoy';
	const Description = 'Источник аниме AnimeJoy [Субтитры]';
	const Version = '0.0.4';
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
		pSource::add(self::Name . "Plugin");
		pParser::add('Proton', array(self::Name . "Plugin", "proton_parse"));
		pParser::add('AllVideos', array(self::Name . "Plugin", "allvideos_parse"));

		return new MPlugin(self::info());
	}

	static function search(string $query, int $episode=1, int $type=3){
		if($type != 2){
			return pSource::set_error(self::Name, 'Отсутствует этот тип перевода');
		}
		$animes = ParserClass::curl_match('https://animejoy.ru/', '<h2 class="ntitle"><a href="(.*?)">(.*?) \[.*?\]<\/a><\/h2>', [], false, "do=search&subaction=search&story=" . urlencode(ParserClass::clear_query($query)));

		if(!empty($animes)){
			foreach($animes as $id=>$anime){
				if(ParserClass::clear_query($anime[2]) == ParserClass::clear_query($query)){
					$main_id = $id;
					break;
				}
			}

			if(!isset($main_id)){
				$main_id = 0;
			}

			$ajax_data = ParserClass::curl_match($animes[$main_id][1], 'div class="playlists-ajax" data-xfname="(.*?)" data-news_id="(.*?)">');

			if(isset($ajax_data[0][2])){
				$data = json_decode(ParserClass::curlexec('https://animejoy.ru/engine/ajax/playlists.php?news_id='.$ajax_data[0][2].'&xfield='.$ajax_data[0][1]), true);

				if($data['success'] == true and isset($data['response'])) {
					$depisodes = ParserClass::match('<li data-file=\"(.*?)" data-id=\".*?\">(.*?) ([а-я]+)<\/li>', $data['response'], true);

					foreach($depisodes as $depisode){
						if($depisode[2] == $episode and $depisode[3] == "серия") {
							if(str::contains($depisode[1], 'sibnet')) {
								$url = OtherClass::sibnet_parse($depisode[1]);
								$source = 'Sibnet';
							}elseif(str::contains($depisode[1], 'csst.online')) {
								$url = self::allvideos_parse($depisode[1], true);
								$source = 'AllVideos [' . $url[1] . ']';
								$url = $url[0];
							}elseif(str::contains($depisode[1], 'protonvideo')){
								$url = self::proton_parse($depisode[1], true);
								$source = 'Proton [' . $url[1] . ']';
								$url = $url[0];
							}

							if(isset($url)){
								pSource::add_dub(self::Name, self::Name, $url, $source);
							}
							unset($url);
						}
					}

					pSource::set_ready(self::Name);
				}else{
					return pSource::set_error(self::Name, 'Эпизоды не найдены');
				}
			}else{
				return pSource::set_error(self::Name, 'Эпизоды не найдены');
			}

		}else{
			return pSource::set_error(self::Name, 'Аниме не найдено');
		}
	}

	static function allvideos_parse($url, bool $returnQuality = false){
		$result = ParserClass::curl_match($url, 'Location: (.*)', [], true);
		$qualities = ParserClass::curl_match($result[0][1], '\[(.*?)p\](.*?)\.mp4\/');
		$max_quality = 0;
		foreach($qualities as $quality){
			if(intval($quality[1]) > $max_quality){
				$max_quality = intval($quality[1]);
				$url = $quality[2].".mp4/";
			}
		}

		return $returnQuality ? [$url, "$max_quality\p"] : $url;
	}

	static function proton_parse($url, bool $returnQuality = false) {
		$result = ParserClass::curl_match($url, "await fetch\\('(.*?)', \\{");
		$id = ParserClass::match('https:\/\/protonvideo\.to\/iframe\/(.*?)\/', $url);
		
		$url = ParserClass::curl_match($result[0][1], '"file":"\[(.*?)p\](.*?) ', ['Referer: https://protonvideo.to/'], false, json_encode(['idi'=>$id]))[0];
		return $returnQuality ? [$url[2], $url[1].'p'] : $url[2];
	}
}
