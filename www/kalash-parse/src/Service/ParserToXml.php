<?php


namespace App\Service;

use App\Entity\Post;
use SimpleXMLElement;
use Symfony\Component\DomCrawler\Crawler;


class ParserToXml
{
    private const XMLNS_EXCERPT =  'http://wordpress.org/export/1.2/excerpt/';

    private const XMLNS_WP =  'http://wordpress.org/export/1.2/';

    private const XMLNS_DC =  'http://purl.org/dc/elements/1.1/';

    private const XMLNS_CONTENT =  'http://purl.org/rss/1.0/modules/content/';

    private const XMLNS_WFW =  'http://wellformedweb.org/CommentAPI/';

    /**
     * @var string
     */
    private const XMLSTR = /** @lang text */
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
                <title>Калашников</title>
                <link>http://kalash</link>
                <description></description>
                <pubDate>Wed, 26 May 2021 09:47:12 +0000</pubDate>
                <language>ru-RU</language>
                <wp:wxr_version>1.2</wp:wxr_version>
                <wp:base_site_url>http://kalash</wp:base_site_url>
                <wp:base_blog_url>http://kalash</wp:base_blog_url>
        
                <wp:author>
                    <wp:author_id>1</wp:author_id>
                    <wp:author_login><![CDATA[]]></wp:author_login>
                    <wp:author_email><![CDATA[]]></wp:author_email>
                    <wp:author_display_name><![CDATA[]]></wp:author_display_name>
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

    private SimpleXMLElement $xmlElement;
    private int $count = 20;
    private string $host = 'http://kalash';

    public function __construct(Crawler $domCrawler)
    {
        $this->domCrawler = $domCrawler;
        $this->xmlElement = new SimpleXMLElement(self::XMLSTR);
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
                $this->extractFromHtml((string)$html);
                return $this->xmlElement;
            }
        }

        return $this->xmlElement;
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
                    return !($crawler->nodeName() === 'h1' || in_array(current($crawler->extract(['class'])), ['statusbar', 'overflow']));
                }
            );

            $html = '';
            foreach ($bodyPost as $node) {
                $html .= $node->ownerDocument->saveHTML($node);
            }

            $this->createItem($html, $titlePost, $datePost,  $post->getRubric());
            return;
            $this->domCrawler->clear();
        }

    }


    private function createItem(string $html, string $titlePost, string $datePost, string $getRubric)
    {
        $postNumber = $this->count++;

        $postName = str_replace(' ','-',$titlePost);
        $postName = str_replace(array('"','/',':','.',',','[',']','“','”'),'',strtolower($postName));

        $item = $this->xmlElement->channel[0]->addChild('item');
        $item->addChild('title', $titlePost);
        $item->addChild('link',$this->host . '/?p=' . $postNumber);
        $item->addChild('pubDate',"Wed, 19 May 2021 06:50:39 +0000"); //$datePost
        $item->addChild('dc:creator','<![CDATA[]]>',self::XMLNS_DC);
        $item->addChild('guid', $this->host . '/?p=' . $postNumber)->addAttribute('isPermaLink', 'false');
        $item->addChild('description',"");
        $item->addChild(
            'content:encoded',
            "<![CDATA[<!-- wp:html -->$html<!-- /wp:html -->]]",
            self::XMLNS_CONTENT
        );
        $item->addChild('excerpt:encoded','<![CDATA[]]>', self::XMLNS_EXCERPT);
        $item->addChild('wp:post_id',$postNumber,self::XMLNS_WP);
        $item->addChild('wp:post_date', '2021-05-19 09:52:38',self::XMLNS_WP);
        $item->addChild('wp:post_date_gmt','2021-05-19 06:52:38',self::XMLNS_WP);
        $item->addChild('wp:comment_status', 'open',self::XMLNS_WP);
        $item->addChild('wp:ping_status','open',self::XMLNS_WP);
        $item->addChild('wp:post_name',$postName,self::XMLNS_WP);
        $item->addChild('wp:status','publish',self::XMLNS_WP);
        $item->addChild('wp:post_parent',0,self::XMLNS_WP);
        $item->addChild('wp:menu_order',0,self::XMLNS_WP);
        $item->addChild('wp:post_type','post', self::XMLNS_WP);
        $item->addChild('wp:post_password','', self::XMLNS_WP);
        $item->addChild('wp:is_sticky',0,self::XMLNS_WP);
        $category = $item->addChild('category',"<![CDATA[$getRubric]]>");
        $category->addAttribute('domain','category');
        $category->addAttribute('nicename',urlencode($getRubric));
        $postmeta = $item->addChild('wp:postmeta',null,self::XMLNS_WP);
        $postmeta->addChild('wp:meta_key','<![CDATA[_edit_last]]>',self::XMLNS_WP);
        $postmeta->addChild('wp:meta_value','<![CDATA[1]]>',self::XMLNS_WP);
    }

}