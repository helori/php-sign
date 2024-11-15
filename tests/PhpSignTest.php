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

use Symfony\Component\Dotenv\Dotenv;
use Carbon\Carbon;


class PhpSignTest extends TestCase
{
    public function line($variable)
    {
        fwrite(STDERR, "\r\n".print_r($variable, true));
    }

    public function testPhpSign()
    {
        //global $argv, $argc;

        $dotenv = new Dotenv();
        $dotenv->load(getcwd().'/.env');

        /*if(!in_array($driver, ['yousignv3', 'yousign', 'universign', 'docusign'])){
            $this->line("Invalid driver. Allowed drivers are : universign, yousign, docusign");
        }*/

        $documentsCount = 1;
        $signersCount = 1;
        $scenario = $this->createScenario($documentsCount, $signersCount);

        $this->line("---------------------------------------------");
        $this->line("Test signature scenario");
        $this->line("Documents : ".$documentsCount);
        $this->line("Signers : ".$signersCount);
        $this->line("---------------------------------------------");

        // ['yousignv3', 'yousign', 'universign', 'docusign']
        $diversToTest = ['yousignv3'];

        foreach($diversToTest as $driver)
        {
            $this->line("Test PHP-SIGN driver : ".ucfirst($driver));
            $this->line("---------------------------------------------");

            $transaction = $this->createTransaction($driver, $scenario);
            $transactionId = $transaction->getId();

            //$this->inviteToSign($transaction);

            // Test all possible webhooks on existing transaction :
            //$this->webhooks($driver, $transactionId);

            // Test the transaction itself
            //$this->existingTransaction($driver, $transactionId);

            $this->line("---------------------------------------------");
        }
    }

    protected function createTransaction(string $driver, $scenario)
    {
        $this->line("-> Creating transaction...");

        $requester = $this->getRequester($driver);
        $transaction = $requester->createTransaction($scenario);
        $id = $transaction->getId();

        $this->line("-> Transaction created with id : ".$id);

        $this->assertTrue(!is_null($id));
        $this->assertTrue($transaction->getStatus() === Transaction::STATUS_READY);
        $this->assertTrue($transaction->getCreatedAt()->isToday());
        $this->assertTrue(intVal(floor($transaction->getCreatedAt()->diffInDays($transaction->getExpireAt()))) === $requester->getExpirationDays());
        $this->assertTrue($transaction->getCustomId() === $scenario->getCustomId());
        $this->assertTrue($transaction->getTitle() === $scenario->getTitle());

        $signersResult = $transaction->getSigners();
        $this->assertTrue(count($signersResult) === count($scenario->getSigners()));

        foreach($signersResult as $i => $signerResult)
        {
            $this->assertTrue($signerResult->getFirstname() === 'Firstname '.($i + 1));
            $this->assertTrue($signerResult->getLastname() === 'LASTNAME '.($i + 1));
            $this->assertTrue(!is_null($signerResult->getEmail()));
            $this->assertTrue(!is_null($signerResult->getPhone()));
            $this->assertTrue(!is_null($signerResult->getUrl()));
            $this->assertTrue($signerResult->getStatus() === SignerResult::STATUS_READY);
        }

        return $transaction;
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
                'api_key' => $_ENV['YOUSIGN_API_KEY'],
                'mode' => $_ENV['SIGNATURE_MODE'],
            ];
        }else if($driverName === 'yousignv3'){
            $driverConfig = [
                'api_key' => $_ENV['YOUSIGN_API_KEY_V3'],
                'mode' => $_ENV['SIGNATURE_MODE'],
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

    protected function createScenario(int $documentsCount, int $signersCount)
    {
        $documents = [];
        $signers = [];
        $signatures = [];

        for($i=1; $i<=$documentsCount; ++$i)
        {
            $document = new Document();
            $document->setId(1);
            $document->setName('Document '.$i);
            $document->setFilepath(__DIR__.'/Files/document'.$i.'.pdf');
            $document->setMetadata([]);
            $documents[] = $document;
        }

        for($i=1; $i<=$signersCount; ++$i)
        {
            $signer = new Signer();
            $signer->setId($i);
            $signer->setFirstname('Firstname '.$i);
            $signer->setLastname('Lastname '.$i);
            $signer->setEmail('first.last@gmail.com');
            $signer->setPhone('+33659765544');
            $signer->setBirthday(Carbon::now()->subYears(30));
            $signers[] = $signer;

            for($j=1; $j<=$documentsCount; ++$j)
            {
                $signature = new Signature();
                $signature->setSignerId($i);
                $signature->setDocumentId($j);
                $signature->setLabel('Mon label de signature');
                $signature->setPage(1);
                $signature->setLocation([100 * $i, 100, 200, 80]);
                $signatures[] = $signature;
            }
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

        return $scenario;
    }
}
