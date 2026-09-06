<?php

namespace Garak\Rummy\Test;

use Garak\Card\Card;
use Garak\Rummy\Exception\InvalidMeldException;
use Garak\Rummy\Set;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class SetTest extends TestCase
{
    #[Test]
    #[DataProvider('validProvider')]
    public function validSets(string $cards, int $points): void
    {
        $set = new Set(self::cards($cards));
        self::assertSame($points, $set->getPoints());
        self::assertSame($cards, $set->toString(true));
    }

    /** @return iterable<string, array{string, int}> */
    public static function validProvider(): iterable
    {
        yield 'three kings' => ['Kh,Kd,Ks', 39];
        yield 'four aces' => ['Ah,Ad,As,Ac', 4];
        yield 'joker in the middle' => ['5h,wb,5s', 15];
        yield 'two jokers' => ['wb,wr,9c', 27];
        yield 'four with joker' => ['7h,7d,7s,wb', 28];
        yield 'same face from two decks is fine in different suits' => ['Thr,Tdb,Tsr', 30];
    }

    #[Test]
    #[DataProvider('invalidProvider')]
    public function invalidSets(string $cards): void
    {
        $this->expectException(InvalidMeldException::class);
        new Set(self::cards($cards));
    }

    /** @return iterable<string, array{string}> */
    public static function invalidProvider(): iterable
    {
        yield 'too short' => ['Kh,Kd'];
        yield 'too long' => ['Kh,Kd,Ks,Kc,wb'];
        yield 'different ranks' => ['Kh,Kd,Qs'];
        yield 'same suit twice' => ['Kh,Kh,Ks'];
        yield 'same suit twice from two decks' => ['Khr,Khb,Ks'];
        yield 'only jokers' => ['wb,wr,wb'];
        yield 'a run is not a set' => ['5h,6h,7h'];
    }

    #[Test]
    public function valueOfEachCard(): void
    {
        self::assertSame(12, (new Set(self::cards('wb,Qd,Qs')))->getValue());
    }

    /** @return list<Card> */
    private static function cards(string $cards): array
    {
        return \array_map(static fn (string $rs): Card => Card::fromRankSuit($rs), \explode(',', $cards));
    }
}
