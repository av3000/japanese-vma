import { useEffect } from 'react';
import { Button } from '@/components/shared/Button';
import { useLatest } from '@/hooks/useLatest';
import styles from './AnswerOptions.module.css';

export interface AnswerOptionsProps {
	options: readonly string[];
	/** Options in Japanese script get `lang="ja"` and the Japanese font. */
	japanese: boolean;
	onSelect: (option: string) => void;
	disabled?: boolean;
}

const isTypingTarget = (target: EventTarget | null) =>
	target instanceof HTMLElement &&
	(target.tagName === 'INPUT' || target.tagName === 'TEXTAREA' || target.isContentEditable);

/**
 * Four choices. Keys 1 to 4 pick the matching option from anywhere on the page unless the
 * learner is typing somewhere; the number is shown on each button so the shortcut is
 * discoverable. Options arrive shuffled by the deck seed, so the order is reproducible.
 */
export const AnswerOptions = ({ options, japanese, onSelect, disabled = false }: AnswerOptionsProps) => {
	const latestOptions = useLatest(options);
	const latestOnSelect = useLatest(onSelect);

	useEffect(() => {
		if (disabled) return;

		const onKeyDown = (event: KeyboardEvent) => {
			if (
				event.defaultPrevented ||
				event.altKey ||
				event.ctrlKey ||
				event.metaKey ||
				isTypingTarget(event.target)
			)
				return;

			const current = latestOptions.current;
			const index = Number(event.key) - 1;
			if (Number.isInteger(index) && index >= 0 && index < current.length) {
				event.preventDefault();
				latestOnSelect.current(current[index]);
			}
		};

		window.addEventListener('keydown', onKeyDown);
		return () => window.removeEventListener('keydown', onKeyDown);
	}, [disabled, latestOptions, latestOnSelect]);

	return (
		<ol className={styles.list} aria-label="Answer options">
			{options.map((option, index) => (
				<li key={`${index}-${option}`}>
					<Button
						type="button"
						variant="outline"
						isFullWidth
						disabled={disabled}
						className={styles.option}
						onClick={() => onSelect(option)}
						aria-keyshortcuts={String(index + 1)}
					>
						<span className={styles.key} aria-hidden="true">
							{index + 1}
						</span>
						<span className={styles.text} lang={japanese ? 'ja' : undefined}>
							{option}
						</span>
					</Button>
				</li>
			))}
		</ol>
	);
};

export default AnswerOptions;
