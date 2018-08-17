<?php

namespace Helori\PhpSign\Drivers;

use Globalis\Universign\Request\TransactionSigner;
use Globalis\Universign\Request\DocSignatureField;
use Globalis\Universign\Request\TransactionDocument;
use Globalis\Universign\Request\TransactionRequest;
use Globalis\Universign\Response\TransactionResponse;
use Globalis\Universign\Response\TransactionInfo as UniversignTransactionInfo;
use Globalis\Universign\Requester;

use Helori\PhpSign\Elements\Scenario;
use Helori\PhpSign\Elements\Transaction;
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
     * Create a transaction from a scenario
     *
     * @param  \Helori\PhpSign\Elements\Scenario  $scenario
     * @return array
     */
    public function createTransaction(Scenario $scenario)
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
		$transactionResponse = $this->requester->requestTransaction($request);

		return $this->getTransaction($transactionResponse->id);
    }

    /**
     * Get transaction
     *
     * @param  string  $transactionId
     * @return Transaction
     */
    public function getTransaction(string $transactionId)
    {
        $transaction = new Transaction();
        $transaction->setId($transactionId);

        $transactionInfo = $this->requester->getTransactionInfo($transactionId);

        $signersInfos = [];

        foreach($transactionInfo->signerInfos as $signerInfo){

            $signersInfo = [
                'status' => $signerInfo->status,
                'url' => $signerInfo->url,
                'email' => $signerInfo->email,
                'firstname' => $signerInfo->firstName,
                'lastname' => $signerInfo->lastName,
                //'error' => $signerInfo->error,
                //'certificateInfo' => $signerInfo->certificateInfo,
                //'actionDate' => $signerInfo->actionDate,
                //'refusedDocs' => $signerInfo->refusedDocs,
            ];

            $signersInfos[] = $signersInfo;
        }

        $transaction->setSignersInfos($signersInfos);
        
        $transactionStatus = Transaction::STATUS_UNKNOWN;
        switch ($transactionInfo->status) {

        	case UniversignTransactionInfo::STATUS_READY:
        		$transactionStatus = Transaction::STATUS_READY;
        		break;

        	case UniversignTransactionInfo::STATUS_EXPIRED:
        		$transactionStatus = Transaction::STATUS_EXPIRED;
        		break;

        	case UniversignTransactionInfo::STATUS_CANCELED:
        		$transactionStatus = Transaction::STATUS_CANCELED;
        		break;

        	case UniversignTransactionInfo::STATUS_FAILED:
        		$transactionStatus = Transaction::STATUS_FAILED;
        		break;

        	case UniversignTransactionInfo::STATUS_COMPLETED:
        		$transactionStatus = Transaction::STATUS_COMPLETED;
        		break;
        	
        	default:
        		$transactionStatus = Transaction::STATUS_UNKNOWN;
        		break;
        }

        $transaction->setStatus($transactionStatus);

        return $transaction;
    }
}
