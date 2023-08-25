<?php

namespace Biswajit\BankGB\api;

use pocketmine\Server;
use pocketmine\player\Player;

use Biswajit\BankGB\BankGB;

class VariablesAPI
{
  
  /** @var VariablesAPI */
  private static $instance;
  
  /** @var array */
  private array $Money;
 
  public function __construct()
  {
    self::$instance = $this;
    $this->Money = [];
  }
  
  /**
   * @return VariablesAPI
   */
  public static function getInstance(): VariablesAPI
  {
    return self::$instance;
  }
  
  /**
   * @param string $type
   * @param int|array|string $value
   * @return Void
   */
  public function setVariable(string $Type, $Value): Void
  {
    if($Type === "Money")
    {
      if(is_array($Value))
      {
        $this->Money = $Value;
      }
    }
  }
  
  /**
   * @param string $Type
   * @return Varible
   */
  public function getVariable(string $Type)
  {
    if($Type === "Money")
    {
      return $this->Money;
    }
  }
  
  /**
   * @param string $Type
   * @param int|array|string $Key
   * @return Bool
   */
  public function hasKey(string $Type, $Key)
  {
    $Variable = $this->getVariable($Type);
    
    if(is_array($Variable))
    {
      if(array_key_exists($Key, $Variable))
      {
        $Bool = true;
      }else{
        $Bool = false;
      }
    }else{
      $Bool = false;
    }
    
    return $Bool;
  }
  
  /**
   * @param string $Type
   * @param int|array|string $Key
   * @return Void
   */
  public function removeKey(string $Type, $Key)
  {
    $Variable = $this->getVariable($Type);
    
    if(is_array($Variable))
    {
      if(array_key_exists($Key, $Variable))
      {
        if($Type === "Money")
        {
          unset($this->Money[$Key]);
        }
      }
    }
  }
  
  /**
   * @return BankGB
   */
  public function getSource(): BankGB
  {
    return BankGB::getInstance();
  }
  
}