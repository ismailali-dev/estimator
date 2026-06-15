<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ContentPage;

class ContentPagesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        $contentpages = [
            ["title"=>"Page One", "description"=>"Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus maximus diam non libero sodales, scelerisque.", "status"=>0],
            ["title"=>"Page Two", "description"=>"Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus maximus diam non libero sodales, scelerisque.", "status"=>0],
            ["title"=>"Page Three", "description"=>"Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus maximus diam non libero sodales, scelerisque.", "status"=>0],
            ["title"=>"Page Four", "description"=>"Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus maximus diam non libero sodales, scelerisque.", "status"=>0],
            ["title"=>"Page Five", "description"=>"Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus maximus diam non libero sodales, scelerisque.", "status"=>0],
        ];

        foreach ($contentpages as $page){
            $isExists = ContentPage::where("title", "=", $page["title"])->first();
            if (empty($isExists)){
                $newPage = new ContentPage();
                $newPage->title = $page["title"];
                $newPage->description = $page["description"];
                $newPage->status = $page["status"];
                $newPage->save();
            }
        }
    }
}
