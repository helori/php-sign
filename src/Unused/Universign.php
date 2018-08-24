<?php

use PhpXmlRpc;

$GLOBALS['xmlrpc_internalencoding'] = 'UTF-8';

class Universign
{
    protected $uni_url = '';
    protected $uni_username = '';
    protected $uni_password = '';

    protected $uni_profile = "production"; //"default";

    public function __construct(){
        
        if(env('APP_ENV') === 'production'){

            $this->uni_url = 'https://ws.universign.eu/sign/rpc/';
            $this->uni_username = '';
            $this->uni_password = '';
        
        }else{

            $this->uni_url = 'https://sign.test.cryptolog.com/sign/rpc/';
            $this->uni_username = '';
            $this->uni_password = '';
        }
    }

    // ---------------------------------------------------------------------
    //  Universign signature
    //  After calling this, $_SESSION['uni_id'] will be set.
    //  It can be used later to retrieve the signed documents.
    //
    //  - certType : simple or certified
    //  - chainingMode : This option indicates how the signers are chained during the signing process.
    //      none: must contact physically, email: all signers receive email invitations, web: all signers are present
    // ---------------------------------------------------------------------
    public function prepareToSign($docs, $signers, $returnPage, $certType = 'simple', $chainingMode = 'web', $hand = 0)
    {
        $language = "fr";
        $signers_xml = [];
        $docs_xml = [];

        foreach($signers as $signer)
        {
            $signer_xml = [
                "firstname" => new PhpXmlRpc\Value($signer["firstname"], "string"),
                "lastname" => new PhpXmlRpc\Value($signer["lastname"], "string"),
                "emailAddress" => new PhpXmlRpc\Value($signer["emailAddress"], "string"),
                // the return urls
                "successURL" =>  new PhpXmlRpc\Value($returnPage."success", "string"),
                "failURL" =>  new PhpXmlRpc\Value($returnPage."fail", "string"),
                "cancelURL" =>  new PhpXmlRpc\Value($returnPage."cancel", "string"),
            ];
            if(isset($signer["phoneNum"])){
                $signer_xml['phoneNum'] = new PhpXmlRpc\Value($signer["phoneNum"], "string");
            }
            if(isset($signer["birthDate"])){
                $signer_xml['birthDate'] = new PhpXmlRpc\Value($signer["birthDate"], "dateTime.iso8601");
            }
            $signers_xml[] = new PhpXmlRpc\Value($signer_xml, "struct");
        }
        
        foreach($docs as $doc)
        {
            $signatures_xml = [];
            foreach($doc["signatures"] as $signature)
            {
                $signature_xml = new PhpXmlRpc\Value([
                    "page" => new PhpXmlRpc\Value(intVal($signature["page"]), "int"),
                    "x" => new PhpXmlRpc\Value(intVal($signature["x"]), "int"),
                    "y" => new PhpXmlRpc\Value(intVal($signature["y"]), "int"),
                    "signerIndex" => new PhpXmlRpc\Value($signature["signerIndex"], "int"),
                    "label" => new PhpXmlRpc\Value($signature["label"], "string")
                ], "struct");
                $signatures_xml[] = $signature_xml;
            }

            if(!is_file($doc["filepath"]))
                abort(500, "Contrat introuvable : ".$d["filepath"]);

            $doc_xml = new PhpXmlRpc\Value([
                "content" => new PhpXmlRpc\Value(file_get_contents($doc["filepath"]), "base64"),
                "name" => new PhpXmlRpc\Value($doc["filename"], "string"),
                "signatureFields" => new PhpXmlRpc\Value($signatures_xml, "array")
            ], "struct");

            $docs_xml[] = $doc_xml;
        }

        $request = [
            "documents" => new PhpXmlRpc\Value($docs_xml, "array"),
            "signers" =>  new PhpXmlRpc\Value($signers_xml, "array"),
            "description" =>  new PhpXmlRpc\Value("Signature de votre souscription SCPI", "string"),
            // handwritten signature : 
            // 0: disabled
            // 1: enabled
            // 2: enabled if touch interface
            "handwrittenSignatureMode" =>  new PhpXmlRpc\Value($hand, "int"),
            // the profile to use
            "profile" =>  new PhpXmlRpc\Value($this->uni_profile, "string"),
            //the types of accepted certificate : all | on-the-fly | local
            "certificateType" => new PhpXmlRpc\Value($certType, "string"),
            "language" => new PhpXmlRpc\Value($language, "string"),
            "identificationType" => new PhpXmlRpc\Value("email", "string"),
            // Tells whether each signer must receive the signed documents by email
            // when the transaction is completed. False by default :
            "finalDocSent" =>  new PhpXmlRpc\Value(false, "boolean"),
            // Tells whether the requester must receive the signed documents via e-mail
            // when the transaction is completed. False by default.
            "finalDocRequesterSent" =>  new PhpXmlRpc\Value(false, "boolean"),
            // If set to True, the first signer will receive an invitation to sign the document(s) by e-mail
            // as soon as the transaction is requested. False by default.
            "mustContactFirstSigner" =>  new PhpXmlRpc\Value(true, "boolean"),
            // This option indicates how the signers are chained during the signing process.
            // none: must contact physically, email: all signers receive email invitations, web: all signers are present
            "chainingMode" => new PhpXmlRpc\Value($chainingMode, "string")
        ];

        return $this->sendRequest('requestTransaction', [
            new PhpXmlRpc\Value($request, "struct")
        ]);
    }

    public function getTransactionInfo($uni_id)
    {
        return $this->sendRequest('getTransactionInfo', [
            new PhpXmlRpc\Value($uni_id, "string")
        ]);
    }

    public function getDocuments($uni_id)
    {
        return $this->sendRequest('getDocuments', [
            new PhpXmlRpc\Value($uni_id, "string")
        ]);
    }
    
    protected function sendRequest($request, $params)
    {
        //create the request
        $c = new PhpXmlRpc\Client($this->uni_url);
        $c->username = $this->uni_username;
        $c->password = $this->uni_password;
        $f = new PhpXmlRpc\Request('requester.'.$request, $params);

        //SSL verification (should be enabled in production)
        $c->setSSLVerifyHost(0);
        $c->setSSLVerifyPeer(0);
        $c->setDebug(0);
        
        //Send request an analyse response
        $r = $c->send($f);
        if(!$r->faultCode()){
            return [    
                "status" => "success",
                "data" => $r
            ];
        }
        else{
            return [
                "status" => "error",
                "code" => $r->faultCode(),
                "message" => $r->faultString()
            ];
        }
    }
};


