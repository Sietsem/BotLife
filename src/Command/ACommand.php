<?php

namespace Botlife\Command;

abstract class ACommand
{

    const RESPONSE_PUBLIC   = 1;
    const RESPONSE_PRIVATE  = 2;

    public $code            = null;
    public $action          = 'run';

    public $needsSpamfilter = true;
    public $needsAuth       = false;
    public $needsAdmin      = false;
    public $needsOp         = false;
    
    public $responseSymbols = array(
        self::RESPONSE_PUBLIC  => array('@'),
        self::RESPONSE_PRIVATE => array('.', '!'),
    );
    public $responseType    = self::RESPONSE_PRIVATE;
    public $responses       = array();
    public $botChannels     = array('#stats', '#bots');
    
    public function __construct()
    {
        $commands = \Botlife\Application\Storage::loadData('commands');
        if (!isset($commands[get_class($this)])) {
        
            $command = new \StorageObject;
            $command->enabled = true;
            
            $commands[get_class($this)] = $command;
        }
        \Botlife\Application\Storage::saveData('commands', $commands);
    }
    
    public function respondWithInformation($information, $prefix = null)
    {
        $c = new \Botlife\Application\Colors;
        $response = null;
        $i        = 0;
        $total    = count($information);
        foreach ($information as $key => $value) {
            if (is_array($value)) {
                $subResponse = null;
                if (isset($value[0])) {
                    $subResponse .= $c(3, $value[0]);
                    unset($value[0]);
                }
                foreach ($value as $data) {
                    $subResponse .= $c(12, '(');
                    $subI     = 0;
                    $subTotal = count($data);
                    foreach ($data as $subKey => $subValue) {
                        if (is_numeric($subKey)) {
                            $subResponse .= $c(3, $subValue);
                        } else {
                            $subResponse .= $c(12, $subKey . ': ')
                                . $c(3, $subValue);
                        }
                        if ($subI + 1 != $subTotal) {
                            $subResponse .= $c(12, '/');
                        }
                        ++$subI;
                    }
                    $subResponse .=$c(12, ')');
                }
                $response .= $c(12, $key . ': ') . $c(3, $subResponse);
            } else {
                $response .= $c(12, $key . ': ') . $c(3, $value);
            }
            if ($i + 1 != $total) {
                $response .= $c(12, ' - ');
            }
            ++$i; 
        }
        $this->respondWithPrefix($response, $prefix);
    }
    
    public function respondWithPrefix($message, $prefix = null)
    {
        $c = new \Botlife\Application\Colors;
        if (!$prefix) {
            $prefix = strtoupper($this->code);
        }
        $messages = explode(PHP_EOL, $message);
        foreach ($messages as $message) {
            $response = $c(12, '[') . $c(3, $prefix) . $c(12, '] ');
            $response .= $message;
            $this->respond($response);
        }
    }
    
    public function respond($message)
    {
        $this->responses[] = $message;
    }
    
    public function detectResponseType($command, $target = null)
    {
        if (!$target || in_array($target, $this->botChannels)) {
            $this->responseType = self::RESPONSE_PRIVATE;
        } 
        $symbol = substr($command, 0, 1);
        foreach ($this->responseSymbols as $type => $symbols) {
            if (in_array($symbol, $symbols)) {
                $this->responseType = $type;
            }
        }
    }

}
