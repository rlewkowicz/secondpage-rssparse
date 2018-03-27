<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use FeedIo\Factory;

class FeedController extends Controller
{
    public function index($slug)
    {
        $catagories = [
        "Politics" =>
          [
            "Washington Post" => "http://feeds.washingtonpost.com/rss/politics"
          ],
        "Opinion" =>
          [
            "Wall Street Journal" => "http://www.wsj.com/xml/rss/3_7041.xml",
            "Washington Post" => "http://feeds.washingtonpost.com/rss/opinions"
          ],
        "Sports" =>
          [
            "Washington Post" => "http://feeds.washingtonpost.com/rss/sports"
          ],
        "National" =>
          [
            "Washington Post" => "http://feeds.washingtonpost.com/rss/national"
          ],
        "World" =>
          [
            "Wall Street Journal" => "http://www.wsj.com/xml/rss/3_7085.xml",
            "Washington Post" => "http://feeds.washingtonpost.com/rss/world"
          ],
        "Business" =>
          [
            "Wall Street Journal" => "http://www.wsj.com/xml/rss/3_7014.xml",
            "Washington Post" => "http://feeds.washingtonpost.com/rss/business"
          ],
        "Lifestyle" =>
          [
            "Wall Street Journal" => "http://www.wsj.com/xml/rss/3_7201.xml",
            "Washington Post" => "http://feeds.washingtonpost.com/rss/lifestyle"
          ],
        "Entertainment" =>
          [
            "Washington Post" => "http://feeds.washingtonpost.com/rss/entertainment"
          ],
        "Technology" =>
          [
            "Wall Street Journal" => "http://www.wsj.com/xml/rss/3_7455.xml",
          ],
        "Markets" =>
          [
            "Wall Street Journal" => "http://www.wsj.com/xml/rss/3_7031.xml",
          ]
      ];
        if (! isset($catagories[$slug])) {
            return "Error: The requested category does not exist";
        }

        $publications = [];

        foreach ($catagories[$slug] as $publicationKey => $publication) {
            $feedIo = Factory::create()->getFeedIo();
            $result = $feedIo->read($publication)->getFeed();
            $publications[$publicationKey] = $result;
        }

        $shell=[
        "catagory" => $slug,
        "publications" => $publications
        ];
        return $shell;
    }
}
