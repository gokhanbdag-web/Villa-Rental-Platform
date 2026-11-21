<?php
declare(strict_types=1);

namespace App\Core;

final class HomepageDesign
{
    // The section key also identifies its existing theme component.
    public const SECTIONS = [
        'hero'=>['Kapak ve villa araması',1],
        'trust'=>['Güven kartları',2],
        'featured'=>['Öne çıkan konaklamalar',3],
        'latest'=>['Son eklenen villalar',3],
        'banner'=>['Aile bannerı',4],
        'concepts'=>['Tatil konseptleri',5],
        'hosts'=>['Ev sahibi çağrısı',6],
        'reviews'=>['Misafir yorumları',8],
        'support'=>['İstatistikler',10],
        'destinations'=>['Destinasyonlar',2147483647],
    ];

    public static function fields(): array
    {
        $fields = [
            'hero'=>['home_eyebrow'=>'Üst başlık','home_title'=>'Başlık','home_subtitle'=>'Açıklama','hero_image'=>'Arka plan görseli','home_mobile_search_button'=>'Mobil arama düğmesi'],
            'featured'=>['featured_title'=>'Başlık','featured_subtitle'=>'Açıklama','home_properties_link'=>'Bağlantı metni'],
            'latest'=>['home_latest_eyebrow'=>'Üst başlık','home_latest_title'=>'Başlık','home_latest_subtitle'=>'Açıklama','home_latest_link'=>'Bağlantı metni'],
            'concepts'=>['home_concepts_eyebrow'=>'Üst başlık','home_concepts_title'=>'Başlık','home_concepts_subtitle'=>'Açıklama'],
            'banner'=>['banner_eyebrow'=>'Üst başlık','banner_title'=>'Başlık','banner_description'=>'Açıklama','banner_image'=>'Görsel'],
            'hosts'=>['home_host_title'=>'Başlık','home_host_subtitle'=>'Açıklama','home_host_cta'=>'Düğme metni','home_host_banner_image'=>'Görsel'],
            'reviews'=>['home_reviews_eyebrow'=>'Üst başlık','home_reviews_title'=>'Başlık','home_reviews_subtitle'=>'Açıklama'],
            'support'=>['home_stats_eyebrow'=>'Üst başlık','home_stats_title'=>'Başlık','home_stats_subtitle'=>'Açıklama'],
            'destinations'=>['home_regions_eyebrow'=>'Üst başlık','home_regions_title'=>'Başlık','home_regions_subtitle'=>'Açıklama','home_destinations_link'=>'Bağlantı metni'],
        ];
        for ($i=1; $i<=4; $i++) {
            $fields['trust']["home_trust_{$i}_title"] = "Kart $i başlığı";
            $fields['trust']["home_trust_{$i}_text"] = "Kart $i açıklaması";
        }
        return $fields;
    }

    public static function key(?array $page): string
    {
        return !$page || $page['slug'] === 'home' ? 'theme-home' : 'theme-home:'.(int)$page['id'];
    }

    public static function read(\PDO $pdo, string $key, string $locale): array
    {
        $query = $pdo->prepare('SELECT layout_json FROM cms_page_layouts WHERE page_key=? AND language_code=?');
        $query->execute([$key,$locale]);
        return json_decode((string)$query->fetchColumn(),true) ?: [];
    }

    public static function validate(string $json): array
    {
        $value = json_decode($json,true);
        if (strlen($json)>250000 || !is_array($value)) throw new \InvalidArgumentException();
        $fields = self::fields();
        foreach ($value as $section=>$settings) {
            if (!isset(self::SECTIONS[$section]) || !is_array($settings)) throw new \InvalidArgumentException();
            foreach ($settings as $key=>$entry) {
                if ($key==='visible' && is_bool($entry)) continue;
                if ($key==='order' && is_int($entry) && $entry>=0 && $entry<=2147483647) continue;
                if (!isset($fields[$section][$key]) || !is_string($entry) || strlen($entry)>4000) throw new \InvalidArgumentException();
                if (str_ends_with($key,'image') && $entry!=='' && !preg_match('~^(?:https?://[^/\s]+/|/(?!/))[^\s"\'()<>\\\\]*$~iD',$entry)) throw new \InvalidArgumentException();
            }
        }
        return $value;
    }
}
