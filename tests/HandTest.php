<?php

namespace Garak\Rummy\Test;

use Garak\Card\Card;
use Garak\Rummy\Hand;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class HandTest extends TestCase
{
    #[Test]
    public function keepsOrderButSortsForDisplay(): void
    {
        $hand = Hand::createFromString('Kh,Ah,2c,wb');
        self::assertSame('Kh,Ah,2c,wb', (string) $hand);
        self::assertSame('2♣ A♥ K♥ wb', $hand->toText());
    }

    #[Test]
    public function penaltyOfCardsLeftInHand(): void
    {
        $hand = Hand::createFromString('Kh,Ah,2c,wb');
        self::assertSame(46, $hand->getPenalty());
        self::assertSame(66, $hand->getPenalty(50));
        self::assertSame(0, (new Hand([]))->getPenalty());
    }

    #[Test]
    public function addAndPlayKeepBacks(): void
    {
        $hand = Hand::createFromString('Khr,Khb');
        $hand = $hand->add(Card::fromRankSuit('Ahr'));
        self::assertCount(3, $hand);
        $hand = $hand->play(Card::fromRankSuit('Khb'));
        self::assertSame('Khr,Ahr', $hand->toString(true));
        self::assertTrue($hand->has(Card::fromRankSuit('Khr')));
        self::assertFalse($hand->has(Card::fromRankSuit('Khb')));
    }

    #[Test]
    public function customSortingAndChecking(): void
    {
        $checked = false;
        $hand = new Hand(
            [Card::fromRankSuit('2c'), Card::fromRankSuit('Ac')],
            checking: static function () use (&$checked): void { $checked = true; },
            sorting: static function (): void {},
        );
        self::assertTrue($checked);
        self::assertSame('2♣ A♣', $hand->toText());
    }
}
