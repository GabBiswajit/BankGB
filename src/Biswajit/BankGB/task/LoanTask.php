<?php

namespace Biswajit\BankGB\task;

use pocketmine\Server;
use Biswajit\BankGB\BankGB;
use pocketmine\player\Player;
use pocketmine\scheduler\Task;

class LoanTask extends Task
{
    
    /** @var BankGB */
    private BankGB $source;
    
    /** @var string */
    private string $player;
    
    /** @var Int */
    private int $time;
    
    /** @var API */
    private $api;
    
    public function __construct(BankGB $source, string $player, int $time)
    {
        $this->time = $time;
        $this->source = $source;
        $this->player = $player;
        $this->api = $this->source->getInstance()->getAPI();
    }
    
    public function onRun(): void
    {
      if($this->api->getLoan($this->player) !== 0)
      {
        if(!($this->source->getInstance()->getPlayerFile($this->player)->getNested("Bank.Time") >= $this->source->getInstance()->getPlayerFile($this->player)->getNested("Bank.MaxTime")))
        {
          $this->api->addLoanTime($this->player, $this->time);
        }else{
          $player = Server::getInstance()->getPlayerExact($this->player);
          if($player instanceof Player)
          {
            $player->sendMessage("§c Your Time To Pay Your Loan Is Exceeded");
          }
          $this->getHandler()->cancel();
          $this->api->recoverLoan($this->player);
          $file = $this->source->getInstance()->getPlayerFile($this->player);
          $file->setNested("Bank.Time", 0);
          $file->setNested("Bank.MaxTime", 0);
          $file->save();
        }
      }else{
        $this->getHandler()->cancel();
        $file = $this->source->getInstance()->getPlayerFile($this->player);
        $file->setNested("Bank.Time", 0);
        $file->setNested("Bank.MaxTime", 0);
        $file->save();
      }
    }
}