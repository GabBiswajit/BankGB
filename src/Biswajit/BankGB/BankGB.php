<?php

namespace Biswajit\BankGB;

use Biswajit\BankGB\InterestTask;
use davidglitch04\libEco\libEco;
use jojoe77777\SimpleForm;
use jojoe77777\CustomForm;

use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\Server;
use pocketmine\player\Player;

use pocketmine\plugin\PluginBase;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;

use pocketmine\utils\Config;


class BankGB extends PluginBase implements Listener{

    private static $instance;
    public $player;
    public $playerList = [];

    public function onEnable(): void
    {
        $this->saveDefaultConfig();
        self::$instance = $this;
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        if (!file_exists($this->getDataFolder() . "Players")){
            mkdir($this->getDataFolder() . "Players");
        }
        date_default_timezone_set($this->getConfig()->get("timezone"));
        if ($this->getConfig()->get("enable-interest") === true) {
            $this->getScheduler()->scheduleRepeatingTask(new InterestTask($this), 20);
        }
    }

    public function dailyInterest(){
        if (date("H:i") === "12:00"){
            foreach (glob($this->getDataFolder() . "Players/*.yml") as $players) {
                $playerBankMoney = new Config($players);
                $interest = ($this->getConfig()->get("interest-rates") / 100 * $playerBankMoney->get("Money"));
                $playerBankMoney->set("Money", round($playerBankMoney->get("Money") + $interest));
                $playerBankMoney->save();
                if ($playerBankMoney->get('Transactions') === 0){
                    $playerBankMoney->set('Transactions', date("§b[d/m/y]") . "§e - §aInterest $" . round($interest) . "\n");
                }
                else {
                    $playerBankMoney->set('Transactions', $playerBankMoney->get('Transactions') . date("§b[d/m/y]") . "§e - §a$" . round($interest) . " from interest" . "\n");
                }
                $playerBankMoney->save();
            }
            foreach ($this->getServer()->getOnlinePlayers() as $onlinePlayers){
                $playerBankMoney = new Config($this->getDataFolder() . "Players/" . $onlinePlayers->getName() . ".yml", Config::YAML);
                $onlinePlayers->sendMessage("§aYou have earned $" . round(($this->getConfig()->get("interest-rates") / 100) * $playerBankMoney->get("Money")) . " from bank interest");
            }
        }
    }

    public function onJoin(PlayerJoinEvent $event){
        $player = $event->getPlayer();
        if (!file_exists($this->getDataFolder() . "Players/" . $player->getName() . ".yml")) {
            new Config($this->getDataFolder() . "Players/" . $player->getName() . ".yml", Config::YAML, array(
                "Money" => 0,
                "Transactions" => 0,
            ));
        }
    }

    public function onCommand(CommandSender $player, Command $command, string $label, array $args): bool{
        switch($command->getName()){
            case "bank":
                if($player instanceof Player){
                    if (isset($args[0]) && $player->hasPermission("bankgb.bank.cmd") || isset($args[0])) {
                        if (!file_exists($this->getDataFolder() . "Players/" . $args[0] . ".yml")){
                            $player->sendMessage("§c§lError: §r§aThis player does not have a bank account");
                            return true;
                        }
                        $this->otherTransactionsForm($player, $args[0]);
                        return true;
                    }
                    $this->bankForm($player);
                }
        }
        return true;
    }

    public function bankForm($player)
    {
        $playerBankMoney = new Config($this->getDataFolder() . "Players/" . $player->getName() . ".yml", Config::YAML);
        $form = new SimpleForm(function (Player $player, int $data = null){
            $result = $data;
            if ($result === null) {
                return true;
            }
            switch ($result) {
                case 0;
                    $this->withdrawForm($player);
            }
            switch ($result) {
                case 1;
                    $this->depositForm($player);
            }
            switch ($result) {
                case 2;
                    $this->transferCustomForm($player);
            }
            switch ($result) {
                case 3;
                    $this->transactionsForm($player);
            }
        });

        $form->setTitle("§l§cBANK");
        $form->setContent("§r§eBalance: §r§6$" . $playerBankMoney->get("Money"));
        $form->addButton("§l§bWITHDRAW MONEY\n§l§d» §r§8Click To Withdraw", 1, "https://cdn-icons-png.flaticon.com/512/5024/5024665.png");
        $form->addButton("§l§bDEPOSIT MONEY\n§l§d» §r§8Click To Deposit", 1, "https://cdn-icons-png.flaticon.com/512/2721/2721121.png");
        $form->addButton("§l§bTRANSFER MONEY\n§l§d» §r§8Click To Transfer", 1, "https://cdn-icons-png.flaticon.com/512/5717/5717436.png");
        $form->addButton("§l§bTRANSACTIONS\n§l§d» §r§8Click To View", 1, "https://cdn-icons-png.flaticon.com/512/1216/1216995.png");
        $form->addButton("§cEXIT", 0, "textures/blocks/barrier");
        $form->sendtoPlayer($player);
        return $form;
    }

    public function withdrawForm($player)
    {
    	libEco::myMoney($player, function(float $playerMoney) use ($player): void {
        $playerBankMoney = new Config($this->getDataFolder() . "Players/" . $player->getName() . ".yml", Config::YAML);
        $form = new SimpleForm(function (Player $player, int $data = null){
            $result = $data;
            if ($result === null) {
                return true;
            }
            switch ($result) {
                case 0;
                    $playerBankMoney = new Config($this->getDataFolder() . "Players/" . $player->getName() . ".yml", Config::YAML);
                    if ($playerBankMoney->get("Money") == 0){
                        $player->sendMessage("§aYou Don't Have Any Money In The Bank To Withdraw");
                        return;
                    }
                    libEco::addMoney($player, $playerBankMoney->get("Money"));
                    $player->sendMessage("§aSuccessfully Withdraw " . $playerBankMoney->get("Money") . " From The Bank");
                    if ($playerBankMoney->get('Transactions') === 0){
                        $playerBankMoney->set('Transactions', date("§b[d/m/y]") . "§e - §aWithdrew $" . $playerBankMoney->get("Money") . "\n");
                    }
                    else {
                        $playerBankMoney->set('Transactions', $playerBankMoney->get('Transactions') . date("§b[d/m/y]") . "§e - §aWithdrew $" . $playerBankMoney->get("Money") . "\n");
                    }
                    $playerBankMoney->set("Money", 0);
                    $playerBankMoney->save();
            }
            switch ($result) {
                case 1;
                    $playerBankMoney = new Config($this->getDataFolder() . "Players/" . $player->getName() . ".yml", Config::YAML);
                    if ($playerBankMoney->get("Money") == 0){
                        $player->sendMessage("§aYou have no money");
                        return;
                    }
                    libEco::addMoney($player, $playerBankMoney->get("Money") / 2);
                    $player->sendMessage("§aSuccessfully pull" . $playerBankMoney->get("Money") /2 . " From bank");
                    if ($playerBankMoney->get('Transactions') === 0){
                        $playerBankMoney->set('Transactions', date("§b[d/m/y]") . "§e - §aWithdrew $" . $playerBankMoney->get("Money") / 2 . "\n");
                    }
                    else {
                        $playerBankMoney->set('Transactions', $playerBankMoney->get('Transactions') . date("§b[d/m/y]") . "§e - §aWithdrew $" . $playerBankMoney->get("Money") / 2 . "\n");
                    }
                    $playerBankMoney->set("Money", $playerBankMoney->get("Money") / 2);
                    $playerBankMoney->save();
            }
            switch ($result) {
                case 2;
                    $this->withdrawCustomForm($player);
            }
        });
      });
        $form->setTitle("§l§cWITHDRAW");
        $form->setContent("§r§eBalance: §6$" . $playerBankMoney->get("Money"));
        $form->addButton("§l§bWITHDRAW ALL\n§l§d» §r§8Click To Withdraw", 1, "https://cdn-icons-png.flaticon.com/512/883/883887.png");
        $form->addButton("§l§bWITHDRAW HALF\n§l§d» §r§8Click To Withdraw", 1, "https://cdn-icons-png.flaticon.com/512/883/883887.png");
        $form->addButton("§l§bWITHDRAW CUSTOM\n§l§d» §r§8Click To Open", 1, "https://cdn-icons-png.flaticon.com/512/883/883887.png");
        $form->addButton("§cBACK", 0, "textures/ui/icon_import");
        $form->sendtoPlayer($player);
        return $form;
    }

    public function withdrawCustomForm($player)
    {
    	libEco::myMoney($player, function(float $playerMoney) use ($player): void {
        $playerBankMoney = new Config($this->getDataFolder() . "Players/" . $player->getName() . ".yml", Config::YAML);
        $form = new CustomForm(function (Player $player, $data) {
            $result = $data;
            if ($result === null) {
                return;
            }

            $playerBankMoney = new Config($this->getDataFolder() . "Players/" . $player->getName() . ".yml", Config::YAML);
            if ($playerBankMoney->get("Money") == 0){
                $player->sendMessage("§aYou have no money to withdraw");
                return true;
            }
            if ($playerBankMoney->get("Money") < $data[1]){
                $player->sendMessage("§aYou don't have much money to withdraw" . $data[1]);
                return;
            }
            if (!is_numeric($data[1])){
                $player->sendMessage("§aEnter the correct number!");
                return;
            }
            if ($data[1] <= 0){
                $player->sendMessage("§aYou must enter an amount greater than 0");
                return;
            }
            libEco::addMoney($player, $data[1]);
            $player->sendMessage("§aSuccessfully withdraw " . $data[1] . " Form Bank");
            if ($playerBankMoney->get('Transactions') === 0){
                $playerBankMoney->set('Transactions', date("§b[d/m/y]") . "§e - §aWithdrew $" . $data[1] . "\n");
            }
            else {
                $playerBankMoney->set('Transactions', $playerBankMoney->get('Transactions') . date("§b[d/m/y]") . "§e - §aWithdrew $" . $data[1] . "\n");
            }
            $playerBankMoney->set("Money", $playerBankMoney->get("Money") - $data[1]);
            $playerBankMoney->save();
        });
    });
        $form->setTitle("§lWithdrawal");
        $form->addLabel("Money in the bank: " . $playerBankMoney->get("Money"));
        $form->addInput("§rEnter max", "100000");
        $form->sendtoPlayer($player);
        return $form;
    }


    public function depositForm($player)
    {
    	libEco::myMoney($player, function(float $playerMoney) use ($player): void {
        $playerBankMoney = new Config($this->getDataFolder() . "Players/" . $player->getName() . ".yml", Config::YAML);
        $playerMoney = EconomyAPI::getInstance()->myMoney($player);
        $form = new SimpleForm(function (Player $player, int $data = null){
            $result = $data;
            if ($result === null) {
                return;
            }
            switch ($result) {
                case 0;
                    libEco::myMoney($player, function(float $playerMoney) use ($player): void {
                    $playerBankMoney = new Config($this->getDataFolder() . "Players/" . $player->getName() . ".yml", Config::YAML);
                    if ($playerMoney == 0){
                        $player->sendMessage("§aYou don't have enough money to save");
                        return;
                    }
                    if ($playerBankMoney->get('Transactions') === 0){
                        $playerBankMoney->set('Transactions', date("§b[d/m/y]") . "§e - §aDeposited $" . $playerMoney . "\n");
                    }
                    else {
                        $playerBankMoney->set('Transactions', $playerBankMoney->get('Transactions') . date("§b[d/m/y]") . "§e - §aDeposited $" . $playerMoney . "\n");
                    }
                    $playerBankMoney->set("Money", $playerBankMoney->get("Money") + $playerMoney);
                    $player->sendMessage("§aSuccessfully saved" . $playerMoney . " In Bank");
                    libEco::reduceMoney($player, $playerMoney, function() : void {});
                    $playerBankMoney->save();
             });
            }
            switch ($result) {
                case 1;
                    libEco::myMoney($player, function(float $playerMoney) use ($player): void {
                    $playerBankMoney = new Config($this->getDataFolder() . "Players/" . $player->getName() . ".yml", Config::YAML);
                    if ($playerMoney == 0){
                        $player->sendMessage("§aYou don't have much money to save");
                        return;
                    }
                    if ($playerBankMoney->get('Transactions') === 0){
                        $playerBankMoney->set('Transactions', date("§b[d/m/y]") . "§e - §aDeposited $" . $playerMoney / 2 . "\n");
                    }
                    else {
                        $playerBankMoney->set('Transactions', $playerBankMoney->get('Transactions') . date("§b[d/m/y]") . "§e - §aDeposited $" . $playerMoney / 2 . "\n");
                    }
                    $playerBankMoney->set("Money", $playerBankMoney->get("Money") + ($playerMoney / 2));
                    $player->sendMessage("§aSuccessfully saved " . $playerMoney / 2 . " In Bank");
                    $amount = (int) $playerMoney / 2;
                    libEco::reduceMoney($player, $amount, function() : void {});
                    $playerBankMoney->save();
              });
            }
            switch ($result) {
                case 2;
                    $this->depositCustomForm($player);
            }
        });
   });
        $form->setTitle("§l§cDEPOSIT");
        $form->setContent("§r§eBalance: §6$" . $playerBankMoney->get("Money"));
        $form->addButton("§l§bDEPOSIT ALL\n§l§d» §r§8Click To Deposit", 1, "https://cdn-icons-png.flaticon.com/512/4825/4825116.png");
        $form->addButton("§l§bDEPOSIT HALF\n§l§d» §r§8Click To Deposit", 1, "https://cdn-icons-png.flaticon.com/512/4825/4825116.png");
        $form->addButton("§l§bDEPOSIT CUSTOM\n§l§d» §r§8Click To Deposit", 1, "https://cdn-icons-png.flaticon.com/512/4825/4825116.png");
        $form->addButton("§cBACK", 0, "textures/ui/icon_import");
        $form->sendtoPlayer($player);
        return $form;
    }

    public function depositCustomForm($player)
    {
    	libEco::myMoney($player, function(float $playerMoney) use ($player): void {
        $playerBankMoney = new Config($this->getDataFolder() . "Players/" . $player->getName() . ".yml", Config::YAML);
        $form = new CustomForm(function (Player $player, $data) {
            $result = $data;
            if ($result === null) {
                return;
            }
            $playerBankMoney = new Config($this->getDataFolder() . "Players/" . $player->getName() . ".yml", Config::YAML);
            if ($playerMoney < $data[1]){
                $player->sendMessage("§aYou don't have enough money to save" . $data[1] . " ke Bank");
                return;
            }
            if (!is_numeric($data[1])){
                $player->sendMessage("§aEnter the correct number");
                return;
            }
            if ($data[1] <= 0){
                $player->sendMessage("§aEnter a number starting from 0");
                return;
            }
            $player->sendMessage("§aManaged to save Rp" . $data[1] . " ke Bank");
            if ($playerBankMoney->get('Transactions') === 0){
                $playerBankMoney->set('Transactions', date("§b[d/m/y]") . "§e - §aDeposited $" . $data[1] . "\n");
            }
            else {
                $playerBankMoney->set('Transactions', $playerBankMoney->get('Transactions') . date("§b[d/m/y]") . "§e - §aDeposited $" . $data[1] . "\n");
            }
            $playerBankMoney->set("Money", $playerBankMoney->get("Money") + $data[1]);
            libEco::reduceMoney($player, $data[1], function() : void {});
            $playerBankMoney->save();
        });
    });
        $form->setTitle("§lStorage");
        $form->addLabel("Money:" . $playerBankMoney->get("Money"));
        $form->addInput("§rEnter max", "100000");
        $form->sendtoPlayer($player);
        return $form;
    }

    public function transferCustomForm($player)
    {

        $list = [];
        foreach ($this->getServer()->getOnlinePlayers() as $players){
            if ($players->getName() !== $player->getName()) {
                $list[] = $players->getName();
            }
        }
        $this->playerList[$player->getName()] = $list;

        $playerBankMoney = new Config($this->getDataFolder() . "Players/" . $player->getName() . ".yml", Config::YAML);
        libEco::myMoney($player, function(float $playerMoney) use ($player): void {
        $form = new CustomForm(function (Player $player, $data) {
            $result = $data;
            if ($result === null) {
                return;
            }

            if (!isset($this->playerList[$player->getName()][$data[1]])){
                $player->sendMessage("§aYou have to choose the right player");
                return;
            }

            $index = $data[1];
            $playerName = $this->playerList[$player->getName()][$index];

            $playerBankMoney = new Config($this->getDataFolder() . "Players/" . $player->getName() . ".yml", Config::YAML);
            $otherPlayerBankMoney = new Config($this->getDataFolder() . "Players/" . $playerName . ".yml", Config::YAML);
            if ($playerBankMoney->get("Money") == 0){
                $player->sendMessage("§aYou don't have money in the bank");
                return;
            }
            if ($playerBankMoney->get("Money") < $data[2]){
                $player->sendMessage("§aYou don't have enough money to transfer as big Rp" . $data[2]);
                return;
            }
            if (!is_numeric($data[2])){
                $player->sendMessage("§aEnter a number");
                return;
            }
            if ($data[2] <= 0){
                $player->sendMessage("§eYou must send a minimum. 1");
                return;
            }
            $player->sendMessage("§aSuccessful transfer Rp" . $data[2] . " into " . $playerName . "'s ke Bank lain");
            if ($this->getServer()->getPlayerExact($playerName)) {
                $otherPlayer = $this->getServer()->getPlayerExact($playerName);
                $otherPlayer->sendMessage("§a" . $player->getName() . " transfer Rp" . $data[2] . " to Bank you");
            }
            if ($playerBankMoney->get('Transactions') === 0){
                $playerBankMoney->set('Transactions', date("§b[d/m/y]") . "§e - §aTransferred $" . $data[2] . " into " . $playerName . "'s bank account" . "\n");
                $otherPlayerBankMoney->set('Transactions', date("§b[d/m/y]") . "§e - §a" . $player->getName() . " Transferred $" . $data[2] . " into your bank account" . "\n");
            }
            else {
                $otherPlayerBankMoney->set('Transactions', $playerBankMoney->get('Transactions') . date("§b[d/m/y]") . "§e - §a" . $player->getName() . " Transferred $" . $data[2] . " into your bank account" . "\n");
                $playerBankMoney->set('Transactions', $playerBankMoney->get('Transactions') . date("§b[d/m/y]") . "§e - §aTransferred $" . $data[2] . " into " . $playerName . "'s bank account" . "\n");
            }
            $playerBankMoney->set("Money", $playerBankMoney->get("Money") - $data[2]);
            $otherPlayerBankMoney->set("Money", $otherPlayerBankMoney->get("Money") + $data[2]);
            $playerBankMoney->save();
            $otherPlayerBankMoney->save();
            });
       });
  
        $form->setTitle("§lTransfer");
        $form->addLabel("Money in bank: " . $playerBankMoney->get("Money"));
        $form->addDropdown("Choose player", $this->playerList[$player->getName()]);
        $form->addInput("§rEnter the amount max", "100000");
        $form->sendtoPlayer($player);
        return $form;
    }

    public function transactionsForm($player)
    {
    	libEco::myMoney($player, function(float $playerMoney) use ($player): void {
        $playerBankMoney = new Config($this->getDataFolder() . "Players/" . $player->getName() . ".yml", Config::YAML);
        $form = new SimpleForm(function (Player $player, int $data = null){
            $result = $data;
            if ($result === null) {
                return;
            }
        });
     });
        $form->setTitle("§lTransfer");
        if ($playerBankMoney->get('Transactions') === 0){
            $form->setContent("You have not made any transactions yet");
        }
        else {
            $form->setContent($playerBankMoney->get("Transactions"));
        }
        $form->addButton("§l§cEXIT\n§r§dClick to close...",0,"textures/ui/cancel");
        $form->sendtoPlayer($player);
        return $form;
    }

    public function otherTransactionsForm($sender, $player)
    {
        $playerBankMoney = new Config($this->getDataFolder() . "Players/" . $player . ".yml", Config::YAML);
        $form = new SimpleForm(function (Player $player, int $data = null){
            $result = $data;
            if ($result === null) {
                return true;
            }
        });

        $form->setTitle("§l" . $player . "'s Transactions");
        if ($playerBankMoney->get('Transactions') === 0){
            $form->setContent($player . " has not made any transactions yet");
        }
        else {
            $form->setContent($playerBankMoney->get("Transactions"));
        }
        $form->addButton("§l§cEXIT\n§r§dClick to close...",0,"textures/ui/cancel");
        $form->sendtoPlayer($sender);
        return $form;
    }

    public static function getInstance(): BankGB {
        return self::$instance;
    }

}
