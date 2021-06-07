<?php


namespace App\Service;

use App\Entity\Page;
use App\Entity\Post;
use App\Entity\Section;
use SimpleXMLElement;
use Symfony\Component\DomCrawler\Crawler;


class ParserToXml
{
    private const XMLNS_EXCERPT = 'http://wordpress.org/export/1.2/excerpt/';

    private const XMLNS_WP = 'http://wordpress.org/export/1.2/';

    private const XMLNS_DC = 'http://purl.org/dc/elements/1.1/';

    private const XMLNS_CONTENT = 'http://purl.org/rss/1.0/modules/content/';

    private const XMLNS_WFW = 'http://wellformedweb.org/CommentAPI/';

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
                <title>Музей им. М. Т. Калашникова</title>
                <link>http://kalash</link>
                <description></description>
                <pubDate>Wed, 26 May 2021 09:47:12 +0000</pubDate>
                <language>ru-RU</language>
                <wp:wxr_version>1.2</wp:wxr_version>
                <wp:base_site_url>http://kalash</wp:base_site_url>
                <wp:base_blog_url>http://kalash</wp:base_blog_url>
        
                <wp:author></wp:author>
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

    /**
     * @var Page[]
     */
    private array $pages = [];

    private SimpleXMLElement $xmlElement;
    private static int $postId;
    private string $host = 'http://kalash';
    private string $parsedHost = 'http://museum-mtk.ru';

    private int $firstParentId;
    private int $secondParent;

    private string $parentName;

    private string $parentNameRus;
    private string $postDate;

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
        self::$postId = 200;
        $curl = curl_init();

        foreach ($urls as $url) {
            //Указываем адрес страницы
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

            $html = curl_exec($curl);

            $this->extractFromHtml((string)$html);
        }

        return $this->xmlElement;
    }

    public function extractFromHtml($html): void
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

        foreach ($this->posts as $post) {
            $this->domCrawler->clear();
            curl_setopt($curl, CURLOPT_URL, $post->getUrl());
            $html = curl_exec($curl);
            $this->domCrawler->add($html);
            try {
                $titlePost = trim($this->domCrawler->filterXPath("//body//div[contains(@class, 'right-part')]//h1")->text());
                $datePost = trim($this->domCrawler->filterXPath("//body//div[contains(@class, 'right-part')]//p")->first()->text());
                $rightPart = $this->domCrawler->filterXPath("//body//div[contains(@class, 'right-part')]");
            } catch (\Exception $exception) {
                continue;
            }

            $bodyPost = $rightPart->children()->reduce(
                function (Crawler $crawler) {
                    return !($crawler->nodeName() === 'h1' || in_array(current($crawler->extract(['class'])), ['statusbar', 'overflow']));
                }
            );

            $html = '';
            foreach ($bodyPost as $node) {
                $html .= $node->ownerDocument->saveHTML($node);
            }

            $this->createItem($html, $titlePost, $datePost, $post->getRubric());
        }
    }

    private function createItem(string $html, string $titlePost, string $datePost, string $getRubric): void
    {
        $postName = str_replace(' ', '-', $titlePost);
        $postName = str_replace(array('"', '/', ':', '.', ',', '[', ']', '“', '”'), '', strtolower($postName));

        $postId = self::$postId++;

        $item = $this->xmlElement->channel[0]->addChild('item');
        $item->addChild('title', $titlePost);
        $item->addChild('link', $this->host . '/?p=' . $postId);
        $item->addChild('pubDate'); //$datePost
        $item->addChild('dc:creator', self::XMLNS_DC);
        $item->addChild('guid', $this->host . '/?p=' . $postId)->addAttribute('isPermaLink', 'false');
        $item->addChild('description', "");
        $item->addChild(
            'content:encoded',
            "<!-- wp:html -->$html<!-- /wp:html -->",
            self::XMLNS_CONTENT
        );
        $item->addChild('excerpt:encoded', self::XMLNS_EXCERPT);
        $item->addChild('wp:post_id', $postId, self::XMLNS_WP);
        $item->addChild('wp:post_date', '2021-05-19 09:52:38', self::XMLNS_WP);
        $item->addChild('wp:post_date_gmt', '2021-05-19 06:52:38', self::XMLNS_WP);
        $item->addChild('wp:comment_status', 'open', self::XMLNS_WP);
        $item->addChild('wp:ping_status', 'open', self::XMLNS_WP);
        $item->addChild('wp:post_name', $postName, self::XMLNS_WP);
        $item->addChild('wp:status', 'publish', self::XMLNS_WP);
        $item->addChild('wp:post_parent', 0, self::XMLNS_WP);
        $item->addChild('wp:menu_order', 0, self::XMLNS_WP);
        $item->addChild('wp:post_type', 'post', self::XMLNS_WP);
        $item->addChild('wp:post_password', '', self::XMLNS_WP);
        $item->addChild('wp:is_sticky', 0, self::XMLNS_WP);
        $category = $item->addChild('category', $getRubric);
        $category->addAttribute('domain', 'category');
        $getRubric = str_replace(' ', '-', $getRubric);
        $category->addAttribute('nicename', urlencode($getRubric));
        $postmeta = $item->addChild('wp:postmeta', null, self::XMLNS_WP);
        $postmeta->addChild('wp:meta_key', '<![CDATA[_edit_last]]>', self::XMLNS_WP);
        $postmeta->addChild('wp:meta_value', '<![CDATA[1]]>', self::XMLNS_WP);
    }


    /**
     * @param Section $section
     * @return SimpleXMLElement
     */
    public function parseSiteMap(Section $section): SimpleXMLElement
    {
        self::$postId = $section->getPostId();
        $this->firstParentId = $section->getFirstParentId();
        $this->parentName = $section->getParentName();
        $this->parentNameRus = $section->getParentNameRus();
        $this->postDate = $section->getPostDate();

        $this->getUrlPagesFromHtml($section->getHtml());


        $this->getHtmlFromPages();


        $this->createXmlForPages($section->getColor(), $section->getImage());

        return $this->xmlElement;
    }

    private function getUrlPagesFromHtml(string $html): void
    {
        $this->domCrawler->clear();
        $this->domCrawler->add($html);

        $this->domCrawler->filterXPath('//ul')->children()->each(
            function (Crawler $crawler) {

                if ($crawler->nodeName() === 'li') {
                    $attrValue = $crawler->children()->first()->extract(['href'])[0];
                    preg_match('/^(.+\/)(.+)$/', $attrValue, $matches);

                    $nodeValue = $crawler->first()->text();
                    $this->secondParent = self::$postId++;

                    $this->pages[] = (new Page())
                        ->setUrl($attrValue)
                        ->setParentId($this->firstParentId)
                        ->setPostId($this->secondParent)
                        ->setTitle($nodeValue)
                        ->setParentUrl($matches[1])
                        ->setUrl($matches[2]);
                }

                if ($crawler->nodeName() === 'ul') {
                    foreach ($crawler->children() as $node) {
                        if ($node->nodeName === 'li') {
                            $attrValue = $node->firstChild->attributes->item(0)->nodeValue;
                            preg_match('/^(.+\/)(.+\/)$/', $attrValue, $matches);

                            $nodeValue = $node->firstChild->nodeValue;
                            $thirdParent = self::$postId++;

                            $this->pages[] = (new Page())
                                ->setUrl($attrValue)
                                ->setParentId($this->secondParent)
                                ->setPostId($thirdParent)
                                ->setTitle($nodeValue)
                                ->setParentUrl($matches[1])
                                ->setUrl($matches[2]);
                        }

                        if ($node->nodeName === 'ul') {
                            var_dump('test');
                            die();

                        }
                    }
                }
            }
        );
    }

    private function getHtmlFromPages(): void
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $pages = [];

        foreach ($this->pages as $page) {
            $this->domCrawler->clear();
            curl_setopt($curl, CURLOPT_URL, $this->parsedHost . $page->getParentUrl() . $page->getUrl());
            $html = curl_exec($curl);
            $this->domCrawler->add($html);
            try {
                $rightPart = $this->domCrawler->filterXPath("//body//div[contains(@class, 'right-part')]");
            } catch (\Exception $exception) {
                continue;
            }

            $bodyPost = $rightPart->children()->reduce(
                function (Crawler $crawler) {
                    return !in_array(current($crawler->extract(['class'])), ['statusbar', 'overflow']);
                }
            );

            $html = '';
            foreach ($bodyPost as $node) {
                $html .= $node->ownerDocument->saveHTML($node);
            }

            $html = $this->changeLinks($html);

            $pages[] = $page->setContent($html);
        }

        $this->pages = $pages;
    }

    /**
     * @param $_color
     * @param $_image
     */
    private function createXmlForPages($_color, $_image): void
    {
        $item = $this->xmlElement->channel[0]->addChild('item');
        $item->addChild('title', "$this->parentNameRus");
        $item->addChild('link', $this->host . '/?page_id=' . $this->firstParentId);
        $item->addChild('post_id', $this->firstParentId, self::XMLNS_WP);
        $item->addChild('wp:post_name', "$this->parentName", self::XMLNS_WP);
        $item->addChild('wp:post_parent', 0, self::XMLNS_WP);
        $item->addChild('wp:post_type', 'page', self::XMLNS_WP);
        $item->addChild('wp:post_date', $this->postDate, self::XMLNS_WP);


        foreach ($this->pages as $page) {
            $postName = str_replace(' ', '-', $page->getTitle());
            $postName = str_replace(array('"', '/', ':', '.', ',', '[', ']', '“', '”'), '', strtolower($postName));
            $postName = str_replace('/', '', $page->getUrl());


            $item = $this->xmlElement->channel[0]->addChild('item');
            $item->addChild('title', $page->getTitle());
            $item->addChild('link', $this->host . '/?page_id=' . $page->getPostId());
            $item->addChild('pubDate');
            $item->addChild('dc:creator', null, self::XMLNS_DC);
            $item->addChild('guid', $this->host . '/?page_id=' . $page->getPostId())->addAttribute('isPermaLink', 'false');
            $item->addChild('description');
            $item->addChild(
                'content:encoded',
                "<!-- wp:html -->" . $page->getContent() . "<!-- /wp:html -->",
                self::XMLNS_CONTENT
            );
            $item->addChild('excerpt:encoded', null, self::XMLNS_EXCERPT);
            $item->addChild('wp:post_id', $page->getPostId(), self::XMLNS_WP);
            $item->addChild('wp:post_date', null, self::XMLNS_WP);
            $item->addChild('wp:post_date_gmt', null, self::XMLNS_WP);
            $item->addChild('wp:comment_status', 'closed', self::XMLNS_WP);
            $item->addChild('wp:ping_status', 'closed', self::XMLNS_WP);
            $item->addChild('wp:post_name', $postName, self::XMLNS_WP);
            $item->addChild('wp:status', 'publish', self::XMLNS_WP);
            $item->addChild('wp:post_parent', $page->getParentId(), self::XMLNS_WP);
            $item->addChild('wp:menu_order', 0, self::XMLNS_WP);
            $item->addChild('wp:post_type', 'page', self::XMLNS_WP);
            $item->addChild('wp:post_password', null, self::XMLNS_WP);
            $item->addChild('wp:is_sticky', 0, self::XMLNS_WP);

            $postmeta = $item->addChild('wp:postmeta', null, self::XMLNS_WP);
            $postmeta->addChild('wp:meta_key', '<![CDATA[_edit_last]]>', self::XMLNS_WP);
            $postmeta->addChild('wp:meta_value', '<![CDATA[1]]>', self::XMLNS_WP);

            $postmeta = $item->addChild('wp:postmeta', null, self::XMLNS_WP);
            $postmeta->addChild('wp:meta_key', '<![CDATA[_color]]>', self::XMLNS_WP);
            $postmeta->addChild('wp:meta_value', "<![CDATA[" . $_color . "]]>", self::XMLNS_WP);

            $postmeta = $item->addChild('wp:postmeta', null, self::XMLNS_WP);
            $postmeta->addChild('wp:meta_key', '<![CDATA[_image]]>', self::XMLNS_WP);
            $postmeta->addChild('wp:meta_value', "<![CDATA[" . $_image . "]]>", self::XMLNS_WP);
        }
    }

    /**
     * @param string $html
     * @return string
     */
    private function changeLinks(string $html): string
    {
        // 2. Переделать хост http://museum-mtk.ru/ на /
        $html = str_replace(['http://museum-mtk.ru', '/museum-mtk.ru', 'detail.htm?id='], '', $html);

        // 1, Из ссылок вида armourers/kalashnikov добавить ведущий слеш

        preg_match_all('/ ?href="((?!mailto|http|\/)[\S.]+)?"/', $html, $links);

        foreach ($links[1] as $link) {
            $html = str_replace("$link", "/$link", $html);
        }

        // 3. exhibitions/past/detail.htm?id=731615 на post/detail/731615

//        preg_match_all('/ ?href="([\S.]+?\/detail\.htm\?id=\d+[\S.]+)?"/i', $html, $links);
//
//        foreach ($links[1] as $link){
//            $path = preg_replace('/^.+?detail\.htm\?id=(\d+[\S.]+)/i', "/post/detail/$1", $link);
//            $html = str_replace("$link", $path, $html);
//        }

        // 4. Архив документов сохранить /wp-content/uploads/2021/06/1.jpg (переделать ссылки на документы)

        preg_match_all('/ ?href="([\S.]+?(_galleries|_downloads)\/[\S.]+)?"/i', $html, $links);

        if (count($links) > 0) {
            foreach ($links[1] as $link) {
                $path = preg_replace("/^.+?\/([^\s\/]+)$/i", "/wp-content/uploads/2021/06/$1", $link);

                if(file_exists($_SERVER['DOCUMENT_ROOT'] . $path)){
                    $path = preg_replace("/^.+?\/(\w{1,29})?\/([^\s\/]+)$/i", "/wp-content/uploads/2021/06/$1_$2", $link);
                    echo '<p>' . $path . '</p>';
                }

                if($file = file_get_contents($this->parsedHost . $link)){
                    file_put_contents($_SERVER['DOCUMENT_ROOT'] . $path, $file );
                }

                $html = str_replace("$link", $path, $html);
            }
        }

        // 5. Архив картинок сохранить wp-content/uploads/2021/06/1.jpg (переделать ссылки на картинки)

        preg_match_all('/ ?src="([\S.]+?(_images|_common)\/[\S.]+)?"/', $html, $links);

        if (count($links) > 0) {
            foreach ($links[1] as $link) {
                $path = preg_replace("/^.+?\/([^\s\/]+)$/i", "/wp-content/uploads/2021/06/$1", $link);

                if ($file = @file_get_contents($this->parsedHost . $link)) {
                    file_put_contents($_SERVER['DOCUMENT_ROOT'] . $path, $file);
                }

                $html = str_replace("$link", $path, $html);
            }
        }

        echo '<p>' . $html . '</p>';

        return $html;
    }
}