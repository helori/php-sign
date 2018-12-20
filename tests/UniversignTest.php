<?php

use PHPUnit\Framework\TestCase;

use Helori\PhpSign\Elements\Requester;
use Helori\PhpSign\Elements\Signer;
use Helori\PhpSign\Elements\Document;
use Helori\PhpSign\Elements\Signature;
use Helori\PhpSign\Elements\Scenario;


class Universigntest extends TestCase
{
    public function testUniversign()
    {
        $documents = [];
        $signers = [];
        $signatures = [];

        $document1 = new Document();
        $document1->setId(1);
        $document1->setName('Document 1');
        $document1->setFilepath(__DIR__.'/Files/document1.pdf');
        $documents[] = $document1;

        $document2 = new Document();
        $document2->setId(2);
        $document2->setName('Document 2');
        $document2->setFilepath(__DIR__.'/Files/document2.pdf');
        $documents[] = $document2;

        $signer1 = new Signer();
        $signer1->setId(1);
        $signer1->setFirstname('Jean');
        $signer1->setLastname('Moulin');
        $signer1->setEmail('helori.lanos@gmail.com');
        $signer1->setPhone('+33659765544');
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
        //$scenario->setStatusUrl('');

        $driverName = 'universign';
        $driverConfig = [
            'endpoint' => 'https://sign.test.cryptolog.com/sign/rpc/',
            'username' => 'helori.lanos@francescpi.com',
            'password' => 'Melanie',
            'profile' => 'production',
        ];

        $requester = new Requester($driverName, $driverConfig);
        $transaction = $requester->createTransaction($scenario);

        dd($transaction);

        $this->assertTrue(!is_null($transaction->getId()));
    }
}
