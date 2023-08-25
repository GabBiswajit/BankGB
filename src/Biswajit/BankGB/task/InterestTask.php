<?php

namespace Biswajit\BankGB\task;

use Biswajit\BankGB\BankGB;
use pocketmine\player\Player;
use pocketmine\scheduler\Task;

class InterestTask extends Task
{
    
    /** @var BankGB */
    private BankGB $source;
    
    /** @var Player */
    private string $player;
    
    /** @var API */
    private $api;
    
    /** @var Bool */
    private $firstTime;
    
    public function __construct(BankGB $source, string $player)
    {
        $this->source = $source;
        $this->player = $player;
        $this->firstTime = true;
        $this->api = $this->source->getInstance()->getAPI();
    }
    
    public function onRun(): void
    {
      if(!$this->firstTime)
      {
        $playerBankMoney = $this->api->getBankMoney($this->player);
        $interest = $this->source->getInstance()->getConfigFile()->getNested("Interest");
        $addingMoney = $playerBankMoney/$interest;
        $this->api->addBankMoney($this->player, $addingMoney);
        $player = Server::getInstance()->getPlayerExact($this->player);
        if($player instanceof Player)
        {
          $player->sendMessage(" §aRecieved §e$interest% §ainsterest in your bank account");
        }
        if($this->api->getLoanMerit($this->player) < 100)
        {
          $file = $this->source->getInstance()->getPlayerFile($this->player);
          $old_Merit = $file->getNested("Bank.Merit");
          $file->setNested("Bank.Merit", ($old_Merit + 5));
          $file->save();
        }
      }else{
        $this->firstTime = false;
      }
    }
}
