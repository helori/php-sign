# php-sign
PHP-SIGN is a single electronic signature SDK which works with most common electronic signature services (Universign, Docusign, Yousign...)
It allows you to integrate your prefered electronic signature solution without worrying about how you will have to switch from one to another in the future.
Besides, it brings you a clean and easy way to plug your signature service in your app (probably easier than integrating it directly).
It also removes you the hassle of maintaining that part of the code.

## Installation and setup

Install the package by running:
```bash
composer require helori/php-sign
```

## How does it work ?

To launch an electronic signature process (also refered as a *"transaction"*), you must define some common elements :
- *"documents"* : the PDF files to be signed.
- *"signers"* : the persons who will sign the documents.
- *"signatures"* : the locations on the documents pages where each person's signature will appear.

These elements are parts of a transaction *"scenario"* which defines how the signature process should run.
Once your scenario is defined, a *"requester"* will send it to the signature service and return a *"transaction"* object.
The requester must be configured with the secret credentials of your signature service user account.

## Usage

### Create a Transaction

The first step is to define a *scenario* to create a *transaction*.

```php
use Helori\PhpSign\Elements\Document;
use Helori\PhpSign\Elements\Signer;
use Helori\PhpSign\Elements\Signature;
use Helori\PhpSign\Elements\Scenario;
use Helori\PhpSign\Elements\Requester;
use Helori\PhpSign\Elements\Transaction;

$document = new Document();
$document->setId(1);
$document->setName('Document #1');
$document->setFilepath('/document/1/absolute/path');

$signer = new Signer();
$signer->setId(1);
$signer->setFirstname('John');
$signer->setLastname('Doe');
$signer->setEmail('john@doe.com');
$signer->setPhone('+33611223344');

$signature = new Signature();
$signature->setDocumentId(1);
$signature->setSignerId(1);
$signature->setLabel('Your signature');
$signature->setPage(1);
$signature->setX(100);
$signature->setY(200);
$signature->setWidth(150);
$signature->setHeight(80);

$scenario = new Scenario();
$scenario->setSigners([$signer]);
$scenario->setDocuments([$document]);
$scenario->setSignatures([$signature]);

// You may use your own signature service configuration here :
$driverName = 'yousign'; // 'universign', 'docusign', ...
$driverConfig = [
    // mode : can be 'production' for production mode, or another (free) value for testing mode
    'mode' => 'production',
    'api_key' => 'your_yousign_secret_api_key',
];

$requester = new Requester($driverName, $driverConfig);
$transaction = $requester->createTransaction($scenario);
```

### Retrieve a Transaction

After creating a *transaction*, you probably need to store its *transaction ID*.
It allows you to retrieve all information about a transaction : status, signers info, documents...
You may at a minimum save the ID in your database.

The transaction ID is a string, but doesn't have a standard format. 
It is the unique ID used by your signature service, and thus its length and pattern may vary.
For example, it can be a Docusign "envelope ID", or a Yousign "procedure ID", or a Universign "transaction ID"...

Here is an example of how to retrieve a transaction from its ID :

```php
$requester = new Requester($driverName, $driverConfig);
$transaction = $requester->getTransaction($transactionId);
```

### Webhooks

The code above allows you to check the transaction status at any time.
But polling servers to update your transactions may not be the best idea !
Instead, you can take advantage of the *webhooks* sent by the signature services.

All webhooks will be sent to the *status URL* defined in your scenario.

```php
$scenario->setStatusUrl('https://your-app.com/your-sign-webhook-uri');
```

This URL must correspond to a route defined in your application.
The signature service (used behind the scene) will call your status URL with its own set of parameters.
You can then use the *Webhook* element to convert the received request to a standard "webhook object".
It will be automatically populated with the request params.

```php
use Helori\PhpSign\Elements\Webhook;

// Automatically populated with the current request params
$webhook = new Webhook();

// Get the transaction ID and status from the webhook to decide what you need to do.
$transactionId = $webhook->getTransactionId();
$transactionStatus = $webhook->getTransactionStatus();
```

### Download the signed documents

Once the documents have been signed, the transaction status is set to Transaction::STATUS_COMPLETED.
You can download the signed documents by simply calling the getDocuments() method :

```php
$requester = new Requester($driverName, $driverConfig);
$documents = $requester->getDocuments($transactionId);

foreach($documents as $document){
    $content = $document->getContent();
    file_put_contents('/path/where/you/want/to/store/the/document.pdf', $document->getContent());
}
```

### Further integration...

There are other options you may want to use : sms authentication, return urls, metadata...
Feel free to look at the source code (especially the "Elements“ folder) which is well documented.

## Help & Support

Any question or contribution is welcome :)



