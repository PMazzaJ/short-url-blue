<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\Url;

class ApiController extends AbstractController
{
   
    /**
     * @Route("/url-shortener", name="url_shortener", methods={"POST"})     
     */
    public function urlShortener(Request $request)
    {

        $em = $this->getDoctrine()->getManager();
        $data = $request->request->all();
        $missingMsg = "";

        /* array que contém os campos obrigatórios da API */
        $requiredFields = [
            'url',            
        ];

        /* verifica os campos obrigatórios foram enviados no request */
        foreach ($requiredFields as $field) {       
            if (!isset($data[$field]) || empty($data[$field])) {
                $missingMsg .= " {$field},";
            }
        }

        /* se faltou algum campo obrigatório retorna 400 */
        if (!empty($missingMsg)) {

            return new JsonResponse([
                'code' => JsonResponse::HTTP_BAD_REQUEST,
                'message' => "Missing parameter(s):" . rtrim($missingMsg, ",")
            ], JsonResponse::HTTP_BAD_REQUEST);

        }     

        /* manipula url recebida por POST para gerar a url reduzida */
        $baseUrl = $data["url"];               
        $explodedUrl = explode("/", $baseUrl);        
        $lastUrlPart = array_pop($explodedUrl);                      
        $shortenedUrlPart = substr(md5($lastUrlPart.mt_rand()),0,rand(5,10));                                     
        $shortenedUrl = implode("/", $explodedUrl) . "/" . $shortenedUrlPart;     
        
        /* persiste url no banco */
        $url = new Url();
        $url->setFullName($baseUrl);
        $url->setName($lastUrlPart);
        $url->setFullShortName($shortenedUrl);        
        $url->setShortName($shortenedUrlPart);        
        $url->setCreatedAt(new \Datetime('NOW'));
        $em->persist($url);    
        $em->flush();                      

        /* retorno da api com a respectiva url reduzida */
        return new JsonResponse([            
            'shortenedUrl' => $shortenedUrl
        ], JsonResponse::HTTP_OK);
    }
    
    /**
     * @Route("/{shortenedUrl}", name="find_url_shortname", methods={"GET"})
     */
    public function findUrlByShortName($shortenedUrl)
    {         

        $em = $this->getDoctrine()->getManager();     
        $url = $em->getRepository(Url::class)->findOneBy(["shortName" => $shortenedUrl]);   

        //se url existir no banco
        if (!empty($url)) {
            
            $requestTime = new \Datetime('NOW');
            $urlCreatedTime = $url->getCreatedAt();
            $interval = $requestTime->diff($urlCreatedTime);
            
            //verifica tempo de expiração da url, no caso escolhi 1 hora
            if ($interval->h > 1 || ($interval->h === 1 && $interval->i > 0)) {
                return new JsonResponse([            
                    'message' => "$url is expired"
                ], JsonResponse::HTTP_NOT_FOUND);
            }                 

            //redirect para url original
            return $this->redirect("http://" . $url->getName());

        } else {
            //caso url não existir retorna not found
            return new JsonResponse([            
                'message' => "$url doesn't exist"
            ], JsonResponse::HTTP_NOT_FOUND);
        }       
        
    }

}