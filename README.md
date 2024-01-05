# ekolotech/mobilemoney-gateway

**Auteur :** [@igorcyberdyne](https://github.com/igorcyberdyne), [@EKOLOTECH](https://ekolotech.fr)
**Version :** 1.0.0

**ekolotech/mobilemoney-gateway** est un composant qui fournit un ensemble de classes et méthodes permettant d'utiliser
les API des opérations de paiement et de dépôts d'argent via le mobile money pour les opérateurs et les plateformes de paiement.

***Opérateurs et plateforme pris en compte :***
- MTN Mobile Money
- Airtel Mobile Money
- MyPVit

### Comment installer ?
Vous pouvez

> Télécharger le composant sur [github ekolotech/mobilemoney-gateway](https://github.com/igorcyberdyne/MobileMoneyGateway)

OU exécuter la commande ci-dessous dans la console

    composer require ekolotech/mobilemoney-gateway


### ---------------------------------------- CAS D'UTILISATION ---------------------------------------

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
Cette interface permet de renseigner les données de la configuration pour communiquer avec le serveur de MoMo API en environnement de sandbox ou en production.

```php
interface MtnApiAccessAndEnvironmentConfigInterface
{
    /** Methodes de configuration de l'environnement **/
    public function getBaseApiUrl(): string; // l'url de base de MTN MoMo API. Cette url varie selon l'environnement de sandbox ou de production
    public function isProd(): bool; // définition si le sandbox ou production. false pour le sandbox
    public function getCurrency(): string; // la devise de l'environnement, en €(EURO) pour le sandbox
    public function getProviderCallbackUrl(): string; // Votre url de callback. Exemple : https://mon-application.com/callback
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
        // TODO something
    }

    public function onApiKeyCreated(string $apiKey): void
    {
        // TODO something
    }

    public function onTokenCreated(MtnAccessToken $mtnAccessToken): void
    {
        // TODO something
    }
}
```



```php
/** 
 * @var CollectionGatewayInterface $collectionGateway 
 */
$collectionGateway = ApiGatewayFactory::loadMtnCollectionGateway(
    new CollectionGatewayServiceImpl()
);

$number = "066304925";
if ($collectionGateway->collect(new CollectRequestBody(150, $number, ""))) {
    echo "Collect or request to pay is failed"
}

```

### Application de démonstration
Pour voir un exemple beaucoup plus complet, consultez la démonstration dans le **projet** `MobileMoneyGateway > DemoApp`.
Vous pouvez également exécuter la démo directement depuis la racine du projet, avec la commande suivante.

    php DemoApp\DemoApp.php