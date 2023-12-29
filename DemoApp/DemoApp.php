<?php

namespace DemoApp;

use DemoApp\Service\TransactionService;
use Ekolotech\MoMoGateway\Api\MtnGateway\Collection\CollectionGatewayInterface;
use Ekolotech\MoMoGateway\Api\MtnGateway\Disbursement\DisbursementGatewayInterface;
use Exception;
use function PHPUnit\Framework\assertArrayHasKey;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertIsArray;

require dirname(__DIR__) . '/vendor/autoload.php';

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
     * @return void
     * @throws Exception
     */
    public function makeCollectAndCheckingProcess(string $number): void
    {
        $this->execute(function () use ($number) {
            $collectReference = $this->transactionService->executeCollect(1, $number);
            $this->display("Collect reference created --> [[$collectReference]]");

            $collectCheckingData = $this->transactionService->checkCollect($collectReference);
            assertIsArray($collectCheckingData);
            assertArrayHasKey("status", $collectCheckingData);
            assertEquals("SUCCESSFUL", $collectCheckingData["status"]);
            $this->display("Reference checking --> [[$collectReference]]");

        }, __METHOD__);
    }

    /**
     * @param string $number
     * @return void
     * @throws Exception
     */
    public function makeDisburseAndCheckingProcess(string $number): void
    {
        $this->execute(function () use ($number) {
            $disburseReference = $this->transactionService->executeDisburse(1, $number);
            $this->display("Disburse reference created --> [[$disburseReference]]");

            $disburseCheckingData = $this->transactionService->checkDisburse($disburseReference);
            assertIsArray($disburseCheckingData);
            assertArrayHasKey("status", $disburseCheckingData);
            assertEquals("SUCCESSFUL", $disburseCheckingData["status"]);
            $this->display("Reference checking --> [[$disburseReference]]");

        }, __METHOD__);
    }

    private function accountHolderProcess(
        string                                                  $number,
        CollectionGatewayInterface|DisbursementGatewayInterface $gateway,
        string                                                  $processName
    ): void
    {
        $this->execute(
            function () use ($gateway, $number) {
                $basicInfo = $gateway->getAccountBasicInfo($number);

                assertIsArray($basicInfo);

                $this->display("Account basic info --> \n" . var_export($basicInfo, true));
            },
            $processName
        );
    }

    public function collectAccountHolderProcess(string $number): void
    {
        $this->accountHolderProcess(
            $number,
            $this->transactionService->getCollectionGateway(),
            __METHOD__
        );
    }

    public function disburseAccountHolderProcess(string $number): void
    {
        $this->accountHolderProcess(
            $number,
            $this->transactionService->getDisbursementGateway(),
            __METHOD__
        );
    }


    /**
     * @throws Exception
     */
    public function runApp(): void
    {
        $number = "066304920";
        $this->makeCollectAndCheckingProcess($number);
        $this->collectAccountHolderProcess($number);

        $this->makeDisburseAndCheckingProcess($number);
        $this->disburseAccountHolderProcess($number);
    }
}

try {
    $demoApp = new DemoApp();
    $demoApp->runApp();
} catch (Exception $e) {
    echo DemoApp::colorLog("App error : " . $e->getMessage() . DemoApp::colorLog("", ""), "e");
}