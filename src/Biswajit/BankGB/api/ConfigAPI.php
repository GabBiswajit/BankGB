<?php

namespace Biswajit\BankGB\api;

use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\player\Player;
use Biswajit\BankGB\BankGB;
use Biswajit\BankGB\API;

class ConfigAPI
{
  
  /** @var Config */
  private Config $Config;
  
  public function __construct($player, bool $NewFile = false)
  {
    if($player instanceof Player)
    {
      $PlayerName = $player->getName();
    }else{
      $PlayerName = $player;
    }
    if($NewFile)
    {
      $this->saveResource("players/" . $PlayerName . ".yml");
    }
    $this->Config = new Config(API::getInstance()->getSource()->getDataFolder() . "players/" . $PlayerName . ".yml", Config::YAML, []);
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