<?php


namespace App\Service;


class Slugify
{
    public function generate(string $input) : string
    {
        $preslug = trim(iconv('utf-8', 'ascii//TRANSLIT', $input));
        $slug = preg_replace('/[^a-z0-9 ]+/i', '', $preslug);
        return preg_replace('/\s+/', '-', strtolower($slug));
    }
}