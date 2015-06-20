<?php

use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Stopwatch\StopwatchEvent;

class ArrayStorage implements \ArrayAccess
{
    const ROOT_LEVEL = 1;

    protected $data = array();

    protected $auto_persist_level = -1;

    protected $level = self::ROOT_LEVEL;

    protected $options = array();

    protected static $prototype;

    public function __construct($level = 1, $options = array())
    {
        $this->setOptions($options);

        $this->setLevel($level);
    }

    public function setLevel($level)
    {
        $this->level = $level;

        if($this->options["debug"]) echo "[ARRAY STORAGE] CREATION D'UN NIVEAU ".$this->level." AVEC AUTO PERSIST AU NIVEAU ".$this->auto_persist_level.PHP_EOL;

        if($this->options["debug"] && $this->level == $this->options["auto_persist_level"])
        {
            echo "[ARRAY STORAGE] AUTO PERSIST".PHP_EOL;
        }

        return $this;
    }

    protected function factory()
    {
        if(self::$prototype === null)
        {
            self::$prototype = new self($this->level + 1, $this->options);

            return clone self::$prototype;
        }

        $clone = clone self::$prototype;

        return $clone->setLevel($this->level + 1);
    }

    public function setOptions(array $options)
    {
        $this->options = array_merge(array(
            "auto_persist_level" => -1,
            "mode" => "rw",
            "debug" => false
        ), $options);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Whether a offset exists
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     * An offset to check for.
     * </p>
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     */
    public function offsetExists($offset)
    {
        // TODO: Implement offsetExists() method.
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     * The offset to retrieve.
     * </p>
     * @return mixed Can return all value types.
     */
    public function offsetGet($offset)
    {
        return $this->data[$offset];
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to set
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     * The offset to assign the value to.
     * </p>
     * @param mixed $value <p>
     * The value to set.
     * </p>
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        if($this->options["mode"] == "r")
        {
            throw new \Exception("ce tableau est en lecture seule");
        }

        if($offset == "")
        {
            $this->data[] = $this->transformElement($value);
        }
        else
        {
            $this->data[$offset] = $this->transformElement($value);
        }
    }

    public function transformElement($valueItem)
    {
        if(!is_array($valueItem))
        {
            return $valueItem;
        }

        $data = $this->factory();

        foreach($valueItem as $key => $value)
        {
            $data[$key] = $this->transformElement($value);
        }

        return $data;
    }

    public function reverseTransform($valueItem)
    {
        if(!is_object($valueItem) || !$valueItem instanceof ArrayStorage)
        {
            return $valueItem;
        }

        return $valueItem->toArray();
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     * The offset to unset.
     * </p>
     * @return void
     */
    public function offsetUnset($offset)
    {
        // TODO: Implement offsetUnset() method.
    }

    public function toArray()
    {
        $data = array();

        foreach($this->data as $key => $value)
        {
            $data[$key] = $this->reverseTransform($value);
        }

        return $data;
    }


    public function persist()
    {
        return print_r($this->toArray(), true);
    }

    public function __destruct()
    {
        if($this->options["debug"] && $this->level == self::ROOT_LEVEL) echo $this->persist();
    }
}

function buildData(&$data)
{
    $fhandle = fopen("test.csv", "r");

    while(($dataRow = fgetcsv($fhandle, 2048, ";")) !== false)
    {
        $i = 1;

        $row = array();

        foreach($dataRow as $dataRowItem)
        {
            $row["FIELD".$i] = $dataRowItem;

            $i++;
        }

        $data[] = $row;
    }

    fclose($fhandle);
}

require "vendor/autoload.php";
require "StopWatchCustom.php";

const FACTORY_ARRAY = "factory_array";
const FACTORY_SUPER_ARRAY = "factory_super_array";
const JSON_ENCODE_ARRAY = "json_encode_array";
const JSON_ENCODE_SUPER_ARRAY = "json_encore_super_array";

$events = array();

$stopWatch = new StopWatchCustom();

$stopWatch->start(FACTORY_ARRAY);

$data = array();

buildData($data);

$events[FACTORY_ARRAY] = $stopWatch->stop(FACTORY_ARRAY);

$stopWatch->start(FACTORY_SUPER_ARRAY);

$superData = new ArrayStorage(1, array(
    "auto_persist_level" => 2,
    "mode" => "rw"
));

buildData($superData);

$events[FACTORY_SUPER_ARRAY] = $stopWatch->stop(FACTORY_SUPER_ARRAY);

$stopWatch->start(JSON_ENCODE_ARRAY);

json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

$events[JSON_ENCODE_ARRAY] = $stopWatch->stop(JSON_ENCODE_ARRAY);

$stopWatch->start(JSON_ENCODE_SUPER_ARRAY);

json_encode($superData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

$events[JSON_ENCODE_SUPER_ARRAY] = $stopWatch->stop(JSON_ENCODE_SUPER_ARRAY);

foreach($events as $name => $event)
{
    /** @var $event StopwatchEvent */

    echo "=============== $name =================".PHP_EOL;

    echo "duration : ".$event->getDuration().PHP_EOL;

    echo "memory : ".$event->getMemory().PHP_EOL;

    echo "=======================================".PHP_EOL;
}

/**
$test = new ArrayStorage(1, array(
    "auto_persist_level" => 2,
    "mode" => "rw"
));

$test["un"] = array();

$test["un"]["a"] = array();

$test["un"]["a"]["b"] = "b";

$test["deux"] = array(
    "trois" => 3
);
 */