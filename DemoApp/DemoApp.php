<?php

namespace DemoApp;

use DemoApp\Service\TransactionService;
use Ekolotech\MoMoGateway\Exception\AccountHolderException;
use Ekolotech\MoMoGateway\Exception\ApiGatewayException;
use Ekolotech\MoMoGateway\Exception\BalanceException;
use Ekolotech\MoMoGateway\Exception\CollectionException;
use Ekolotech\MoMoGateway\Exception\DisbursementException;
use Ekolotech\MoMoGateway\Exception\MtnAccessKeyException;
use Ekolotech\MoMoGateway\Exception\RefreshAccessException;
use Ekolotech\MoMoGateway\Exception\TokenCreationException;
use Ekolotech\MoMoGateway\Exception\TransactionReferenceException;
use Ekolotech\MoMoGateway\MtnGateway\Collection\CollectionGatewayInterface;
use Ekolotech\MoMoGateway\MtnGateway\Disbursement\DisbursementGatewayInterface;
use Exception;


$filename = dirname(__DIR__) . '/../../../vendor/autoload.php';
if (!file_exists($filename)) {
    $filename = dirname(__DIR__) . '/vendor/autoload.php';
}

require $filename;

class DemoApp
{
    private TransactionService $transactionService;

    public function __construct()
    {
        date_default_timezone_set("Europe/Paris");

        $this->transactionService = new TransactionService();
    }

    private function display(string $text, ?string $colorType = null): void
    {
        echo self::colorLog("$text\n", $colorType ?? "d");
    }

    private function execute(callable $callable, string $title): void
    {
        $start = "START PROCESS >>>>>>>>>>> $title";
        $this->display(str_pad("_", strlen($start), "_"));
        $this->display("$start\n", "i");

        $callable();

        $end = "\nEND PROCESS >>>>>>>>>>> $title";
        $this->display($end, "i");
        $this->display("");
    }

    public static function colorLog(string $str, string $type = 'i'): string
    {
        $color = "\033[39m$str";

        switch ($type) {
            case 'e': //error
                $color = "\033[31m$str";
                break;
            case 's': //success
                $color = "\033[32m$str";
                break;
            case 'w': //warning
                $color = "\033[33m$str";
                break;
            case 'i': //info
                $color = "\033[36m$str";
                break;
            default:
                break;
        }

        return $color;
    }


    /**
     * @param string $number
     * @param int $amount
     * @return void
     * @throws MtnAccessKeyException
     * @throws RefreshAccessException
     * @throws TokenCreationException
     * @throws CollectionException
     * @throws TransactionReferenceException
     */
    public function makeCollectAndCheckingProcess(string $number, int $amount): void
    {
        $this->execute(function () use ($amount, $number) {
            $collectReference = $this->transactionService->executeCollect($amount, $number);
            $this->display("Collect reference created --> [[$collectReference]]");

            $collectCheckingData = $this->transactionService->checkCollect($collectReference);
            if (!key_exists("status", $collectCheckingData)) {
                die("Key 'status' not exist");
            }

            if ($collectCheckingData["status"] != "SUCCESSFUL") {
                die("In sandbox, the collect status must be 'SUCCESSFUL'");
            }

            $this->display("Reference checking --> [[$collectReference]]");
        }, __METHOD__);
    }


    /**
     * @return void
     * @throws MtnAccessKeyException
     * @throws RefreshAccessException
     * @throws TokenCreationException
     * @throws BalanceException
     */
    public function makeCollectBalanceProcess(): void
    {
        $this->execute(function () {
            $balance = $this->transactionService->collectBalance();

            $this->display("Collect balance --> [[{$balance['availableBalance']} {$balance['currency']}]]");
        }, __METHOD__);
    }

    /**
     * @return void
     * @throws BalanceException
     * @throws MtnAccessKeyException
     * @throws RefreshAccessException
     * @throws TokenCreationException
     */
    public function makeDisburseBalanceProcess(): void
    {
        $this->execute(function () {
            $balance = $this->transactionService->disburseBalance();

            $this->display("Disburse balance --> [[{$balance['availableBalance']} {$balance['currency']}]]");
        }, __METHOD__);
    }

    /**
     * @param string $number
     * @param int $amount
     * @return void
     * @throws MtnAccessKeyException
     * @throws RefreshAccessException
     * @throws TokenCreationException
     * @throws TransactionReferenceException
     * @throws DisbursementException
     */
    public function makeDisburseAndCheckingProcess(string $number, int $amount): void
    {
        $this->execute(function () use ($amount, $number) {
            $disburseReference = $this->transactionService->executeDisburse($amount, $number);
            $this->display("Disburse reference created --> [[$disburseReference]]");

            $disburseCheckingData = $this->transactionService->checkDisburse($disburseReference);

            if (!key_exists("status", $disburseCheckingData)) {
                die("Key 'status' not exist");
            }

            if ($disburseCheckingData["status"] != "SUCCESSFUL") {
                die("In sandbox, the disburse status must be 'SUCCESSFUL'");
            }

            $this->display("Reference checking --> [[$disburseReference]]");

        }, __METHOD__);
    }

    /**
     * @param string $number
     * @param CollectionGatewayInterface|DisbursementGatewayInterface $gateway
     * @param string $processName
     * @return void
     * @throws AccountHolderException
     * @throws MtnAccessKeyException
     * @throws RefreshAccessException
     * @throws TokenCreationException
     */
    private function accountHolderProcess(
        string                                                  $number,
        CollectionGatewayInterface|DisbursementGatewayInterface $gateway,
        string                                                  $processName
    ): void
    {
        $this->execute(
            function () use ($gateway, $number) {
                $basicInfo = $gateway->getAccountBasicInfo($number);

                $this->display("Account basic info name --> [[{$basicInfo['name']}]]");
            },
            $processName
        );
    }

    /**
     * @param string $number
     * @param CollectionGatewayInterface|DisbursementGatewayInterface $gateway
     * @param string $processName
     * @return void
     * @throws AccountHolderException
     * @throws MtnAccessKeyException
     * @throws RefreshAccessException
     * @throws TokenCreationException
     */
    private function isAccountHolderActiveProcess(
        string                                                  $number,
        CollectionGatewayInterface|DisbursementGatewayInterface $gateway,
        string                                                  $processName
    ): void
    {
        $this->execute(
            function () use ($gateway, $number) {
                $isAccountIsActive = $gateway->isAccountIsActive($number);

                $this->display("Is mobile money account is active for number [[$number]] ? --> [[" . ($isAccountIsActive ? "YES" : "NO") . "]]");
            },
            $processName
        );
    }

    /**
     * @param string $number
     * @return void
     * @throws AccountHolderException
     * @throws MtnAccessKeyException
     * @throws RefreshAccessException
     * @throws TokenCreationException
     */
    public function collectAccountHolderProcess(string $number): void
    {
        $this->accountHolderProcess(
            $number,
            $this->transactionService->getCollectionGateway(),
            __METHOD__
        );
    }

    /**
     * @param string $number
     * @return void
     * @throws AccountHolderException
     * @throws MtnAccessKeyException
     * @throws RefreshAccessException
     * @throws TokenCreationException
     */
    public function collectIsAccountHolderActiveProcess(string $number): void
    {
        $this->isAccountHolderActiveProcess(
            $number,
            $this->transactionService->getCollectionGateway(),
            __METHOD__
        );
    }

    /**
     * @param string $number
     * @return void
     * @throws AccountHolderException
     * @throws MtnAccessKeyException
     * @throws RefreshAccessException
     * @throws TokenCreationException
     */
    public function disburseAccountHolderProcess(string $number): void
    {
        $this->accountHolderProcess(
            $number,
            $this->transactionService->getDisbursementGateway(),
            __METHOD__
        );
    }

    /**
     * @param string $number
     * @return void
     * @throws AccountHolderException
     * @throws MtnAccessKeyException
     * @throws RefreshAccessException
     * @throws TokenCreationException
     */
    public function disburseIsAccountHolderActiveProcess(string $number): void
    {
        $this->isAccountHolderActiveProcess(
            $number,
            $this->transactionService->getDisbursementGateway(),
            __METHOD__
        );
    }


    /**
     * @return void
     * @throws AccountHolderException
     * @throws BalanceException
     * @throws CollectionException
     * @throws DisbursementException
     * @throws MtnAccessKeyException
     * @throws RefreshAccessException
     * @throws TokenCreationException
     * @throws TransactionReferenceException
     */
    public function runApp(): void
    {
        $number = "066304920";
        $this->display("****************************** START COLLECT PROCESS ******************************");
        $this->makeCollectAndCheckingProcess($number, 1);
        $this->collectAccountHolderProcess($number);
        $this->collectIsAccountHolderActiveProcess($number);
        sleep(10);
        $this->makeCollectBalanceProcess();


        sleep(10);
        $this->display("****************************** START DISBURSE PROCESS ******************************");
        $this->makeDisburseAndCheckingProcess($number, 1);
        $this->disburseAccountHolderProcess($number);
        $this->disburseIsAccountHolderActiveProcess($number);
        sleep(10);
        $this->makeDisburseBalanceProcess();
    }
}

try {
    $demoApp = new DemoApp();
    $demoApp->runApp();
    exit(0);
} catch (Exception $e) {
    $message = $e instanceof ApiGatewayException ? $e->getMessage() . "; " . $e->getMessageOrigin() : $e->getMessage();

    echo DemoApp::colorLog("[[APP ERROR]] Code -> {$e->getCode()}, Message -> $message, " . DemoApp::colorLog("", ""), "e");
    exit(1);
}