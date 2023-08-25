<?php

/** 
 * 
 * ███████╗██╗   ██╗███████╗███╗  ██╗████████╗██╗  ██╗ █████╗ ███╗  ██╗██████╗░██╗     ███████╗██████╗ 
 * ██╔════╝██║   ██║██╔════╝████╗ ██║╚══██╔══╝██║  ██║██╔══██╗████╗ ██║██╔══██╗██║     ██╔════╝██╔══██╗
 * █████╗  ╚██╗ ██╔╝█████╗  ██╔██╗██║   ██║   ███████║███████║██╔██╗██║██║  ██║██║     █████╗  ██████╔╝
 * ██╔══╝   ╚████╔╝ ██╔══╝  ██║╚████║   ██║   ██╔══██║██╔══██║██║╚████║██║  ██║██║     ██╔══╝  ██╔══██╗
 * ███████╗  ╚██╔╝  ███████╗██║ ╚███║   ██║   ██║  ██║██║  ██║██║ ╚███║██████╔╝███████╗███████╗██║  ██║
 * ╚══════╝   ╚═╝   ╚══════╝╚═╝  ╚══╝   ╚═╝   ╚═╝  ╚═╝╚═╝  ╚═╝╚═╝  ╚══╝╚═════╝ ╚══════╝╚══════╝╚═╝  ╚═╝
 */
namespace Biswajit\BankGB;

use pocketmine\Server;
use pocketmine\player\Player;

use Biswajit\BankGB\API;
use Biswajit\BankGB\BankGB;
use Biswajit\BankGB\api\VariablesAPI;
use Biswajit\BankGB\task\InterestTask;
use cooldogedev\BedrockEconomy\libs\cooldogedev\libSQL\context\ClosureContext;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use cooldogedev\BedrockEconomy\event\transaction\TransactionSubmitEvent;

class EventHandler implements Listener
{
  
  /** @var Instance */
  private static $instance;
  
  /** @var TaskHandler */
  public $interest;
 
  /** @var API */
  public $api;
  
  /** @var BankGB */
  private $source;
  
  /** @var Config */
  public $players;
  
  /** @var Config */
  public $config;
  
  public function __construct(BankGB $source)
  {
    self::$instance = $this;
    $this->source = $source;
    $this->interest = [];
    $this->api = $source->getAPI();
    $this->config = $source->getConfigFile();
  }
  
  public static function getInstance(): EventHandler
  {
    return self::$instance;
  }
  
  public function onJoin(PlayerJoinEvent $event)
  {
    $player = $event->getPlayer();
    $playerName = $player->getName();
    $bankMoney = $this->api->getBankMoney($player);
    $this->api->registerPlayer($player);
    
      if($bankMoney >= 1)
      {
        $this->interest[$playerName] = $this->getSource()->getScheduler()->scheduleRepeatingTask(new InterestTask($this->source, $playerName), 72000);
      }
    $API = API::getBalanceAPI($player);
    $API->registerBalance();
  }
  
  public function onQuit(PlayerQuitEvent $event)
  {
    $player = $event->getPlayer();
    $playerName = $player->getName();
   
    if(isset($this->interest[$playerName]))
    {
      $this->interest[$playerName]->cancel(); 
    }
    $Variables = VariablesAPI::getInstance();
    $API = API::getBalanceAPI($player);
    $API->unregisterBalance();
  }
  /**
   * @return BankGB
   */
  public function getSource(): BankGB
  {
    $BankGB = API::getSource();
    return $BankGB;
  }
  
}
