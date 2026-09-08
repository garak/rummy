<?php

namespace Garak\Rummy\Test;

use Garak\Card\Card;
use Garak\Rummy\Exception\InvalidMeldException;
use Garak\Rummy\Meld;
use Garak\Rummy\Run;
use Garak\Rummy\Set;
use PHPUnit\Framework\Attributes\DataProvider;
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
    public function fromCardsInAnyOrderSortsARun(): void
    {
        self::assertSame('Td,Jd,Qd', (string) Meld::createFromString('Jd,Td,Qd', anyOrder: true));
        self::assertSame('Td,wb,Qd', (string) Meld::createFromString('Qd,Td,wb', anyOrder: true));
        self::assertSame('Ad,2d,wb', (string) Meld::createFromString('wb,Ad,2d', anyOrder: true));
        self::assertInstanceOf(Run::class, Meld::createFromString('Jd,Td,Qd', anyOrder: true));
    }

    #[Test]
    public function fromCardsInAnyOrderKeepsAValidMeldAsGiven(): void
    {
        self::assertSame('wb,Td,Jd', (string) Meld::createFromString('wb,Td,Jd', anyOrder: true));
        self::assertSame('Ks,Kh,Kd', (string) Meld::createFromString('Ks,Kh,Kd', anyOrder: true));
    }

    #[Test]
    #[DataProvider('anyOrderInvalidProvider')]
    public function fromCardsInAnyOrderRefusesWhatIsNotARunEvenWhenSorted(string $cards, string $message): void
    {
        $this->expectException(InvalidMeldException::class);
        $this->expectExceptionMessage($message);
        Meld::createFromString($cards, anyOrder: true);
    }

    /** @return iterable<string, array{string, string}> */
    public static function anyOrderInvalidProvider(): iterable
    {
        yield 'not consecutive' => ['Jd,Td,Ad', 'Cards in a run must be consecutive, got Jd,Td,Ad.'];
        yield 'gap wider than the jokers' => ['Kd,Td,wb', 'Cards in a run must be consecutive, got Kd,Td,wb.'];
        yield 'different suits' => ['Jd,Th,Qd', 'All cards in a run must share the suit, got Jd,Th,Qd.'];
        yield 'duplicate' => ['Td,Jd,Td', 'Cards in a run must be consecutive, got Td,Jd,Td.'];
        yield 'too short' => ['Jd,Td', 'A meld needs at least 3 cards, 2 given.'];
        yield 'only jokers' => ['wb,wr,wb', 'A meld cannot be made of jokers only.'];
        yield 'wrong set' => ['5s,5h,5s', 'Suits in a set must be different, got 5s,5h,5s.'];
    }

    #[Test]
    public function fromCardsIsStrictByDefault(): void
    {
        $this->expectException(InvalidMeldException::class);
        Meld::createFromString('Jd,Td,Qd');
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
