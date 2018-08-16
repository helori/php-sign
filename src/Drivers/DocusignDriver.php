<?php

namespace Helori\PhpSign\Drivers;

use DocuSign\eSign\Configuration;
use DocuSign\eSign\ApiClient;
use DocuSign\eSign\Api\AuthenticationApi;
use DocuSign\eSign\Api\AuthenticationApi\LoginOptions;
use DocuSign\eSign\Api\EnvelopesApi;
use DocuSign\eSign\Model\Document;
use DocuSign\eSign\Model\SignHere;
use DocuSign\eSign\Model\Tabs;
use DocuSign\eSign\Model\Signer;
use DocuSign\eSign\Model\Recipients;
use DocuSign\eSign\Model\EnvelopeDefinition;
use DocuSign\eSign\Api\EnvelopesApi\CreateEnvelopeOptions;
use DocuSign\eSign\Model\RecipientViewRequest;

use Helori\PhpSign\Elements\Scenario;
use Helori\PhpSign\Exceptions\DriverAuthException;


class DocusignDriver implements DriverInterface
{
	/**
     * The Docusign API Client object
     *
     * @var \DocuSign\eSign\ApiClient
     */
    protected $client;

    /**
     * The Docusign account id
     *
     * @var string
     */
    protected $accountId;

	/**
     * Create a new UniversignDriver instance.
     *
     * @return void
     */
    public function __construct(array $config)
    {
		$configuration = new Configuration();
        $configuration->setHost($config['endpoint']);

        $docusignHeader = [
			'Username' => $config['username'],
			'Password' => $config['password'],
			'IntegratorKey' => $config['integrator_key'],
        ];

        $configuration->addDefaultHeader("X-DocuSign-Authentication", json_encode($docusignHeader, JSON_FORCE_OBJECT));

        $this->client = new ApiClient($configuration);
        
        $authenticationApi = new AuthenticationApi($this->client);
        $options = new LoginOptions();
        $loginInformation = $authenticationApi->login($options);

        if(isset($loginInformation) && count($loginInformation->getLoginAccounts()) > 0)
        {
            $loginAccount = $loginInformation->getLoginAccounts()[0];
            $host = $loginAccount->getBaseUrl();
            $host = explode("/v2", $host);
            $host = $host[0];

            // update configuration object and instantiate a new docusign api client (that has the correct baseUrl/host)
            $configuration->setHost($host);
            $this->client = new ApiClient($configuration);
            $this->accountId = $loginAccount->getAccountId();

        }else{

        	throw new DriverAuthException('Could not connect to Docusign');
        }
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

    		$signer = new Signer();
            $signer->setEmail($scSigner->getEmail());
            $signer->setName($scSigner->getFullname());
            $signer->setRecipientId($scSigner->getId());
            //$signer->setClientUserId($scSigner->getId());
            
            $signHereTabs = [];

            foreach($scenario->getSignatures() as $scSignature){

            	if($scSignature->getSignerId() === $scSigner->getId()){

            		$signHere = new SignHere();
		            $signHere->setXPosition($scSignature->getX());
		            $signHere->setYPosition($scSignature->getY());
		            $signHere->setDocumentId($scSignature->getDocumentId());
		            $signHere->setPageNumber($scSignature->getPage());
		            $signHere->setRecipientId($scSignature->getSignerId());
		            $signHereTabs[] = $signHere;
            	}
            }

            $tabs = new Tabs();
            $tabs->setSignHereTabs($signHereTabs);
            $signer->setTabs($tabs);

            $signers[] = $signer;
    	}

    	foreach($scenario->getDocuments() as $scDocument){

    		$document = new Document();
            $document->setDocumentBase64(base64_encode(file_get_contents($scDocument->getFilepath())));
            $document->setName($scDocument->getName());
            $document->setDocumentId($scDocument->getId());
            $documents[] = $document;
    	}

    	$recipients = new Recipients();
        $recipients->setSigners($signers);

        // Create envelope and set envelope status to "sent" to immediately send the signature request
        $envelopeDefinition = new EnvelopeDefinition();
        //$envelopeDefinition->setEmailSubject("[DocuSign PHP SDK] - Please sign this doc");
        $envelopeDefinition->setStatus('created'); 
        $envelopeDefinition->setRecipients($recipients);
        $envelopeDefinition->setDocuments($documents);

        $options = new CreateEnvelopeOptions();
        $options->setCdseMode(null);
        $options->setMergeRolesOnDraft(null);

        $envelopeApi = new EnvelopesApi($this->client);
        $envelopeSummary = $envelopeApi->createEnvelope($this->accountId, $envelopeDefinition, $options);
        $envelopeId = $envelopeSummary->getEnvelopeId();

        $signers_urls = [];

        foreach($scenario->getSigners() as $scSigner){

        	$recipientViewRequest = new RecipientViewRequest();
	        $recipientViewRequest->setReturnUrl('https://algoart.fr/return');
	        //$recipientViewRequest->setClientUserId($scSigner->getId());
	        $recipientViewRequest->setAuthenticationMethod("email");
	        $recipientViewRequest->setUserName($scSigner->getFullname());
	        $recipientViewRequest->setEmail($scSigner->getEmail());

	        $signingView = $envelopeApi->createRecipientView($this->accountId, $envelopeId, $recipientViewRequest);
	        $signers_urls[] = $signingView->getUrl();
        }

        return [
        	'envelope_id' => $envelopeId,
        	'recipients_urls' => $signers_urls,
        ];
    }
}
