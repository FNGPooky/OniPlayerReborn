<?

use std, gui, framework, app, jurl;

class NekomoriPlugin
{
	const Name = 'Nekomori';
	const Description = 'Источник аниме Nekomori [Озвучка\Субтитры]';
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
		pSource::add('NekomoriPlugin');
		
		return new MPlugin(self::info());
	}
	
	static function search(string $query, int $episode=1, int $type=3){
		$wak_type = array(2=>"subs", 3=>"dub")[$type];
		
		$id = json_decode(ParserClass::curlexec('https://nekomori.ch/api/arts?search='.urlencode($query).'&page=1'), true);
		$seed = ParserClass::curl_match('https://nekomori.ch/api/external/kartinka?artId='.$id['page'][0]['id'], "https:\/\/wakanim\.xyz\/cdn\/(.*)")[0][1];
		$headers[] = "seed: {$seed}";
		$headers[] = "referer: https://wakanim.xyz/cdn/{$seed}";
		
		$data = json_decode(ParserClass::curlexec("https://wakanim.xyz/cdn/list?page={$episode}", $headers), true);
		
		if(empty($data)){
			return pSource::set_error(self::Name, 'Аниме не найдено');
		}
		
		foreach($data as $video){
			if($video["kind"]  != $wak_type){
				continue;
			}
			switch($video["player"]["name"]){
				case "Sibnet" :
					$url = OtherClass::sibnet_parse($video["src"]);
				break;
				
				case "SovetRomantica" :
					$url = pParser::SovetRomantica($video["src"]);
				break;
				
				case "Proton" :
					$url = pParser::Proton($video["src"]);
				break;
				
				case "AllVideos":
					$url = pParser::AllVideos($video["src"]);
				break;
			}
			
			if (isset($url)) pSource::add_dub(self::Name, $video["authors"][0]["name"]?:'Unknown', $url, $video["player"]["name"]);
			
			unset($url);
		}
		
		pSource::set_ready(self::Name);
	}
}