<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\DomCrawler\Crawler;
use App\Entity\Url;
use DOMDocument;
use DOMXPath;

class ApiController extends AbstractController
{
   
    /**
     * @Route("/{url}", name="share", requirements={"url"=".+"})
     */
    public function home($url)
    {
        $em = $this->getDoctrine()->getManager();                    

        $existentShortUrl = $em->getRepository(Url::class)->findOneByShortName([$url]);

        if ($existentShortUrl) {
            $existentShortUrl->incrementAccesses();
            return $this->redirect($existentShortUrl->getFullName());
        }              
        
        $existentUrl = $em->getRepository(Url::class)->findOneByFullName([$url]);
        
        if (!$existentUrl) {            
            $title = $this->crawlToTitle($url);
            $newShortUrl = $this->persistNewUrl($url, $title);            
            
        } else {            
            $existentUrl->incrementAccesses();
        }

        $em->flush();

        $topOneHundred = $em->getRepository(Url::class)->getTopOneHundred();        

        return $this->render('url/index.html.twig', [
            'url' => $url,
            'shortUrl' => $existentUrl ? $existentUrl->getShortName() : $newShortUrl,
            'topOneHundred' => $topOneHundred
        ]);
    }

    public function crawlToTitle(string $url) : ?string
    {        
        $doesItHaveHttps = str_contains($url, "https://");      

        if (!$doesItHaveHttps) {
            $url = "https://" . $url;
        }     

        $html = file_get_contents($url);        
        return $this->getTitle($html, "<title>", "</title>");
    }

    public function getShortUrl(string $url) : string 
    {       
        $explodedUrl = explode("/", $url);        
        $lastUrlPart = array_pop($explodedUrl);                      
        $shortenedUrlPart = substr(md5($lastUrlPart.mt_rand()),0,rand(5,10));                                           
        return $shortenedUrlPart;     
    }
    
    public function persistNewUrl(string $url, string $title) : string 
    {
        $em = $this->getDoctrine()->getManager();        
        $shortUrl = $this->getShortUrl($url);
        $newUrl = new Url();
        $newUrl->setFullName($url);        
        $newUrl->setShortName($shortUrl);        
        $newUrl->setTitle($title);        
        $newUrl->setAccesses(1);
        $newUrl->setCreatedAt(new \Datetime('NOW'));
        $em->persist($newUrl);                   
        return $shortUrl;
    }


    function getTitle(string $string, string $start, string $end) : ?string
    {
        $string = ' ' . $string;
        $ini = strpos($string, $start);
        if ($ini == 0) return '';
        $ini += strlen($start);
        $len = strpos($string, $end, $ini) - $ini;
        return substr($string, $ini, $len);
    }
    
}
