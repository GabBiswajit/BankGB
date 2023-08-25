<?php

namespace Biswajit\BankGB\api;

use pocketmine\Server;
use pocketmine\player\Player;

use Biswajit\BankGB\BankGB;

class BankAPI
{
  
  public function __construct()
  {
  }
  
  public function getSource(): BankGB
  {
    return BankGB::getInstance();
  }
  
}
