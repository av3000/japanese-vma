import classNames from 'classnames';
import styles from './Spinner.module.scss';

interface SpinnerProps {
	size: 'sm' | 'md' | 'lg';
}

const Spinner = (props: SpinnerProps) => {
	const { size } = props;

	return (
		<div className={styles.wrapper}>
			<div className={classNames(styles.spinner, styles[size])} />
		</div>
	);
};

export default Spinner;
