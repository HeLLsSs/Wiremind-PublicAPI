<?php

namespace AppBundle\Controller;

use Github\Client;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {

        $githubToken = $this->container->getParameter('github_token');

        $gitHubClient = new Client();
        $gitHubClient->authenticate($githubToken, null, $gitHubClient::AUTH_HTTP_TOKEN);


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

        $orgInfo = $gitHubClient->api('graphql')->execute($query, $variables);

        $repo = $gitHubClient->api('repository')->all();

        var_dump($repo);die;

        // replace this example code with whatever you need
        return $this->render('default/index.html.twig', [
            'base_dir' => realpath($this->getParameter('kernel.project_dir')) . DIRECTORY_SEPARATOR,
        ]);
    }
}
