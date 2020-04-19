<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\CssSelector\CssSelectorConverter;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\DependencyInjection\ContainerInterface;
use App\Entity\Package;
use App\Model\WSSoapClient;


class PostalCrawlerCommand extends Command
{
    private $container;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct();
        $this->container = $container;
    }

    protected static $defaultName = 'PostalCrawler';

    protected function configure()
    {
        $this
            ->setDescription('Get all packages')
            ->addArgument('startfrom', InputArgument::OPTIONAL, 'Starting value')
        ;
    }

    private $activePackages = 0;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $io = new SymfonyStyle($input, $output);
		ini_set('default_socket_timeout', 7200);


		$em = $this->container->get('doctrine');
		$connection = $em->getManager()->getConnection();
		$connection->getConfiguration()->setSQLLogger(null);
		$prefiksy = ["RR"]; //EE,CP,VV
		$mnozniki = [8,6,4,2,3,5,9,7];


		$istart = $input->getArgument('startfrom');
		if(!$istart) $istart = 0;
		$istop = 30000000;
		$this->activePackages = 0;
		$progressBar = new ProgressBar($output, ($istop-$istart)*count($prefiksy));
		
		ProgressBar::setPlaceholderFormatterDefinition(
			'acp',
			function (ProgressBar $progressBar, OutputInterface $output) {
				return "acp: ".$this->activePackages;
			}
		);
		

		$progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s% %acp%');
		
		
		$header = ['Username' => 'sledzeniepp', 'Password' => 'PPSA', 'passwordType' => 'PasswordText'];
		$wsclient = new WSSoapClient("https://tt.poczta-polska.pl/Sledzenie/services/Sledzenie?wsdl",['connection_timeout' => 10800,'cache_wsdl' => WSDL_CACHE_NONE]);
		$wsclient->__setUserNameToken($header['Username'],$header['Password'],$header['passwordType']);
		$hello = $wsclient->witaj(['imie' => 'użytkowniku, pomyślnie połączyłeś się z PP'])->return;
		$io->note($hello);
		//$przesylka = (array) ($wsclient->sprawdzPrzesylke(['numer' => 'RR123456789PL'])->return);
		

		//$prefiksy = ["RR","EE","CP"]; //VV
		//$mnozniki = [8,6,4,2,3,5,9,7];
		
		//for($i=0;$i<100000000;$i++) {
		for($i=$istart;$i<$istop;$i++) {
			
			if($i%1000 == 0) {gc_collect_cycles();}
			$packageNumber = (string) $i;
			$packageNumber = str_pad($packageNumber,8,"0");
			
			$spn = str_split($packageNumber);
			$suma = 0;
			$ck = 0;
			foreach($spn as $ix => $pn) {
				$suma += $spn[$ix]*$mnozniki[$ix];
			}
			
			$reszta = $suma%11;
			
			switch($reszta) {
				case 0: { $ck = 5; break;}
				case 1: { $ck = 0; break;}
				default: { $ck = 11 - $reszta; break;}
			}
			$packageNumber .= $ck;
			
			//checksum calculated, now accessing webpage
			foreach($prefiksy as $pref) 
			{ 
						 $przesylka = (array) ($wsclient->sprawdzPrzesylke(['numer' => $pref.$packageNumber."PL"])->return);
						 if($przesylka['status'] == 0) {

								$dataNadania = date_create_from_format("Y-m-d",$przesylka['danePrzesylki']->dataNadania);
								
								if($dataNadania && $dataNadania>date_create_from_format("Y-m-d","2019-12-31")) {
									$this->activePackages++;
									$package = new Package();
									 $package->setDataNadania($dataNadania);
									 $package->setKodKrajuNadania($przesylka['danePrzesylki']->kodKrajuNadania);
									 $package->setKodKrajuPrzezn($przesylka['danePrzesylki']->kodKrajuPrzezn);
									 $package->setKodRodzPrzes($przesylka['danePrzesylki']->kodRodzPrzes);
									 $package->setNumer($przesylka['numer']);
									 $package->setZakonczonoObsluge($przesylka['danePrzesylki']->zakonczonoObsluge);
									 if($package->getZakonczonoObsluge) {
										 foreach($przesylka['danePrzesylki']->zdarzenia->zdarzenie as $zd) {
											 //get konczace
											if($zd->konczace) $package->setDataDoreczenia(date_create_from_format("Y-m-d",$zd->czas));
										 }
									 }
									 $package->setMasa($przesylka['danePrzesylki']->masa);
									 $package->setFormat($przesylka['danePrzesylki']->format);
									 $package->setUpdatedAt(new \DateTime());
									 $em->getManager()->persist($package);
									 //$em->getManager()->detach($package);
 									 $em->getManager()->flush();
									 $em->getManager()->clear();
									 $package = null;
									 unset($package);

}
							 

						 }
						 
						 $progressBar->advance();
			}

		}

		$progressBar->finish();
        $io->success($activePackages);

        return 0;
    
    }
}
