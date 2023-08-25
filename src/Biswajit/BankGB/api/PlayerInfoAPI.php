<?php

namespace Biswajit\BankGB\api;

use pocketmine\Server;
use pocketmine\player\Player;
use skyland\api\ConfigAPI;
use Biswajit\BankGB\BankGB;

class PlayerInfoAPI
{
  
  /** @var string */
  private string $PlayerName;
  
  public function __construct($player)
  {
    if($player instanceof Player)
    {
      $this->PlayerName = $player->getName();
    }else{
      $this->PlayerName = $player;
    }
  }
  /**
   * @return ConfigAPI
   */
  public function getFile(): ConfigAPI
  {
    $Config = new ConfigAPI($this->PlayerName);
    return $Config;
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