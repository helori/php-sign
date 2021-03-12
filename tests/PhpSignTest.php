<?php

use PHPUnit\Framework\TestCase;

use Helori\PhpSign\Elements\Requester;
use Helori\PhpSign\Elements\Signer;
use Helori\PhpSign\Elements\Document;
use Helori\PhpSign\Elements\Signature;
use Helori\PhpSign\Elements\Scenario;
use Helori\PhpSign\Elements\Transaction;
use Helori\PhpSign\Elements\SignerResult;
use Helori\PhpSign\Elements\Webhook;

use Carbon\Carbon;


class PhpSignTest extends TestCase
{
    public function line($variable)
    {
        fwrite(STDERR, "\r\n".print_r($variable, true));
    }

    public function testPhpSign()
    {
        global $argv, $argc;
        
        if($argc === 2){

            $this->line("Please specify a driver : universign, yousign, docusign");

        }else if($argc === 3){

            // => create a new transaction for the specified driver
            $driver = $argv[2];
            if(!in_array($driver, ['yousign', 'universign', 'docusign'])){
                $this->line("Invalid driver. Allowed drivers are : universign, yousign, docusign");
            }
            $this->newTransaction($driver);

        }else if($argc === 4){

            // A driver and a transaction are specified
            // => test an existing transaction
            $driver = $argv[2];
            $transactionId = $argv[3];

            // Test all possible webhooks on existing transaction :
            $this->webhooks($driver, $transactionId);

            // Test the transaction itself
            $this->existingTransaction($driver, $transactionId);
        
        }else{

            $this->assertTrue(false);
        }
    }

    protected function newTransaction(string $driver)
    {
        $this->line("---------------------------");
        $this->line(ucfirst($driver)." Test");
        $this->line("---------------------------");

        $documents = [];
        $signers = [];
        $signatures = [];

        $document1 = new Document();
        $document1->setId(1);
        $document1->setName('Document 1');
        $document1->setFilepath(__DIR__.'/Files/document1.pdf');
        $document1->setMetadata([
            'localId1' => '123',
            'localId2' => 456,
        ]);
        $documents[] = $document1;

        $document2 = new Document();
        $document2->setId(2);
        $document2->setName('Document 2');
        $document2->setFilepath(__DIR__.'/Files/document2.pdf');
        $document2->setMetadata([]);
        $documents[] = $document2;

        $signer1 = new Signer();
        $signer1->setId(1);
        $signer1->setFirstname('Jean');
        $signer1->setLastname('Moulin');
        $signer1->setEmail('helori.lanos@gmail.com');
        $signer1->setPhone('+33659765544');
        $signer1->setBirthday(Carbon::now()->subYears(30));
        $signers[] = $signer1;

        $signature11 = new Signature();
        $signature11->setSignerId(1);
        $signature11->setDocumentId(1);
        $signature11->setLabel('Mon label de signature');
        $signature11->setPage(1);
        $signature11->setLocation([100, 100, 200, 80]);
        $signatures[] = $signature11;

        $signature12 = new Signature();
        $signature12->setSignerId(1);
        $signature12->setDocumentId(2);
        $signature12->setLabel('Mon label de signature');
        $signature12->setPage(1);
        $signature12->setLocation([100, 100, 200, 80]);
        $signatures[] = $signature12;

        $useSigner2 = true;

        if($useSigner2){

            $signer2 = new Signer();
            $signer2->setId(2);
            $signer2->setFirstname('Jeanne');
            $signer2->setLastname('Mouline');
            $signer2->setEmail('helori.lanos@gmail.com');
            $signer2->setPhone('+33659765544');
            $signers[] = $signer2;

            $signature21 = new Signature();
            $signature21->setSignerId(2);
            $signature21->setDocumentId(1);
            $signature21->setLabel('Mon label de co-signature');
            $signature21->setPage(1);
            $signature21->setLocation([100, 100, 200, 80]);
            $signatures[] = $signature21;

            $signature22 = new Signature();
            $signature22->setSignerId(2);
            $signature22->setDocumentId(2);
            $signature22->setLabel('Mon label de co-signature');
            $signature22->setPage(1);
            $signature22->setLocation([100, 100, 200, 80]);
            $signatures[] = $signature22;
        }
        
        $scenario = new Scenario();
        $scenario->setTitle('Mon scénario de signature');
        $scenario->setSigners($signers);
        $scenario->setDocuments($documents);
        $scenario->setSignatures($signatures);
        $scenario->setLang('fr');
        $scenario->setSuccessUrl('https://www.google.com/');
        $scenario->setErrorUrl('https://www.google.com/');
        $scenario->setCancelUrl('https://www.google.com/');
        $scenario->setInvitationMode(Scenario::INVITATION_MODE_CHAIN);
        $scenario->setCustomId(uniqId()."");
        //$scenario->setStatusUrl('');

        $requester = $this->getRequester($driver);
        $transaction = $requester->createTransaction($scenario);
        $id = $transaction->getId();

        $this->assertTrue(!is_null($id));
        $this->assertTrue($transaction->getStatus() === Transaction::STATUS_READY);
        $this->assertTrue($transaction->getCreatedAt()->isToday());
        $this->assertTrue($transaction->getCreatedAt()->diffInDays($transaction->getExpireAt()) === $requester->getExpirationDays());
        $this->assertTrue($transaction->getCustomId() === $scenario->getCustomId());
        $this->assertTrue($transaction->getTitle() === $scenario->getTitle());

        $signersResult = $transaction->getSigners();
        $this->assertTrue(count($signersResult) === ($useSigner2 ? 2 : 1));

        $signer1Result = $signersResult[0];
        $this->assertTrue($signer1Result->getFirstname() === $signer1->getFirstname());
        $this->assertTrue($signer1Result->getLastname() === $signer1->getLastname());
        $this->assertTrue($signer1Result->getEmail() === $signer1->getEmail());
        $this->assertTrue(!is_null($signer1Result->getUrl()));
        $this->assertTrue($signer1Result->getStatus() === SignerResult::STATUS_READY);

        $this->line("-> Transaction created with ID : ".$id);

        $this->inviteToSign($transaction);
    }


    protected function existingTransaction(string $driver, string $transactionId)
    {
        $requester = $this->getRequester($driver);
        $transaction = $requester->getTransaction($transactionId);
        $this->assertTrue(!is_null($transaction->getId()));

        if($transaction->getStatus() === Transaction::STATUS_READY){

            $this->inviteToSign($transaction);

        }else if($transaction->getStatus() === Transaction::STATUS_COMPLETED){

            $documents = $requester->getDocuments($transactionId);
            $this->assertTrue(count($documents) === 2);

            foreach($documents as $i => $document){

                $this->assertTrue($document->getName() === 'Document '.$document->getId());
                $this->assertTrue(!empty($document->getContent()));
                
                $metadata = $document->getMetadata();
                $this->assertTrue(is_array($metadata));

                if($document->getId() === 1){

                    $this->assertTrue(isset($metadata['localId1']));
                    $this->assertTrue(isset($metadata['localId2']));
                    $this->assertTrue($metadata['localId1'] === '123');
                    $this->assertTrue($metadata['localId2'] === 456);

                }else if($document->getId() === 2){
                    
                    $this->assertTrue(is_array($metadata));
                }
            }

        }else{

            $this->line("-> Transaction infos : ");
            $this->line($transaction->toArray());
        }
    }

    protected function webhooks(string $driverName, string $transactionId)
    {
        $requester = $this->getRequester($driverName);

        if($driverName === 'universign'){

            $webhook = $requester->formatWebhook([
                'id' => $transactionId,
                'status' => 0
            ]);
            $this->assertTrue($webhook->getTransactionStatus() === Transaction::STATUS_READY);

            $webhook = $requester->formatWebhook([
                'id' => $transactionId,
                'status' => 1
            ]);
            $this->assertTrue($webhook->getTransactionStatus() === Transaction::STATUS_EXPIRED);

            $webhook = $requester->formatWebhook([
                'id' => $transactionId,
                'status' => 2
            ]);
            $this->assertTrue($webhook->getTransactionStatus() === Transaction::STATUS_COMPLETED);

            $webhook = $requester->formatWebhook([
                'id' => $transactionId,
                'status' => 3
            ]);
            $this->assertTrue($webhook->getTransactionStatus() === Transaction::STATUS_REFUSED);

            $webhook = $requester->formatWebhook([
                'id' => $transactionId,
                'status' => 4
            ]);
            $this->assertTrue($webhook->getTransactionStatus() === Transaction::STATUS_FAILED);
        
        }else if($driverName === 'yousign'){

            $webhook = $requester->formatWebhook([
                'procedure' => $transactionId,
                'eventName' => 'procedure.started',
            ]);
            $this->assertTrue($webhook->getTransactionStatus() === Transaction::STATUS_READY);

            $webhook = $requester->formatWebhook([
                'procedure' => $transactionId,
                'eventName' => 'procedure.finished',
            ]);
            $this->assertTrue($webhook->getTransactionStatus() === Transaction::STATUS_COMPLETED);

            $webhook = $requester->formatWebhook([
                'procedure' => $transactionId,
                'eventName' => 'procedure.refused',
            ]);
            $this->assertTrue($webhook->getTransactionStatus() === Transaction::STATUS_REFUSED);

            $webhook = $requester->formatWebhook([
                'procedure' => $transactionId,
                'eventName' => 'procedure.expired',
            ]);
            $this->assertTrue($webhook->getTransactionStatus() === Transaction::STATUS_EXPIRED);

            $webhook = $requester->formatWebhook([
                'procedure' => $transactionId,
                'eventName' => 'member.started',
            ]);
            $this->assertTrue($webhook->getTransactionStatus() === Transaction::STATUS_READY);

            $webhook = $requester->formatWebhook([
                'procedure' => $transactionId,
                'eventName' => 'member.finished',
            ]);
            $this->assertTrue($webhook->getTransactionStatus() === Transaction::STATUS_READY);

        }
    }

    protected function getRequester(string $driverName)
    {
        $driverName = $driverName;
        $driverConfig = [];

        if($driverName === 'universign'){
            $driverConfig = [
                'username' => '<username>',
                'password' => '<password>',
                'profile' => '<universign_profile>',
                'mode' => '<enviroment_type>',
            ];
        }else if($driverName === 'yousign'){
            $driverConfig = [
                'api_key' => '<your_api_key>',
                'mode' => '<enviroment_type>',
            ];
        }else if($driverName === 'docusign'){

            $driverConfig = [
                'mode' => '<enviroment_type>', // development environment or in production possible values (production, test, developer sandbox...)
                'username' => '<username>', //optional
                'password' => '<password>', //optional
                'integrator_key' => '<application_integration_key>',
                'user_id' => '<docusign_api_username>',
                'redirect_uri' => '<application_redirect_uri>',
                'private_key' => '<application_rsa_private_key>',
            ];
        }
        return new Requester($driverName, $driverConfig);
    }

    protected function inviteToSign($transaction)
    {
        global $argv;

        $this->line("-> Transaction retreived with ID : ".$transaction->getId());
        $this->line("-> Transaction status is : ".Transaction::getStatusText($transaction->getStatus()));
        foreach($transaction->getSigners() as $signer){
            $this->line("-> Sign the documents at : ".$signer->getUrl());
        }
        $this->line("-> Relaunch the test with : ".implode(' ', $argv)." ".$transaction->getId());
        $this->line("-----------------------------------");
    }
}
