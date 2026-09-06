<?php

namespace Garak\Rummy\Test;

use Garak\Card\Card;
use Garak\Rummy\Exception\IllegalMoveException;
use Garak\Rummy\Exception\NotYourTurnException;
use Garak\Rummy\Game;
use Garak\Rummy\GameStatus;
use Garak\Rummy\Meld;
use Garak\Rummy\Rules;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class GameTest extends TestCase
{
    #[Test]
    public function joinPlayers(): void
    {
        $game = new Game();
        $alice = new StubPlayer('Alice');
        $bob = new StubPlayer('Bob');
        self::assertFalse($game->hasPlayer($alice));
        $game->join($alice);
        self::assertTrue($game->hasPlayer($alice));
        self::assertTrue($alice->isPlaying($game));
        self::assertFalse($bob->isPlaying($game));
        self::assertCount(1, $game->getPlayers());
        self::assertSame(GameStatus::Waiting, $game->getStatus());
        self::assertFalse($game->hasOpened($alice));
        self::assertSame(14, $game->getRules()->handSize);
    }

    #[Test]
    public function cannotJoinTwice(): void
    {
        $game = new Game();
        $game->join(new StubPlayer('Alice'));
        $this->expectException(\InvalidArgumentException::class);
        $game->join(new StubPlayer('Alice'));
    }

    #[Test]
    public function cannotJoinAfterDeal(): void
    {
        $game = self::game();
        $this->expectException(IllegalMoveException::class);
        $game->join(new StubPlayer('Carol'));
    }

    #[Test]
    public function dealNeedsTwoPlayers(): void
    {
        $game = new Game();
        $game->join(new StubPlayer('Alice'));
        $this->expectException(IllegalMoveException::class);
        $game->deal();
    }

    #[Test]
    public function dealNeedsEnoughCards(): void
    {
        $game = new Game(new Rules(handSize: 4));
        $game->join(new StubPlayer('Alice'));
        $game->join(new StubPlayer('Bob'));
        $this->expectException(IllegalMoveException::class);
        $game->deal(self::cards('Ah,2h,3h,4h,5h,6h,7h'));
    }

    #[Test]
    public function cannotDealTwice(): void
    {
        $game = self::game();
        $this->expectException(IllegalMoveException::class);
        $game->deal();
    }

    #[Test]
    public function dealWithShuffledDeck(): void
    {
        $game = new Game();
        $alice = new StubPlayer('Alice');
        $bob = new StubPlayer('Bob');
        $carol = new StubPlayer('Carol');
        $game->join($alice);
        $game->join($bob);
        $game->join($carol);
        $game->deal();
        self::assertSame(GameStatus::Playing, $game->getStatus());
        self::assertCount(14, $game->getHand($alice));
        self::assertCount(14, $game->getHand($bob));
        self::assertCount(14, $game->getHand($carol));
        self::assertSame(106 - 42, $game->getStockCount());
        self::assertTrue($game->getTable()->isEmpty());
        self::assertSame($alice, $game->getCurrentPlayer());
        self::assertNull($game->getWinner());
        self::assertFalse($game->isOver());
    }

    #[Test]
    public function noCurrentPlayerBeforeDeal(): void
    {
        $game = new Game();
        $this->expectException(IllegalMoveException::class);
        $game->getCurrentPlayer();
    }

    #[Test]
    public function noHandBeforeDeal(): void
    {
        $game = new Game();
        $alice = new StubPlayer('Alice');
        $game->join($alice);
        $this->expectException(IllegalMoveException::class);
        $game->getHand($alice);
    }

    #[Test]
    public function unknownPlayer(): void
    {
        $game = self::game();
        $this->expectException(\InvalidArgumentException::class);
        $game->getHand(new StubPlayer('Carol'));
    }

    #[Test]
    public function turnsRotate(): void
    {
        $game = new Game(new Rules(decks: 1, jokers: 0, handSize: 2));
        $alice = new StubPlayer('Alice');
        $bob = new StubPlayer('Bob');
        $carol = new StubPlayer('Carol');
        $game->join($alice);
        $game->join($bob);
        $game->join($carol);
        $game->deal(self::deck('2c,3c,4c,5c,6c,7c,8c,9c,Tc', new Rules(decks: 1, jokers: 0)));
        self::assertSame($alice, $game->getCurrentPlayer());
        self::assertSame('8c', (string) $game->draw($alice));
        self::assertSame($bob, $game->getCurrentPlayer());
        self::assertSame('9c', (string) $game->draw($bob));
        self::assertSame($carol, $game->getCurrentPlayer());
        self::assertSame('Tc', (string) $game->draw($carol));
        self::assertSame($alice, $game->getCurrentPlayer());
        self::assertSame('2c,3c,8c', (string) $game->getHand($alice));
        self::assertSame(52 - 9, $game->getStockCount());
    }

    #[Test]
    public function fullGame(): void
    {
        // Alice: Kh,Kd,Ks,3h,4h - Bob: 5h,6h,7h,8h,3c - stock: 9h, wb, ...
        $rules = new Rules(decks: 1, jokers: 1, handSize: 5);
        $game = new Game($rules);
        $alice = new StubPlayer('Alice');
        $bob = new StubPlayer('Bob');
        $game->join($alice);
        $game->join($bob);
        $game->deal(self::deck('Kh,Kd,Ks,3h,4h,5h,6h,7h,8h,3c,9h,wb', $rules));
        self::assertSame('Kh,Kd,Ks,3h,4h', (string) $game->getHand($alice));
        self::assertSame('5h,6h,7h,8h,3c', (string) $game->getHand($bob));
        self::assertSame(43, $game->getStockCount());

        // not Bob's turn
        try {
            $game->play($bob, self::melds('5h,6h,7h'));
            self::fail('Bob should not be allowed to play.');
        } catch (NotYourTurnException) {
        }

        // Alice opens with three kings
        $game->play($alice, self::melds('Kh,Kd,Ks'));
        self::assertSame('3h,4h', (string) $game->getHand($alice));
        self::assertTrue($game->hasOpened($alice));
        self::assertSame('Kh,Kd,Ks', (string) $game->getTable());
        self::assertSame($bob, $game->getCurrentPlayer());

        // Bob cannot open with 26 points
        try {
            $game->play($bob, self::melds('Kh,Kd,Ks;5h,6h,7h,8h'));
            self::fail('Bob should not be allowed to open with 26 points.');
        } catch (IllegalMoveException $e) {
            self::assertSame('Opening melds are worth 26 points, 30 needed.', $e->getMessage());
        }
        self::assertSame('9h', (string) $game->draw($bob));
        self::assertFalse($game->hasOpened($bob));
        self::assertSame($alice, $game->getCurrentPlayer());

        // Alice tries a few illegal layouts
        $illegal = [
            'Kh,Kd,Ks' => 'At least one card from the hand must be played, otherwise draw.',
            '' => 'Cards cannot leave the table: Kh,Kd,Ks.',
            'Kh,Kd,Ks;Ah,2h,3h' => 'Cards not in hand: Ah,2h.',
            'Kh,Kd,Ks,wb' => 'Cards not in hand: wb.',
        ];
        foreach ($illegal as $layout => $message) {
            try {
                $game->play($alice, self::melds($layout));
                self::fail(\sprintf('Layout "%s" should be illegal.', $layout));
            } catch (IllegalMoveException $e) {
                self::assertSame($message, $e->getMessage());
            }
        }
        self::assertSame('Kh,Kd,Ks', (string) $game->getTable());
        self::assertSame('wb', (string) $game->draw($alice));

        // Bob opens with a 35 points run
        $game->play($bob, self::melds('Kh,Kd,Ks;5h,6h,7h,8h,9h'));
        self::assertTrue($game->hasOpened($bob));
        self::assertSame('3c', (string) $game->getHand($bob));
        self::assertSame($alice, $game->getCurrentPlayer());

        // Alice rearranges the table and goes out
        $game->play($alice, self::melds('Kh,Kd,Ks,wb;3h,4h,5h,6h,7h,8h,9h'));
        self::assertTrue($game->getHand($alice)->isEmpty());
        self::assertTrue($game->isOver());
        self::assertSame(GameStatus::Finished, $game->getStatus());
        self::assertSame($alice, $game->getWinner());
        self::assertSame(3, $game->getScore($alice));
        self::assertSame(-3, $game->getScore($bob));

        $this->expectException(IllegalMoveException::class);
        $game->draw($bob);
    }

    #[Test]
    public function stalemateWhenEveryonePasses(): void
    {
        // Alice: Th,Jh,Qh,Ac - Bob: 7h,8h,9h,Kc - stock: 2c
        $rules = new Rules(decks: 1, jokers: 0, handSize: 4);
        $game = new Game($rules);
        $alice = new StubPlayer('Alice');
        $bob = new StubPlayer('Bob');
        $game->join($alice);
        $game->join($bob);
        $game->deal(self::cards('Th,Jh,Qh,Ac,7h,8h,9h,Kc,2c'));
        self::assertSame(1, $game->getStockCount());

        try {
            $game->pass($alice);
            self::fail('Passing with a non-empty stock should be illegal.');
        } catch (IllegalMoveException $e) {
            self::assertSame('Cannot pass while the stock is not empty: play or draw.', $e->getMessage());
        }

        $game->play($alice, self::melds('Th,Jh,Qh'));

        // Bob cannot rearrange before opening, even if the result is worth enough
        try {
            $game->play($bob, self::melds('7h,8h,9h,Th,Jh,Qh'));
            self::fail('Rearranging before opening should be illegal.');
        } catch (IllegalMoveException $e) {
            self::assertSame('The table cannot be rearranged before opening.', $e->getMessage());
        }
        self::assertSame('2c', (string) $game->draw($bob));
        self::assertSame(0, $game->getStockCount());

        try {
            $game->draw($alice);
            self::fail('Drawing from an empty stock should be illegal.');
        } catch (IllegalMoveException $e) {
            self::assertSame('The stock is empty: play or pass.', $e->getMessage());
        }

        $game->pass($alice);
        self::assertFalse($game->isOver());
        $game->pass($bob);
        self::assertTrue($game->isOver());
        // Alice holds 1 point, Bob 39: Bob loses 38, Alice wins 38
        self::assertSame($alice, $game->getWinner());
        self::assertSame(38, $game->getScore($alice));
        self::assertSame(-38, $game->getScore($bob));
    }

    #[Test]
    public function passCounterResetsOnPlay(): void
    {
        // Alice: Th,Jh,Qh,Ac - Bob: 7h,8h,9h,2d - Carol: Kd,Ks,Kc,Ad - empty stock
        $rules = new Rules(decks: 1, jokers: 0, handSize: 4);
        $game = new Game($rules);
        $alice = new StubPlayer('Alice');
        $bob = new StubPlayer('Bob');
        $carol = new StubPlayer('Carol');
        $game->join($alice);
        $game->join($bob);
        $game->join($carol);
        $game->deal(self::cards('Th,Jh,Qh,Ac,7h,8h,9h,2d,Kd,Ks,Kc,Ad'));
        $game->pass($alice);
        $game->pass($bob);
        $game->play($carol, self::melds('Ks,Kd,Kc'));
        $game->play($alice, self::melds('Ks,Kd,Kc;Th,Jh,Qh'));
        $game->pass($bob);
        $game->pass($carol);
        self::assertFalse($game->isOver());
        $game->pass($alice);
        self::assertTrue($game->isOver());
        // Alice and Carol both hold 1 point: the first in turn order wins, Bob loses 25
        self::assertSame($alice, $game->getWinner());
        self::assertSame(25, $game->getScore($alice));
        self::assertSame(-25, $game->getScore($bob));
        self::assertSame(0, $game->getScore($carol));
    }

    #[Test]
    public function jokerPenalty(): void
    {
        // Alice: Kh,Kd,Ks - Bob: wb,2c,3c
        $rules = new Rules(decks: 1, jokers: 1, handSize: 3);
        $game = new Game($rules);
        $alice = new StubPlayer('Alice');
        $bob = new StubPlayer('Bob');
        $game->join($alice);
        $game->join($bob);
        $game->deal(self::deck('Kh,Kd,Ks,wb,2c,3c', $rules));
        $game->play($alice, self::melds('Kh,Kd,Ks'));
        self::assertSame($alice, $game->getWinner());
        self::assertSame(35, $game->getScore($alice));
        self::assertSame(-35, $game->getScore($bob));
    }

    #[Test]
    public function twoDecksTellCardsApart(): void
    {
        // Alice: Khr,Khb,Ksr,Kdb - Bob: Kdr,2cr,3cr,4cr
        $rules = new Rules(decks: 2, jokers: 0, handSize: 4);
        $game = new Game($rules);
        $alice = new StubPlayer('Alice');
        $bob = new StubPlayer('Bob');
        $game->join($alice);
        $game->join($bob);
        $game->deal(self::deck('Khr,Khb,Ksr,Kdb,Kdr,2cr,3cr,4cr', $rules));
        try {
            $game->play($alice, self::melds('Khr,Kdr,Ksr'));
            self::fail('Alice holds the blue king of diamonds, not the red one.');
        } catch (IllegalMoveException $e) {
            self::assertSame('Cards not in hand: Kdr.', $e->getMessage());
        }
        $game->play($alice, self::melds('Khr,Kdb,Ksr'));
        self::assertSame('Khr,Kdb,Ksr', $game->getTable()->toString(true));
        self::assertSame('Khb', $game->getHand($alice)->toString(true));
    }

    private static function game(): Game
    {
        $game = new Game();
        $game->join(new StubPlayer('Alice'));
        $game->join(new StubPlayer('Bob'));
        $game->deal();

        return $game;
    }

    /** @return list<Card> */
    private static function cards(string $cards): array
    {
        return \array_map(static fn (string $rs): Card => Card::fromRankSuit($rs), \explode(',', $cards));
    }

    /**
     * A full deck starting with the given cards, followed by all the others.
     *
     * @return list<Card>
     */
    private static function deck(string $top, Rules $rules): array
    {
        $cards = self::cards($top);
        $keys = \array_map(static fn (Card $card): string => $card->toString(true), $cards);
        foreach ($rules->createDeck() as $card) {
            if (!\in_array($card->toString(true), $keys, true)) {
                $cards[] = $card;
            }
        }

        return $cards;
    }

    /** @return list<Meld> */
    private static function melds(string $layout): array
    {
        return '' === $layout ? [] : \array_map(static fn (string $meld): Meld => Meld::createFromString($meld), \explode(';', $layout));
    }
}
