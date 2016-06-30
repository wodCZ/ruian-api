<?php

namespace App\Console\Commands;

use App\Ruian\Converter;
use Hobnob\XmlStreamReader\Parser;
use Illuminate\Console\Command;
use Jenssegers\Mongodb\Connection;

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


        $fullLink = \Ruian::getLatestFullCountryLink();

        $this->importLink($fullLink);

        $fullCitiesLinks = \Ruian::getLatestFullCitiesLinks();
        $this->output->success('Got '.count($fullCitiesLinks).' cities links');

        foreach ($fullCitiesLinks as $link) {
            $this->importLink($link);
        }
    }

    private function importLink($link)
    {
        if(\Ruian::isImported($link)){
            return;
        }

        $file = \Ruian::downloadFile($link);
        $this->output->success($link . ' downloaded.');


        $progress = $this->output->createProgressBar(); 
        \Ruian::getNodes($file, function($node) use($progress) {
            $node['entry']['_id'] = $node['entry']['kod'];
            unset($node['entry']['kod']);
            \DB::collection($node['type'])
                ->updateOrInsert(
                    ['_id' => $node['entry']['_id']],
                    $node['entry']
                );
            $progress->advance();
        });
        
        $progress->finish();

        \Ruian::markImported($link);

    }

}
