<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use FeedIo\Factory;
use Storage;
use GuzzleHttp\Client;

class FeedController extends Controller
{
    public function index($slug)
    {
        $categories = [
        "Politics" =>
          [
            "Washington Post" => "http://feeds.washingtonpost.com/rss/politics",
            "New York Times" => "https://www.nytimes.com/services/xml/rss/nyt/Politics.xml"
          ],
        "Opinion" =>
          [
            "Wall Street Journal" => "http://www.wsj.com/xml/rss/3_7041.xml",
            "Washington Post" => "http://feeds.washingtonpost.com/rss/opinions"
          ],
        "Sports" =>
          [
            "Washington Post" => "http://feeds.washingtonpost.com/rss/sports",
            "New York Times" => "https://www.nytimes.com/services/xml/rss/nyt/Sports.xml"
          ],
        "National" =>
          [
            "Washington Post" => "http://feeds.washingtonpost.com/rss/national",
            "New York Times" => "https://www.nytimes.com/services/xml/rss/nyt/US.xml"
          ],
        "World" =>
          [
            "Wall Street Journal" => "http://www.wsj.com/xml/rss/3_7085.xml",
            "Washington Post" => "http://feeds.washingtonpost.com/rss/world",
            "New York Times" => "https://www.nytimes.com/services/xml/rss/nyt/World.xml",
            "The Economist" => "https://www.economist.com/sections/international/rss.xml"
          ],
        "Business" =>
          [
            "Wall Street Journal" => "http://www.wsj.com/xml/rss/3_7014.xml",
            "Washington Post" => "http://feeds.washingtonpost.com/rss/business",
            "New York Times" => "http://feeds.nytimes.com/nyt/rss/Business"
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
            "TechCrunch" => "http://feeds.feedburner.com/TechCrunch/",
            "Wall Street Journal" => "http://www.wsj.com/xml/rss/3_7455.xml",
            "New York Times" => "http://feeds.nytimes.com/nyt/rss/Technology",
            "The Economist" => "https://www.economist.com/sections/science-technology/rss.xml"
          ],
        "Markets" =>
          [
            "Wall Street Journal" => "http://www.wsj.com/xml/rss/3_7031.xml",
            "New York Times" => "https://www.nytimes.com/services/xml/rss/nyt/Economy.xml",
            "The Economist" => "https://www.economist.com/sections/markets-data/rss.xml"
          ],
        "Science" =>
          [
            "New York Times" => "http://rss.nytimes.com/services/xml/rss/nyt/Science.xml",
            "BBC" => "http://feeds.bbci.co.uk/news/video_and_audio/science_and_environment/rss.xml"
          ]
      ];

        $consul_url = "http://".getenv('CONSUL_HOST').":8500";
        $client = new Client(['base_uri' => $consul_url]);
        $client->request('PUT', '/v1/kv/categories', ['body' => json_encode($categories)]);

        $content=file_get_contents("http://".getenv('CONSUL_HOST').":8500/v1/kv/categories");
        $data=get_object_vars(json_decode($content)[0]);
        $categories=(array)json_decode(base64_decode($data["Value"], true));


        if (! isset($categories[$slug])) {
            return "Error: The requested category does not exist";
        }

        $d = new \DateTime();

        $publications = [];

        foreach ($categories[$slug] as $publicationKey => $publication) {
            $timestamp=0;
            $filename=$publicationKey.$slug;
            $timestampget=@file_get_contents("http://".getenv('CONSUL_HOST').":8500/v1/kv/".rawurlencode($filename));
            if ($timestampget){
              $timestampgetobject=get_object_vars(json_decode($timestampget)[0]);
              $timestamp=new \DateTime(base64_decode($timestampgetobject["Value"], true));
            }
            if (!$timestamp) {
              $n = new \DateTime('2000-01-01 00:00:00');
              $timestamp = $n;
              $client->request('PUT', '/v1/kv/'.$filename, ['body' => (string)$n->format('Y-m-d H:i:s')]);
            }
            $feedIo = Factory::create()->getFeedIo();
            $feedIo->getDateTimeBuilder()->setFeedTimezone(new \DateTimeZone('America/New_York'));
            $olddate = $timestamp;
            $dateDiff= $olddate->getTimestamp()-$d->getTimestamp();
            $result = $feedIo->readSince($publication, new \DateTime($dateDiff." seconds"))->getFeed();
            $publications[$publicationKey] = $result;
            $client->request('PUT', '/v1/kv/'.$filename, ['body' => (string)$d->format('Y-m-d H:i:s')]);
        }

        $shell=[
        "category" => $slug,
        "publications" => $publications
        ];
        return $shell;
    }
}
