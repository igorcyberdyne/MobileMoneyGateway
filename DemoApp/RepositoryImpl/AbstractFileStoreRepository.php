<?php

namespace DemoApp\RepositoryImpl;

use DemoApp\Config;
use Ekolotech\MobileMoney\Gateway\Api\Helper\AbstractTools;
use Exception;

abstract class AbstractFileStoreRepository
{
    private array $store;
    private string $storeFilename;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        $this->storeFilename = Config::dataDir() . "/" . AbstractTools::slugify(get_called_class()) . ".json";

        $this->loadStore();
    }

    /**
     * @return array
     */
    protected function getStore(): array
    {
        return $this->store;
    }

    /**
     * @param array $store
     * @throws Exception
     */
    public function setStore(array $store): void
    {
        $this->store = array_merge($this->store, $store);

        $this->saveStore();
    }

    /**
     * @return void
     * @throws Exception
     */
    protected function loadStore(): void
    {
        $this->store = [];

        if (!file_exists($this->storeFilename)) {
            return;
        }

        try {
            $content = json_decode(file_get_contents($this->storeFilename), true);

            $this->store = is_array($content) ? $content : throw new Exception("Store is empty");
        } catch (Exception $exception) {
            throw new Exception("Error on loading store", $exception->getCode());
        }
    }


    /**
     * @throws Exception
     */
    private function saveStore(): void
    {
        try {
            file_put_contents($this->storeFilename, json_encode($this->store));
        } catch (Exception $exception) {
            throw new Exception("Error on saving store", $exception->getCode());
        }
    }

}