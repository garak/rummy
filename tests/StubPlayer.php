<?php

namespace Garak\Rummy\Test;

use Garak\Rummy\Player;

/**
 * @extends Player<StubPlayer>
 */
final class StubPlayer extends Player
{
    public function isEqual(Player $player): bool
    {
        return $this->name === $player->getName();
    }
}
