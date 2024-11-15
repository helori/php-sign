<?php

namespace Helori\PhpSign\Drivers;

use Helori\PhpSign\Utilities\RestApiRequester;
use Helori\PhpSign\Utilities\DateParser;
use Helori\PhpSign\Elements\Scenario;
use Helori\PhpSign\Elements\Transaction;
use Helori\PhpSign\Elements\SignerResult;
use Helori\PhpSign\Elements\DocumentResult;
use Helori\PhpSign\Elements\Webhook;
use Helori\PhpSign\Exceptions\SignException;
use Helori\PhpSign\Exceptions\ValidationException;
use Carbon\Carbon;

/**
 * Yousign API V3 implemented on November 2024
 * https://developers.yousign.com/reference/oas-specification
 */
class Yousignv3Driver implements DriverInterface
{
    /**
     * The Yousign API Requester
     *
     * @var \Helori\PhpSign\Utilities\RestApiRequester
     */
    protected $requester;

    /**
     * The Yousign API Base URL
     *
     * @var string
     */
    protected $apiBaseUrl;

    /**
     * Create a new YousignDriver instance.
     *
     * @return void
     */
    public function __construct(array $config)
    {
        $requiredConfigKeys = ['api_key', 'mode'];

        foreach($requiredConfigKeys as $key){

            if(!isset($config[$key]) || $config[$key] === ''){

                throw new ValidationException('Yousign V3 config parameter "'.$key.'" must be set');
            }
        }

        if($config['mode'] === 'production'){

            $this->apiBaseUrl = 'https://api.yousign.app/v3';

        }else{

            $this->apiBaseUrl = 'https://api-sandbox.yousign.app/v3';
        }

        $this->requester = new RestApiRequester($config['api_key'], $this->apiBaseUrl);
    }

    /**
     * Get the driver's name
     *
     * @return string
     */
    public function getName()
    {
        return 'yousignv3';
    }

    /**
     * Create a transaction from a scenario
     *
     * @param  \Helori\PhpSign\Elements\Scenario  $scenario
     * @return array
     */
    public function createTransaction(Scenario $scenario)
    {
        $config = [];

        if($scenario->getStatusUrl()){

            /*$webhookParams = [
                [
                    'url' => $scenario->getStatusUrl(),
                    'method' => 'GET',
                ]
                // Other webhooks can be added here...
            ];

            $config['webhook'] = [
                // Fired when a procedure is created (POST /procedures)
                'procedure.started' => $webhookParams,
                // Fired when a procedure is finished (all members have signed)
                'procedure.finished' => $webhookParams,
                // Fired when a procedure is refused (a member have refused)
                'procedure.refused' => $webhookParams,
                // Fired when a procedure expired (The expiresAt date is reached)
                'procedure.expired' => $webhookParams,
                // Fired when a member can sign
                'member.started' => $webhookParams,
                // Fired when a member have signed
                'member.finished' => $webhookParams,
                // Fired when someone comment a procedure
                //'comment.created' => $webhookParams,
            ];*/
        }

        $data = [
            'name' => $scenario->getTitle(),
            // Delivery mode to notify signers :
            'delivery_mode' => 'email',
            'expiration_date' => Carbon::now()->addDays($this->getExpirationDays())->format('Y-m-d'),
            'timezone' => 'Europe/Paris',
        ];

        if($scenario->getCustomId())
        {
            // Store a custom id that will be added to webhooks & appended to redirect urls :
            $data['external_id'] = $scenario->getCustomId();
        }

        // Create the signature request.
        // Documents and signers will be added later
        $result = $this->requester->post('/signature_requests', $data);
        $signatureRequest = $this->checkedApiResult($result);

        // keep track of yousign generated ids : "php-sign document id" => "yousign document id"
        $documentIds = [];

        // keep track of yousign generated ids : "php-sign signer id" => "yousign signer id"
        $signerIds = [];

        // Add documents to the signature request
        foreach($scenario->getDocuments() as $scDocument)
        {
            //$metadata = $scDocument->getMetadata();

            // When retreiving the documents later, we have no way to identify them !
            // So, let's use the metadata to store the document ID :
            //$metadata['temporary-internal-document-id'] = $scDocument->getId();

            $result = $this->requester->post('/signature_requests/'.$signatureRequest['id'].'/documents', [
                'nature' => 'signable_document',
                //'name' => $scDocument->getName(),
            ], [
                'file' => $scDocument->getFilePath(),
            ]);

            $ysDocument = $this->checkedApiResult($result);
            $documentIds[$scDocument->getId()] = $ysDocument['id'];
        }

        foreach($scenario->getSigners() as $scSigner)
        {
            if(!$scSigner->getPhone()){
                throw new SignException('The phone number is required by Yousign');
            }

            if(!$scSigner->getEmail()){
                throw new SignException('The email is required by Yousign');
            }

            $data = [
                'info' => [
                    'first_name' => $scSigner->getFirstname(),
                    'last_name' => $scSigner->getLastname(),
                    'email' => $scSigner->getEmail(),
                    'phone_number' => $scSigner->getPhone(),
                    'locale' => 'fr',
                ],
                'signature_level' => 'electronic_signature',
                'signature_authentication_mode' => 'otp_sms', // no_otp, otp_sms, otp_email
                'fields' => [],
            ];

            foreach($scenario->getSignatures() as $scSignature)
            {
                if($scSignature->getSignerId() === $scSigner->getId())
                {
                    $data['fields'][] = [
                        'document_id' => $documentIds[$scSignature->getDocumentId()],
                        'type' => 'signature',
                        'page' => $scSignature->getPage(),
                        'x' => $scSignature->getX(),
                        'y' => $scSignature->getY(),
                        'width' => $scSignature->getWidth()
                    ];
                }
            }

            // Add signers to the signature request
            $result = $this->requester->post('/signature_requests/'.$signatureRequest['id'].'/signers', $data);
            $ysSigner = $this->checkedApiResult($result);

            $signerIds[$scSigner->getId()] = $ysSigner['id'];
        }

        // Activates a Signature request, so it is not in draft status anymore.
        // The signatureRequest result contains a signers array with signature links
        $result = $this->requester->post('/signature_requests/'.$signatureRequest['id'].'/activate');
        $signatureRequest = $this->checkedApiResult($result);

        // Calling getTransaction() to soon after creation will not return signature links !
        // instead of sleeping a few seconds before calling getTransaction(),
        // we use the values returned in $signatureRequest containing the urls
        //sleep(3);
        //return $this->getTransaction($signatureRequest['id']);

        return $this->transactionFromSignatureRequest($signatureRequest);
    }

    /**
     * Get transaction
     *
     * @param  string  $transactionId
     * @return Transaction
     */
    public function getTransaction(string $transactionId)
    {
        // Get the signature request.
        // It contains a signers array with signers IDs but without signature links.
        // They will have to fetched, and at least 1 second after creation
        $result = $this->requester->get('/signature_requests/'.$transactionId);
        $signatureRequest = $this->checkedApiResult($result);

        return $this->transactionFromSignatureRequest($signatureRequest);
    }

    public function transactionFromSignatureRequest(array $signatureRequest)
    {
        $transaction = new Transaction($this->getName());
        $transaction->setId($signatureRequest['id']);
        $transaction->setStatus(self::convertTransactionStatus($signatureRequest['status']));
        $transaction->setCreatedAt(DateParser::parse($signatureRequest['created_at']));
        $transaction->setExpireAt(DateParser::parse($signatureRequest['expiration_date']));
        $transaction->setTitle($signatureRequest['name']);
        $transaction->setCustomId($signatureRequest['external_id']);

        $signers = [];

        foreach($signatureRequest['signers'] as $i => $ysSigner)
        {
            $result = $this->requester->get('/signature_requests/'.$signatureRequest['id'].'/signers/'.$ysSigner['id']);
            $ysSignerData = $this->checkedApiResult($result);

            $signer = new SignerResult();
            $signer->setId($i + 1);
            $signer->setFirstname($ysSignerData['info']['first_name']);
            $signer->setLastname($ysSignerData['info']['last_name']);
            $signer->setEmail($ysSignerData['info']['email']);
            $signer->setPhone($ysSignerData['info']['phone_number']);
            $signer->setStatus(self::convertSignerStatus($ysSignerData['status']));

            if(isset($ysSigner['signature_link']) && $ysSigner['signature_link']){
                $signer->setUrl($ysSigner['signature_link']);
            }
            else if(isset($ysSignerData['signature_link']) && $ysSignerData['signature_link']){
                $signer->setUrl($ysSignerData['signature_link']);
            }

            $signers[] = $signer;
        }

        $transaction->setSigners($signers);

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
        $documents = [];
        $transaction = $this->getTransaction($transactionId);

        if($transaction->getStatus() === Transaction::STATUS_COMPLETED) {

            $result = $this->requester->get('/signature_requests/'.$transactionId);
            $procedure = $this->checkedApiResult($result);

            foreach($procedure['documents'] as $i => $procedureFile)
            {
                $downloadResult = $this->requester->get('/signature_requests/'.$transactionId.'/documents/'.$procedureFile['id'].'/download');
                $content = $downloadResult->getBody()->getContents();

                $documentData = $this->requester->get('/signature_requests/'.$transactionId.'/documents/'.$procedureFile['id']);
                $documentData = $this->checkedApiResult($documentData);

                $document = new DocumentResult();
                //$document->setId($documentData['id']); // Incompatible : string cannot be int
                $document->setName($documentData['filename']);
                $document->setMetadata($documentData);
                $document->setContent($content);

                $documents[] = $document;
            }

        }else{

            throw new SignException('Could not download signed files because they are not signed yet.');
        }

        return $documents;
    }

    /**
     * Cancel a transaction
     *
     * @param  string  $transactionId
     * @return \Helori\PhpSign\Elements\Transaction
     */
    public function cancelTransaction(string $transactionId)
    {
        $result = $this->requester->post('/signature_requests/'.$transactionId.'/cancel');
        $response = $this->checkedApiResult($result);
        return $this->getTransaction($transactionId);
    }

    /**
     * Get the driver's specific expiration days
     *
     * @return int
     */
    public function getExpirationDays()
    {
        return 30;
    }

    /**
     * Format Yousign API exceptions
     *
     * @param  object $apiResult
     * @return mixed
     */
    public function checkedApiResult($apiResult)
    {
        $data = json_decode($apiResult->getBody()->getContents(), true);

        if($apiResult->getStatusCode() >= 400){

            $message = $apiResult->getReasonPhrase();

            if(isset($data['detail'])){
                $message = $data['detail'];
            }else if(isset($data['error'])){
                $message = $data['error'];
            }

            $message .= json_encode($data);

            throw new \Exception($message, $apiResult->getStatusCode());
        }

        return $data;
    }

    /**
     * Convert Yousign transaction status to PhpSign transaction status
     * https://developers.yousign.com/docs/signature-request-2
     *
     * @param  string  $yousignStatus
     * @return string
     */
    protected function convertTransactionStatus(string $yousignStatus)
    {
        $status = Transaction::STATUS_UNKNOWN;

        switch ($yousignStatus) {

            case 'draft':
            case 'approval':
                $status = Transaction::STATUS_DRAFT;
                break;

            case 'ongoing':
                $status = Transaction::STATUS_READY;
                break;

            case 'done':
                $status = Transaction::STATUS_COMPLETED;
                break;

            case 'expired':
                $status = Transaction::STATUS_EXPIRED;
                break;

            case 'rejected':
            case 'declined':
            case 'deleted':
            case 'canceled':
                $status = Transaction::STATUS_REFUSED;
                break;

            default:
                $status = Transaction::STATUS_UNKNOWN;
                break;

        }
        return $status;
    }

    /**
     * Convert Yousign signer status to PhpSign signer status
     * https://developers.yousign.com/docs/signer-1
     *
     * @param  string  $yousignStatus
     * @return string
     */
    protected function convertSignerStatus(string $yousignStatus)
    {
        $status = SignerResult::STATUS_UNKNOWN;

        switch ($yousignStatus) {

            // The signer has not signed yet
            case 'initiated':
            case 'notified':

                $status = SignerResult::STATUS_READY;
                break;

            // The signer has accessed the signature service.
            case 'processing':
                $status = SignerResult::STATUS_ACCESSED;
                break;

            // The signer has successfully signed.
            case 'signed':
                $status = SignerResult::STATUS_SIGNED;
                break;

            // The signer refused to sign, or one of the previous signers canceled or failed its signature.
            case 'declined':
            case 'aborted':
            case 'error':
                $status = SignerResult::STATUS_CANCELED;
                break;

            default:
                $status = SignerResult::STATUS_UNKNOWN;
                break;
        }
        return $status;
    }

    /**
     * Convert a webhook request into the common webhook data format
     *
     * @param  array  $requestData
     * @return \Helori\PhpSign\Elements\Webhook
     */
    public function formatWebhook(array $requestData)
    {
        //throw new SignException('formatWebhook is not implemented yet for Yousign');

        if(!isset($requestData['procedure'])){
            throw new ValidationException('Yousign webhook parameter "procedure" must be set');
        }

        if(!isset($requestData['eventName'])){
            throw new ValidationException('Yousign webhook parameter "eventName" must be set');
        }

        $transactionId = $requestData['procedure'];
        $eventName = $requestData['eventName'];
        $status = Transaction::STATUS_UNKNOWN;

        switch ($eventName) {

            case 'procedure.started':
                $status = Transaction::STATUS_READY;
                break;

            case 'procedure.finished':
                $status = Transaction::STATUS_COMPLETED;
                break;

            case 'procedure.refused':
                $status = Transaction::STATUS_REFUSED;
                break;

            case 'procedure.expired':
                $status = Transaction::STATUS_EXPIRED;
                break;

            // This is the most corresponding status, but not accurate...
            case 'member.started':
                $status = Transaction::STATUS_READY;
                break;

            // This is the most corresponding status, but not accurate.
            // We don't use STATUS_COMPLETED here because another signer may be ready to sign.
            case 'member.finished':
                $status = Transaction::STATUS_READY;
                break;

            default:
                throw new ValidationException('Unknown Yousign webhook event name : '.$eventName);
                break;
        }

        $webhook = new Webhook();
        $webhook->setTransactionId($transactionId);
        $webhook->setTransactionStatus($status);
        return $webhook;
    }
}
