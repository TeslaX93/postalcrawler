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
use SoapClient;

/* https://gist.github.com/Turin86/5569152 */
/**
 * This class can add WSSecurity authentication support to SOAP clients
 * implemented with the PHP 5 SOAP extension.
 *
 * It extends the PHP 5 SOAP client support to add the necessary XML tags to
 * the SOAP client requests in order to authenticate on behalf of a given
 * user with a given password.
 *
 * This class was tested with Axis, WSS4J servers and CXF.
 *
 * @author Roger Veciana - http://www.phpclasses.org/browse/author/233806.html
 * @author John Kary <johnkary@gmail.com>
 * @author Alberto Martínez  - https://gist.github.com/Turin86/5569152
 * @see http://stackoverflow.com/questions/2987907/how-to-implement-ws-security-1-1-in-php5
 */
 
class WSSoapClient extends \SoapClient
{
	private $OASIS = 'http://docs.oasis-open.org/wss/2004/01';

	/**
	 * WS-Security Username
	 * @var string
	 */
	private $username;
	
	/**
	 * WS-Security Password
	 * @var string
	 */
	private $password;
	 
	/**
	 * WS-Security PasswordType
	 * @var string
	 */
	private $passwordType;
	  
	/**
	 * Set WS-Security credentials
	 * 
	 * @param string $username
	 * @param string $password
	 * @param string $passwordType
	 */
	public function __setUsernameToken($username, $password, $passwordType)
	{
		$this->username = $username;
		$this->password = $password;
		$this->passwordType = $passwordType;
	}
	   
	/**
	 * Overwrites the original method adding the security header.
	 * As you can see, if you want to add more headers, the method needs to be modified.
	 */
	public function __call($function_name, $arguments)
	{
		$this->__setSoapHeaders($this->generateWSSecurityHeader());
		return parent::__call($function_name, $arguments);
	}
	    
	/**
	 * Generate password digest.
	 * 
	 * Using the password directly may work also, but it's not secure to transmit it without encryption.
	 * And anyway, at least with axis+wss4j, the nonce and timestamp are mandatory anyway.
	 * 
	 * @return string   base64 encoded password digest
	 */
	private function generatePasswordDigest()
	{
		$this->nonce = mt_rand();
		$this->timestamp = gmdate('Y-m-d\TH:i:s\Z');
		
		$packedNonce = pack('H*', $this->nonce);
		$packedTimestamp = pack('a*', $this->timestamp);
		$packedPassword = pack('a*', $this->password);
		
		$hash = sha1($packedNonce . $packedTimestamp . $packedPassword);
		$packedHash = pack('H*', $hash);
		
		return base64_encode($packedHash);
	}
	
	/**
	 * Generates WS-Security headers
	 * 
	 * @return SoapHeader
	 */
	private function generateWSSecurityHeader()
	{
		if ($this->passwordType === 'PasswordDigest')
		{
			$password = $this->generatePasswordDigest();
			$nonce = sha1($this->nonce);
		}
		elseif ($this->passwordType === 'PasswordText')
		{
			$password = $this->password;
			$nonce = sha1(mt_rand());
		}
		else
		{
			return '';
		}

		$xml = '
<wsse:Security SOAP-ENV:mustUnderstand="1" xmlns:wsse="' . $this->OASIS . '/oasis-200401-wss-wssecurity-secext-1.0.xsd">
	<wsse:UsernameToken>
	<wsse:Username>' . $this->username . '</wsse:Username>
	<wsse:Password Type="' . $this->OASIS . '/oasis-200401-wss-username-token-profile-1.0#' . $this->passwordType . '">' . $password . '</wsse:Password>
	<wsse:Nonce EncodingType="' . $this->OASIS . '/oasis-200401-wss-soap-message-security-1.0#Base64Binary">' . $nonce . '</wsse:Nonce>';
		
		if ($this->passwordType === 'PasswordDigest')
		{
			$xml .= "\n\t" . '<wsu:Created xmlns:wsu="' . $this->OASIS . '/oasis-200401-wss-wssecurity-utility-1.0.xsd">' . $this->timestamp . '</wsu:Created>';
		}
		
		$xml .= '
	</wsse:UsernameToken>
</wsse:Security>';
		
		return new \SoapHeader(
			$this->OASIS . '/oasis-200401-wss-wssecurity-secext-1.0.xsd',
			'Security',
			new \SoapVar($xml, XSD_ANYXML),
			true);
	}
}


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
		$wsclient = new WSSoapClient("https://tt.poczta-polska.pl/Sledzenie/services/Sledzenie?wsdl",['connection_timeout' => 7200,'cache_wsdl' => WSDL_CACHE_NONE]);
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
