<?php


namespace App\Ruian;


class Ruian
{
    public function getLatestFullLink()
    {

        $source = 'http://vdp.cuzk.cz/vdp/ruian/vymennyformat/seznamlinku?vf.pu=S&_vf.pu=on&_vf.pu=on&vf.cr=U&vf.up=ST&vf.ds=Z&vf.vu=Z&_vf.vu=on&_vf.vu=on&_vf.vu=on&_vf.vu=on&search=Vyhledat';

        $links = file_get_contents($source);
        $links = array_filter(explode("\r\n", $links));
        rsort($links);
        $latest = reset($links);

        return $latest;
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
}
