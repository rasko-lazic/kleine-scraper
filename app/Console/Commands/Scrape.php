<?php

namespace App\Console\Commands;

use App\Mail\ClassifiedAlert;
use App\Models\Classified;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpClient\HttpClient;
use App\Mail\ClassifiedUpdate;
use Illuminate\Support\Facades\Mail;

class Scrape extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scrape';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scrapes the latest batch of classifieds and pushes it to database';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $blacklistedGames = ["SET", "Ra"];

        $currentAdCount = Classified::count();
        Log::channel('scrape')->info('Started a new scrape.');
        $notifications = [];
        $browser = new HttpBrowser(HttpClient::create());

        try {
            $gameList = $browser->request('GET', "https://boardgamegeek.com/collection/user/O_____o?geekranks=Board%20Game%20Rank&objecttype=thing&sort=wishlist&sortdir=asc&columns=title%7Cthumbnail%7Cstatus%7Cwishlistcomment%7Ccommands&wishlist=1&ff=1&subtype=boardgame");
            $gameNames = [];
            $gameList->filter('.primary')->each(function (Crawler $node) use (&$gameNames) {
                $gameNames[] = $node->innerText();
            });
            $gameNames = array_diff($gameNames, $blacklistedGames);

            for ($i = 1; $i <= 50; $i++) {
                $crawler = $browser->request('GET', "https://www.kleinanzeigen.de/s-spielzeug/gesellschaftsspiele/seite:$i/c23+spielzeug.art_s:gesellschaftsspiele");
                $adElements = $crawler->filter('article.aditem');
                $adElements->each(function (Crawler $node) use ($browser, $gameNames, &$notifications) {
                    $ad = new Classified();

                    $url = $node->filter('.aditem-image > a')->link()->getUri();

//                dd(levenshtein("Secret Hitler NEU! 29,99€ inkl. Kostenlosen Versand", "Secret Hitler", 1, 1, 0));


                    if (Classified::where('url', $url)->exists()) return;


                    $location = $node->filter('.aditem-main--top--left')->innerText();
                    preg_match('/^(\d+)\s(.+)/', $location, $locationBreakdown);
                    $fullPrice = $node->filter('.aditem-main--top--left')->innerText();
                    preg_match('/^(\d+)\s(.+)/', $fullPrice, $priceBreakdown);
                    $fullTime = $node->filter('.aditem-main--top--right')->innerText();
                    preg_match('/^(.+)\s(\d{2}):(\d{2})/', $fullTime, $timeBreakdown);
                    $time = now()->setTime($timeBreakdown[2] ?? null, $timeBreakdown[3] ?? null)->toDateTime();

                    $fullAd = $browser->request('GET', $url);
                    $fullAd->filter('.addetailslist--detail')->each(function (Crawler $detailsNode) use (&$ad) {
                        if ($detailsNode->innerText() === 'Zustand') {
                            $ad->condition = $detailsNode->filter('.addetailslist--detail--value')->innerText();
                        }
                    });
                    $description = $fullAd->filter('#viewad-description-text')->innerText();

                    foreach (['english', 'englische'] as $string) {
                        if (stripos($description, $string) !== false) {
                            $ad->in_english = true;
                            break;
                        }
                    }

                    $profile = $fullAd->filter('.userprofile-vip > a');

                    $ad->title = $node->filter('.text-module-begin > *')->first()->innerText();
                    $ad->description = $description;
                    $ad->date = $time;
                    $ad->price = $priceBreakdown[1] ?? null;
                    $ad->full_price = $fullPrice;
                    $ad->full_address = $fullAd->filter('.boxedarticle--details--full')->text();
                    $ad->zipcode = $locationBreakdown[1] ?? null;
                    $ad->city = $locationBreakdown[2] ?? null;
                    $ad->seller = $profile->count() === 0 ? null : $profile->link()->getUri();
                    $ad->seller_name = $profile->count() === 0 ? null : $profile->innerText();
                    $ad->negotiable = stripos($fullPrice, 'VB') !== false;
                    $ad->top_promotion = $node->filter('.badge-topad')->count() > 0 ||
                        $node->filter('.is-topad')->count() > 0;
                    $ad->shipping_possible = $node->filter('.icon-package')->count() > 0;
                    $ad->buy_directly = $node->filter('.icon-send-money')->count() > 0;
                    $ad->url = $url;

                    foreach ($gameNames as $name) {
//                        if (levenshtein($ad->title, $name, 1, 1, 0) < 5) {
                        if (stripos($ad->title, $name)) {
                            $notifications[] = [
                                'title' => $ad->title,
                                'url' => $ad->url
                            ];
                            $ad->flagged = true;
                            break;
                        }
                    }

                    $ad->save();

                    $microseconds = rand(1000000, 2000000);
                    usleep($microseconds);
                });
            }

            if (count($notifications) > 0) {
                Mail::to('raskolazic@gmail.com')->send(new ClassifiedUpdate($notifications));
            }
            $newCount = Classified::count();
            Log::channel('scrape')->info("Finished scraping. Old ad count: $currentAdCount, new count: $newCount");
        } catch (\Throwable $e) {
            Mail::to('raskolazic@gmail.com')->send(new ClassifiedAlert());
            Log::channel('scrape')->info("Finished scraping due to error");
            throw $e;
        }
    }
}
