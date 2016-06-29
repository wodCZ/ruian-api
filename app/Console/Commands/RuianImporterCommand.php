<?php

namespace App\Console\Commands;

use App\Ruian\Converter;
use Hobnob\XmlStreamReader\Parser;
use Illuminate\Console\Command;

class RuianImporterCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ruian:import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $fullLink = \Ruian::getLatestFullLink();

        $file = \Ruian::downloadFile($fullLink);

        $this->output->success($fullLink . ' downloaded.');

        $parser = new Parser();

        $nodes = [
            'stat' => '/vf:VymennyFormat/vf:Data/vf:Staty/vf:Stat',
//            'regionSoudrznosti' => '/vf:VymennyFormat/vf:Data/vf:RegionySoudrznosti/vf:RegionSoudrznosti',
            'kraj' => '/vf:VymennyFormat/vf:Data/vf:Kraje/vf:Kraj',
//            'vusc' => '/vf:VymennyFormat/vf:Data/vf:Vusc/vf:Vusc',
            'okres' => '/vf:VymennyFormat/vf:Data/vf:Okresy/vf:Okres',
//            'orp' => '/vf:VymennyFormat/vf:Data/vf:Orp/vf:Orp',
//            'pou' => '/vf:VymennyFormat/vf:Data/vf:Pou/vf:Pou',
//            'obec' => '/vf:VymennyFormat/vf:Data/vf:Obce/vf:Obec',
//            'castObce' => '/vf:VymennyFormat/vf:Data/vf:CastiObci/vf:CastObce',
//            'mop' => '/vf:VymennyFormat/vf:Data/vf:Mop/vf:Mop',
            'spravniObvod' => '/vf:VymennyFormat/vf:Data/vf:SpravniObvody/vf:SpravniObvod',
            'momc' => '/vf:VymennyFormat/vf:Data/vf:Momc/vf:Momc',
//            'katastralniUzemi' => '/vf:VymennyFormat/vf:Data/vf:KatastralniUzemi/vf:KatastralniUzemi',
//            'parcela' => '/vf:VymennyFormat/vf:Data/vf:Parcely/vf:Parcela',
            'ulice' => '/vf:VymennyFormat/vf:Data/vf:Ulice/vf:Ulice',
//            'stavebniObjekt' => '/vf:VymennyFormat/vf:Data/vf:StavebniObjekty/vf:StavebniObjekt',
            'adresniMisto' => '/vf:VymennyFormat/vf:Data/vf:AdresniMista/vf:AdresniMisto',
//            'zsj' => '/vf:VymennyFormat/vf:Data/vf:Zsj/vf:Zsj',
        ];

        foreach ($nodes as $name => $xpath) {
            $parser->registerCallback(
                $xpath,
                function (Parser $parser, \SimpleXMLElement $node) use($name) {
                    $entry = $this->parseXML($node);
                    $this->normalizeEntry($entry);
                    dump($name);
//                    dump($entry);
                }
            );
        }


        $parser->parse(fopen($file, 'r'));
    }

    public function parseXML(\SimpleXMLElement $node)
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
            if(is_array($value)){
                $this->normalizeEntry($value, implode('.', array_filter([$context, $key])));
            } else {
                if($key === 'pos' && $context == 'geometrie.definicnibod.point'){
                    $converter = new Converter();
                    $exploded = array_map('abs',explode(' ', $value));
                    $value = $converter->JTSKtoWGS84($exploded[1], $exploded[0]);
                }
            }
        }
    }
}
