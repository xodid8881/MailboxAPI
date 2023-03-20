<?php
declare(strict_types=1);

namespace MailboxAPI\Commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;
use pocketmine\player\Player;
use MailboxAPI\MailboxAPI;

class MainCommand extends Command
{
  
  protected $plugin;
  private $chat;
  
  public function __construct(MailboxAPI $plugin)
  {
    $this->plugin = $plugin;
    parent::__construct('메일함', '메일함 명령어.', '/메일함');
  }
  
  public function execute(CommandSender $sender, string $commandLabel, array $args)
  {
    $tag = "§l§b[ MailBox ] §r§7";
    $name = $sender->getName ();
    if (! isset ( $this->chat [$name] )) {
      $this->plugin->pagedb [strtolower ( $name )] ["페이지"] = 0;
      unset($this->plugin->listdb [strtolower($name)]);
      $this->plugin->save();
      $this->plugin->PlayerBoxEvent ($sender);
      $this->chat [$name] = date("YmdHis",strtotime ("+3 seconds"));
      return true;
    }
    if (date("YmdHis") - $this->chat [$name] < 3) {
      $sender->sendMessage ( $tag . "이용 쿨타임이 지나지 않아 불가능합니다." );
      return true;
    } else {
      $this->plugin->pagedb [strtolower ( $name )] ["페이지"] = 0;
      unset($this->plugin->listdb [strtolower($name)]);
      $this->plugin->save();
      $this->plugin->PlayerBoxEvent ($sender);
      $this->chat [$name] = date("YmdHis",strtotime ("+3 seconds"));
      return true;
    }
    return true;
  }
}
