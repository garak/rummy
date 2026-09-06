<?php

namespace Garak\Rummy\Test;

use Garak\Card\Card;
use Garak\Rummy\Exception\InvalidMeldException;
use Garak\Rummy\Meld;
use Garak\Rummy\Run;
use Garak\Rummy\Set;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class MeldTest extends TestCase
{
    #[Test]
    public function fromCardsGuessesType(): void
    {
        self::assertInstanceOf(Set::class, Meld::createFromString('Kh,Kd,Ks'));
        self::assertInstanceOf(Set::class, Meld::createFromString('Kh,wb,Ks,Kc'));
        self::assertInstanceOf(Run::class, Meld::createFromString('Ah,2h,3h'));
        self::assertInstanceOf(Run::class, Meld::createFromString('wb,2h,3h,wr'));
        self::assertInstanceOf(Run::class, Meld::createFromString('Ah,2h,3h,4h,5h'));
    }

    #[Test]
    public function ambiguousDefaultsToSet(): void
    {
        self::assertInstanceOf(Set::class, Meld::createFromString('wb,5h,wr'));
    }

    #[Test]
    public function fromCardsRejectsInvalid(): void
    {
        $this->expectException(InvalidMeldException::class);
        Meld::createFromString('Kh,Kd,Qs');
    }

    #[Test]
    public function cardsAndJokers(): void
    {
        $meld = Meld::createFromString('Kh,wb,Ks');
        self::assertCount(3, $meld);
        self::assertTrue($meld->hasJoker());
        self::assertSame('Kh,Ks', \implode(',', $meld->getRegularCards()));
        self::assertSame('Kh,wb,Ks', \implode(',', $meld->getCards()));
        self::assertFalse(Meld::createFromString('Kh,Kd,Ks')->hasJoker());
    }

    #[Test]
    public function hasSameCards(): void
    {
        self::assertTrue(Meld::createFromString('Kh,Kd,Ks')->hasSameCards(Meld::createFromString('Ks,Kh,Kd')));
        self::assertFalse(Meld::createFromString('Kh,Kd,Ks')->hasSameCards(Meld::createFromString('Kh,Kd,Kc')));
        self::assertFalse(Meld::createFromString('Khr,Kd,Ks')->hasSameCards(Meld::createFromString('Khb,Kd,Ks')));
        self::assertFalse(Meld::createFromString('Kh,Kd,Ks')->hasSameCards(Meld::createFromString('Kh,Kd,Ks,Kc')));
    }

    #[Test]
    public function toStringWithBack(): void
    {
        $meld = new Run([Card::fromRankSuit('Ahr'), Card::fromRankSuit('2hb'), Card::fromRankSuit('3h')]);
        self::assertSame('Ah,2h,3h', (string) $meld);
        self::assertSame('Ahr,2hb,3h', $meld->toString(true));
    }
}
