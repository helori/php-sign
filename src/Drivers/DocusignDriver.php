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
use DocuSign\eSign\Model\EventNotification;

use Helori\PhpSign\Elements\Scenario;
use Helori\PhpSign\Elements\Transaction;
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
     * Get the driver's name
     *
     * @return string
     */
    public function getName()
    {
        return 'docusign';
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

    		$signer = new Signer();
            $signer->setEmail($scSigner->getEmail());
            $signer->setName($scSigner->getFullname());
            $signer->setRecipientId($scSigner->getId());
            
            // Set the recipient as "embedded" (instead of "remote") by setting a clientUserId
            // When embedded, the recipient will not receive an email, except if EmbeddedRecipientStartUrl is specified
            // Not specifying the clientUserId leaves the signer as "remote" : an email will be sent by docusign.
            $signer->setClientUserId($scSigner->getId()); 

            // If a clientUserId is set (= recipient is embedded) an email can be sent by docusign with a link to your app.
            // The app is then responsible for authenticating the signer,
            // and must generate a signingView URL to redirect the signer.
            // Setting the magic value 'SIGN_AT_DOCUSIGN' causes the recipient to be both embedded,
            // and receive an official "please sign" email from DocuSign.
            $signer->setEmbeddedRecipientStartUrl(null);
            
            $signHereTabs = [];

            foreach($scenario->getSignatures() as $scSignature){

            	if($scSignature->getSignerId() === $scSigner->getId()){

            		$signHere = new SignHere();
		            $signHere->setXPosition($scSignature->getX());
		            $signHere->setYPosition($scSignature->getY());
		            $signHere->setDocumentId($scSignature->getDocumentId());
		            $signHere->setPageNumber($scSignature->getPage());
		            $signHere->setRecipientId($scSigner->getId());
		            $signHereTabs[] = $signHere;
            	}
            }

            $tabs = new Tabs();
            $tabs->setSignHereTabs($signHereTabs);
            $signer->setTabs($tabs);

            $signers[] = $signer;
    	}

    	foreach($scenario->getDocuments() as $scDocument){

            // See possible values : https://developers.docusign.com/esign-rest-api/reference/Envelopes/Envelopes
    		$document = new Document();
            $document->setDocumentBase64(base64_encode(file_get_contents($scDocument->getFilepath())));
            $document->setName($scDocument->getName());
            $document->setDocumentId($scDocument->getId());
            $document->setSignerMustAcknowledge('view_accept');
            $documents[] = $document;
    	}

    	$recipients = new Recipients();
        $recipients->setSigners($signers);

        $eventNotification = new EventNotification();
        $eventNotification->setUrl($scenario->getStatusUrl());

        // Create envelope and set envelope status to "sent" to immediately send the signature request
        $envelopeDefinition = new EnvelopeDefinition();
        $envelopeDefinition->setEmailSubject("[DocuSign PHP SDK] - Please sign this doc");
        $envelopeDefinition->setStatus('sent'); 
        $envelopeDefinition->setRecipients($recipients);
        $envelopeDefinition->setDocuments($documents);
        $envelopeDefinition->setEventNotification($eventNotification);

        $options = new CreateEnvelopeOptions();
        $options->setCdseMode(null);
        $options->setMergeRolesOnDraft(null);

        $envelopeApi = new EnvelopesApi($this->client);
        $envelopeSummary = $envelopeApi->createEnvelope($this->accountId, $envelopeDefinition, $options);
        $envelopeId = $envelopeSummary->getEnvelopeId();

        return $this->getTransaction($envelopeId);
    }

    /**
     * Get transaction
     *
     * @param  string  $transactionId
     * @return Transaction
     */
    public function getTransaction(string $transactionId)
    {
        $envelopeApi = new EnvelopesApi($this->client);

        $envelope = $envelopeApi->getEnvelope($this->accountId, $transactionId);
        $recipients = $envelopeApi->listRecipients($this->accountId, $transactionId);

        $transaction = new Transaction($this->getName());
        $transaction->setId($envelope->getEnvelopeId());

        $transactionStatus = Transaction::STATUS_UNKNOWN;

        switch ($envelope->getStatus()) {

            case 'created':
                $transactionStatus = Transaction::STATUS_DRAFT;
                break;

            case 'sent':
                $transactionStatus = Transaction::STATUS_READY;
                break;

            case 'delivered':
                $transactionStatus = Transaction::STATUS_READY;
                break;

            case 'processing':
                $transactionStatus = Transaction::STATUS_READY;
                break;

            case 'signed':
                $transactionStatus = Transaction::STATUS_COMPLETED;
                break;

            case 'completed':
                $transactionStatus = Transaction::STATUS_COMPLETED;
                break;

            case 'declined':
                $transactionStatus = Transaction::STATUS_REFUSED;
                break;

            case 'voided':
                $transactionStatus = Transaction::STATUS_FAILED;
                break;

            case 'deleted':
                $transactionStatus = Transaction::STATUS_CANCELED;
                break;

            case 'timedout':
                $transactionStatus = Transaction::STATUS_EXPIRED;
                break;
            
            default:
                $transactionStatus = Transaction::STATUS_UNKNOWN;
                break;
        }

        $transaction->setStatus($transactionStatus);

        $signersInfos = [];

        foreach($recipients->getSigners() as $signer){

            // The signer URL has very short lifetime
            // It can be re-generated as much as needed
            // Use it quickly after retreiving it (making a redirect)

            $recipientViewRequest = new RecipientViewRequest();
            $recipientViewRequest->setReturnUrl('https://algoart.fr/return');
            $recipientViewRequest->setAuthenticationMethod("email");
            $recipientViewRequest->setUserName($signer->getName());
            $recipientViewRequest->setEmail($signer->getEmail());
            $recipientViewRequest->setRecipientId($signer->getRecipientId());
            $recipientViewRequest->setClientUserId($signer->getClientUserId());

            $signingView = $envelopeApi->createRecipientView(
                $this->accountId, 
                $envelope->getEnvelopeId(), 
                $recipientViewRequest);

            $signersInfo = [
                'status' => $signer->getStatus(),
                'name' => $signer->getName(),
                'email' => $signer->getEmail(),
                'url' => $signingView->getUrl(),
            ];

            $signersInfos[] = $signersInfo;
        }

        $transaction->setSignersInfos($signersInfos);

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

        $envelopeApi = new EnvelopesApi($this->client);

        $docsList = $envelopeApi->listDocuments($this->accountId, $transactionId);
        $documents = $docsList->getEnvelopeDocuments();

        foreach($documents as $document)
        {
            // The signature certificate is one of the returned documents
            $isCertificate = (strpos($document->getDocumentId(), 'certificate') !== false);
            if($isCertificate){
                continue;
            }

            $content = $envelopeApi->getDocument($this->accountId, $transactionId, $document->getDocumentId());

            $files[] = [
                'name' => $document->getName(),
                'content' => $content,

                // Universign specific :
                'attachment_tab_id' => $document->getAttachmentTabId(),
                'available_document_types' => $document->getAvailableDocumentTypes(),
                'contains_pdf_form_fields' => $document->getContainsPdfFormFields(),
                'display' => $document->getDisplay(),
                'document_fields' => $document->getDocumentFields(),
                'document_group' => $document->getDocumentGroup(),
                'document_id' => $document->getDocumentId(),
                'error_details' => $document->getErrorDetails(),
                'include_in_download' => $document->getIncludeInDownload(),
                'order' => $document->getOrder(),
                'pages' => $document->getPages(),
                'signer_must_acknowledge' => $document->getSignerMustAcknowledge(),
                'template_locked' => $document->getTemplateLocked(),
                'template_required' => $document->getTemplateRequired(),
                'type' => $document->getType(),
                'uri' => $document->getUri(),
            ];
        }

        return $files;
    }
}
