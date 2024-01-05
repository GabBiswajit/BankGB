<?php

namespace Biswajit\BankGB;

use Cassandra\Time;
use Biswajit\BankGB\BankGB;

use pocketmine\scheduler\Task;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\Config;

class InterestTask extends Task{

    public function __construct(private BankGB $plugin) {
        $this->plugin = $plugin;
    }

    public function onRun(): void
  {
        $this->plugin->dailyInterest();
    }
}
