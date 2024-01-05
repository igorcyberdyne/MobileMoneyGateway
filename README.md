# ekolotech/mobilemoney-gateway

**Auteur :** [@igorcyberdyne](https://github.com/igorcyberdyne), [@EKOLOTECH](https://ekolotech.fr)
**Version :** 1.0.0

**ekolotech/mobilemoney-gateway** est un composant qui fournit un ensemble de classes et méthodes permettant d'utiliser
les API de transactions de paiement et de dépôts d'argent via le mobile money pour les opérateurs et les plateformes de paiement.

***Opérateurs et plateforme pris en compte :***
- MTN Mobile Money
- Airtel Mobile Money
- MyPVit

### Exemple
Ci-dessous la mise en place du composant

> Télécharger le composant sur [github ekolotech/mobilemoney-gateway](https://github.com/igorcyberdyne/MobileMoneyGateway), 
> soit utiliser `$ composer ekolotech/mobilemoney-gateway`


### CAS D'UTILISATION

#### I - L'API MTN Mobile Money ([MoMo API](https://momoapi.mtn.com/api-documentation))
A ce niveau le composant implémente deux (2) produits de l'API MoMo API; 
à savoir le produit [Collections](https://momoapi.mtn.com/product#product=collections) 
et le produit [Disbursements](https://momoapi.mtn.com/product#product=disbursements).

Pour effectuer les opérations sur ces deux produits, deux objets ayant la signature des interfaces
`CollectionGatewayInterface` et `DisbursementGatewayInterface` vous permettent d'utiliser respectivement 
les encaissements ou la collect d'argent et les décaissements ou les dépôts d'argent.

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

Une factory du composant vous permet de créer ces objects, telque dans l'exemple ci-dessous.
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
    
    /** Methodes événementiel à écouter **/
    public function onApiUserCreated(string $apiUser): void;
    public function onApiKeyCreated(string $apiKey): void;
    public function onTokenCreated(MtnAccessToken $mtnAccessToken): void;
    
    /** Methodes de configuration des données d'authentification MoMo API **/
    public function getMtnAuthenticationProduct() : MtnAuthenticationProduct;
    public function getMtnAccessToken(): ?MtnAccessToken;
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

**N.B :** Il est indispensable de bien traiter le traiter `$isExpired` pour éviter que le composant le créé toutes les fois.

#### Exemple d'implémentation:

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
```