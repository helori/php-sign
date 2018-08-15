<?php

namespace Helori\PhpSign\Elements;

use Carbon\Carbon;


class Signer
{
    /**
     * The signer's id
     *
     * @var int
     */
    protected $id;

    /**
     * The signer's firstname
     *
     * @var string
     */
    protected $firstname;

    /**
     * The signer's lastname
     *
     * @var string
     */
    protected $lastname;

    /**
     * The signer's email
     *
     * @var string
     */
    protected $email;

    /**
     * The signer's phone number
     *
     * @var string
     */
    protected $phone;

    /**
     * The signer's birthday
     *
     * @var \Carbon
     */
    protected $birthday;

    /**
     * Create a new Signer instance.
     *
     * @return void
     */
    public function __construct()
    {
        
    }

    /**
     * Get the signer's id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set the signer's id
     *
     * @param  int  $id
     * @return int
     */
    public function setId(int $id)
    {
        return $this->id = $id;
    }

    /**
     * Get the signer's firstname
     *
     * @return string
     */
    public function getFirstname()
    {
        return $this->firstname;
    }

    /**
     * Set the signer's firstname
     *
     * @param  string  $firstname
     * @return string
     */
    public function setFirstname(string $firstname)
    {
        return $this->firstname = $firstname;
    }

    /**
     * Get the signer's lastname
     *
     * @return string
     */
    public function getLastname()
    {
        return $this->lastname;
    }

    /**
     * Set the signer's lastname
     *
     * @param  string  $lastname
     * @return string
     */
    public function setLastname(string $lastname)
    {
        return $this->lastname = $lastname;
    }

    /**
     * Get the signer's email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set the signer's email
     *
     * @param  string  $email
     * @return string
     */
    public function setEmail(string $email)
    {
        return $this->email = $email;
    }

    /**
     * Get the signer's phone number
     *
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * Set the signer's phone number
     *
     * @param  string  $phone
     * @return string
     */
    public function setPhone(string $phone)
    {
        return $this->phone = $phone;
    }

    /**
     * Get the signer's birthday
     *
     * @return \Carbon
     */
    public function getBirthday()
    {
        return $this->birthday;
    }

    /**
     * Set the signer's birthday
     *
     * @param  \Carbon  $birthday
     * @return \Carbon
     */
    public function setBirthday(Carbon $birthday)
    {
        return $this->birthday = $birthday;
    }
}
