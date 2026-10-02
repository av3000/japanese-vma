import styles from './SessionProgress.module.css';

export interface SessionProgressProps {
	/** 1-based index of the current card. */
	current: number;
	total: number;
	correct: number;
}

/** "7 of 20" with a bar. Motion is reduced to none under `prefers-reduced-motion`. */
export const SessionProgress = ({ current, total, correct }: SessionProgressProps) => {
	const percent = total === 0 ? 0 : Math.round((Math.min(current, total) / total) * 100);

	return (
		<div className={styles.progress}>
			<p className={styles.text}>
				<span>
					Card <strong>{Math.min(current, total)}</strong> of {total}
				</span>
				<span className={styles.score}>{correct} correct</span>
			</p>
			<div
				className={styles.track}
				role="progressbar"
				aria-label="Session progress"
				aria-valuemin={0}
				aria-valuemax={total}
				aria-valuenow={Math.min(current, total)}
			>
				<div className={styles.bar} style={{ width: `${percent}%` }} />
			</div>
		</div>
	);
};

export default SessionProgress;
