<?php
/**
 * Some tools for automating typical tasks when working with the ufee/amoapi library
 */

namespace Lihoy\Amo;

use Exception;
use Ufee\Amo\Oauthapi as AmoClient;
use Ufee\Amo\Services\Account as AmoAccount;

class Assistent
{
    private
        $amo_client,
        $storage,
        $storage_path,
        $storage_default_key;

    public function __construct(
        $amoClient,
        string $storagePath
    ) {
        $this->storage_default_key = 'cache';
        if (empty($storagePath)) {
            throw new Exception("Storage path is empty.");
        }
        $this->storage_path = $storagePath;
        $this->setStorage($this->storage_path);
        // set amo client
        if ($amoClient instanceof AmoClient) {
            $this->amo_client = $amoClient;
            return;
        } 
        if (gettype($amoClient) === "array") {
            $this->setAmoClient($amoClient);
        } else {
            throw new Exception("Wrong type of 'amoClient' argument.");
        }
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
        throw new Exception("Storage '$key' doesn't exist.");
    }

    /**
     * puts data into storage in json format
     * 
     * @param mixed data
     * @param string path
     * @return bool
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
            throw new Exception('Wrong json.');
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

        if (empty($path)) {
            throw new Exception("Path is empty.");
        }
        $pattern = '/^\/(([a-z\d\/]+)\/)?([a-z\d\._-]*?)$/i';
        $matches = [];
        $path_validated = preg_match($pattern, $path, $matches);
        if ($path_validated !== 1) {
            throw new Exception(
                "Wrong path (validation pattern: " . addslashes($pattern) . ")."
            );
        }
        $subfolderLine = $matches[2];
        $subfolderList = explode('/', $subfolderLine);
        $dir = "/";
        foreach ($subfolderList as $subfolder) {
            $dir = $dir . "$subfolder" . '/';
            $fm = fileManager($dir);
            if (false === $fm->exists()) {
                $fm->createDir('', 0700);
            }
        }
        $this->storage = $fm;
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
    public function getCustomField(
        \Ufee\Amo\Base\Models\ModelWithCF $entity = null,
        string $customFieldName = '')
    {
        if (
            !empty($entity)
            && !empty($customFieldName)
        ) {
            if ($entity->cf($customFieldName)) {
                $customFieldValue = $entity->cf($customFieldName)->getValue();
                return empty($customFieldValue) ? "" : $customFieldValue;
            } else {
                throw new Exception("Custom field \"$customFieldName\" doesт`t exist.");
            }
        }
        throw new Exception("Wrong params");
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
     * @param array confirg
     * @return \Ufee\Amo\Oauthapi
     */
    public function setAmoClient(
        array $config
    ) {
        $client = AmoClient::setInstance($config);
        $client->queries->logs($config['log_queries'] ?? false);
        $client->queries->setDelay($config['query_delay'] ?? 0.5);
        $client->setOauthPath(
            $config['oauth_storage'] ?? (self::$storage_path . '/Oauth')
        );
        AmoAccount::setCacheTime($config['account_cache_time'] ?? 1800);
        $this->amo_client = $client;
        return $this->amo_client;
    }

    /**
	 * Сhecks the existence of a custom field and, if it exists, writes the required value to it
	 * @return boolean
	 */
    public function setCustomField(
        \Ufee\Amo\Base\Models\ModelWithCF $entity,
        string $name,
        string $value,
        string $type = 'text'
    ) {
        if (empty($name)) {
            throw new Exception("Argument 'name' can`t be empty.");
        }
        if (empty($value)) {
            throw new Exception("Argument 'value' can`t be empty.");
        }
        if (!$entity->cf($name)) {
            throw new Exception("Wrong name of field.");
        }
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

}
