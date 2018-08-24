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
use Helori\PhpSign\Exceptions\SignException;
use Helori\PhpSign\Exceptions\ValidationException;

use Carbon\Carbon;


class UniversignDriver implements DriverInterface
{
	/**
     * The universign requester
     *
     * @var \Globalis\Universign\Requester
     */
    protected $requester;

    /**
     * The universign profile to use.
     * Universign stores some information in containers called "profiles".
     * Only the Universign team can modify it (no API nor UI for this).
     * A profile contain :
     * - Signature UI page customization elemnts such as logo...
     * - Status push URL where GET requests are sent when a transaction status changed
     * - Custom elements for the signatures blocks appearing where signers put their signatures on the documents
     *
     * @var string
     */
    protected $profile;

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
        $this->profile = $config['profile'];
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
                //->setBirthday($scSigner->getBirthday());  // ->format('Ymd\TH:i:s\Z')
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

        // The profile contains information to customize the web interface (logo...), 
        // the push status URL to get notified of each signature step,
        // and the signature field customization (size, text and image)
        $request->setProfile($this->profile);
        // Cannot set $scenario->getStatusUrl() here !!!!


        // local, certified, advanced, simple
        $request->setCertificateType('simple'); 
        $request->setChainingMode(TransactionRequest::CHAINING_MODE_WEB);
        $request->setLanguage('fr');

        $request->setHandwrittenSignatureMode(TransactionRequest::HANDWRITTEN_SIGNATURE_MODE_DIGITAL);
        //$request->setCustomId();

        // Send an email to the first signer
    	$request->setMustContactFirstSigner(false);

        // Tells whether each signer must receive the signed documents by e-mail when the transaction is completed. False by default.
    	$request->setFinalDocSent(false);
        // Tells whether the requester must receive the signed documents via e-mail when the transaction is completed. False by default.
        $request->setFinalDocRequesterSent(false);
        // Tells whether the observers must receive the signed documents via e-mail when the transaction is completed. It takes the finalDocSent value by default.
        $request->setFinalDocObserverSent(false);

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
                'action_date' => $signerInfo->actionDate ? Carbon::instance($signerInfo->actionDate) : null,
                //'error' => $signerInfo->error,
                //'certificateInfo' => $signerInfo->certificateInfo,
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

    /**
     * Get transaction documents
     *
     * @param  string  $transactionId
     * @return array
     */
    public function getDocuments(string $transactionId)
    {
        $files = [];

        $transaction = $this->getTransaction($transactionId);

        if ($transaction->getStatus() === Transaction::STATUS_COMPLETED) {

            $docs = $this->requester->getDocuments($transactionId);

            foreach ($docs as $doc) {

                $files[] = [
                    'name' => $doc->name,
                    'content' => $doc->content,

                    // Universign specific :
                    'documentType' => $doc->documentType,
                    'signatureFields' => $doc->signatureFields,
                    'checkBoxTexts' => $doc->checkBoxTexts,
                    'metaData' => $doc->metaData,
                    'displayName' => $doc->displayName,
                    'SEPAData' => $doc->SEPAData,
                ];
            }
        
        }else{

            throw new SignException('Could not download signed files because they are not signed yet.');
        }

        return $files;
    }
}
