<?php

namespace Garak\Rummy\Test;

use Garak\Card\Card;
use Garak\Card\Suit;
use Garak\Rummy\Exception\InvalidMeldException;
use Garak\Rummy\Run;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RunTest extends TestCase
{
    /** @param list<int> $values */
    #[Test]
    #[DataProvider('validProvider')]
    public function validRuns(string $cards, int $points, array $values): void
    {
        $run = new Run(self::cards($cards));
        self::assertSame($points, $run->getPoints());
        self::assertSame($values, $run->getValues());
        self::assertSame($cards, $run->toString(true));
    }

    /** @return iterable<string, array{string, int, list<int>}> */
    public static function validProvider(): iterable
    {
        yield 'low run' => ['Ah,2h,3h', 6, [1, 2, 3]];
        yield 'high run' => ['Js,Qs,Ks', 36, [11, 12, 13]];
        yield 'joker in the middle' => ['5d,wb,7d', 18, [5, 6, 7]];
        yield 'joker at the start' => ['wb,5d,6d', 15, [4, 5, 6]];
        yield 'joker at the end' => ['5d,6d,wr', 18, [5, 6, 7]];
        yield 'two jokers' => ['wb,wr,3c,4c', 10, [1, 2, 3, 4]];
        yield 'jokers around a single card' => ['wb,Tc,wr', 30, [9, 10, 11]];
        yield 'full suit' => ['Ac,2c,3c,4c,5c,6c,7c,8c,9c,Tc,Jc,Qc,Kc', 91, \range(1, 13)];
        yield 'backs are irrelevant' => ['Ahr,2hb,3hr', 6, [1, 2, 3]];
    }

    #[Test]
    #[DataProvider('sortProvider')]
    public function sort(string $cards, string $expected): void
    {
        self::assertSame($expected, \implode(',', \array_map(static fn (Card $card): string => $card->toString(true), Run::sort(self::cards($cards)))));
    }

    /** @return iterable<string, array{string, string}> */
    public static function sortProvider(): iterable
    {
        yield 'in order' => ['Td,Jd,Qd', 'Td,Jd,Qd'];
        yield 'scrambled' => ['Jd,Td,Qd', 'Td,Jd,Qd'];
        yield 'descending' => ['Kd,Qd,Jd,Td', 'Td,Jd,Qd,Kd'];
        yield 'ace first' => ['3d,Ad,2d', 'Ad,2d,3d'];
        yield 'joker filling the gap' => ['Qd,Td,wb', 'Td,wb,Qd'];
        yield 'two jokers filling a wider gap' => ['Kd,wr,Td,wb', 'Td,wr,wb,Kd'];
        yield 'spare joker after the last card' => ['Jd,Td,wb', 'Td,Jd,wb'];
        yield 'spare joker before the first card when at the king' => ['Kd,Qd,wb', 'wb,Qd,Kd'];
        yield 'spare jokers before the first card keep their order' => ['Kd,Qd,wb,wr', 'wb,wr,Qd,Kd'];
        yield 'spare jokers split around the run keep their order' => ['Qd,wbr,wrr,wbb', 'wrr,wbb,Qd,wbr'];
        yield 'joker below the ace moved after the run' => ['wb,Ad,2d', 'Ad,2d,wb'];
        yield 'joker above the king moved before the run' => ['Qd,Kd,wb', 'wb,Qd,Kd'];
        yield 'gap wider than the jokers is left open' => ['Kd,Td,wb', 'Td,wb,Kd'];
        yield 'only jokers are left alone' => ['wb,wr', 'wb,wr'];
        yield 'backs are kept' => ['Jdr,Tdb,Qdr', 'Tdb,Jdr,Qdr'];
    }

    #[Test]
    #[DataProvider('invalidProvider')]
    public function invalidRuns(string $cards): void
    {
        $this->expectException(InvalidMeldException::class);
        new Run(self::cards($cards));
    }

    /** @return iterable<string, array{string}> */
    public static function invalidProvider(): iterable
    {
        yield 'too short' => ['Ah,2h'];
        yield 'too long' => ['Ac,2c,3c,4c,5c,6c,7c,8c,9c,Tc,Jc,Qc,Kc,wb'];
        yield 'different suits' => ['Ah,2h,3c'];
        yield 'not consecutive' => ['Ah,2h,4h'];
        yield 'descending' => ['3h,2h,Ah'];
        yield 'duplicate' => ['2h,2h,3h'];
        yield 'joker below the ace' => ['wb,Ah,2h'];
        yield 'joker above the king' => ['Qh,Kh,wb'];
        yield 'wrapping around' => ['Qh,Kh,Ah'];
        yield 'only jokers' => ['wb,wr,wb'];
        yield 'a set is not a run' => ['Kh,Kd,Ks'];
    }

    #[Test]
    public function suitOfTheRun(): void
    {
        self::assertSame(Suit::Diamonds, (new Run(self::cards('wb,5d,6d')))->getSuit());
    }

    /** @return list<Card> */
    private static function cards(string $cards): array
    {
        return \array_map(static fn (string $rs): Card => Card::fromRankSuit($rs), \explode(',', $cards));
    }
}
