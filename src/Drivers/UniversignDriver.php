<?php

namespace Helori\PhpSign\Drivers;

use Helori\PhpSign\Utilities\XmlRpcRequester;
use Helori\PhpSign\Elements\Scenario;
use Helori\PhpSign\Elements\Transaction;
use Helori\PhpSign\Exceptions\SignException;
use Helori\PhpSign\Exceptions\ValidationException;
use PhpXmlRpc\Value;

use Carbon\Carbon;


class UniversignDriver implements DriverInterface
{
    /**
     * The Universign API Requester
     *
     * @var \Helori\PhpSign\Utilities\XmlRpcRequester
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
        $requiredConfigKeys = ['username', 'password', 'endpoint'];

        foreach($requiredConfigKeys as $key){

            if(!isset($config[$key]) || $config[$key] === ''){

                throw new ValidationException('Universign config parameter "'.$key.'" must be set');
            }
        }

        $this->requester = new XmlRpcRequester($config['username'], $config['password'], $config['endpoint']);
        $this->profile = $config['profile'];
    }

    /**
     * Get the driver's name
     *
     * @return string
     */
    public function getName()
    {
        return 'universign';
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

            $signer = [
                "firstname" => new Value($scSigner->getFirstname(), "string"),
                "lastname" => new Value($scSigner->getLastname(), "string"),
                "emailAddress" => new Value($scSigner->getEmail(), "string"),
                "successURL" =>  new Value($scenario->getSuccessUrl(), "string"),
                "failURL" =>  new Value($scenario->getErrorUrl(), "string"),
                "cancelURL" =>  new Value($scenario->getCancelUrl(), "string"),
            ];

            // If phone is not set, it will be asked at signature time
            if($scSigner->getPhone()){
                $signer['phoneNum'] = new Value($scSigner->getPhone(), "string");
            }

            $signers[] = new Value($signer, "struct");
        }

        foreach($scenario->getDocuments() as $scDocument){

            $signatures = [];

            foreach($scenario->getSignatures() as $scSignature){

                if($scSignature->getDocumentId() === $scDocument->getId()){

                    $signature = [
                        "page" => new Value($scSignature->getPage(), "int"),
                        "x" => new Value($scSignature->getX(), "int"),
                        "y" => new Value($scSignature->getY(), "int"),
                        "label" => new Value($scSignature->getLabel(), "string"),
                        "signerIndex" => null,
                    ];

                    $signerIndex = null;
                    foreach($scenario->getSigners() as $index => $scSigner){
                        if($scSigner->getId() === $scSignature->getSignerId()){
                            $signature['signerIndex'] = new Value($index, "int");
                        }
                    }
                    if(is_null($signature['signerIndex'])){

                        throw new ValidationException('The signature\'s signerId "'.$scSignature->getSignerId().'" has no corresponding signer');
                    }

                    $signatures[] = new Value($signature, "struct");
                }
            }

            $document = [
                "content" => new Value(file_get_contents($scDocument->getFilepath()), "base64"),
                "name" => new Value($scDocument->getName(), "string"),
                "signatureFields" => new Value($signatures, "array")
            ];

            $documents[] = new Value($document, "struct");
        }

        $request = [
            // the profile to use
            "profile" => new Value($this->profile, "string"),
            "signers" => new Value($signers, "array"),
            "documents" => new Value($documents, "array"),
            "description" => new Value($scenario->getTitle(), "string"),
            // Possible types : certified, advanced, simple
            "certificateType" => new Value("simple", "string"),
            // The interface language for this transaction
            "language" => new Value($scenario->getLang(), "string"),
            // handwritten signature : 
            // 0: disabled
            // 1: enabled
            // 2: enabled if touch interface
            "handwrittenSignatureMode" => new Value(2, "int"),
            // This option indicates how the signers are chained during the signing process.
            // none: must contact physically, email: all signers receive email invitations, web: all signers are present
            "chainingMode" => new Value($this->chainingModeFromInvitation($scenario->getInvitationMode()), "string"),

            // If set to True, the first signer will receive an invitation to sign the document(s) by e-mail
            // as soon as the transaction is requested. False by default.
            "mustContactFirstSigner" => new Value($this->mustContactFirstFromInvitation($scenario->getInvitationMode()), "boolean"),
            // Tells whether each signer must receive the signed documents by email
            // when the transaction is completed. False by default :
            "finalDocSent" => new Value(false, "boolean"),
            // Tells whether the requester must receive the signed documents via e-mail
            // when the transaction is completed. False by default.
            "finalDocRequesterSent" =>  new Value(false, "boolean"),
            // Tells whether the observers must receive the signed documents via e-mail
            // when the transaction is completed. It takes the finalDocSent value by default.
            "finalDocObserverSent" =>  new Value(false, "boolean"),
        ];

        $response = $this->requester->sendRequest('requester.requestTransaction', [
            new Value($request, "struct")
        ]);

        $transactionId = $response->structMem('id')->scalarVal();
        return $this->getTransaction($transactionId);
    }

    /**
     * Get transaction
     *
     * @param  string  $transactionId
     * @return Transaction
     */
    public function getTransaction(string $transactionId)
    {
        $response = $this->requester->sendRequest('requester.getTransactionInfo', [
            new Value($transactionId, "string")
        ]);

        $signersInfos = [];

        foreach($response->structMem('signerInfos')->scalarVal() as $signerInfo){

            $signersInfos[] = [
                'status' => $signerInfo->structMem('status')->scalarVal(),
                'url' => $signerInfo->structMem('url')->scalarVal(),
                'email' => $signerInfo->structMem('email')->scalarVal(),
                'firstname' => $signerInfo->structMem('firstName')->scalarVal(),
                'lastname' => $signerInfo->structMem('lastName')->scalarVal(),
                'action_date' => $signerInfo['actionDate'] ? $signerInfo->structMem('actionDate')->scalarVal() : '',
                //'error' => $signerInfo->structMem('error')->scalarVal(),
                //'certificateInfo' => $signerInfo->certificateInfo,
                //'refusedDocs' => $signerInfo->refusedDocs,
            ];
        }
        
        $transactionStatus = Transaction::STATUS_UNKNOWN;

        switch ($response->structMem('status')->scalarVal()) {

            case 'ready':
                $transactionStatus = Transaction::STATUS_READY;
                break;

            case 'expired':
                $transactionStatus = Transaction::STATUS_EXPIRED;
                break;

            case 'canceled':
                $transactionStatus = Transaction::STATUS_CANCELED;
                break;

            case 'failed':
                $transactionStatus = Transaction::STATUS_FAILED;
                break;

            case 'completed':
                $transactionStatus = Transaction::STATUS_COMPLETED;
                break;
            
            default:
                $transactionStatus = Transaction::STATUS_UNKNOWN;
                break;
        }

        $transaction = new Transaction($this->getName());
        $transaction->setId($transactionId);
        $transaction->setSignersInfos($signersInfos);
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

        if($transaction->getStatus() === Transaction::STATUS_COMPLETED) {

            $response = $this->requester->sendRequest('requester.getDocuments', [
                new Value($transactionId, "string")
            ]);
            
            for($i = 0; $i < $response->arraySize(); $i++)
            {
                $files[] = [
                    'name' => $response->arrayMem($i)->structMem('name')->scalarVal(),
                    'content' => $response->arrayMem($i)->structMem('content')->scalarVal(),
                ];
            }
        
        }else{

            throw new SignException('Could not download signed files because they are not signed yet.');
        }

        return $files;
    }

    /**
     * Get chaining mode to use from a scenario's invitation mode
     *
     * @param  string  $invitationMode
     * @return string  none: must contact physically, email: all signers receive email invitations, web: all signers are present
     */
    protected function chainingModeFromInvitation(string $invitationMode){

        $chainingMode = 'none';
        
        if($invitationMode === Scenario::INVITATION_MODE_EMAIL){
            $chainingMode = 'email';
        }else if($invitationMode === Scenario::INVITATION_MODE_CHAIN){
            $chainingMode = 'web';
        }

        return $chainingMode;
    }

    /**
     * Know if must contact first signer from a scenario's invitation mode
     *
     * @param  string  $invitationMode
     * @return string
     */
    protected function mustContactFirstFromInvitation(string $invitationMode){

        return ($invitationMode === Scenario::INVITATION_MODE_EMAIL);
    }
}
