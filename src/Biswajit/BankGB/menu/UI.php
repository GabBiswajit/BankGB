<?php

namespace Biswajit\BankGB\menu;

use pocketmine\Server;
use pocketmine\player\Player;

use Biswajit\BankGB\Forms\GBForm;
use Biswajit\BankGB\Forms\CustomForm;

use Biswajit\BankGB\API;
use Biswajit\BankGB\BankGB;
use Biswajit\BankGB\task\LoanTask;
use pocketmine\utils\Config;
use pocketmine\scheduler\ClosureTask;
use cooldogedev\BedrockEconomy\libs\cooldogedev\libSQL\context\ClosureContext;

class UI
{
  
  /** @var Instance */
  private static $instance;
  
  /** @var BankGB */
  private $source;
  
  /** @var API */
  public $api;
  
  /** @var Config */
  public $players;
  
  /** @var Config */
  public $config;
  
  public function __construct(BankGB $source)
  {
    self::$instance = $this;
    $this->source = $source;
    $this->api = $source->getInstance()->getAPI();
    $this->config = $source->getInstance()->getConfigFile();
  }
  
  public static function getInstance(): UI
  {
    return self::$instance;
  }
  
  public function bankForm($player)
    {   
        $playerName = $player->getName();
        $playerBankMoney = $this->api->getSource()->getPlayerFile($playerName)->getNested("Bank.Money");
        $form = new GBForm(function (Player $player, int $data = null){
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
                    $this->loanForm($player);
            }
        });

        $form->setTitle("§l§cBANK");
        $form->setContent("§r§eBalance: §r§6$" . $playerBankMoney);
        $form->addButton("§l§bWITHDRAW MONEY\n§l§d» §r§8Click To Withdraw", 1, "https://cdn-icons-png.flaticon.com/512/5024/5024665.png");
        $form->addButton("§l§bDEPOSIT MONEY\n§l§d» §r§8Click To Deposit", 1, "https://cdn-icons-png.flaticon.com/512/2721/2721121.png");
        $form->addButton("§l§bTRANSFER MONEY\n§l§d» §r§8Click To Transfer", 1, "https://cdn-icons-png.flaticon.com/512/5717/5717436.png");
        $form->addButton("§l§bLOAN\n§l§d» §r§8Click To View", 1, "https://cdn-icons-png.flaticon.com/512/1216/1216995.png");
        $form->addButton("§cEXIT", 0, "textures/blocks/barrier");
        $form->sendtoPlayer($player);
        return $form;
    }
    
  public function withdrawForm($player)
    {
        $playerName = $player->getName();
        $playerBankMoney = $this->api->getSource()->getPlayerFile($playerName)->getNested("Bank.Money");
        
        $form = new GBForm(function (Player $player, int $data = null){
            $result = $data;
            if ($result === null) {
                return true;
            }
            switch ($result) {
                case 0;
          $economy = Server::getInstance()->getPluginManager()->getPlugin("BedrockEconomy")->getAPI();
          if($economy !== null)
          {
            $playerBankMoney = $this->api->getBankMoney($player);
            $addingMoney = $playerBankMoney;
            if($addingMoney >= 1)
            {
              $economy->addToPlayerBalance($player->getName(), $addingMoney);
              $this->api->reduceBankMoney($player, $addingMoney);
              $player->sendMessage("§aSuccessfully whitdrawed §e$addingMoney$ §afrom your bank account");
            }else{
              $player->sendMessage("§cError can't whitdraw §e$addingMoney$ §cfrom your bank account");
            }
            $player->removeCurrentWindow();
          }
          }
            switch ($result) {
                case 1;
          $economy = Server::getInstance()->getPluginManager()->getPlugin("BedrockEconomy")->getAPI();
          if($economy !== null)
          {
            $playerBankMoney = $this->api->getBankMoney($player);
            $addingMoney = $playerBankMoney/2;
            if($addingMoney >= 1)
            {
              $economy->addToPlayerBalance($player->getName(), $addingMoney);
              $this->api->reduceBankMoney($player, $addingMoney);
              $player->sendMessage("§aSuccessfully whitdrawed §e$addingMoney$ §afrom your bank account");
            }else{
              $player->sendMessage("§cError can't whitdraw §e$addingMoney$ §cfrom your bank account");
            }
            $player->removeCurrentWindow();
          }
         }
            switch ($result) {
                case 2;
                    $this->withdrawCustomForm($player);
            }
        });

        $form->setTitle("§l§cWITHDRAW");
        $form->setContent("§r§eBalance: §6$" . $playerBankMoney);
        $form->addButton("§l§bWITHDRAW ALL\n§l§d» §r§8Click To Withdraw", 1, "https://cdn-icons-png.flaticon.com/512/883/883887.png");
        $form->addButton("§l§bWITHDRAW HALF\n§l§d» §r§8Click To Withdraw", 1, "https://cdn-icons-png.flaticon.com/512/883/883887.png");
        $form->addButton("§l§bWITHDRAW CUSTOM\n§l§d» §r§8Click To Open", 1, "https://cdn-icons-png.flaticon.com/512/883/883887.png");
        $form->addButton("§cBACK", 0, "textures/ui/icon_import");
        $form->sendtoPlayer($player);
        return $form;
    }
    
  public function depositForm($player)
    {
        $playerName = $player->getName();
        $playerBankMoney = $this->api->getSource()->getPlayerFile($playerName)->getNested("Bank.Money");
        $form = new GBForm(function (Player $player, int $data = null){
            $result = $data;
            if ($result === null) {
                return true;
            }
            switch ($result) {
                case 0;
                    $economy = Server::getInstance()->getPluginManager()->getPlugin("BedrockEconomy")->getAPI();
          if($economy !== null)
          {
            $economy->getPlayerBalance($player->getName(),
              ClosureContext::create(
                function (?int $balance) use($player, $economy): void
                {
                  if(!is_null($balance))
                  {
                    $bankMoney = $this->api->getBankMoney($player);
                    $addingMoney = $balance;
                    if($addingMoney >= 1)
                    {
                      $economy->subtractFromPlayerBalance($player->getName(), $addingMoney);
                      $this->api->addBankMoney($player, $addingMoney);
                      $player->sendMessage("§aSuccessfully added §e$addingMoney$ §ato your bank account");
                    }else{
                      $player->sendMessage("§cError can't add §e$addingMoney$ §cto your bank account");
                    }
                  }else{
                    $player->sendMessage("§cError can't add §e$addingMoney$ §cto your bank account");
                  }
                },
              ));
            $player->removeCurrentWindow();
          }
          }
            switch ($result) {
                case 1;
                    $economy = Server::getInstance()->getPluginManager()->getPlugin("BedrockEconomy")->getAPI();
          if($economy !== null)
          {
            $economy->getPlayerBalance($player->getName(),
              ClosureContext::create(
                function (?int $balance) use($player, $economy): void
                {
                  if(!is_null($balance))
                  {
                    $bankMoney = $this->api->getBankMoney($player);
                    $addingMoney = ceil($balance / 2);
                    if($addingMoney >= 1)
                    {
                      $economy->subtractFromPlayerBalance($player->getName(), $addingMoney);
                      $this->api->addBankMoney($player, $addingMoney);
                      $player->sendMessage("§aSuccessfully added §e$addingMoney$ §ato your bank account");
                    }else{
                      $player->sendMessage("§cError can't add §e$addingMoney$ §cto your bank account");
                    }
                   }else{
                    $player->sendMessage("§cError can't add §e$addingMoney$ §cto your bank account");
                  }
                },
              ));
            $player->removeCurrentWindow();
          }
          }
            switch ($result) {
                case 2;
                    $this->depositCustomForm($player);
            }
        });

        $form->setTitle("§l§cDEPOSIT");
        $form->setContent("§r§eBalance: §6$" . $playerBankMoney);
        $form->addButton("§l§bDEPOSIT ALL\n§l§d» §r§8Click To Deposit", 1, "https://cdn-icons-png.flaticon.com/512/4825/4825116.png");
        $form->addButton("§l§bDEPOSIT HALF\n§l§d» §r§8Click To Deposit", 1, "https://cdn-icons-png.flaticon.com/512/4825/4825116.png");
        $form->addButton("§l§bDEPOSIT CUSTOM\n§l§d» §r§8Click To Deposit", 1, "https://cdn-icons-png.flaticon.com/512/4825/4825116.png");
        $form->addButton("§cBACK", 0, "textures/ui/icon_import");
        $form->sendtoPlayer($player);
        return $form;
    }
   
  public function loanForm($player)
    {
        $merit = $this->api->getLoanMerit($player);
        $loan = $this->api->getLoan($player);
        $time = $this->api->changeNumericFormat(($this->source->getInstance()->getPlayerFile($player)->getNested("Bank.MaxTime") - $this->source->getInstance()->getPlayerFile($player)->getNested("Bank.Time")), "time");
        $form = new GBForm(function (Player $player, int $data = null){
            $result = $data;
            if ($result === null) {
                return true;
            }
            switch ($result) {
                case 0;
            $playTime = $player->getFirstPlayed();
            $currentTime = time();
            $oneDayInSeconds = 86400; // 1 day in seconds

            if (($currentTime - $playTime) >= $oneDayInSeconds) {
                $this->AquireLoanMenu($sender);
            } else {
                $player->sendMessage("You need to play for 1 day before acquiring a loan.");
            }
            }
            switch ($result) {
            	case 1;
              $this->PayLoanMenu($player);
            }
        });

        $form->setTitle("§l§cLOAN");
        $form->setContent("§r§eInfo §r\n§r §aMerit§7: §e$merit §r\n§r §aCurrent Loan§7: §e$loan §r\n§r §aRemaining Time§7: §e$time §r");
        $form->addButton("§l§bAquire Loan\n§l§d» §r§8Click To Get Loan", 1, "https://cdn-icons-png.flaticon.com/512/4825/4825116.png");
        $form->addButton("§l§bPay Loan \n§l§d» §r§8Click To Pay Loan", 1, "https://cdn-icons-png.flaticon.com/512/4825/4825116.png");
        $form->addButton("§cBACK", 0, "textures/ui/icon_import");
        $form->sendtoPlayer($player);
        return $form;
    }

  public function depositCustomForm($player) {
    $form = new CustomForm(
      function (Player $player, $data)
      {
        if ($data === null)
        {
          $player->sendMessage("§cError please enter a valid value");
          return true;
        }
        $result = $data[0];
        if(is_numeric($result))
        {
          if($result >= 1)
          {
            $economy = Server::getInstance()->getPluginManager()->getPlugin("BedrockEconomy")->getAPI();
            $economy->getPlayerBalance($player->getName(),
              ClosureContext::create(
                function (?int $balance) use ($player, $result, $economy): void
                {
                  if(!is_null($balance))
                  {
                    if($balance >= $result)
                    {
                      $economy->subtractFromPlayerBalance($player->getName(), $result);
                      $this->api->addBankMoney($player, $result);
                      $player->sendMessage("§aSuccessfully added §e$result$ §ainto your bank account");
                    }else{
                      $player->sendMessage("§cError you don't have §e$result$ §cin your wallet");
                    }
                  }else{
                    $player->sendMessage("§cError you don't have §e$result$ §cin your wallet");
                  }
                },
              ));
          }
          return true;
        }
     });
   
   $form->setTitle("§3Deposit §bMenu");
   $form->addInput("Please Enter A Numeric Value");
   $form->sendToPlayer($player);
  }
  
  public function withdrawCustomForm($player) {
    $api = Server::getInstance()->getPluginManager()->getPlugin("FormAPI");
    $form = new CustomForm(function (Player $player, $data){
      if ($data === null)
      {
        $player->sendMessage("§cError please enter a valid value");
        return true;
      }
      $result = $data[0];
      if(is_numeric($result))
      {
        if($result >= 1)
        {
          $economy = Server::getInstance()->getPluginManager()->getPlugin("BedrockEconomy")->getAPI();
          $playerBankMoney = $this->api->getBankMoney($player);
          if($playerBankMoney >= $result)
          {
            $economy->addToPlayerBalance($player->getName(), $result);
            $this->api->reduceBankMoney($player, $result);
            $player->sendMessage("§aSuccessfully withdrew §e$result$ §afrom your bank account");
            return true;
          }else{
            $player->sendMessage("§cError you don't have §e$result$ §cin your bank account");
            return false;
          }
        }else{
          $player->sendMessage("§cError can't withdraw §e$result$ §cfrom your bank account");
          return false;
        }
      }
      return false;
   });
   
   $form->setTitle("§3Wihtdraw §bMenu");
   $form->addInput("Please Enter A Numeric Value");
   $form->sendToPlayer($player);
  }
  
  public function AquireLoanMenu($player) {
    $api = Server::getInstance()->getPluginManager()->getPlugin("FormAPI");
    $maxAquiring = $this->api->getLoanMerit($player) * 10000;
    $form = new CustomForm(function (Player $player, $data) use ($maxAquiring){
      if ($data === null)
      {
        $player->sendMessage("§cError please enter a valid value");
        return true;
      }
      $result1 = $data[1];
      $result2 = $data[2];
      if($result1 <= $maxAquiring)
      {
        if($result1 >= 1)
        {
          if($this->api->getLoanMerit($player) >= 50)
          {
            if($this->api->getLoan($player) === 0)
            {
              $this->api->addLoan($player, $result1);
              $array = array("1 Hour", "10 Hour", "1 day", "2 Day");
              if($array[$result2] === "1 Hour")
              {
                $this->api->setMaxLoanTime($player, 3600);
              }elseif($array[$result2] === "10 Hour")
              {
                $this->api->setMaxLoanTime($player, 36000);
              }elseif($array[$result2] === "1 Day")
              {
                $this->api->setMaxLoanTime($player, 86400);
              }elseif($array[$result2] === "2 Day")
              {
                $this->api->setMaxLoanTime($player, 172800);
              }
              $this->source->getInstance()->loanTask[$player->getName()] = $this->source->getInstance()->getScheduler()->scheduleRepeatingTask(new LoanTask($this->source, $player->getName(), 1), 20);
              $player->sendMessage("§aAquired §e$result1$ §aLoan");
              return true;
            }else{
              $player->sendMessage("§cYou have already aquired a loan");
              return false;
            }
          }else{
            $player->sendMessage("§cYour merit is less than 50");
            return false;
          }
        }else{
          $player->sendMessage("§cYou can't aquire §e$result1$");
          return false;
        }
      }else{
        $player->sendMessage("§cYou can't aquire §e$result1$");
        return false;
      }
      return false;
   });
   
   $form->setTitle("§bAquire §3Loan");
   $form->addLabel("§bCan Aquire§7: §3$maxAquiring\n§l§cWARNING§7: §r§anot paying back Loans is dangerous. if you acquire a loan and didn't pay it back under the time you chose, §cYour All Money will be RESETTED instantly. §aOut Server is not Responsible for any kind of damage caused by Loans. thanks. §7(§eTIP: Check §b/guide§7)");
   $form->addSlider("Please Select A Value", 0, $maxAquiring);
   $form->addDropdown("Select Time", array("1 Hour", "10 Hour", "1 day", "2 Day"));
   $form->sendToPlayer($player);
  }
  
  public function PayLoanMenu($player) {
    $api = Server::getInstance()->getPluginManager()->getPlugin("FormAPI");
    $loan = $this->api->getLoan($player);
    $form = new CustomForm(function (Player $player, $data) use($loan){
      if ($data === null)
      {
        $player->sendMessage("§cError please enter a valid value");
        return true;
      }
      $result = $data[1];
      if(is_numeric($result))
      {
        if($result >= 1)
        {
          if($loan >= 1)
          {
            if($result <= $loan)
            {
              $economy = Server::getInstance()->getPluginManager()->getPlugin("BedrockEconomy")->getAPI();
              $economy->getPlayerBalance($player->getName(),
                ClosureContext::create(
                  function (?int $balance) use($player, $result, ): void
                  {
                    if(!is_null($balance))
                    {
                      if($balance >= $result)
                      {
                        if(array_key_exists($player->getName(), $this->source->getInstance()->loanTask))
                        {
                          $playerName = $player->getName();
                          $this->source->getInstance()->loanTask[$playerName]->cancel();
                          $this->api->reduceLoan($player, $result);
                          if($this->api->getLoan($player) >= 1)
                          {
                            $this->source->getInstance()->loanTask[$playerName] = $this->source->getInstance()->getScheduler()->scheduleRepeatingTask(new LoanTask($this->source, $player->getName(), 1), 20);
                          }
                    l      $player->sendMessage("§aSuccessfully Payed A Total Of §e$result$");
                        }else{
                          $player->sendMessage("§cyou have not taken loan");
                        }
                      }else{
                        $player->sendMessage("§cError You Don't Have §e$result$ §cIn Your Wallet");
                      }
                    }else{
                      $player->sendMessage("§cError You Don't Have §e$result$ §cIn Your Wallet");
                    }
                  },
                ));
              return true;
            }else{
              $player->sendMessage("§cError Unable To Pay §e$result$");
              return false;
            }
          }else{
            $player->sendMessage("§cError You Haven't Aquired Loan");
            return false;
          }
        }else{
          $player->sendMessage("§cError Can't Pay §e$result$");
          return false;
        }                
      }else{
        $player->sendMessage("§cError Please Enter A Number");
        return false;
      }
      return false;
   });
   
   $form->setTitle("§bPay §3Loan");
   $form->addLabel("§eTotal Loan§7: §b$loan");
   $form->addInput("Please Enter A Numric Value");
   $form->sendToPlayer($player);
  }
  public function transferCustomForm($player)
    {

        $list = [];
        foreach ($player->getServer()->getOnlinePlayers() as $players){
            if ($players->getName() !== $player->getName()) {
                $list[] = $players->getName();
            }
        }
        $this->playerList[$player->getName()] = $list;
        $playerName = $player->getName();
        $playerBankMoney = $this->api->getSource()->getPlayerFile($playerName)->getNested("Bank.Money");
    
        $form = new CustomForm(function (Player $player, $data) {
            $result = $data;
            if ($result === null) {
                return true;
            }

            if (!isset($this->playerList[$player->getName()][$data[1]])){
                $player->sendMessage("§aYou have to choose the right player");
                return true;
            }

            $index = $data[1];
            $playerName = $this->playerList[$player->getName()][$index];
            $Name = $player->getName();
            $playerBankMoney = $this->api->getSource()->getPlayerFile($Name)->getNested("Bank.Money");
            $otherPlayerBankMoney = $this->api->getSource()->getPlayerFile($Name)->getNested("Bank.Money");
            if ($playerBankMoney == 0){
                $player->sendMessage("§aYou don't have money in the bank");
                return true;
            }
            if ($playerBankMoney < $data[2]){
                $player->sendMessage("§aYou don't have enough money to transfer as big Rp" . $data[2]);
                return true;
            }
            if (!is_numeric($data[2])){
                $player->sendMessage("§aEnter a number");
                return true;
            }
            if ($data[2] <= 0){
                $player->sendMessage("§eYou must send a minimum. 1");
                return true;
            }
            $player->sendMessage("§aSuccessful transfer Rp" . $data[2] . " into " . $playerName . "'s Bank");
            $Name = $player->getName();
            if ($this->api->getSource()->getPlayerFile($Name)) {
                $otherPlayer = $this->api->getSource()->getPlayerFile($Name);
                $otherPlayer->sendMessage("§a" . $player->getName() . " transfer Rp" . $data[2] . " to Bank you");
            }
  
            $playerBankMoney->set("Bank.Money", $playerBankMoney - $data[2]);
            $otherPlayerBankMoney->set("Bank.Money", $otherPlayerBankMoney + $data[2]);
            $playerBankMoney->save();
            $otherPlayerBankMoney->save();
            });


        $form->setTitle("§lTransfer");
        $form->addLabel("Money in bank: " . $playerBankMoney);
        $form->addDropdown("Choose player", $this->playerList[$player->getName()]);
        $form->addInput("§rEnter the amount max", "100000");
        $form->sendtoPlayer($player);
        return $form;
    }
}￼Enter      }else{
        $player->sendMessage("§cError Please Enter A Number");
        return false;
      }
      return false;
   });
   
   $form->setTitle("§bPay §3Loan");
   $form->addLabel("§eTotal Loan§7: §b$loan");
   $form->addInput("Please Enter A Numric Value");
   $form->sendToPlayer($player);
  }
  public function transferCustomForm($player)
    {

        $list = [];
        foreach ($player->getServer()->getOnlinePlayers() as $players){
            if ($players->getName() !== $player->getName()) {
                $list[] = $players->getName();
            }
        }
        $this->playerList[$player->getName()] = $list;
        $playerName = $player->getName();
        $playerBankMoney = $this->api->getSource()->getPlayerFile($playerName)->getNested("Bank.Money");
    
        $form = new CustomForm(function (Player $player, $data) {
            $result = $data;
            if ($result === null) {
                return true;
            }

            if (!isset($this->playerList[$player->getName()][$data[1]])){
                $player->sendMessage("§aYou have to choose the right player");
                return true;
            }

            $index = $data[1];
            $playerName = $this->playerList[$player->getName()][$index];
            $Name = $player->getName();
            $playerBankMoney = $this->api->getSource()->getPlayerFile($Name)->getNested("Bank.Money");
            $otherPlayerBankMoney = $this->api->getSource()->getPlayerFile($Name)->getNested("Bank.Money");
            if ($playerBankMoney == 0){
                $player->sendMessage("§aYou don't have money in the bank");
                return true;
            }
            if ($playerBankMoney < $data[2]){
                $player->sendMessage("§aYou don't have enough money to transfer as big Rp" . $data[2]);
                return true;
            }
            if (!is_numeric($data[2])){
                $player->sendMessage("§aEnter a number");
                return true;
            }
            if ($data[2] <= 0){
                $player->sendMessage("§eYou must send a minimum. 1");
                return true;
            }
            $player->sendMessage("§aSuccessful transfer Rp" . $data[2] . " into " . $playerName . "'s Bank");
            $Name = $player->getName();
            if ($this->api->getSource()->getPlayerFile($Name)) {
                $otherPlayer = $this->api->getSource()->getPlayerFile($Name);
                $otherPlayer->sendMessage("§a" . $player->getName() . " transfer Rp" . $data[2] . " to Bank you");
            }
  
            $playerBankMoney->set("Bank.Money", $playerBankMoney - $data[2]);
            $otherPlayerBankMoney->set("Bank.Money", $otherPlayerBankMoney + $data[2]);
            $playerBankMoney->save();
            $otherPlayerBankMoney->save();
            });


        $form->setTitle("§lTransfer");
        $form->addLabel("Money in bank: " . $playerBankMoney);
        $form->addDropdown("Choose player", $this->playerList[$player->getName()]);
        $form->addInput("§rEnter the amount max", "100000");
        $form->sendtoPlayer($player);
        return $form;
    }
}
