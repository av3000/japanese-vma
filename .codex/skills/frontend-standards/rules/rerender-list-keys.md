---
title: Use Stable List Keys, Not Array Indexes
impact: MEDIUM
impactDescription: turns O(n) re-renders on reorder into O(1) DOM moves
tags: rerender, keys, lists, reconciliation, state
---

## Use Stable List Keys, Not Array Indexes

Keys tell React which item is which between renders. With `key={index}`, inserting at the front shifts every item's key by one, so React sees every single row as changed: it re-renders all of them and rewrites their DOM, instead of inserting one node and leaving the rest alone.

The correctness cost is worse than the performance cost. Because React matches state by key, component state lands on the wrong row — a checked checkbox or a focused input follows the *position*, not the item.

**Incorrect (index as key):**

```tsx
function TodoList({ todos }: { todos: Todo[] }) {
  return (
    <ul>
      {todos.map((todo, index) => (
        <TodoItem key={index} todo={todo} />
      ))}
    </ul>
  )
}

// Prepend a todo: every TodoItem re-renders, and the draft text typed
// into row 0 now appears on the item that moved to row 1.
```

**Correct (stable identity from the data):**

```tsx
function TodoList({ todos }: { todos: Todo[] }) {
  return (
    <ul>
      {todos.map(todo => (
        <TodoItem key={todo.id} todo={todo} />
      ))}
    </ul>
  )
}

// Prepend a todo: one new node is inserted, the rest are untouched.
```

**Never generate keys during render.** `key={Math.random()}` or `key={crypto.randomUUID()}` produces a new key every render, so React unmounts and remounts the entire list every time — destroying DOM nodes, state, focus, and scroll position. If items lack an ID, assign one when the data is created, not while rendering it.

**Index keys are acceptable** only when the list is static for the lifetime of the component: never reordered, filtered, inserted into, or removed from, and the items hold no state.

For lists long enough that per-item render cost matters even with correct keys, see `rendering-content-visibility` and `rerender-memo`.

Reference: [Why does React need keys?](https://react.dev/learn/rendering-lists#why-does-react-need-keys)

_Source: local addition based on react.dev; not in the upstream Vercel skill._
