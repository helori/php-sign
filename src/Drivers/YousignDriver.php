<?php

namespace Helori\PhpSign\Drivers;

use Yousign\Authentication;
use Yousign\ClientFactory;
use Yousign\Environment;
use Yousign\Client;

use Helori\PhpSign\Utilities\RestApiRequester;
use Helori\PhpSign\Elements\Scenario;
use Helori\PhpSign\Exceptions\DriverAuthException;
use Helori\PhpSign\Exceptions\ValidationException;


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
     * Initialize a transaction from a scenario
     *
     * @param  \Helori\PhpSign\Elements\Scenario  $scenario
     * @return array
     */
    public function initTransaction(Scenario $scenario)
    {
        $procedure = $this->requester->post('/procedures', [
            'name' => $scenario->getTitle(),
            'description' => '',
            'start' => false,
            //'ordered' => true,
            /*'config' => [
                'webhook' => [
                    'member.finished' => [
                        'url' => 'https://algoart.fr',
                        'method' => 'POST',
                    ]
                ]
            ]*/
        ]);

        // keep track of yousign generated ids : "php-sign document id" => "yousign file id"
        $fileIds = [];

        // keep track of yousign generated ids : "php-sign signer id" => "yousign member id"
        $memberIds = [];

        foreach($scenario->getDocuments() as $scDocument){

            $file = $this->requester->post('/files', [
                'name' => $scDocument->getName(),
                'content' => base64_encode(file_get_contents($scDocument->getFilePath())),
                'procedure' => $procedure['id'],
            ]);

            $fileIds[$scDocument->getId()] = $file['id'];
        }

        foreach($scenario->getSigners() as $scSigner){

            $member = $this->requester->post('/members', [
                //'position' => $scSigner->getId(),
                'firstname' => $scSigner->getFirstname(),
                'lastname' => $scSigner->getLastname(),
                'email' => $scSigner->getEmail(),
                'phone' => $scSigner->getPhone(),
                'procedure' => $procedure['id'],
            ]);

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

        $procedure = $this->requester->put($procedure['id'], [
            'start' => true,
        ]);

        $signUrlBase = 'https://staging-app.yousign.com/procedure/sign?';
        $signUrls = [];

        foreach($memberIds as $memberId){

            $signUrls[] = $signUrlBase.http_build_query([
                'members' => $memberId,
                //'signature_uis' => '',
            ]);
        }

        return $signUrls;
        

        /*$signatureUi = $this->requester->post('/signature_uis', [
            'name' => 'Signature UI',
            'description' => '',
            'defaultZoom' => 100,
            'languages' => [ 'fr' ],
            'defaultLanguage' => 'fr',
            'redirectCancel' => [
                'url' => '',
                'target' => '_self_',
                'auto' => false,
            ],
            'redirectError' => [
                'url' => '',
                'target' => '_self_',
                'auto' => false,
            ],
            'redirectSuccess' => [
                'url' => '',
                'target' => '_self_',
                'auto' => false,
            ],
        ]);*/
    }
}
