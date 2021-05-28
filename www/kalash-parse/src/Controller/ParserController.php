<?php


namespace App\Controller;


use App\Service\ParserToXml;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ParserController extends AbstractController
{
    private ParserToXml $parser;

    /**
     * ParserController constructor.
     * @param ParserToXml $parser
     */
    public function __construct(ParserToXml $parser)
    {
        $this->parser = $parser;
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

        $fp = fopen('data.xml', 'w');
        fwrite($fp, $xmlData->asXML());
        fclose($fp);
        var_dump($xmlData);

        return new Response($xmlData->asXML());
    }
}