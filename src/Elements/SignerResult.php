<?php

namespace Helori\PhpSign\Elements;

use Helori\PhpSign\Utilities\DateParser;
use Carbon\Carbon;


class SignerResult extends Signer
{
    // The signer has not yet been invited to sign. Others signers must sign prior to this user.
    const STATUS_WAITING = 'waiting';
    // The signer has been invited to sign, but has not tried yet.
    const STATUS_READY = 'ready';
    // The signer has accessed the signature service.
    const STATUS_ACCESSED = 'accessed';
    // The signer agreed to sign and has been sent an OTP.
    const STATUS_CODE_SENT = 'code-sent';
    // The signer has successfully signed.
    const STATUS_SIGNED = 'signed';
    // The signer refused to sign, or one of the previous signers canceled or failed its signature.
    const STATUS_CANCELED = 'canceled';
    // An error occurred during the signature. In this case, error is set.
    const STATUS_FAILED = 'failed';
    const STATUS_UNKNOWN = 'unknown';

    /**
     * The signer's status
     *
     * @var string
     */
    protected $status;

    /**
     * The signer's url
     *
     * @var string
     */
    protected $url;

    /**
     * The signer's last action
     *
     * @var \Carbon\Carbon
     */
    protected $actionAt;

    /**
     * The error message in case of failure
     *
     * @var string
     */
    protected $error;

    /**
     * Create a new SignerResult instance.
     *
     * @return void
     */
    public function __construct()
    {
        
    }

    /**
     * Get the signer's status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Get the signer status text
     *
     * @param  string  $status
     * @return string
     */
    public static function getStatusText(string $status)
    {
        $texts = [
            self::STATUS_WAITING => "Waiting",
            self::STATUS_READY => "Ready",
            self::STATUS_ACCESSED => "Accessed",
            self::STATUS_CODE_SENT => "Code Sent",
            self::STATUS_SIGNED => "Signed",
            self::STATUS_CANCELED => "Canceled",
            self::STATUS_FAILED => "Failed",
            self::STATUS_UNKNOWN => "Unknown",
        ];

        return isset($texts[$status]) ? $texts[$status] : '';
    }

    /**
     * Set the signer's status
     *
     * @param  string  $status
     * @return string
     */
    public function setStatus(?string $status)
    {
        return $this->status = $status;
    }

    /**
     * Get the signer's url
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set the signer's url
     *
     * @param  string  $url
     * @return string
     */
    public function setUrl(?string $url)
    {
        return $this->url = $url;
    }

    /**
     * Get the signer's last action date
     *
     * @return \Carbon\Carbon
     */
    public function getActionAt()
    {
        return $this->actionAt;
    }

    /**
     * Set the signer's last action date
     *
     * @param  mixed  $actionAt
     * @return \Carbon\Carbon|null
     */
    public function setActionAt($actionAt)
    {
        return $this->actionAt = DateParser::parse($actionAt);
    }

    /**
     * Get the error message in case of failure
     *
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * Set the error message in case of failure
     *
     * @param  string  $error
     * @return string
     */
    public function setError(?string $error)
    {
        return $this->error = $error;
    }
}
