import { Container } from '@/components/shared/layout';
import styles from './AppErrorFallback.module.css';

const AppErrorFallback = () => (
	<div className="app-wrapper">
		<main className="main-content">
			<Container className={styles.page}>
				<h1 className={styles.title}>Something went wrong</h1>
				<p className={styles.message}>The error has been captured. Refresh the page and try again.</p>
			</Container>
		</main>
	</div>
);

export default AppErrorFallback;
