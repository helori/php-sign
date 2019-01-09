<?php

namespace Helori\PhpSign\Drivers;

use Helori\PhpSign\Utilities\RestApiRequester;
use Helori\PhpSign\Elements\Scenario;
use Helori\PhpSign\Elements\Transaction;
use Helori\PhpSign\Exceptions\DriverAuthException;
use Helori\PhpSign\Exceptions\ValidationException;
use Helori\PhpSign\Exceptions\SignException;


class YousignDriver implements DriverInterface
{
    /**
     * The Yousign API Requester
     *
     * @var \Helori\PhpSign\Utilities\RestApiRequester
     */
    protected $requester;

    /**
     * Create a new YousignDriver instance.
     *
     * @return void
     */
    public function __construct(array $config)
    {
        $requiredConfigKeys = ['api_key', 'endpoint'];

        foreach($requiredConfigKeys as $key){

            if(!isset($config[$key]) || $config[$key] === ''){

                throw new ValidationException('Yousign config parameter "'.$key.'" must be set');
            }
        }

        $this->requester = new RestApiRequester($config['api_key'], $config['endpoint']);
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
        $data = [
            'name' => $scenario->getTitle(),
            //'description' => '',
            'start' => false,
            //'ordered' => true,
            'config' => [],
        ];

        if($scenario->getStatusUrl()){

            $webhookParams = [
                [
                    'url' => $scenario->getStatusUrl(),
                    'method' => 'GET',
                ]
                // Other webhooks can be added here...
            ];

            $data['config']['webhook'] = [
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

        $result = $this->requester->post('/procedures', $data);
        $procedure = $this->checkedApiResult($result);

        // keep track of yousign generated ids : "php-sign document id" => "yousign file id"
        $fileIds = [];

        // keep track of yousign generated ids : "php-sign signer id" => "yousign member id"
        $memberIds = [];

        foreach($scenario->getDocuments() as $scDocument){

            $result = $this->requester->post('/files', [
                'name' => $scDocument->getName(),
                'content' => base64_encode(file_get_contents($scDocument->getFilePath())),
                'procedure' => $procedure['id'],
            ]);

            $file = $this->checkedApiResult($result);

            $fileIds[$scDocument->getId()] = $file['id'];
        }

        foreach($scenario->getSigners() as $scSigner){

            $result = $this->requester->post('/members', [
                //'position' => $scSigner->getId(),
                'firstname' => $scSigner->getFirstname(),
                'lastname' => $scSigner->getLastname(),
                'email' => $scSigner->getEmail(),
                'phone' => $scSigner->getPhone(),
                'procedure' => $procedure['id'],
            ]);

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

        $result = $this->requester->post('/signature_uis', [
            'languages' => $scenario->getAllowedLanguages(),
            'defaultLanguage' => $scenario->getLang(),
            'redirectCancel' => [
                'url' => $scenario->getCancelUrl(),
                'target' => '_self', // "_top or _blank or _self or _parent
                'auto' => false,
            ],
            'redirectError' => [
                'url' => $scenario->getErrorUrl(),
                'target' => '_self',
                'auto' => false,
            ],
            'redirectSuccess' => [
                'url' => $scenario->getSuccessUrl(),
                'target' => '_self',
                'auto' => false,
            ],

            // TODO : more customization options... 
            // https://dev.yousign.com/
        ]);
        $signatureUI = $this->checkedApiResult($result);

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

        $transaction = new Transaction($this->getName());
        $transaction->setId($transactionId);

        $transactionStatus = Transaction::STATUS_UNKNOWN;

        switch ($procedure['status']) {

            case 'draft':
                $transactionStatus = Transaction::STATUS_DRAFT;
                break;

            case 'active':
                $transactionStatus = Transaction::STATUS_READY;
                break;

            case 'finished':
                $transactionStatus = Transaction::STATUS_COMPLETED;
                break;

            case 'expired':
                $transactionStatus = Transaction::STATUS_EXPIRED;
                break;

            case 'refused':
                $transactionStatus = Transaction::STATUS_REFUSED;
                break;

            default:
                $transactionStatus = Transaction::STATUS_UNKNOWN;
                break;
        }

        $transaction->setStatus($transactionStatus);

        $signUrlBase = 'https://staging-app.yousign.com/procedure/sign?';
        $signersInfos = [];

        foreach($procedure['members'] as $member){

            $signersInfo = [
                'status' => $member['status'],
                'url' => $signUrlBase.http_build_query(['members' => $member['id']]),
                'firstname' => $member['firstname'],
                'lastname' => $member['lastname'],
                'email' => $member['email'],
                'phone' => $member['phone'],
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
        $transaction = $this->getTransaction($transactionId);

        if($transaction->getStatus() === Transaction::STATUS_COMPLETED) {

            $result = $this->requester->get($transactionId);
            $procedure = $this->checkedApiResult($result);

            foreach($procedure['files'] as $procedureFile)
            {
                $fileResult = $this->requester->get($procedureFile['id'].'/download');
                $fileContent = base64_decode($this->checkedApiResult($fileResult));

                $files[] = [
                    'name' => $procedureFile['name'],
                    'content' => $fileContent,
                ];
            }
        
        }else{

            throw new SignException('Could not download signed files because they are not signed yet.');
        }

        return $files;
    }

    /**
     * Cancel a transaction
     *
     * @param  string  $transactionId
     * @return \Helori\PhpSign\Elements\Transaction
     */
    public function cancelTransaction(string $transactionId)
    {
        throw new SignException('cancelTransaction is not implemented yet for Yousign');
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
}
