<?php

/**
*  █████╗ ██████╗ ██╗
* ██╔══██╗██╔══██╗██║
* ███████║██████╔╝██║
* ██╔══██║██╔═══╝ ██║
* ██║  ██║██║     ██║
* ╚═╝  ╚═╝╚═╝     ╚═╝
                   
*/

namespace Biswajit\BankGB;

use pocketmine\Server;
use pocketmine\player\Player;

use Biswajit\BankGB\BankGB;
use Biswajit\BankGB\EventHandler;
use Biswajit\BankGB\api\BalanceAPI;
use Biswajit\BankGB\api\VariablesAPI;
use Biswajit\BankGB\task\InterestTask;
use Biswajit\BankGB\api\PlayerInfoAPI;
use cooldogedev\BedrockEconomy\libs\cooldogedev\libSQL\context\ClosureContext;

class API
{
  
  /** @var API */
  private static $instance;
  
  /** @var BankGB */
  private $source;
  
  
  public function __construct(BankGB $source)
  {
    self::$instance = $this;
    $this->source = $source;
    $this->config = $this->getSource()->getConfigFile();
  }
  
  /**
   * @return API
   */
  public static function getInstance(): API
  {
    return self::$instance;
  }
 
  /**
   * @return VariablesAPI
   */
  public static function getVariables(): VariablesAPI
  {
    $Variables = VariablesAPI::getInstance();
    return $Variables;
  }
  
  /**
   * @return PlayerInfoAPI
   */
  public static function getPlayerInfo($player): PlayerInfoAPI
  {
    $Info = new PlayerInfoAPI($player);
    return $Info;
  }
  
  /**
   * @return BalanceAPI
   */
  public static function getBalanceAPI($player): BalanceAPI
  {
    $Balance = new BalanceAPI($player);
    return $Balance;
  }
  
  public function registerPlayer($player): void
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
      $playerFile = $this->getSource()->getPlayerFile($playerName);
      $playerFile->setNested("Bank.Money", 0);
      $playerFile->setNested("Bank.Loan", 0);
      $playerFile->setNested("Bank.Merit", 100);
      $playerFile->setNested("Bank.MaxTime", 0);
      $playerFile->setNested("Bank.Time", 0);
      $playerFile->save();
    }
 
  public function getBankMoney($player)
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
      $data = $this->getSource()->getPlayerFile($playerName)->getNested("Bank.Money");
      return $data;
    }else{
      return null;
    }
  }
  
  public function addBankMoney($player, int $amount): bool
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
      $money = $this->getBankMoney($player);
      $total = $money + $amount;
      $playerFile = $this->getSource()->getPlayerFile($playerName);
      $playerFile->setNested("Bank.Money", $total);
      $playerFile->save();
      if($this->getBankMoney($player) === 0)
      {
        if($amount >= 1)
        {
          if(!array_key_exists($playerName, Eventhandler::getInstance()->interest))
          {
            EventHandler::getInstance()->interest[$playerName] =  $this->getSource()->getScheduler()->scheduleRepeatingTask(new InterestTask($this->source, $playerName), 72000);
          }
        }
      }
      return true;
    }else{
      return false;
    }
  }
  
  public function reduceBankMoney($player, int $amount): bool
  {
  if ($player instanceof Player)
  {
    $playerName = $player->getName();
    $money = $this->getBankMoney($player);
    $total = $money - $amount;
    $playerFile = $this->getSource()->getPlayerFile($playerName);
    $playerFile->setNested("Bank.Money", $total);
    $playerFile->save();
    if ($total === 0)
    {
      if (array_key_exists($playerName, Eventhandler::getInstance()->interest))
      {
        EventHandler::getInstance()->interest[$playerName]->cancel();
      }
      return true;
    }
    else
    {
      return false;
    }
  }else
  {
    return false;
  }
}
  
  public function getLoanMerit($player)
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
      $data = $this->getSource()->getPlayerFile($playerName)->getNested("Bank.Merit");
      return $data;
    }else{
      return null;
    }
  }
  
  public function getLoan($player)
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
      $data = $this->getSource()->getPlayerFile($playerName)->getNested("Bank.Loan");
      return $data;
    }else{
      return null;
    }
  }
  
  public function addLoan($player, int $amount): bool
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
      $economy = Server::getInstance()->getPluginManager()->getPlugin("BedrockEconomy")->getAPI();
      $economy->addToPlayerBalance($playerName, $amount);
      $playerFile = $this->getSource()->getPlayerFile($playerName);
      $playerFile->setNested("Bank.Loan", $amount);
      $playerFile->save();
      return true;
    }else{
      return false;
    }
  }
  
  public function reduceLoan($player, int $amount): bool
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
      $economy = Server::getInstance()->getPluginManager()->getPlugin("BedrockEconomy")->getAPI();
      $economy->subtractFromPlayerBalance($playerName, $amount);
      $loan = $this->getLoan($playerName);
      $playerFile = $this->getSource()->getPlayerFile($playerName);
      $playerFile->setNested("Bank.Loan", ($loan - $amount));
      $playerFile->save();
      if($loan - $amount === 0)
      {
        $playerFile->setNested("Bank.Time", 0);
        $playerFile->setNested("Bank.MaxTime", 0);
        $playerFile->save();
      }
      return true;
    }else{
      return false;
    }
  }
  
  public function addLoanTime($player, int $time): bool
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
      $oldTime = $this->getSource()->getPlayerFile($playerName)->getNested("Bank.Time");
      $playerFile = $this->getSource()->getPlayerFile($playerName);
      $playerFile->setNested("Bank.Time", $oldTime + $time);
      $playerFile->save();
      return true;
    }else{
      return false;
    }
  }
 
  public function setMaxLoanTime($player, int $time): bool
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
      $playerFile = $this->getSource()->getPlayerFile($playerName);
      $playerFile->setNested("Bank.MaxTime", $time);
      $playerFile->save();
      return true;
    }else{
      return false;
    }
  }
  
  public function recoverLoan($player): bool
  {
    if($player instanceof Player)
    {
        $playerName = $player->getName();
        $loan = $this->getSource()->getPlayerFile($playerName)->getNested("Bank.Loan");
        $playerFile = $this->getSource()->getPlayerFile($playerName);
        $name = $player->getName();
        Server::getInstance()->getCommandMap()->dispatch($player, "setbalance \"$name\" 0");
        $a_merit = $this->getSource()->getPlayerFile($playerName)->getNested("Bank.Merit");
        $b_merit = $loan/($a_merit * 1000);
        $playerFile = $this->getSource()->getPlayerFile($playerName);
        $playerFile->setNested("Bank.Merit", $b_merit);
        $playerFile->setNested("Bank.Loan", 0);
        $playerFile->setNested("Bank.Money", 0);
        $playerFile->save();
        return true;
      }else{
        return false;
      }
    }
  
 public function changeNumericFormat(int $number, string $format)
  {
    if($format === "k")
    {
      $numeric = $number/1000;
      $data = $numeric."k";
      return $data;
    }
    if($format === "time")
    {
      $secs = (int)$number;
        if($secs === 0)
        {
          return '0 secs';
        }
        $mins  = 0;
        $hours = 0;
        $days  = 0;
        $weeks = 0;
        if($secs >= 60)
        {
          $mins = (int)($secs / 60);
          $secs = $secs % 60;
        }
        if($mins >= 60)
        {
          $hours = (int)($mins / 60);
          $mins = $mins % 60;
        }
        if($hours >= 24)
        {
          $days = (int)($hours / 24);
          $hours = $hours % 60;
        }
        if($days >= 7)
        {
          $weeks = (int)($days / 7);
          $days = $days % 7;
        }
        
        $result = '';
        if($weeks)
        {
            $result .= "$weeks weeks ";
        }
        if($days)
        {
            $result .= "$days days ";
        }
        if($hours)
        {
            $result .= "$hours hours ";
        }
        if($mins)
        {
            $result .= "$mins mins ";
        }
        if($secs)
        {
            $result .= "$secs secs ";
        }
        $result = rtrim($result);
        return $result;
    }
  }
  

  public static function getSource(): BankGB
  {
    $BankGB = BankGB::getInstance();
    return $BankGB;
  }
  
}
