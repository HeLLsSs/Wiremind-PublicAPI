<?php

namespace AppBundle\Controller;

use Cache\Adapter\Redis\RedisCachePool;
use Github\Client;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\PdoSessionHandler;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        return $this->render('@App/Default/index.html.twig');
    }

    /**
     * @Route("/search/{searchText}", name="search")
     */
    public function searchAction(Request $request, $searchText = null)
    {
        $filter = $request->get('filter');
        $checkbox = $request->get('checkbox');

        $session = new Session();

        if ($request->isMethod('POST') && $request->get('ajax-search')) {
            $checkbox = empty((array)json_decode($checkbox));
            $datas = $this->getDataApi($checkbox, $filter);
            $session->set('datas', $datas);

            return new JsonResponse(array('success' => true, 'filter' => $filter, 'datas' => array_slice($datas['items'], 0,3), 'total' => $datas['total']));
        }

        $datas = $session->get('datas');

        if (empty($datas)) {
            if (isset($searchText) && !empty($searchText)) {
                $checkbox = empty(($checkbox));
                $datas = $this->getDataApi($checkbox, $searchText);
            } else {
                return $this->redirectToRoute('homepage');
            }
        }

        return $this->render('@App/Default/search.html.twig', ['datas' => $datas]);

    }

    private function getDataApi(bool $legacy = false, $filter) {

        if (empty($legacy)) {
            $datas = $this->searchAPI('find', $filter, true);
        } else {
            $datas = $this->searchAPI('repositories', $filter, false);
        }

        return $datas;
    }

    private function searchAPI(String $type, String $search, bool $legacy = false)
    {

        $githubToken = $this->container->getParameter('github_token');

        $gitHubClient = new Client();
        $gitHubClient->authenticate($githubToken, null, $gitHubClient::AUTH_HTTP_TOKEN);

        if (extension_loaded('redis')) {
            $redisHost = $this->container->getParameter('redis_host');
            $redisPort = $this->container->getParameter('redis_port');

            $client = new \Redis();
            $client->connect($redisHost, $redisPort);
            $pool = new RedisCachePool($client);
            $gitHubClient->addCache($pool);
        }

        $returnDatas = [];

        if ($legacy) {
            $datas = $gitHubClient->api('repo')->$type($search, ['start_page' => 1]);
            $returnDatas['total'] = count($datas['repositories']);
            $returnDatas['items'] = $datas['repositories'];
        } else {
            $datas = $gitHubClient->api('search')->$type($search);
            $returnDatas['total'] = $datas['total_count'];
            $returnDatas['items'] = $datas['items'];
        }

        return $returnDatas;
    }

    private function tryApiGraphql($gitHubClient)
    {
        $query = <<<'QUERY'
query($number_of_repos:Int!) {
  viewer {
    name
     repositories(last: $number_of_repos) {
       nodes {
         name
       }
     }
   }
}
QUERY;

        $variables = [
            'number_of_repos' => 3
        ];

        return $gitHubClient->api('graphql')->execute($query, $variables);
    }

    /**
     * @Route("/search-detail/{username}/{repository}", name="search_detail")
     */
    public function detailSearchAction($username, $repository) {
        $githubToken = $this->container->getParameter('github_token');

        $gitHubClient = new Client();
        $gitHubClient->authenticate($githubToken, null, $gitHubClient::AUTH_HTTP_TOKEN);

        $datas['statistics'] = $gitHubClient->api('repo')->statistics($username,$repository);

        return $this->render('@App/Default/search-detail.html.twig', ['datas' => $datas]);
    }
}
