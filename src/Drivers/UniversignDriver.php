<?php

namespace Helori\PhpSign\Drivers;

use Globalis\Universign\Request\TransactionSigner;
use Globalis\Universign\Request\DocSignatureField;
use Globalis\Universign\Request\TransactionDocument;
use Globalis\Universign\Request\TransactionRequest;
use Globalis\Universign\Response\TransactionResponse;
use Globalis\Universign\Requester;

use Helori\PhpSign\Elements\Scenario;
use Helori\PhpSign\Exceptions\ValidationException;


class UniversignDriver implements DriverInterface
{
	/**
     * The universign requester
     *
     * @var \Globalis\Universign\Requester
     */
    protected $requester;

	/**
     * Create a new UniversignDriver instance.
     *
     * @return void
     */
    public function __construct(array $config)
    {
		$client = new \PhpXmlRpc\Client($config['endpoint']);
		$client->setCredentials(
		    $config['username'],
		    $config['password']
		);

		$this->requester = new Requester($client);
    }

    /**
     * Initialize a transaction from a scenario
     *
     * @param  \Helori\PhpSign\Elements\Scenario  $scenario
     * @return array
     */
    public function initTransaction(Scenario $scenario)
    {
    	$signers = [];
    	$documents = [];

    	foreach($scenario->getSigners() as $scSigner){

    		$signer = new TransactionSigner();
			$signer->setFirstname($scSigner->getFirstname())
			    ->setLastname($scSigner->getLastname())
			    ->setPhoneNum($scSigner->getPhone())
			    ->setEmailAddress($scSigner->getEmail());
			    //->setSuccessURL('https://www.universign.eu/fr/sign/success/')
			    //->setCancelURL('https://www.universign.eu/fr/sign/cancel/')
			    //->setFailURL('https://www.universign.eu/fr/sign/failed/')
			    //->setProfile('profil_vendeur');
			$signers[] = $signer;
    	}

    	foreach($scenario->getDocuments() as $scDocument){

    		$document = new TransactionDocument();
			$document->setPath($scDocument->getFilepath());

			$signatures = [];

			foreach($scenario->getSignatures() as $scSignature){

				if($scSignature->getDocumentId() === $scDocument->getId()){

					$signature = new DocSignatureField();

					$signature->setPage($scSignature->getPage())
					    ->setX($scSignature->getX())
					    ->setY($scSignature->getY())
					    //->setPatternName('default')
					    ->setLabel($scSignature->getLabel());

					$signerIndex = null;
					foreach($scenario->getSigners() as $index => $scSigner){
						if($scSigner->getId() === $scSignature->getSignerId()){
							$signerIndex = $index;
						}
					}
					if(is_null($signerIndex)){

						throw new ValidationException('The signature\'s signerId "'.$scSignature->getSignerId().'" has no corresponding signer');
					}

					$signature->setSignerIndex($signerIndex);

					$signatures[] = $signature;
				}
			}

			$document->setSignatureFields($signatures);
			$documents[] = $document;
    	}

    	$request = new TransactionRequest();

    	foreach($documents as $document){
    		$request->addDocument($document);
    	}

    	$request->setSigners($signers);
    	$request->setDescription($scenario->getTitle());
    	$request->setHandwrittenSignatureMode(TransactionRequest::HANDWRITTEN_SIGNATURE_MODE_DIGITAL);
    	$request->setMustContactFirstSigner(false);
    	$request->setFinalDocRequesterSent(true);
    	$request->setChainingMode(TransactionRequest::CHAINING_MODE_WEB);
    	$request->setProfile('default');
    	$request->setCertificateType('simple');
    	$request->setLanguage('fr');

		// Return a \Globalis\Universign\Response\TransactionResponse (with transaction url and id)
		$response = $this->requester->requestTransaction($request);

		$signatureUrl = $response->url;
		$transactionId = $response->id;

		return [
			'id' => $transactionId,
			'url' => $signatureUrl,
		];
    }
}
