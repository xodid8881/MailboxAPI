<?php
declare(strict_types=1);

namespace MailboxAPI\Commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;
use pocketmine\player\Player;
use MailboxAPI\MailboxAPI;

class OPCommand extends Command
{

  protected $plugin;

  public function __construct(MailboxAPI $plugin)
  {
    $this->plugin = $plugin;
    parent::__construct('메일선물', '메일선물 명령어.', '/메일선물');
  }

  public function execute(CommandSender $sender, string $commandLabel, array $args)
  {
    $encode = [
      'type' => 'form',
      'title' => '§l§b[ Mailbox ]',
      'content' => '§r§7버튼을 눌러주세요.',
      'buttons' => [
        [
          'text' => "§r§7랜덤선물\n- 랜덤으로 선물을 지급합니다."
        ],
        [
          'text' => "§r§7플레이어지정\n- 플레이어를 지정해 선물을 지급합니다."
        ],
        [
          'text' => "§r§7모든플레이어\n- 모든 플레이어에게 선물을 지급합니다."
        ]
      ]
    ];
    $packet = new ModalFormRequestPacket ();
    $packet->formId = 1752962;
    $packet->formData = json_encode($encode);
    $sender->getNetworkSession()->sendDataPacket($packet);
  }
}
