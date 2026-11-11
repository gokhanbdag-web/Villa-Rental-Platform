<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use RuntimeException;

final class PageBuilder
{
    public static function widgets(): array
    {
        $groups = [
            'hero' => ['Hero alanı','hero','Tam genişlik','Bölünmüş','Görsel üstü','Minimal'],
            'search' => ['Konaklama arama','search','Yatay','Dikey','Kompakt','Yüzen panel'],
            'destinations' => ['Destinasyonlar','destinations','Kart ızgarası','Büyük görsel','Yatay kaydırma','Editoryal'],
            'properties' => ['Konaklamalar','properties','Standart kart','Yatay kart','Büyük görsel','Dergi düzeni'],
            'featured' => ['Öne çıkan villalar','featured','Vitrin','Kaydırmalı','Büyük kart'],
            'categories' => ['Konaklama kategorileri','categories','İkonlu','Görselli','Yatay'],
            'gallery' => ['Fotoğraf galerisi','gallery','Mozaik','Izgara','Şerit','Asimetrik'],
            'video' => ['Video alanı','video','Tam genişlik','Bölünmüş','Kapak görseli'],
            'text' => ['Metin alanı','text','Editoryal','Ortalanmış','İki kolon','Vurgulu'],
            'image' => ['Görsel alanı','image','Tam genişlik','Çerçeveli','Yuvarlatılmış'],
            'image_text' => ['Görsel ve metin','image_text','Görsel solda','Görsel sağda','Üst üste','Kart'],
            'cta' => ['Eylem çağrısı','cta','Renkli bant','Görselli','Minimal','Kutu'],
            'banner' => ['Tanıtım bannerı','banner','Tam genişlik','Bölünmüş','Kompakt'],
            'testimonials' => ['Misafir yorumları','testimonials','Kartlar','Alıntı','Kaydırmalı','Puan odaklı'],
            'stats' => ['İstatistikler','stats','Yatay sayaç','İkonlu kart','Büyük rakam'],
            'features' => ['Özellikler ve avantajlar','features','İkon ızgarası','Yatay liste','Numaralı'],
            'services' => ['Hizmetler','services','Kartlar','İkonlu','Görselli'],
            'faq' => ['Sık sorulan sorular','faq','Akordeon','İki kolon','Basit liste'],
            'blog' => ['Blog yazıları','blog','Kart ızgarası','Editoryal','Yatay'],
            'map' => ['Harita alanı','map','Tam genişlik','Bölünmüş','Kompakt'],
            'contact' => ['İletişim alanı','contact','Form ve bilgi','İki kolon','Minimal'],
            'newsletter' => ['E-posta aboneliği','newsletter','Bant','Kart','Minimal'],
            'logos' => ['Marka logoları','logos','Şerit','Izgara','Monokrom'],
            'hosts' => ['Ev sahibi tanıtımı','hosts','Banner','İki kolon','Kart'],
            'promo' => ['Kampanya alanı','promo','Görsel kart','İndirim bandı','Vitrin'],
            'spacer' => ['Boşluk','spacer','Küçük','Orta','Büyük'],
            'divider' => ['Ayraç','divider','Düz','Noktalı','Başlıklı'],
            'icon_list' => ['İkonlu bilgi listesi','icon_list','Yatay','Dikey','Kartlar'],
            'quote' => ['Alıntı','quote','Büyük tipografi','Kart','Minimal'],
            'timeline' => ['Zaman çizelgesi','timeline','Dikey','Yatay','Numaralı'],
            'team' => ['Ekip alanı','team','Kartlar','Portre','Minimal'],
            'awards' => ['Ödüller ve belgeler','awards','Izgara','Şerit','Kart'],
            'pricing' => ['Fiyatlandırma','pricing','Kartlar','Karşılaştırma','Vurgulu'],
            'booking_cta' => ['Rezervasyon çağrısı','booking_cta','Arama paneli','Banner','Kart'],
            'property_search' => ['Gelişmiş villa arama','property_search','Yatay','Dikey','Filtre paneli'],
            'recent_properties' => ['Son eklenen villalar','recent_properties','Kart ızgarası','Yatay','Kaydırmalı'],
            'popular_properties' => ['Popüler villalar','popular_properties','Kart ızgarası','Vitrin','Kaydırmalı'],
            'location_cards' => ['Lokasyon kartları','location_cards','Izgara','Büyük kart','Yatay'],
            'review_summary' => ['Değerlendirme özeti','review_summary','Puan kartı','İstatistik','Alıntı'],
            'social' => ['Sosyal medya alanı','social','İkonlar','Görsel ızgara','Takip bandı'],
            'html' => ['Özel HTML','html','Kod alanı','Gömülü içerik','Ham içerik'],
            'countdown' => ['Geri sayım','countdown','Büyük sayaç','Kart','Bant'],
            'trust' => ['Güven unsurları','trust','İkonlu şerit','Kartlar','Rozetler'],
            'steps' => ['Nasıl çalışır?','steps','Numaralı kart','Yatay adımlar','Dikey adımlar'],
            'amenities' => ['Villa özellikleri','amenities','İkon ızgarası','Liste','Kartlar'],
            'nearby' => ['Yakın çevre','nearby','Mesafe listesi','İkonlu kart','Harita yanı'],
            'seasonal' => ['Sezon önerileri','seasonal','Görsel kartlar','Yatay bant','Izgara'],
        ];
        $result = [];
        foreach ($groups as $type => $data) {
            $result[$type] = ['type'=>$type,'label'=>$data[0],'category'=>self::category($type),'styles'=>array_slice($data,2),'default_style'=>$data[2]];
        }
        return $result;
    }

    private static function category(string $type): string
    {
        if (in_array($type,['hero','search','property_search','booking_cta'],true)) return 'Başlangıç ve arama';
        if (in_array($type,['destinations','properties','featured','categories','recent_properties','popular_properties','location_cards','amenities','nearby'],true)) return 'Konaklama ve lokasyon';
        if (in_array($type,['gallery','video','image','image_text','banner','promo'],true)) return 'Görsel içerikler';
        if (in_array($type,['text','quote','icon_list','divider','spacer','html','timeline','steps'],true)) return 'İçerik ve düzen';
        if (in_array($type,['testimonials','review_summary','stats','features','services','faq','blog','map','contact','newsletter','logos','hosts','team','awards','pricing','social','countdown','trust','seasonal'],true)) return 'Pazarlama ve iletişim';
        return 'Diğer';
    }

    public static function load(string $theme, string $page): array
    {
        $stmt = Database::connection()->prepare('SELECT layout_json,status,version,updated_at FROM page_builder_layouts WHERE theme_slug=? AND page_key=? LIMIT 1');
        $stmt->execute([$theme,$page]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return ['widgets'=>[],'status'=>'draft','version'=>0,'updated_at'=>null];
        $layout = json_decode((string)$row['layout_json'],true);
        return ['widgets'=>is_array($layout['widgets']??null)?$layout['widgets']:[],'status'=>$row['status'],'version'=>(int)$row['version'],'updated_at'=>$row['updated_at']];
    }

    public static function sanitizePreview(string $theme, array $payload): array
    {
        if (!isset(ThemeManager::installed()[$theme])) throw new RuntimeException('Geçersiz tema seçimi.');
        $items=$payload['widgets']??[];
        if (!is_array($items)||count($items)>80) throw new RuntimeException('Bir sayfada en fazla 80 widget kullanılabilir.');
        $registry=self::widgets();$clean=[];$seen=[];
        foreach($items as $item){
            if(!is_array($item))continue;
            $id=(string)($item['id']??'');$type=(string)($item['type']??'');
            if(!preg_match('/^[a-zA-Z0-9_-]{1,40}$/',$id)||isset($seen[$id])||!isset($registry[$type]))continue;
            $seen[$id]=true;$style=(string)($item['style']??$registry[$type]['default_style']);
            if(!in_array($style,$registry[$type]['styles'],true))$style=$registry[$type]['default_style'];
            $settings=is_array($item['settings']??null)?$item['settings']:[];
            $safe=[];
            foreach(['eyebrow','title','subtitle','description','items_text','gallery_images','image_url','mobile_image_url','video_url','map_query','contact_phone','contact_email','contact_address','target_date','location_placeholder','button_text','button_url','alignment','background','columns','limit','show_title','layout','hero_layout','hero_text_position','search_position','search_layout','search_columns','show_search','hero_height','overlay_opacity','sort_by','location_id','country_id','category_id','min_guests','min_price','max_price','show_location','show_guests','show_price','show_rating','show_review_count','show_gallery','show_favorite','show_badges','image_ratio','empty_message','min_rating','show_property_count','card_radius','card_gap','image_height','title_size','price_size','meta_size','card_shadow','section_width','section_spacing','content_alignment','content_position','media_width','content_gap','media_radius','media_ratio','button_alignment','item_gap','item_radius','item_padding','item_icon_size','item_alignment'] as $key){
                if(!array_key_exists($key,$settings))continue;$value=$settings[$key];
                if(in_array($key,['columns','limit','search_columns','hero_height','overlay_opacity','min_rating','location_id','country_id','category_id','min_guests','min_price','max_price','card_radius','card_gap','image_height','title_size','price_size','meta_size','media_width','content_gap','media_radius','item_gap','item_radius','item_padding','item_icon_size'],true)){
                    $ranges=['columns'=>[1,4],'limit'=>[1,24],'search_columns'=>[1,5],'hero_height'=>[300,900],'overlay_opacity'=>[0,90],'min_rating'=>[0,10],'location_id'=>[0,2147483647],'country_id'=>[0,2147483647],'category_id'=>[0,2147483647],'min_guests'=>[0,100],'min_price'=>[0,100000000],'max_price'=>[0,100000000],'card_radius'=>[0,32],'card_gap'=>[4,40],'image_height'=>[140,420],'title_size'=>[12,28],'price_size'=>[12,28],'meta_size'=>[10,20],'media_width'=>[30,70],'content_gap'=>[8,64],'media_radius'=>[0,32],'item_gap'=>[4,40],'item_radius'=>[0,32],'item_padding'=>[8,40],'item_icon_size'=>[14,40]];
                    [$min,$max]=$ranges[$key];$safe[$key]=max($min,min($max,(int)$value));continue;
                }
                if(in_array($key,['show_title','show_search','show_location','show_guests','show_price','show_rating','show_review_count','show_gallery','show_favorite','show_badges','show_property_count'],true)){$safe[$key]=filter_var($value,FILTER_VALIDATE_BOOLEAN);continue;}
                $choices=['hero_layout'=>['overlay','split','minimal'],'hero_text_position'=>['left','center','right'],'search_position'=>['below','left','right','inside-bottom'],'search_layout'=>['horizontal','vertical'],'alignment'=>['left','center','right'],'image_ratio'=>['4:3','1:1','3:2','16:9','portrait'],'card_shadow'=>['none','soft','medium','strong'],'section_width'=>['full','wide','normal','narrow'],'section_spacing'=>['compact','normal','airy'],'content_alignment'=>['left','center','right'],'content_position'=>['left','right','top','bottom'],'media_ratio'=>['4:3','1:1','3:2','16:9','portrait'],'button_alignment'=>['left','center','right'],'item_alignment'=>['left','center']];
                if(isset($choices[$key])){$candidate=(string)$value;$safe[$key]=in_array($candidate,$choices[$key],true)?$candidate:$choices[$key][0];continue;}
                $value=trim((string)$value);
                if(in_array($key,['image_url','mobile_image_url','button_url'],true)&&$value!==''&&!preg_match('~^(https?://|/)~i',$value))$value='';
                $safe[$key]=mb_substr($value,0,($key==='items_text'?8000:(($key==='description'||$key==='subtitle')?1200:500)));
            }
            $clean[]=['id'=>$id,'type'=>$type,'style'=>$style,'settings'=>$safe];
        }
        return ['widgets'=>$clean,'status'=>'published','version'=>0,'updated_at'=>null];
    }

    public static function save(string $theme, string $page, array $payload, string $status, int $userId): void
    {
        if (!isset(ThemeManager::installed()[$theme])) throw new RuntimeException('Geçersiz tema seçimi.');
        if ($page!=='home' && !preg_match('/^[a-z0-9-]{1,40}$/',$page)) throw new RuntimeException('Geçersiz sayfa anahtarı.');
        if (!in_array($status,['draft','published'],true)) $status='draft';
        $registry=self::widgets();
        $items=$payload['widgets']??[];
        if (!is_array($items)||count($items)>80) throw new RuntimeException('Bir sayfada en fazla 80 widget kullanılabilir.');
        $clean=[];$seen=[];
        foreach($items as $item){
            if(!is_array($item))continue;
            $id=(string)($item['id']??'');
            $type=(string)($item['type']??'');
            if(!preg_match('/^[a-zA-Z0-9_-]{1,40}$/',$id)||isset($seen[$id])||!isset($registry[$type]))continue;
            $seen[$id]=true;
            $style=(string)($item['style']??$registry[$type]['default_style']);
            if(!in_array($style,$registry[$type]['styles'],true))$style=$registry[$type]['default_style'];
            $settings=is_array($item['settings']??null)?$item['settings']:[];
            $safe=[];
            foreach(['eyebrow','title','subtitle','description','items_text','gallery_images','image_url','mobile_image_url','video_url','map_query','contact_phone','contact_email','contact_address','target_date','location_placeholder','button_text','button_url','alignment','background','columns','limit','show_title','layout','hero_layout','hero_text_position','search_position','search_layout','search_columns','show_search','hero_height','overlay_opacity','sort_by','location_id','country_id','category_id','min_guests','min_price','max_price','show_location','show_guests','show_price','show_rating','show_review_count','show_gallery','show_favorite','show_badges','image_ratio','empty_message','min_rating','show_property_count','card_radius','card_gap','image_height','title_size','price_size','meta_size','card_shadow','section_width','section_spacing','content_alignment','content_position','media_width','content_gap','media_radius','media_ratio','button_alignment','item_gap','item_radius','item_padding','item_icon_size','item_alignment'] as $key){
                if(!array_key_exists($key,$settings))continue;
                $value=$settings[$key];
                if(in_array($key,['columns','limit','search_columns','hero_height','overlay_opacity','min_rating','location_id','country_id','category_id','min_guests','min_price','max_price','card_radius','card_gap','image_height','title_size','price_size','meta_size','media_width','content_gap','media_radius','item_gap','item_radius','item_padding','item_icon_size'],true)){$ranges=['columns'=>[1,4],'limit'=>[1,24],'search_columns'=>[1,5],'hero_height'=>[300,900],'overlay_opacity'=>[0,90],'min_rating'=>[0,10],'location_id'=>[0,2147483647],'country_id'=>[0,2147483647],'category_id'=>[0,2147483647],'min_guests'=>[0,100],'min_price'=>[0,100000000],'max_price'=>[0,100000000],'card_radius'=>[0,32],'card_gap'=>[4,40],'image_height'=>[140,420],'title_size'=>[12,28],'price_size'=>[12,28],'meta_size'=>[10,20],'media_width'=>[30,70],'content_gap'=>[8,64],'media_radius'=>[0,32],'item_gap'=>[4,40],'item_radius'=>[0,32],'item_padding'=>[8,40],'item_icon_size'=>[14,40]];[$min,$max]=$ranges[$key];$safe[$key]=max($min,min($max,(int)$value));continue;}
                if(in_array($key,['show_title','show_search','show_location','show_guests','show_price','show_rating','show_review_count','show_gallery','show_favorite','show_badges','show_property_count'],true)){$safe[$key]=(bool)$value;continue;}
                $choices=['hero_layout'=>['overlay','split','minimal'],'hero_text_position'=>['left','center','right'],'search_position'=>['below','left','right','inside-bottom'],'search_layout'=>['horizontal','vertical'],'alignment'=>['left','center','right'],'image_ratio'=>['4:3','1:1','3:2','16:9','portrait'],'card_shadow'=>['none','soft','medium','strong'],'section_width'=>['full','wide','normal','narrow'],'section_spacing'=>['compact','normal','airy'],'content_alignment'=>['left','center','right'],'content_position'=>['left','right','top','bottom'],'media_ratio'=>['4:3','1:1','3:2','16:9','portrait'],'button_alignment'=>['left','center','right'],'item_alignment'=>['left','center']];
                if(isset($choices[$key])){$candidate=(string)$value;$safe[$key]=in_array($candidate,$choices[$key],true)?$candidate:$choices[$key][0];continue;}
                $value=trim((string)$value);
                if(in_array($key,['image_url','mobile_image_url','button_url'],true) && $value!=='' && !preg_match('~^(https?://|/)~i',$value))$value='';
                $safe[$key]=mb_substr($value,0,($key==='items_text'?8000:(($key==='description'||$key==='subtitle')?1200:500)));
            }
            $clean[]=['id'=>$id,'type'=>$type,'style'=>$style,'settings'=>$safe];
        }
        $json=json_encode(['widgets'=>$clean],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
        $pdo=Database::connection();
        $stmt=$pdo->prepare('INSERT INTO page_builder_layouts(theme_slug,page_key,layout_json,status,version,created_by,updated_by) VALUES(?,?,?,?,1,?,?) ON DUPLICATE KEY UPDATE layout_json=VALUES(layout_json),status=VALUES(status),version=version+1,updated_by=VALUES(updated_by),updated_at=NOW()');
        $stmt->execute([$theme,$page,$json,$status,$userId,$userId]);
    }
}
