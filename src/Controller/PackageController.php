<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Model\WSSoapClient;
use Exception;

class PackageController extends AbstractController
{
    /**
     * @Route("/", name="homepage")
     */
    public function index()
    {
		
		$header = ['Username' => 'sledzeniepp', 'Password' => 'PPSA', 'passwordType' => 'PasswordText'];
		try {
			$wsclient = new WSSoapClient("https://tt.poczta-polska.pl/Sledzenie/services/Sledzenie?wsdl",['connection_timeout' => 10800,'cache_wsdl' => WSDL_CACHE_NONE]);
			$wsclient->__setUserNameToken($header['Username'],$header['Password'],$header['passwordType']);
			$hello = $wsclient->witaj(['imie' => 'użytkowniku, pomyślnie połączyłeś się z serwisem Poczty Polskiej'])->return;
			$failed = false;
		} catch(Exception $e) {
			$hello = "Błąd z połączeniem z serwerami Poczty Polskiej";
			$failed = true;
		}
		
		
		
		
        return $this->render('package/index.html.twig', [
			'hello' => $hello,
			'failed' => $failed,
        ]);
    }
}
