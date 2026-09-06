<?php

namespace Garak\Rummy\Test;

use Garak\Card\Card;
use Garak\Rummy\CardSorter;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CardSorterTest extends TestCase
{
    #[Test]
    public function sortBySuitThenValueJokersLast(): void
    {
        $cards = \array_map(static fn (string $rs): Card => Card::fromRankSuit($rs), ['wb', 'Kh', 'Ah', '2c', 'Ac', 'Td', 'wr', '3s']);
        CardSorter::sort($cards);
        self::assertSame('Ac,2c,Td,Ah,Kh,3s,wb,wr', \implode(',', $cards));
    }
}
