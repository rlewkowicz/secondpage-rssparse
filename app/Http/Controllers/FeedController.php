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
            "New York Times" => "https://www.nytimes.com/services/xml/rss/nyt/World.xml"
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
            "Hacker News" => "https://news.ycombinator.com/rss"
          ],
        "Markets" =>
          [
            "Wall Street Journal" => "http://www.wsj.com/xml/rss/3_7031.xml",
            "New York Times" => "https://www.nytimes.com/services/xml/rss/nyt/Economy.xml"
          ],
        "Science" =>
          [
            "New York Times" => "http://rss.nytimes.com/services/xml/rss/nyt/Science.xml",
            "BBC" => "http://feeds.bbci.co.uk/news/video_and_audio/science_and_environment/rss.xml"
          ]
      ];

        $consul_url = 'http://127.0.0.1:8500';
        $client = new Client(['base_uri' => $consul_url]);
        $client->request('PUT', '/v1/kv/categories', ['body' => json_encode($categories)]);

        $content=file_get_contents("http://127.0.0.1:8500/v1/kv/categories");
        $data=get_object_vars(json_decode($content)[0]);
        $categories=(array)json_decode(base64_decode($data["Value"], true));


        if (! isset($categories[$slug])) {
            return "Error: The requested category does not exist";
        }

        $d = new \DateTime();

        $publications = [];

        foreach ($categories[$slug] as $publicationKey => $publication) {
            $filename=$publicationKey.$slug.".timestamp";
            if (!Storage::disk('public')->exists($filename)) {
                $n = new \DateTime('2000-01-01 00:00:00');
                Storage::disk('public')->put($filename, $n->format('Y-m-d H:i:s'));
            }
            $feedIo = Factory::create()->getFeedIo();
            $olddate = new \DateTime(Storage::disk('public')->get($filename));
            $dateDiff= $olddate->getTimestamp()-$d->getTimestamp();
            $result = $feedIo->readSince($publication, new \DateTime($dateDiff." seconds"))->getFeed();
            $publications[$publicationKey] = $result;
            Storage::disk('public')->put($filename, $d->format('Y-m-d H:i:s'));
        }

        $shell=[
        "category" => $slug,
        "publications" => $publications
        ];
        return $shell;
    }
}
