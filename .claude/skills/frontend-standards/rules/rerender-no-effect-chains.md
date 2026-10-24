---
title: Avoid Chains of Effects That Set State
impact: MEDIUM
impactDescription: eliminates one full render+commit pass per link in the chain
tags: rerender, useEffect, derived-state, cascading-renders, state
---

## Avoid Chains of Effects That Set State

When an effect sets state that triggers another effect that sets more state, React must render, commit, and run effects once per link in the chain before the UI settles. A four-step chain is three wasted render passes, each one paint-blocking, and the intermediate states are briefly visible to the user.

Calculate what you can during render, and do the rest in the event handler that started it.

**Incorrect (each effect triggers the next):**

```tsx
function Game() {
  const [card, setCard] = useState<Card | null>(null)
  const [goldCardCount, setGoldCardCount] = useState(0)
  const [round, setRound] = useState(1)
  const [isGameOver, setIsGameOver] = useState(false)

  useEffect(() => {
    if (card !== null && card.gold) {
      setGoldCardCount(c => c + 1)
    }
  }, [card])

  useEffect(() => {
    if (goldCardCount > 3) {
      setRound(r => r + 1)
      setGoldCardCount(0)
    }
  }, [goldCardCount])

  useEffect(() => {
    if (round > 5) {
      setIsGameOver(true)
    }
  }, [round])

  // Playing one gold card costs 4 render passes before the UI settles.
}
```

**Correct (derive during render, branch in the handler):**

```tsx
function Game() {
  const [card, setCard] = useState<Card | null>(null)
  const [goldCardCount, setGoldCardCount] = useState(0)
  const [round, setRound] = useState(1)

  // Derived during render - no state, no effect, no extra pass.
  const isGameOver = round > 5

  function handlePlaceCard(nextCard: Card) {
    if (isGameOver) {
      throw new Error('Game already ended.')
    }

    // One event, one state update batch, one render.
    setCard(nextCard)
    if (nextCard.gold) {
      if (goldCardCount <= 3) {
        setGoldCardCount(goldCardCount + 1)
      } else {
        setGoldCardCount(0)
        setRound(round + 1)
      }
    }
  }
}
```

Updates inside a single event handler are batched into one render. The chained version cannot batch, because each effect only runs after the previous render has committed.

**When a chain is unavoidable:** if a link genuinely needs to wait on something external (a network response, an animation frame), keep that one effect and derive everything downstream of it during render. Chains are only a problem when each link is pure computation that could have happened synchronously.

Reference: [Chains of computations](https://react.dev/learn/you-might-not-need-an-effect#chains-of-computations)

_Source: local addition based on react.dev; not in the upstream Vercel skill._
