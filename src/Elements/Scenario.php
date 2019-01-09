<?php

namespace Helori\PhpSign\Elements;

use Helori\PhpSign\Exceptions\NotFoundException;
use Helori\PhpSign\Exceptions\ValidationException;


class Scenario
{
    const INVITATION_MODE_NONE = 'none';
    const INVITATION_MODE_EMAIL = 'email';
    const INVITATION_MODE_CHAIN = 'chain';

    /**
     * The scenario title
     *
     * @var string
     */
    protected $title;

    /**
     * The signers
     *
     * @var array
     */
    protected $signers;

    /**
     * The documents to sign
     *
     * @var array
     */
    protected $documents;

    /**
     * The signatures locations for the signers on the documents
     *
     * @var array
     */
    protected $signatures;

    /**
     * The URL used to send status changes requests
     *
     * @var string
     */
    protected $statusUrl;

    /**
     * The URL used to redirect the user after a successful signature
     *
     * @var string
     */
    protected $successUrl;

    /**
     * The URL used to redirect the user after a canceled signature
     *
     * @var string
     */
    protected $cancelUrl;

    /**
     * The URL used to redirect the user after a failed signature
     *
     * @var string
     */
    protected $errorUrl;

    /**
     * The language used in the signature UI
     *
     * @var string
     */
    protected $lang = 'en';

    /**
     * Allowed languages
     *
     * @var array
     */
    protected $allowedLanguages = ['en', 'fr'];

    /**
     * The way signers are invited to sign their documents
     *
     * @var array
     */
    protected $invitationMode = 'none';

    /**
     * A custom id to identify the resulting transaction
     *
     * @var string
     */
    protected $customId;

    /**
     * Create a new Sign instance.
     *
     * @return void
     */
    public function __construct()
    {
        
    }

    /**
     * Get the scenario title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set the scenario title
     *
     * @param  string  $title
     * @return string
     */
    public function setTitle($title)
    {
        return $this->title = $title;
    }

    /**
     * Get the signers
     *
     * @return array
     */
    public function getSigners()
    {
        return $this->signers;
    }

    /**
     * Get a specific signer from its id
     *
     * @return Signer
     */
    public function getSigner(int $id)
    {
        $signer = null;

        foreach($this->signers as $signer){
            if($signer->id === $id){
                return $signer;
            }
        }

        if(is_null($signer)){
            throw new NotFoundException('Signer with id "'.$id.'" could not be found');
        }

        return $signer;
    }

    /**
     * Add new signers
     *
     * @param  array  $signers
     * @return array
     */
    public function setSigners(array $signers)
    {
        foreach($signers as $signer){
            $this->setSigner($signer);
        }

        return $this->signers;
    }

    /**
     * Add a new signer
     *
     * @param  Signer  $signer
     * @return Signer
     */
    public function setSigner(Signer $signer)
    {
        $this->signers[] = $signer;
        return $signer;
    }

    /**
     * Get the documents
     *
     * @return array
     */
    public function getDocuments()
    {
        return $this->documents;
    }

    /**
     * Get a specific document from its id
     *
     * @return Document
     */
    public function getDocument(int $id)
    {
        $document = null;

        foreach($this->documents as $document){
            if($document->id === $id){
                return $document;
            }
        }

        if(is_null($document)){
            throw new NotFoundException('Document with id "'.$id.'" could not be found');
        }

        return $document;
    }

    /**
     * Add new documents
     *
     * @param  array  $documents
     * @return array
     */
    public function setDocuments(array $documents)
    {
        foreach($documents as $document){
            $this->setDocument($document);
        }

        return $this->documents;
    }

    /**
     * Add a new document
     *
     * @param  Document  $document
     * @return Document
     */
    public function setDocument(Document $document)
    {
        $this->documents[] = $document;
        return $document;
    }

    /**
     * Get the signatures
     *
     * @return array
     */
    public function getSignatures()
    {
        return $this->signatures;
    }

    /**
     * Add new signatures
     *
     * @param  array  $signatures
     * @return array
     */
    public function setSignatures(array $signatures)
    {
        foreach($signatures as $signature){
            $this->setSignature($signature);
        }

        return $this->signatures;
    }

    /**
     * Add a new signature
     *
     * @param  Signature  $signature
     * @return Signature
     */
    public function setSignature(Signature $signature)
    {
        $this->signatures[] = $signature;
        return $signature;
    }

    /**
     * Get the URL used to send status changes
     *
     * @return string
     */
    public function getStatusUrl()
    {
        return $this->statusUrl;
    }

    /**
     * Set the URL used to send status changes
     *
     * @param  string  $statusUrl
     * @return string
     */
    public function setStatusUrl($statusUrl)
    {
        return $this->statusUrl = $statusUrl;
    }

    /**
     * Get the URL used to redirect the user after a successful signature
     *
     * @return string
     */
    public function getSuccessUrl()
    {
        return $this->successUrl;
    }

    /**
     * Set the URL used to redirect the user after a successful signature
     *
     * @param  string  $successUrl
     * @return string
     */
    public function setSuccessUrl($successUrl)
    {
        return $this->successUrl = $successUrl;
    }

    /**
     * Get the URL used to redirect the user after a canceled signature
     *
     * @return string
     */
    public function getCancelUrl()
    {
        return $this->cancelUrl;
    }

    /**
     * Set the URL used to redirect the user after a canceled signature
     *
     * @param  string  $cancelUrl
     * @return string
     */
    public function setCancelUrl($cancelUrl)
    {
        return $this->cancelUrl = $cancelUrl;
    }

    /**
     * Get the URL used to redirect the user after a failed signature
     *
     * @return string
     */
    public function getErrorUrl()
    {
        return $this->errorUrl;
    }

    /**
     * Set the URL used to redirect the user after a failed signature
     *
     * @param  string  $errorUrl
     * @return string
     */
    public function setErrorUrl($errorUrl)
    {
        return $this->errorUrl = $errorUrl;
    }

    /**
     * Get the language used in the signature UI
     *
     * @return string
     */
    public function getLang()
    {
        return $this->lang;
    }

    /**
     * Set the language used in the signature UI
     *
     * @param  string  $lang
     * @return string
     */
    public function setLang($lang)
    {
        if(!in_array($lang, $this->allowedLanguages)){

            throw new ValidationException('Signature UI : The language "'.$lang.'" must be one of '.implode(', ', $this->allowedLanguages));
        }

        return $this->lang = $lang;
    }

    /**
     * Get the allowed languages
     *
     * @return array
     */
    public function getAllowedLanguages()
    {
        return $this->allowedLanguages;
    }

    /**
     * Get the way signers are invited to sign their documents
     *
     * @return string
     */
    public function getInvitationMode()
    {
        return $this->invitationMode;
    }

    /**
     * Set the way signers are invited to sign their documents
     *
     * @param  string  $invitationMode
     * @return string
     */
    public function setInvitationMode($invitationMode)
    {
        return $this->invitationMode = $invitationMode;
    }

    /**
     * Get the custom ID of the resulting transaction
     *
     * @return string
     */
    public function getCustomId()
    {
        return $this->customId;
    }

    /**
     * Set the custom ID of the resulting transaction
     *
     * @param  string  $customId
     * @return string
     */
    public function setCustomId(string $customId)
    {
        return $this->customId = $customId;
    }
}
