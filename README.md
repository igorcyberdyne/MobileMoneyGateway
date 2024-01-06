# ekolotech/mobilemoney-gateway

**Auteur :** [@igorcyberdyne](https://github.com/igorcyberdyne), [@EKOLOTECH](https://ekolotech.fr)

**ekolotech/mobilemoney-gateway** est un composant qui fournit un ensemble de classes et méthodes permettant d'utiliser
les API des opérations de paiement et de dépôts d'argent via le mobile money pour les opérateurs et les plateformes de paiement.

***Opérateurs et plateforme pris en compte :***
- MTN Mobile Money

### Comment installer ?
Vous pouvez

> Télécharger le composant sur [`github` ekolotech/mobilemoney-gateway](https://github.com/igorcyberdyne/MobileMoneyGateway.git)

OU exécuter la commande ci-dessous dans la console

    composer require ekolotech/mobilemoney-gateway


### ------------------------------------- CAS D'UTILISATION ------------------------------------

### I - L'API MTN Mobile Money ([MoMo API](https://momoapi.mtn.com/api-documentation))
A ce niveau le composant implémente deux (2) produits de MoMo API; 
à savoir le produit [Collections](https://momoapi.mtn.com/product#product=collections) 
et le produit [Disbursements](https://momoapi.mtn.com/product#product=disbursements).

Pour effectuer les opérations sur ces deux produits, deux objets ayant la signature des interfaces
`CollectionGatewayInterface` et `DisbursementGatewayInterface` vous permettent d'utiliser respectivement 
les opérations encaissements ou collect d'argent et les opérations de décaissements ou dépôts d'argent.

#### 1. `CollectionGatewayInterface`
```php
interface CollectionGatewayInterface
{
    public function collect(CollectRequestBody $collectRequestBody): bool;
    public function collectReference(string $reference): array;
    public function balance(): array;
    public function isAccountIsActive(string $number): bool;
    public function getAccountBasicInfo(string $number): array;
}
```
```php
class CollectRequestBody
{
    public function __construct(
        public readonly int    $amount, // Le montant
        public readonly string $number, // le numéro MoMo du client
        public readonly string $reference, // la référence de la transaction. Il doit être en version 4 du UUID
    )
    {
    }
}
```
Description des méthodes de l'interface `CollectionGatewayInterface`
- `collect(...)` Permet de demander un paiement à un client. La demande de paiement
est en attente jusqu'à ce que la transaction soit autorisée ou refusée par le client 
ou qu'elle soit interrompue par le système après un delais d'attente depassé.
Il est indispensable de vérifier le statut de la demande de paiement avec la méthode suivante.
- `collectReference(...)` Permet d'obtenir le statut d'une demande de paiement.

#### 2. `DisbursementGatewayInterface`
```php
interface DisbursementGatewayInterface
{
    public function disburse(DisburseRequestBody $disburseRequestBody) : bool;
    public function disburseReference(string $reference) : array;
    public function balance() : array;
    public function isAccountIsActive(string $number) : bool;
    public function getAccountBasicInfo(string $number) : array;
}
```
```php
class DisburseRequestBody
{
    public function __construct(
        public readonly int    $amount, // Le montant
        public readonly string $number, // le numéro MoMo du bénéficiaire
        public readonly string $reference, // la référence de la transaction. Il doit être en version 4 du UUID
    )
    {
    }
}
```
Description des méthodes de l'interface `DisbursementGatewayInterface`
- `disburse(...)` Permet de transferer un montant de votre compte propre vers un compte bénéficiaire.
Il est indispensable de vérifier le statut du transfert avec la méthode suivante.
- `disburseReference(...)` Permet d'obtenir le statut d'un transfert

**Pour les deux interfaces :**
- `isAccountIsActive(...)` Permet de vérifier si un titulaire de compte est enregistré et actif dans le système.
- `getAccountBasicInfo(...)` Permet d'obtenir les informations personnelles du titulaire du compte, telque son nom et prénom.
- `balance()` Permet d'obtenir le solde du compte de [Collection](https://momoapi.mtn.com/product#product=collections) ou de [Disbursements](https://momoapi.mtn.com/product#product=disbursements)

#### 3. Comment créer des instances des interfaces `CollectionGatewayInterface` et `DisbursementGatewayInterface`?

L'obtention de ces deux objets se fait à partir d'une factory `ApiGatewayFactory` du composant.
```php
/** 
 * @var CollectionGatewayInterface $collectionGateway 
 */
$collectionGateway = ApiGatewayFactory::loadMtnCollectionGateway(...);

/**
 * @var DisbursementGatewayInterface $disbursementGateway 
 */
$disbursementGateway = ApiGatewayFactory::loadMtnDisbursementGateway(...);
```
Les deux méthodes static de la factory attendent en paramètre une instance de l'interface `MtnApiAccessAndEnvironmentConfigInterface`.
Cette interface permet de renseigner les données de configuration pour communiquer avec le serveur de MoMo API en environnement sandbox ou en production.

```php
interface MtnApiAccessAndEnvironmentConfigInterface
{
    /** Methodes de configuration de l'environnement **/
    public function getBaseApiUrl(): string; // l'url de base de MTN MoMo API. Cette url varie selon l'environnement de sandbox ou de production
    public function isProd(): bool; // définition si le sandbox ou production. false pour le sandbox
    public function getCurrency(): string; // la devise de l'environnement, en €(EURO) pour le sandbox
    public function getProviderCallbackUrl(): string; // Votre end-point de callback. Exemple : https://mon-application.com/callback
    public function getProviderCallbackHost(): string; // Votre host de callback. Exemple : mon-application.com
    
    /** Methodes événementiel à écouter. Il est indispensable d'enregistrer ces données dans une base **/
    public function onApiUserCreated(string $apiUser): void; // Fournit le API User créé
    public function onApiKeyCreated(string $apiKey): void; // Fournit le API key
    public function onTokenCreated(MtnAccessToken $mtnAccessToken): void; // Fournit le token d'accès
    
    /** Methodes de configuration des données d'authentification MoMo API **/
    public function getMtnAuthenticationProduct() : MtnAuthenticationProduct; // Renvoie les clés d'authentification au produit (Collection ou Disbursement)
    public function getMtnAccessToken(): ?MtnAccessToken; // Renvoie le token d'accès
}
```
- `getProviderCallbackUrl(...)` Cette url est indispensable pour l'environnement de production, 
car le serveur MoMo API enverra à ce end-point les données contenant le statut de la transaction de paiement.
La requête envoyé à ce end-point sera en `POST`.

**Description des paramètres du constructeur de la classe `MtnAuthenticationProduct(...)`**
```php
class MtnAuthenticationProduct
{
    public function __construct(
        private readonly string $subscriptionKeyOne,
        private readonly string $subscriptionKeyTwo,
        private ?string         $apiUser = null,
        private ?string         $apiKey = null
    )
    {
    }
}
```
- `$subscriptionKeyOne` **'Primary key'** à récupérer dans votre profile MoMo API de [Collections](https://momoapi.mtn.com/product#product=collections) ou de [Disbursements](https://momoapi.mtn.com/product#product=disbursements).
- `$subscriptionKeyTwo` **'Secondary key'** à récupérer dans votre profile MoMo API de [Collections](https://momoapi.mtn.com/product#product=collections) ou de [Disbursements](https://momoapi.mtn.com/product#product=disbursements).
- `$apiUser` Si c'est pas fournit, il sera créé par le composant, il est préférable d'implémenter la méthode *`onApiUserCreated(string $apiUser): void;`* afin d'enregistrer cette valeur dans votre base de données.
- `$apiKey` Si c'est pas fournit, il sera créé par le composant, il est préférable d'implémenter la méthode *`onApiKeyCreated(string $apiKey): void;`* afin d'enregistrer cette valeur dans votre base de données.

**Description des paramètres du constructeur de la classe `MtnAccessToken(...)`**

Cet objet vous permet d'effectuer certaines opérations. Vous devez fournir cet objet, si vous ne l'avez pas il sera généré. Implémentez la méthode *`onTokenCreated(MtnAccessToken $mtnAccessToken): void`* pour l'enregistrer dans votre base de données.
```php
class MtnAccessToken
{
    public function __construct(
        private readonly string $accessToken,
        private readonly string $tokenType,
        private readonly int    $expiresIn,
        private readonly bool   $isExpired = false,
    )
    {
    }
}
```
- `$accessToken` Le token d'accès qui vous permet d'effectuer des paiements et les dépôts MoMo
- `$tokenType` Le type du token `access_token`
- `$expiresIn` Le temps d'expiration du token `access_token` exprimé en seconde. Ce temps est de `3600 secondes, soit 1 heure`
- `$isExpired` Vous devez calculer en se basant de la date de création et du temps d'expiration pour définir si ce token `access_token` est expiré ou pas. 

**N.B :** Il est indispensable de bien traiter le paramètre `$isExpired` pour éviter que le composant le créé toutes les fois.

#### Exemple d'implémentation :

```php
class CollectionGatewayServiceImpl implements MtnApiAccessAndEnvironmentConfigInterface 
{
    public function getBaseApiUrl(): string
    {
        return "https://sandbox.momodeveloper.mtn.com";
    }

    public function getProviderCallbackUrl(): string
    {
        return "https://sandbox.momodeveloper.mtn.com";
    }

    public function getProviderCallbackHost(): string
    {
        return "sandbox.momodeveloper.mtn.com";
    }

    public function isProd(): bool
    {
        return false;
    }

    public function getCurrency(): string
    {
        return Currency::EUR;
    }

    public function onApiUserCreated(string $apiUser): void
    {
        // TODO something such as save $apiUser in database
    }

    public function onApiKeyCreated(string $apiKey): void
    {
        // TODO something such as save $apiKey in database
    }

    public function onTokenCreated(MtnAccessToken $mtnAccessToken): void
    {
        // TODO something such as save $mtnAccessToken in database
    }
}
```

```php
/** @var CollectionGatewayInterface $collectionGateway */
$collectionGateway = ApiGatewayFactory::loadMtnCollectionGateway(
    new CollectionGatewayServiceImpl()
);

$clientNumber = "066304925";
$collectReference = "a103dbea-d5f7-45f3-b2e5-e495904d44cb";

// Vérification si le numéro est enregistré et possède un compte mobile money
if (!$collectionGateway->isAccountIsActive($clientNumber)) {
    die("Le titulaire du compte n'a pas de compte MoMo enregistré");
}

// Données personnelles du client. Peut être utilisé pour confirmer le nom et prénom du client
$personalData = $collectionGateway->getAccountBasicInfo($clientNumber);

// Demande de paiement au client. En production une notification sera envoyé au client pour valider le paiement.
// Une fois le paiement valider par le client, le serveur MoMo API envoie le statut de la transaction sur votre end-point ou URL de callback
if (!$collectionGateway->collect(new CollectRequestBody(150, $clientNumber, $reference))) {
    die("Echec de la demande de paiement");
}

// Vérification du statut de la demande de paiement.
$referenceData = $collectionGateway->collectReference($collectReference);

// Solde du compte de paiement
$balance = $collectionGateway->balance();
```


### Application de démonstration
Pour voir un exemple beaucoup plus complet, consultez la démonstration dans le **projet** `MobileMoneyGateway > DemoApp`.
Vous pouvez également exécuter la démo directement depuis la racine du projet, avec la commande suivante.

    php DemoApp\DemoApp.php

### Remarque
L'exécution des tests ou de l'application démo peut échouer ou être bloqué pour cause de plusieurs requêtes simultannées.
Généralement le test sur la récupération du solde de compte peut faire échouer les tests s'ils sont tous exécutés à la fois.
Il serait préférable d'exécuter le test ou la méthode en question dans l'application démonstration de façon indépendante des autres.