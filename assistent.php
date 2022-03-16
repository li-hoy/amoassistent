<?php

namespace Lihoy\Amo\Assistent;

use \Ufee\Amo\Oauthapi as AmoClient;
use \Ufee\Amo\Services\Account as AmoAccount;

class Assistent
{
    private
        $amo_client,
        $storage,
        $storage_default_key;

    public function __construct(
        object $amoClient,
        string $stroragePath = ""
    ) {
        // set amo client
        if ($amoClient instanceof AmoClient) {
            $this->amo_client = $amoClient;
        } else {
            $this->setAmoClient($amoClient);
        }

        // set storage
        if (false === empty($stroragePath)) {
            $this->setStorage($stroragePath);
        }
        $this->storage_default_key = 'cache';
    }

    public function getAmoClient()
    {
        return $this->amo_client;
    }

    /**
     * Response data from file in storage
     * 
     * @param path
     * @param associative
     * @return mixed
     */
    public function getFromStorage(
        string $key = "",
        bool $associative = false
    ) {
        if (empty($key)) {
            $key = $this->storage_default_key;
        }
        $fm = $this->storage;
        $fileName = $key . ".json";
        if ($fm->exists() && $fm->exists($fileName)) {
            $fileContent = $fm->get($fileName);
            $fileContent = \json_decode($fileContent, $associative);
            return $fileContent;
        }
        throw new \Exception("Storage '$key' doesn't exist.");
    }

    /**
     * puts data into storage in json format
     * 
     * @param data
     * @param path
     * @return void
     */
    public function saveToStorage(
        $data,
        string $key = "",
        bool $checkJson = false
    ) {
        if (empty($key)) {
            $key = $this->storage_default_key;
        }
        $json = json_encode($data);
        if ($checkJson && is_null(json_decode($data))) {
            throw new \Exception('Wrong json.');
        }
        $fm = $this->stoarage;
        $fileName = $key . ".json";
        if ($fm->exists($fileName)) {
            return $fm->put($fileName, $json);
        } else {
            return $fm->createFile($fileName, $data, 0700);
        }
    }

    protected function setStorage(string $path)
    {
        $subfolderList = [];
        $subfolderLine = "";
        if (false === empty($path)) {
            throw new \Exception("Path is empty.");
        }
        $pattern = '/^\/(([a-z\d\/]+)\/)?([a-z\d\._]*?)$/i';
        $matches = [];
        $path_validated = preg_match($pattern, $path, $matches);
        if (false === $path_validated) {
            throw new \Exception(
                "Wrong path (validation pattern: " . addslashes($pattern) . ")."
            );
        }
        $subfolderLine = $matches[2];
        $subfolderList = explode('/', $subfolderLine);
        $dir = "/";
        foreach ($subfolderList as $subfolder) {
            $dir = $dir . "$subfolder";
            $fm = fileManager($dir);
            if (false === $fm->exists()) {
                $fm->createDir('', 0700);
            }
            $dir = $dir . "/";
        }
        $this->sorage = $fm;
    }

    public function getCusomFieldValues($cf)
    {
        return (array)$cf;
    }

    /**
	 * Сhecks the existence of a custom field and if it exists, returns its value
     * @param entity - object
     * @param customFieldName - string
	 * @return mixed - custom Field Value or false if it does not exist
	 */
    public function getCustomField($entity = null, $customFieldName = '')
    {
        if (
            !empty($entity)
            && !empty($customFieldName)
        ) {
            if ($entity->cf($customFieldName)) {
                $customFieldValue = $entity->cf($customFieldName)->getValue();
                return empty($customFieldValue) ? "" : $customFieldValue;
            } else {
                throw new \Exception("Custom field \"$customFieldName\" doesт`t exist.");
            }
        }
        throw new \Exception("Wrong params");
    }
    
    public function getEntityCategoryByType($entityType)
    {
        switch ($entityType) {
            case 'contact':
                $entitiesType = 'contacts';
                break;
            case 'company':
                $entitiesType = 'companies';
                break;
            case 'lead':
                $entitiesType = 'leads';
                break;
            case 'task':
                $entitiesType = 'tasks';
                break;
            case 'note':
                $entitiesType = 'notes';
                break;
            default:
                return false;
        }
        return $entitiesType;
    }
    
    /**
     * @param confirg
     * @return \Ufee\Amo\Oauthapi
     */
    public function setAmoClient(
        object $config
    ) {
        if (empty($name)) {
            throw new \Exception("Argument 'name' is empty");
        }
        $client = AmoClient::setInstance($config->oauth);
        $client->queries->logs($config->log_queries ?? false);
        $client->queries->setDelay($config->queries_delay ?? 0.15);
        $client->setOauthPath($config->oauth_storage ?? (self::$storage_path . '/Oauth'));
        AmoAccount::setCacheTime($config->account_cache_time ?? 1800);
        self::$amo_clients[$name] = $client;
        return self::$amo_clients[$name];
    }

    /**
	 * Сhecks the existence of a custom field and, if it exists, writes the required value to it
	 * @return boolean
	 */
    public function setCustomField(
        $entity = null,
        $name = "",
        $value = "",
        $type = 'text'
    ) {
        if (
            (!empty($entity) && !empty($name) && !empty($value))
            && $entity->cf($name)
        ) {
            switch ($type) {
                case 'date':
                    $entity->cf($name)->setDate($value);
                    break;
                case 'switch':
                    $value
                        ? $entity->cf($name)->enable()
                        : $entity->cf($name)->disable();
                    break;
                default:
                    $entity->cf($name)->reset();
                    $entity->cf($name)->setValue($value);
            }
            return true;
        }
        throw new \Exception("Wrong params");
    }

}
