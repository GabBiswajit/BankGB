<?php

namespace Biswajit\BankGB;

use pocketmine\Server;
use pocketmine\player\Player;

use Biswajit\BankGB\API;
use Biswajit\BankGB\menu\UI;
use Biswajit\BankGB\task\LoanTask;
use pocketmine\utils\Config;
use Biswajit\BankGB\api\VariablesAPI;
use pocketmine\plugin\PluginBase;
use pocketmine\block\tile\TileFactory;
use cooldogedev\BedrockEconomy\libs\cooldogedev\libSQL\context\ClosureContext;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

class BankGB extends PluginBase
{
  
  /** @var Instance */
  private static $instance;
  
  /** @var array */
  public $loanTask;

  
  public function onEnable(): void 
  {
    self::$instance = $this;
    $this->loanTask = [];
    $this->createplayerFolder();
    $this->getServer()->getPluginManager()->registerEvents(new EventHandler($this), $this);
    $Variables = new VariablesAPI();
    $BedrockEconomy = $this->getServer()->getPluginManager()->getPlugin("BedrockEconomy");
    if($BedrockEconomy === null)
    {
      $this->getServer()->getLogger()->warning("BedrockEconomy not found disable BankGB");
      $this->getServer()->getPluginManager()->disablePlugin($this);
    }
    foreach(glob($this->getDataFolder() . "players/*.yml") as $playerFile) {
      $file = new Config($playerFile, Config::YAML);
      if($file->getNested("Bank.Loan") >= 1)
      {
        $this->loanTask[$file->get("NameTag")] = $this->getScheduler()->scheduleRepeatingTask(new LoanTask($this, $file->get("NameTag"), 1), 20);
      }
  }
  }
  public static function getInstance(): BankGB
  {
    return self::$instance;
  }
  
  public function getUI(): UI
  {
    $ui = new UI($this);
    return $ui->getInstance();
  }
  
  public function getAPI(): API
  {
    $api = new API($this);
    return $api->getInstance();
  }
  
  public function getConfigFile()
  {
    $this->saveResource("config.yml");
    $config = $this->getConfig();
    return $config;
  }
  
  public function getPlayerFile($player)
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    $this->saveResource("players/$playerName.yml");
    $playerFile = new Config($this->getDataFolder() . "players/$playerName.yml", Config::YAML, [
      ]);
    return $playerFile;
  }
  
 private function createplayerFolder() {
        $pluginDataFolder = $this->getDataFolder();
        $playerFolder = $pluginDataFolder . "players/";

        if (!is_dir($playerFolder)) {
            mkdir($playerFolder, 0777, true);
            $this->getLogger()->info("Player folder created successfully!");
        } else {
            $this->getLogger()->info("Player folder already exists.");
        }
  }

  public function onCommand(CommandSender $player, Command $cmd, string $label, array $args): bool 
  {
    switch($cmd->getName())
    {
      case "bank":
        if($player instanceof Player)
        {
          $this->getUI()->bankForm($player);
        }
        break;
      case "setbankmoney":
        if($player instanceof Player)
        {
        if ($sender->isOp()) {
            if (count($args) < 2) {
                $sender->sendMessage("Usage: /setbankmoney <playername> <amount>");
                return true;
            }

            $playerName = $args[0];
            $amount = (int) $args[1];

            $playerFile = $this->getPlayerFile($playerName);
            $playerFile->setNested("Bank.Money", $amount);
            $playerFile->save();

            $sender->sendMessage("Bank money for player $playerName set to $amount.");
        } else {
            $sender->sendMessage("You do not have permission to use this command.");
      }
    }
        break;
      return true;
    }
    return false;
  }
  
}
