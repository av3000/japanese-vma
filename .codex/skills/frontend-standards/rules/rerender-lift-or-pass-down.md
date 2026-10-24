---
title: Do Not Sync Parent and Child State Through Effects
impact: LOW
impactDescription: removes a second render pass per update
tags: rerender, useEffect, lifting-state, props, data-flow
---

## Do Not Sync Parent and Child State Through Effects

Data flows down through props and up through callbacks. Using an effect to push a value across that boundary always costs an extra render: the child commits its own state, and only then does the parent learn about it and re-render the tree a second time.

**Incorrect (notifying the parent from an effect):**

```tsx
function Toggle({ onChange }: { onChange: (on: boolean) => void }) {
  const [isOn, setIsOn] = useState(false)

  // Child commits, then parent re-renders. Two passes per click.
  useEffect(() => {
    onChange(isOn)
  }, [isOn, onChange])

  return <button onClick={() => setIsOn(!isOn)}>{isOn ? 'On' : 'Off'}</button>
}
```

**Correct (update both in the same event):**

```tsx
function Toggle({ onChange }: { onChange: (on: boolean) => void }) {
  const [isOn, setIsOn] = useState(false)

  function handleClick() {
    const nextIsOn = !isOn
    setIsOn(nextIsOn)
    onChange(nextIsOn)
  }

  return <button onClick={handleClick}>{isOn ? 'On' : 'Off'}</button>
}
```

Both updates are batched into one render. Note that the handler passes `nextIsOn` rather than reading `isOn` after setting it — state updates are not applied synchronously.

**Better still, if the parent needs the value anyway:** delete the child's state and let the parent own it. Fully controlled children have no sync problem to solve.

```tsx
function Toggle({ isOn, onChange }: { isOn: boolean; onChange: (on: boolean) => void }) {
  return <button onClick={() => onChange(!isOn)}>{isOn ? 'On' : 'Off'}</button>
}
```

**The same applies to data.** A child that fetches and then hands the result up to its parent through an effect creates a waterfall — the parent cannot start any dependent work until the child has mounted, fetched, and re-rendered. Fetch in the parent through a query hook and pass the data down as props.

References: [Notifying parent components about state changes](https://react.dev/learn/you-might-not-need-an-effect#notifying-parent-components-about-state-changes), [Passing data to the parent](https://react.dev/learn/you-might-not-need-an-effect#passing-data-to-the-parent)

_Source: local addition based on react.dev, adapted for this Vite SPA; not in the upstream Vercel skill._
