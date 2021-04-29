<?
use std, gui, framework, app, jurl;

class AnimeVostPlugin
{
	const Name = 'AnimeVost';
    const Description = 'Источник аниме AnimeVost [Озвучка]';
    const Version = '0.0.1';
    const Type = 'Парсер';
    
    static function info(){
        return [
            'name'=>self::Name,
            'description'=>self::Description,
            'version'=>self::Version,
            'type'=>self::Type
        ];
    }
    
    static function init(){
        pSource::add('AnimeVostPlugin');
        
        return self::info();
    }
    
    static function search(string $query, int $episode=1, int $type=3){
        
		pSource::set_ready(self::Name);
    }
}
