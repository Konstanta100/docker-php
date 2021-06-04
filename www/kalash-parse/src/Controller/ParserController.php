<?php


namespace App\Controller;


use App\Entity\Section;
use App\Service\ParserToXml;
use App\Service\SectionService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ParserController extends AbstractController
{
    private ParserToXml $parser;

    private SectionService $sectionService;

    /**
     * ParserController constructor.
     * @param ParserToXml $parser
     * @param SectionService $sectionService
     */
    public function __construct(ParserToXml $parser, SectionService $sectionService)
    {
        $this->parser = $parser;
        $this->sectionService = $sectionService;
    }

    /**
     * @Route("/parseNews", name="parseNews")
     * @throws \Exception
     */
    public function parseNewsAction(Request $request): Response
    {
        $newsUrl = 'http://museum-mtk.ru/presscenter/news/index.htm?';

        $firstYear = $request->get('start');
        $lastYear = $request->get('end');

        if ($lastYear === null || $firstYear === null){
            $firstYear = 2011;
            $lastYear = (int)date("Y");
        }

        $urls = [];
        for ($year = $firstYear; $year <= $lastYear; $year++) {
            for($month = 1; $month <= 12; $month++){
                $urls[] = $newsUrl . 'year=' . $year . '&month=' . $month;
            }
        }

        $xmlData = $this->parser->getNews($urls);

        $response = 'true';

        if(file_put_contents('news.xml',$xmlData->asXML())){
            $response = 'false';
        }

        return new Response($response);
    }

    /**
     * @Route("/parseSiteMap/{section}", name="parseSiteMap")
     * @throws \Exception
     */
    public function parseSiteMapAction(?string $section): Response
    {
        $response = 'true';

        switch ($section){
            case 'about':
                $html = /** @lang text */
                    <<<HTML
                    <ul>
                        <li><a href="/about/osnovne_svedeniya/">Сведения об организации</a></li>
                    
                        <li><a href="/about/history/">История</a></li>
                    
                        <li><a href="/about/structure/">Структура</a></li>
                    
                        <li><a href="/about/dostupnaya_sreda/">Доступная среда</a></li>
                    
                        <li><a href="/about/partners/">Друзья&nbsp;и&nbsp;партнеры</a></li>
                    
                        <li><a href="/about/contribution/">Сотрудничество</a></li>
                    
                        <li><a href="/about/contacts/">Контакты</a></li>
                    
                        <li><a href="/about/dokument/">Документы</a></li>
                        <ul>
                            <li><a href="/about/dokument/1.10.07._materialnoe_obespechenie/">Материально-техническое обеспечение и оснащенность образовательного процесса</a></li>
                        </ul>
                    </ul>
                HTML;

                $section = (new Section())
                    ->setHtml($html)
                    ->setColor('field_60a64f8f347d1')
                    ->setImage('field_60a77275462f1')
                    ->setPostId(300)
                    ->setFirstParentId(35)
                    ->setParentName('about')
                    ->setParentNameRus('О Музее')
                    ->setPostDate('2021-05-31 15:28:25');

                break;
            case 'noch_muzeev-2021':

                $html = /** @lang text */
                    <<<HTML
                    <ul>
                        <li><a href="/noch_muzeev-2021/01/">Музей как визитная карточка</a></li>
                    
                        <li><a href="/noch_muzeev-2021/02_muzej_kak_interaktivnaya_laboratoriya/">Музей как интерактивная лаборатория</a></li>
                    
                        <li><a href="/noch_muzeev-2021/03/">Музей как учебный класс для детей</a></li>
                    
                        <li><a href="/noch_muzeev-2021/urok_strela/">Музей как демонстрационный зал</a></li>
                    
                        <li><a href="/noch_muzeev-2021/05/">Музей как учебный класс для взрослых</a></li>
                    
                        <li><a href="/noch_muzeev-2021/06/">Музей как сувенирная лавка</a></li>
                    
                        <li><a href="/noch_muzeev-2021/07/">Музей как библиотека</a></li>
                    
                        <li><a href="/noch_muzeev-2021/08/">Музей как концертная площадка</a></li>
                    
                        <li><a href="/noch_muzeev-2021/09/">Музей как кинозал</a></li>
                    
                        <li><a href="/noch_muzeev-2021/11/">Проект «Музейные читки»</a></li>
                    </ul>
                HTML;

                $section = (new Section())
                    ->setHtml($html)
                    ->setColor('field_60a64f8f347d1')
                    ->setImage('field_60a77275462f1')
                    ->setPostId(400)
                    ->setFirstParentId(393)
                    ->setParentName('noch_muzeev-2021')
                    ->setParentNameRus('Ночь музеев-2021!')
                    ->setPostDate('2021-05-31 15:28:25');

                break;
            default:
                return new Response($response);

        }

        $xmlData = $this->parser->parseSiteMap($section);
        if(file_put_contents($section->getParentName() . '.xml', $xmlData->asXML())){
            $response = 'false';
        }

        return new Response($response);
    }
}