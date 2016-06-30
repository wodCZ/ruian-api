<?php


namespace App\Ruian;


use Hobnob\XmlStreamReader\Parser;

class Ruian
{
    public function getLatestFullCountryLink()
    {

        $source = 'http://vdp.cuzk.cz/vdp/ruian/vymennyformat/seznamlinku?vf.pu=S&_vf.pu=on&_vf.pu=on&vf.cr=U&vf.up=ST&vf.ds=Z&vf.vu=Z&_vf.vu=on&_vf.vu=on&_vf.vu=on&_vf.vu=on&search=Vyhledat';

        $links = file_get_contents($source);
        $links = array_filter(explode("\r\n", $links));
        rsort($links);
        $latest = reset($links);

        return $latest;
    }

    public function isImported($link)
    {
        return \DB::collection('links')->where('link', '=', $link)->count() === 1;
    }

    public function markImported($link)
    {
        \DB::collection('links')->insert(['link' => $link]);
    }

    public function getLatestFullCitiesLinks()
    {

        $source = 'http://vdp.cuzk.cz/vdp/ruian/vymennyformat/seznamlinku?vf.pu=S&_vf.pu=on&_vf.pu=on&vf.cr=U&vf.up=OB&vf.ds=Z&vf.vu=Z&_vf.vu=on&_vf.vu=on&_vf.vu=on&_vf.vu=on&vf.uo=A&search=Vyhledat';

        $links = file_get_contents($source);
        $links = array_filter(explode("\r\n", $links));
        rsort($links);
        $first = reset($links);
        $date = explode('_', basename($first))[0];
        $links = array_filter($links, function($link) use($date){
            return explode('_', basename($link))[0] === $date;
        });

        return $links;
    }

    public function downloadFile($link)
    {
        $tmpFile = storage_path('ruian/' . basename($link));
        $tmpFile = str_replace('.xml.gz', '.xml', $tmpFile);
        if (!file_exists($tmpFile)) {
            copy('compress.zlib://' . $link, $tmpFile);
        }

        return $tmpFile;
    }

    public function getNodes($file, callable $callback)
    {
        $parser = new Parser();

        $nodes = [
            'stat' => '/vf:VymennyFormat/vf:Data/vf:Staty/vf:Stat',
//            'regionSoudrznosti' => '/vf:VymennyFormat/vf:Data/vf:RegionySoudrznosti/vf:RegionSoudrznosti',
            'kraj' => '/vf:VymennyFormat/vf:Data/vf:Kraje/vf:Kraj',
//            'vusc' => '/vf:VymennyFormat/vf:Data/vf:Vusc/vf:Vusc',
            'okres' => '/vf:VymennyFormat/vf:Data/vf:Okresy/vf:Okres',
//            'orp' => '/vf:VymennyFormat/vf:Data/vf:Orp/vf:Orp',
//            'pou' => '/vf:VymennyFormat/vf:Data/vf:Pou/vf:Pou',
            'obec' => '/vf:VymennyFormat/vf:Data/vf:Obce/vf:Obec',
            'castObce' => '/vf:VymennyFormat/vf:Data/vf:CastiObci/vf:CastObce',
//            'mop' => '/vf:VymennyFormat/vf:Data/vf:Mop/vf:Mop',
//            'spravniObvod' => '/vf:VymennyFormat/vf:Data/vf:SpravniObvody/vf:SpravniObvod',
//            'momc' => '/vf:VymennyFormat/vf:Data/vf:Momc/vf:Momc',
//            'katastralniUzemi' => '/vf:VymennyFormat/vf:Data/vf:KatastralniUzemi/vf:KatastralniUzemi',
//            'parcela' => '/vf:VymennyFormat/vf:Data/vf:Parcely/vf:Parcela',
            'ulice' => '/vf:VymennyFormat/vf:Data/vf:Ulice/vf:Ulice',
            'stavebniObjekt' => '/vf:VymennyFormat/vf:Data/vf:StavebniObjekty/vf:StavebniObjekt',
            'adresniMisto' => '/vf:VymennyFormat/vf:Data/vf:AdresniMista/vf:AdresniMisto',
//            'zsj' => '/vf:VymennyFormat/vf:Data/vf:Zsj/vf:Zsj',
        ];

        foreach ($nodes as $name => $xpath) {
            $parser->registerCallback(
                $xpath,
                function (Parser $parser, \SimpleXMLElement $node) use ($name, $callback) {
                    $entry = $this->parseXML($node);
                    $this->normalizeEntry($entry);
                    $callback(['type' => $name, 'entry' => $entry]);
                }
            );
        }


        $parser->parse(fopen($file, 'r'), 1024*100);
    }


    private function parseXML(\SimpleXMLElement $node)
    {

        $xml = $node->asXML();
        $xml = preg_replace('|<(\w+):(\w+)(.*?)>|', '<$2>', $xml);
        $xml = preg_replace('|<\/(\w+):(\w+)>|', '</$2>', $xml);
        $node = simplexml_load_string($xml);

        $result = [];
        foreach ($node->children() as $child) {
            if ($child->count() === 0) {
                $result[$child->getName()] = (string)$child;
            } else {
                $result[$child->getName()] = $this->parseXML($child);
            }
        }


        return $result;
    }

    private function normalizeEntry(&$entry, $context = '')
    {
        foreach ($entry as $key => &$value) {
            if (is_array($value)) {
                $this->normalizeEntry($value, implode('.', array_filter([$context, $key])));
            } else {
                if ($key === 'pos' && $context == 'geometrie.definicnibod.point') {
                    $converter = new Converter();
                    $exploded = array_map('abs', explode(' ', $value));
                    $value = $converter->JTSKtoWGS84($exploded[1], $exploded[0]);
                }
                if ($key === 'pos' && $context == 'geometrie.definicnibod.multipoint.pointmembers.point') {
                    $converter = new Converter();
                    $exploded = array_map('abs', explode(' ', $value));
                    $value = $converter->JTSKtoWGS84($exploded[1], $exploded[0]);
                }
            }
        }
    }
}
