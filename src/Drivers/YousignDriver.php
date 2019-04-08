<?php

namespace Helori\PhpSign\Drivers;

use Helori\PhpSign\Utilities\RestApiRequester;
use Helori\PhpSign\Utilities\DateParser;
use Helori\PhpSign\Elements\Scenario;
use Helori\PhpSign\Elements\Transaction;
use Helori\PhpSign\Elements\SignerResult;
use Helori\PhpSign\Elements\DocumentResult;
use Helori\PhpSign\Exceptions\SignException;
use Helori\PhpSign\Exceptions\ValidationException;
use Carbon\Carbon;


class YousignDriver implements DriverInterface
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
     * The Yousign Web App URL
     *
     * @var string
     */
    protected $webAppUrl;

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

                throw new ValidationException('Yousign config parameter "'.$key.'" must be set');
            }
        }

        if($config['mode'] === 'production'){

            $this->apiBaseUrl = 'https://api.yousign.com';
            $this->webAppUrl = 'https://webapp.yousign.com';

        }else{

            $this->apiBaseUrl = 'https://staging-api.yousign.com';
            $this->webAppUrl = 'https://staging-app.yousign.com';
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
        return 'yousign';
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

            $webhookParams = [
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
            ];
        }

        $result = $this->requester->post('/procedures', [
            'name' => $scenario->getTitle(),
            'description' => $scenario->getTitle(),
            'template' => false,
            'start' => false,
            // Yousign does not save the time, only the date => go to begining of next day
            'expiresAt' => Carbon::now()->addDays($this->getExpirationDays() + 1)->startOfDay()->toIso8601ZuluString(), //format('Y-m-d'),
            'metadata' => [
                'customId' => $scenario->getCustomId(),
            ],
            //'ordered' => true,
            'config' => $config,
        ]);

        $procedure = $this->checkedApiResult($result);

        // keep track of yousign generated ids : "php-sign document id" => "yousign file id"
        $fileIds = [];

        // keep track of yousign generated ids : "php-sign signer id" => "yousign member id"
        $memberIds = [];

        foreach($scenario->getDocuments() as $scDocument){

            $metadata = $scDocument->getMetadata();

            // When retreiving the documents later, we have no way to identify them !
            // So, let's use the metadata to store the document ID :
            $metadata['temporary-internal-document-id'] = $scDocument->getId();

            $result = $this->requester->post('/files', [
                'name' => $scDocument->getName(),
                'content' => base64_encode(file_get_contents($scDocument->getFilePath())),
                'procedure' => $procedure['id'],
                'metadata' => $metadata,
            ]);

            $file = $this->checkedApiResult($result);

            $fileIds[$scDocument->getId()] = $file['id'];
        }

        foreach($scenario->getSigners() as $scSigner){

            if(!$scSigner->getPhone()){
                throw new SignException('The phone number is required by Yousign');
            }

            if(!$scSigner->getPhone()){
                throw new SignException('The email is required by Yousign');
            }

            $data = [
                'position' => $scSigner->getId(),
                'firstname' => $scSigner->getFirstname(),
                'lastname' => $scSigner->getLastname(),
                'email' => $scSigner->getEmail(),
                'phone' => $scSigner->getPhone(),
                'procedure' => $procedure['id'],
                'type' => 'signer',
                'operationLevel' => 'custom', // none, custom
                'operationCustomModes' => ['sms'], // sms, inwebo, email
                'modeSmsConfiguration' => [
                    'content' => "Hello, your signature code is {{code}}"
                ],
            ];

            $result = $this->requester->post('/members', $data);

            $member = $this->checkedApiResult($result);

            $memberIds[$scSigner->getId()] = $member['id'];
        }

        foreach($scenario->getSignatures() as $scSignature){

            $fileObject = $this->requester->post('/file_objects', [
                'file' => $fileIds[$scSignature->getDocumentId()],
                'member' => $memberIds[$scSignature->getSignerId()],
                'position' => implode(',', [
                    $scSignature->getX(),
                    $scSignature->getY(),
                    $scSignature->getX() + $scSignature->getWidth(),
                    $scSignature->getY() + $scSignature->getHeight(),
                ]),
                'page' => $scSignature->getPage(),
                'mention' => $scSignature->getLabel(),
                'mention2' => '',
            ]);
        }

        $result = $this->requester->put($procedure['id'], [
            'start' => true,
        ]);

        $procedure = $this->checkedApiResult($result);
        return $this->getTransaction($procedure['id']);
    }

    /**
     * Get transaction
     *
     * @param  string  $transactionId
     * @return Transaction
     */
    public function getTransaction(string $transactionId)
    {
        $result = $this->requester->get($transactionId);
        $procedure = $this->checkedApiResult($result);

        $signUrlBase = $this->webAppUrl.'/procedure/sign?';
        $signers = [];

        foreach($procedure['members'] as $i => $member){

            $signer = new SignerResult();

            $signer->setId($i + 1);
            $signer->setFirstname($member['firstname']);
            $signer->setLastname($member['lastname']);
            $signer->setEmail($member['email']);
            $signer->setPhone($member['phone']);
            $signer->setUrl($signUrlBase.http_build_query(['members' => $member['id']]));
            $signer->setStatus(self::convertSignerStatus($member['status']));
            //$signer->setError();

            foreach($member['fileObjects'] as $fileObject){
                if(isset($fileObject['executedAt'])){
                    $signer->setActionAt(DateParser::parse($fileObject['executedAt']));
                }
            }
            
            $signers[] = $signer;
        }

        $transaction = new Transaction($this->getName());
        $transaction->setId($transactionId);
        $transaction->setStatus(self::convertTransactionStatus($procedure['status']));
        $transaction->setSigners($signers);
        $transaction->setCreatedAt(DateParser::parse($procedure['createdAt']));
        $transaction->setExpireAt(DateParser::parse($procedure['expiresAt']));
        $transaction->setTitle($procedure['name']);

        if(isset($procedure['metadata']) && isset($procedure['metadata']['customId'])){
            $transaction->setCustomId($procedure['metadata']['customId']);
        }

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

            $result = $this->requester->get($transactionId);
            $procedure = $this->checkedApiResult($result);

            foreach($procedure['files'] as $i => $procedureFile)
            {
                $fileResult = $this->requester->get($procedureFile['id'].'/download');
                $content = base64_decode($this->checkedApiResult($fileResult));

                // We use the metadata to identify our documents
                $metadata = $procedureFile['metadata'];
                $documentId = $metadata['temporary-internal-document-id'];
                unset($metadata['temporary-internal-document-id']);

                $document = new DocumentResult();
                $document->setId($documentId);
                $document->setName($procedureFile['name']);
                //$document->setUrl($url);
                $document->setMetadata($metadata);
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
        $result = $this->requester->delete($transactionId);
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
        return 14;
    }

    /**
     * Format Yousign API exceptions
     *
     * @param  object  $apiResult
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
            throw new \Exception($message, $apiResult->getStatusCode());
        }

        return $data;
    }

    /**
     * Convert Yousign transaction status to PhpSign transaction status
     *
     * @param  string  $yousignStatus
     * @return string
     */
    protected function convertTransactionStatus(string $yousignStatus)
    {
        $status = Transaction::STATUS_UNKNOWN;

        switch ($yousignStatus) {

            case 'draft':
                $status = Transaction::STATUS_DRAFT;
                break;

            case 'active':
                $status = Transaction::STATUS_READY;
                break;

            case 'finished':
                $status = Transaction::STATUS_COMPLETED;
                break;

            case 'expired':
                $status = Transaction::STATUS_EXPIRED;
                break;

            case 'refused':
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
     *
     * @param  string  $yousignStatus
     * @return string
     */
    protected function convertSignerStatus(string $yousignStatus)
    {
        $status = SignerResult::STATUS_UNKNOWN;

        switch ($yousignStatus) {

            // The signer has not signed yet
            case 'pending':
                $status = SignerResult::STATUS_READY;
                break;

            // The signer has accessed the signature service.
            case 'processing':
                $status = SignerResult::STATUS_ACCESSED;
                break;

            // The signer has successfully signed.
            case 'done':
                $status = SignerResult::STATUS_SIGNED;
                break;

            // The signer refused to sign, or one of the previous signers canceled or failed its signature.
            case 'refused':
                $status = SignerResult::STATUS_CANCELED;
                break;

            default:
                $status = SignerResult::STATUS_UNKNOWN;
                break;
        }
        return $status;
    }
}
