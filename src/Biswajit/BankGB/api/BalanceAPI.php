<?php

namespace Biswajit\BankGB\api;

use pocketmine\Server;
use pocketmine\player\Player;

use Biswajit\BankGB\BankGB;
use Biswajit\BankGB\api\VariablesAPI;
use cooldogedev\BedrockEconomy\libs\cooldogedev\libSQL\context\ClosureContext;

class BalanceAPI
{
  
  /** @var string */
  private string $PlayerName;
  
  /** @var array */
  private array $Money;
  
  /** @var VariablesAPI */
  private VariablesAPI $Variables;
  
  public function __construct($player)
  {
    if($player instanceof Player)
    {
      $this->PlayerName = $player->getName();
    }else{
      $this->PlayerName = $player;
    }
    
    $this->Variables = VariablesAPI::getInstance();
    $this->Money = $this->Variables->getVariable("Money");
  }
  
  /**
   * @return Void
   */
  public function registerBalance(): void
  {
    $KeyExists = $this->Variables->hasKey("Money", $this->PlayerName);
    if(!$KeyExists)
    {
      $economy = Server::getInstance()->getPluginManager()->getPlugin("BedrockEconomy")->getAPI();
      $economy->getPlayerBalance($this->PlayerName,
        ClosureContext::create(
          function (?int $balance): void
          {
            $Money = $this->Variables->getVariable("Money");
            if(!is_null($balance))
            {
              $Money[$this->PlayerName] = $balance;
            }else{
              $Money[$this->PlayerName] = 0;
            }
            
            $this->Variables->setVariable("Money", $Money);
          }
        )
      );
    }
  }
  
  /**
   * @return Void
   */
  public function unregisterBalance(): void
  {
    $this->Variables->removeKey("Money", $this->PlayerName);
  }
  
  /**
   * @return Void
   */
  public function updateBalance(): void
  {
    $Money = $this->Variables->getVariable("Money");
    foreach($Money as $PlayerName => $Bal)
    {
      $economy = Server::getInstance()->getPluginManager()->getPlugin("BedrockEconomy")->getAPI();
      $economy->getPlayerBalance($PlayerName,
        ClosureContext::create(
          function (?int $balance) use($PlayerName, $Money): void
          {
            if(!is_null($balance))
            {
              $Money[$PlayerName] = $balance;
            }else{
              $Money[$PlayerName] = 0;
            }
            
            $this->Variables->setVariable("Money", $Money);
          }
        )
      );
    }
  }
  
  /**
   * @return Int
   */
  public function getBalance(): int
  {
    $KeyExists = $this->Variables->hasKey("Money", $this->PlayerName);
    if($KeyExists)
    {
      $Money = $this->Variables->getVariable("Money");
      $Balance = $Money[$this->PlayerName];
    }else{
      $Balance = 0;
    }
    
    return $Balance;
  }
  
  /**
   * @return BankGB
   */
  public function getSource(): BankGB
  {
    $BankGB = BankGB::getInstance();
    return $BankGB;
  }
  
}