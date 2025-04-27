// AnimatedBadgeDemo.tsx
import React, { useState } from 'react';
import { Button } from '@/components/shared/Button';
import { Badge } from './Badge';

const AnimatedBadgeDemo = () => {
	const [count, setCount] = useState(1);

	const handleIncrement = () => {
		setCount((prev) => prev + 1);
	};

	const handleDecrement = () => {
		setCount((prev) => Math.max(0, prev - 1));
	};

	const handleReset = () => {
		setCount(0);
	};

	return (
		<div style={{ display: 'flex', flexDirection: 'column', gap: '16px' }}>
			<div style={{ display: 'flex', gap: '24px' }}>
				<div>
					<h4>Animated Badge</h4>
					<div style={{ marginTop: '8px', position: 'relative' }}>
						<Badge badgeContent={count} animated>
							<Button variant="secondary-outline">Notifications</Button>
						</Badge>
					</div>
				</div>

				<div>
					<h4>Regular Badge (No Animation)</h4>
					<div style={{ marginTop: '8px', position: 'relative' }}>
						<Badge badgeContent={count}>
							<Button variant="secondary-outline">Notifications</Button>
						</Badge>
					</div>
				</div>
			</div>

			<div style={{ display: 'flex', gap: '8px', alignItems: 'center' }}>
				<Button onClick={handleDecrement} variant="secondary-outline">
					-
				</Button>
				<span
					style={{
						display: 'inline-flex',
						alignItems: 'center',
						padding: '0 12px',
						minWidth: '30px',
						justifyContent: 'center',
					}}
				>
					{count}
				</span>
				<Button onClick={handleIncrement} variant="secondary-outline">
					+
				</Button>
				<Button onClick={handleReset} variant="secondary-outline">
					Reset
				</Button>
			</div>

			<div>
				<em>Try incrementing to 2 or higher to see the pop animation</em>
			</div>
		</div>
	);
};

export default AnimatedBadgeDemo;
