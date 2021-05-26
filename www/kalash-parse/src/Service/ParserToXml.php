<?php


namespace App\Service;

include 'wp_default.php';

use App\Entity\Post;
use SimpleXMLElement;
use Symfony\Component\DomCrawler\Crawler;


class ParserToXml
{

    /**
     * @var string
     */
    private string $xmlstr = /** @lang text */
        <<<XML
        <?xml version="1.0" encoding="UTF-8" ?>
        
        <rss version="2.0"
             xmlns:excerpt="http://wordpress.org/export/1.2/excerpt/"
             xmlns:content="http://purl.org/rss/1.0/modules/content/"
             xmlns:wfw="http://wellformedweb.org/CommentAPI/"
             xmlns:dc="http://purl.org/dc/elements/1.1/"
             xmlns:wp="http://wordpress.org/export/1.2/"
        >
            <channel>
                <title></title>
                <link></link>
                <description></description>
                <pubDate></pubDate>
                <language></language>
                <wp:wxr_version>1.2</wp:wxr_version>
                <wp:base_site_url></wp:base_site_url>
                <wp:base_blog_url></wp:base_blog_url>
        
                <wp:author>
                    <wp:author_id>1</wp:author_id>
                    <wp:author_login></wp:author_login>
                    <wp:author_email></wp:author_email>
                    <wp:author_display_name></wp:author_display_name>
                    <wp:author_first_name><![CDATA[]]></wp:author_first_name>
                    <wp:author_last_name><![CDATA[]]></wp:author_last_name>
                </wp:author>
        

        
                <generator>https://wordpress.org/?v=5.7.2</generator>
            </channel>
        </rss>
        XML;

    /**
     * @var Crawler
     */
    private Crawler $domCrawler;

    /**
     * @var Post[]
     */
    private array $posts = [];

    public function __construct(Crawler $domCrawler, )
    {
        $this->domCrawler = $domCrawler;
    }

    /**
     * @param array $urls
     * @return SimpleXMLElement
     * @throws \Exception
     */
    public function getNews(array $urls = []): SimpleXMLElement
    {
        //Инициализируем сеанс
        $curl = curl_init();
        $dataPosts = new SimpleXMLElement($this->xmlstr);

        foreach ($urls as $url) {
            //Указываем адрес страницы
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

            $html = curl_exec($curl);

            //Отлавливаем ошибки подключения
            if ($html === false) {
                echo "Ошибка CURL: " . curl_error($curl);
                die();
            } else {
                $this->parsed[] = $this->extractFromHtml((string)$html);
            }
        }

        return $dataPosts;
    }

    public function extractFromHtml($html)
    {
        $this->domCrawler->clear();
        $this->domCrawler->add($html);

        $this->domCrawler->filterXPath("//body//div[contains(@class, 'press-rubric')]")->each(
            function (Crawler $crawler) {
                $url = 'http://museum-mtk.ru/presscenter/news/';
                $nameRubric = trim($crawler->filterXPath("//h2")->text());
                foreach ($crawler->filterXPath("//h3//a")->extract(['href']) as $node) {
                    $this->posts[] = (new Post())->setRubric($nameRubric)->setUrl($url . $node);
                }
            }
        );

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        $this->domCrawler->clear();

        foreach ($this->posts as $post) {
            curl_setopt($curl, CURLOPT_URL, $post->getUrl());
            $html = curl_exec($curl);
            $this->domCrawler->add($html);

            $titlePost = trim($this->domCrawler->filterXPath("//body//div[contains(@class, 'right-part')]//h1")->text());
            $datePost = trim($this->domCrawler->filterXPath("//body//div[contains(@class, 'right-part')]//p")->first()->text());
            $rightPart = $this->domCrawler->filterXPath("//body//div[contains(@class, 'right-part')]");

            $bodyPost = $rightPart->children()->reduce(
                function (Crawler $crawler) {
                    return !($crawler->nodeName() === 'h1' || in_array(current($crawler->extract(['class'])), ['statusbar','overflow']));
                }
            );

            $html = '';
            foreach ($bodyPost as $node) {
                $html .= $node->ownerDocument->saveHTML($node);
            }

            $
            $html;
            $this->domCrawler->clear();
        }




        return [
            'title' => $title,
            'genres' => $genres,
            'description' => $description,
            'release_date' => $releaseDate,
        ];
    }

}