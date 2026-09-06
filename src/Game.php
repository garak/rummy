<?php

namespace Garak\Rummy;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Garak\Card\Card;
use Garak\Card\Pile;
use Garak\Rummy\Exception\IllegalMoveException;
use Garak\Rummy\Exception\NotYourTurnException;

/**
 * A single round of Rummikub-style rummy.
 *
 * On their turn a player either plays (submits the new layout of the whole table), draws a card
 * from the stock, or passes (only when the stock is empty). There is no discard pile.
 * The round ends when a player empties the hand, or when every player passes in a row.
 */
class Game
{
    /** @var Collection<int, Player> */
    protected Collection $players;

    /** @var array<int, Hand> */
    private array $hands = [];

    /** @var array<int, bool> */
    private array $opened = [];

    /** @var array<int, int> */
    private array $scores = [];

    private Table $table;

    private Pile $stock;

    private GameStatus $status = GameStatus::Waiting;

    private int $current = 0;

    private int $passes = 0;

    private ?int $winner = null;

    public function __construct(private readonly Rules $rules = new Rules())
    {
        $this->players = new ArrayCollection();
        $this->table = new Table();
        $this->stock = new Pile();
    }

    public function getRules(): Rules
    {
        return $this->rules;
    }

    public function join(Player $player): void
    {
        if (GameStatus::Waiting !== $this->status) {
            throw new IllegalMoveException('Cannot join a game already started.');
        }
        if ($this->hasPlayer($player)) {
            throw new \InvalidArgumentException('Player already joined.');
        }
        $this->players->add($player);
    }

    public function hasPlayer(Player $player): bool
    {
        return null !== $this->findPlayer($player);
    }

    /** @return Collection<int, Player> */
    public function getPlayers(): Collection
    {
        return $this->players;
    }

    /**
     * Starts the round: builds the stock and deals the hands.
     *
     * @param array<int, Card>|null $deck Pre-arranged deck, dealt from the top (first element first). Useful for testing.
     *                                    If null, a shuffled deck built from the rules is used.
     */
    public function deal(?array $deck = null): void
    {
        if (GameStatus::Waiting !== $this->status) {
            throw new IllegalMoveException('Cards already dealt.');
        }
        $count = $this->players->count();
        if ($count < 2) {
            throw new IllegalMoveException('At least two players are needed.');
        }
        $cards = $deck ?? $this->rules->createDeck();
        if (\count($cards) < $count * $this->rules->handSize) {
            throw new IllegalMoveException(\sprintf('Not enough cards to deal %d cards to %d players.', $this->rules->handSize, $count));
        }
        // the stock is drawn from the top, so the first card of the deck must end up on top
        $this->stock = new Pile(\array_reverse($cards));
        if (null === $deck) {
            $this->stock->shuffle();
        }
        for ($i = 0; $i < $count; ++$i) {
            $handCards = [];
            for ($j = 0; $j < $this->rules->handSize; ++$j) {
                $handCards[] = $this->stock->draw();
            }
            $this->hands[$i] = new Hand($handCards);
            $this->opened[$i] = false;
            $this->scores[$i] = 0;
        }
        $this->status = GameStatus::Playing;
    }

    public function getStatus(): GameStatus
    {
        return $this->status;
    }

    public function isOver(): bool
    {
        return GameStatus::Finished === $this->status;
    }

    public function getCurrentPlayer(): Player
    {
        $this->assertPlaying();

        return $this->players->get($this->current) ?? throw new \LogicException('No current player.');
    }

    public function getTable(): Table
    {
        return $this->table;
    }

    public function getStockCount(): int
    {
        return $this->stock->count();
    }

    public function getHand(Player $player): Hand
    {
        return $this->hands[$this->indexOf($player)] ?? throw new IllegalMoveException('Cards not dealt yet.');
    }

    /**
     * Whether the player has already laid the opening melds.
     */
    public function hasOpened(Player $player): bool
    {
        return $this->opened[$this->indexOf($player)] ?? false;
    }

    /**
     * Plays a turn by submitting the new layout of the whole table.
     *
     * The layout must contain every card already on the table plus at least one card from the player's hand.
     * Before opening, the existing melds must be left untouched and the new ones must be worth at least
     * the opening points.
     *
     * @param array<int|string, Meld> $melds
     *
     * @throws IllegalMoveException
     */
    public function play(Player $player, array $melds): void
    {
        $index = $this->assertTurn($player);
        $layout = new Table($melds);
        $before = new CardBag($this->table->getCards());
        $after = new CardBag($layout->getCards());
        $removed = $before->diff($after);
        if ([] !== $removed) {
            throw new IllegalMoveException(\sprintf('Cards cannot leave the table: %s.', \implode(',', $removed)));
        }
        $played = $after->diff($before);
        if ([] === $played) {
            throw new IllegalMoveException('At least one card from the hand must be played, otherwise draw.');
        }
        $hand = $this->hands[$index];
        $handBag = new CardBag($hand->getCards());
        $notInHand = (new CardBag(\array_map(static fn (string $rs): Card => Card::fromRankSuit($rs), $played)))->diff($handBag);
        if ([] !== $notInHand) {
            throw new IllegalMoveException(\sprintf('Cards not in hand: %s.', \implode(',', $notInHand)));
        }
        if (!$this->opened[$index]) {
            if (!$this->table->isPreservedIn($layout)) {
                throw new IllegalMoveException('The table cannot be rearranged before opening.');
            }
            $points = \array_sum(\array_map(static fn (Meld $meld): int => $meld->getPoints(), $this->table->newMeldsIn($layout)));
            if ($points < $this->rules->openingPoints) {
                throw new IllegalMoveException(\sprintf('Opening melds are worth %d points, %d needed.', $points, $this->rules->openingPoints));
            }
        }

        foreach ($played as $rs) {
            $hand = $hand->play(Card::fromRankSuit($rs));
        }
        $this->hands[$index] = $hand;
        $this->table = $layout;
        $this->opened[$index] = true;
        $this->passes = 0;
        if ($hand->isEmpty()) {
            $this->finish($index);

            return;
        }
        $this->advance();
    }

    /**
     * Draws a card from the stock instead of playing.
     *
     * @throws IllegalMoveException
     */
    public function draw(Player $player): Card
    {
        $index = $this->assertTurn($player);
        if ($this->stock->isEmpty()) {
            throw new IllegalMoveException('The stock is empty: play or pass.');
        }
        $card = $this->stock->draw();
        $this->hands[$index] = $this->hands[$index]->add($card);
        $this->passes = 0;
        $this->advance();

        return $card;
    }

    /**
     * Skips the turn. Allowed only when the stock is empty.
     *
     * @throws IllegalMoveException
     */
    public function pass(Player $player): void
    {
        $this->assertTurn($player);
        if (!$this->stock->isEmpty()) {
            throw new IllegalMoveException('Cannot pass while the stock is not empty: play or draw.');
        }
        ++$this->passes;
        if ($this->passes >= $this->players->count()) {
            $this->finish(null);

            return;
        }
        $this->advance();
    }

    public function getWinner(): ?Player
    {
        return null === $this->winner ? null : $this->players->get($this->winner);
    }

    /**
     * Score of the round: negative for losers (the value of their hand), positive for the winner.
     */
    public function getScore(Player $player): int
    {
        return $this->scores[$this->indexOf($player)] ?? 0;
    }

    /**
     * @param int|null $winner index of the player who emptied the hand, or null on a stalemate
     */
    private function finish(?int $winner): void
    {
        $penalties = \array_map(fn (Hand $hand): int => $hand->getPenalty($this->rules->jokerPenalty), $this->hands);
        \assert([] !== $penalties);
        if (null === $winner) {
            // stalemate: lowest hand wins, everyone's total is reduced by the winner's own penalty
            $winner = (int) \array_search(\min($penalties), $penalties, true);
            $penalties = \array_map(static fn (int $penalty): int => $penalty - $penalties[$winner], $penalties);
        }
        foreach ($penalties as $index => $penalty) {
            $this->scores[$index] = $index === $winner ? \array_sum($penalties) : -$penalty;
        }
        $this->winner = $winner;
        $this->status = GameStatus::Finished;
    }

    private function advance(): void
    {
        $this->current = ($this->current + 1) % $this->players->count();
    }

    private function assertPlaying(): void
    {
        if (GameStatus::Playing !== $this->status) {
            throw new IllegalMoveException(\sprintf('Game is not in progress (%s).', $this->status->name));
        }
    }

    /**
     * @return int index of the player
     */
    private function assertTurn(Player $player): int
    {
        $this->assertPlaying();
        $index = $this->indexOf($player);
        if ($index !== $this->current) {
            throw new NotYourTurnException(\sprintf('It is %s\'s turn, not %s\'s.', $this->getCurrentPlayer(), $player));
        }

        return $index;
    }

    private function indexOf(Player $player): int
    {
        return $this->findPlayer($player) ?? throw new \InvalidArgumentException(\sprintf('Player %s is not in this game.', $player));
    }

    private function findPlayer(Player $player): ?int
    {
        foreach ($this->players as $index => $candidate) {
            if ($candidate->isEqual($player)) {
                return $index;
            }
        }

        return null;
    }
}
